<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    // Funciones originales del api.php (mantenidas para compatibilidad)
    case 'buscar_productos':
        buscarProductos($input['query']);
        break;
    case 'crear_pedido':
        crearPedido($input['pedido']);
        break;
    case 'obtener_combo':
        obtenerCombo($input['combo_id']);
        break;
    case 'obtener_pedidos':
        obtenerPedidos($input['fecha'] ?? date('Y-m-d'));
        break;
    case 'cambiar_estado':
        cambiarEstadoPedido($input['pedido_id'], $input['estado']);
        break;
    case 'eliminar_pedido':
        eliminarPedido($input['pedido_id']);
        break;
    case 'editar_pedido':
        editarPedido($input);
        break;
    case 'actualizar_metodo_pago':
        actualizar_metodo_pago($pdo, $input['pedido_id'], $input['metodo_pago']);
        break;
    case 'obtener_pedido':
        obtenerPedido($input['pedido_id']);
        break;
    
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

// =================== FUNCIONES ORIGINALES (MANTENIDAS) ===================
function actualizar_metodo_pago($pdo, $pedidoId, $metodoPago) {
    try {
        $stmt = $pdo->prepare("UPDATE pedidos SET metodo_pago = ? WHERE id = ?");
        $stmt->execute([$metodoPago, $pedidoId]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function buscarProductos($query) {
    global $pdo;
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }
    
    try {
        $resultados = [];
        $searchTerm = '%' . $query . '%';

        // Buscar productos
        $stmt_productos = $pdo->prepare("SELECT id, nombre, precio, 'producto' AS tipo FROM productos WHERE nombre LIKE ? AND activo = TRUE LIMIT 10");
        $stmt_productos->execute([$searchTerm]);
        $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

        // Buscar combos
        $stmt_combos = $pdo->prepare("SELECT id, nombre, precio, 'combo' AS tipo FROM combos WHERE nombre LIKE ? AND activo = TRUE LIMIT 10");
        $stmt_combos->execute([$searchTerm]);
        $combos = $stmt_combos->fetchAll(PDO::FETCH_ASSOC);

        // Combinar los resultados en una sola lista
        $resultados = array_merge($productos, $combos);
        
        echo json_encode($resultados);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

function crearPedido($pedido) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Calcular totales considerando precios modificados por cantidad
        $subtotal = 0;
        foreach ($pedido['items'] as $item) {
            if (isset($item['modificado']) && $item['modificado'] && isset($item['cantidadModificada']) && $item['cantidadModificada'] > 0) {
                // Calcular precio mixto: parte con precio original, parte con precio modificado
                $cantidadNormal = $item['cantidad'] - $item['cantidadModificada'];
                $subtotalItem = ($cantidadNormal * $item['precioOriginal']) + ($item['cantidadModificada'] * $item['precio']);
                $subtotal += $subtotalItem;
            } else {
                // Precio uniforme para toda la cantidad
                $subtotal += $item['precio'] * $item['cantidad'];
            }
        }

        // Calcular descuento global
        $descuento = 0;
        if (isset($pedido['descuento']) && $pedido['descuento']['tipo'] === 'soles') {
            $descuento = $pedido['descuento']['valor'];
        } elseif (isset($pedido['descuento']) && $pedido['descuento']['tipo'] === 'porcentaje') {
            $descuento = $subtotal * ($pedido['descuento']['valor'] / 100);
        }

        $total = max(0, $subtotal - $descuento);
        $ubicacion = $pedido['ubicacion'] ?? 'Libre';

        // Insertar pedido
        $stmt = $pdo->prepare("INSERT INTO pedidos (total, descuento, notas, ubicacion, fecha_pedido, estado) VALUES (?, ?, ?, ?, NOW(), 'pendiente')");
        $stmt->execute([$total, $descuento, $pedido['notas'], $ubicacion]);
        $pedidoId = $pdo->lastInsertId();

        // Insertar detalles del pedido
        foreach ($pedido['items'] as $item) {
            $productoId = null;
            $comboId = null;
            
            // Asignar el ID al campo correcto según el tipo
            if ($item['tipo'] === 'producto') {
                $productoId = $item['id'];
            } elseif ($item['tipo'] === 'combo') {
                $comboId = $item['id'];
            }

            $stmt = $pdo->prepare("INSERT INTO pedido_detalle 
                (pedido_id, producto_id, combo_id, cantidad, precio_unitario, 
                 precio_modificado, cantidad_modificada, modificacion_tipo, modificacion_valor, notas_item) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $pedidoId,
                $productoId,
                $comboId,
                $item['cantidad'],
                $item['precioOriginal'] ?? $item['precio'],
                isset($item['modificado']) && $item['modificado'] ? $item['precio'] : null,
                $item['cantidadModificada'] ?? 0,
                $item['modificacion']['tipo'] ?? null,
                $item['modificacion']['valor'] ?? 0,
                $item['notas'] ?? null
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'pedido_id' => $pedidoId]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function obtenerCombo($comboId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM combos WHERE id = ? AND activo = 1");
    $stmt->execute([$comboId]);
    $combo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode($combo);
}

// =================== NUEVAS FUNCIONES PARA GESTIÓN DE PEDIDOS ===================

function obtenerPedidos($fecha) {
    global $pdo;
    
    try {
        // Obtener pedidos del día especificado
        $stmt = $pdo->prepare("
            SELECT p.*
            FROM pedidos p 
            WHERE DATE(p.fecha_pedido) = ? 
            ORDER BY p.fecha_pedido DESC
        ");
        $stmt->execute([$fecha]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada pedido, obtener sus items
        foreach ($pedidos as &$pedido) {
            $stmt = $pdo->prepare("
                SELECT 
                    pd.*,
                    p.nombre as nombre,
                    p.precio as precio_unitario,
                    c.nombre as combo_nombre,
                    c.precio as combo_precio
                FROM pedido_detalle pd
                LEFT JOIN productos p ON pd.producto_id = p.id
                LEFT JOIN combos c ON pd.combo_id = c.id
                WHERE pd.pedido_id = ?
                ORDER BY pd.id
            ");
            $stmt->execute([$pedido['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear items
            $pedido['items'] = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'nombre' => $item['combo_id'] ? $item['combo_nombre'] : $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'cantidad_modificada' => $item['cantidad_modificada'] ?? 0,
                    'precio_unitario' => $item['combo_id'] ? $item['combo_precio'] : $item['precio_unitario'],
                    'precio_modificado' => $item['precio_modificado'],
                    'modificacion_tipo' => $item['modificacion_tipo'],
                    'modificacion_valor' => $item['modificacion_valor'],
                    'es_descuento' => ($item['modificacion_valor'] < 0), // Se calcula dinámicamente
                    'notas_item' => $item['notas_item'],
                    'es_combo' => $item['combo_id'] ? true : false
                ];
            }, $items);
        }
        
        echo json_encode($pedidos);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function obtenerPedido($pedidoId) {
    global $pdo;
    
    try {
        // Obtener pedido
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            echo json_encode(['error' => 'Pedido no encontrado']);
            return;
        }
        
        // Obtener items del pedido
        $stmt = $pdo->prepare("
            SELECT 
                pd.*,
                p.nombre as nombre,
                p.precio as precio_unitario,
                c.nombre as combo_nombre,
                c.precio as combo_precio
            FROM pedido_detalle pd
            LEFT JOIN productos p ON pd.producto_id = p.id
            LEFT JOIN combos c ON pd.combo_id = c.id
            WHERE pd.pedido_id = ?
            ORDER BY pd.id
        ");
        $stmt->execute([$pedidoId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear items
        $pedido['items'] = array_map(function($item) {
            return [
                'id' => $item['id'],
                'nombre' => $item['combo_id'] ? $item['combo_nombre'] : $item['nombre'],
                'cantidad' => $item['cantidad'],
                'cantidad_modificada' => $item['cantidad_modificada'] ?? 0,
                'precio_unitario' => $item['combo_id'] ? $item['combo_precio'] : $item['precio_unitario'],
                'precio_modificado' => $item['precio_modificado'],
                'modificacion_tipo' => $item['modificacion_tipo'],
                'modificacion_valor' => $item['modificacion_valor'],
                'es_descuento' => ($item['modificacion_valor'] < 0), // Se calcula dinámicamente
                'notas_item' => $item['notas_item'],
                'es_combo' => $item['combo_id'] ? true : false
            ];
        }, $items);
        
        echo json_encode($pedido);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function cambiarEstadoPedido($pedidoId, $estado) {
    global $pdo;
    
    try {
        $estados_validos = ['pendiente', 'completado', 'cancelado'];
        
        if (!in_array($estado, $estados_validos)) {
            throw new Exception('Estado no válido');
        }
        
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $pedidoId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function eliminarPedido($pedidoId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Eliminar detalles del pedido
        $stmt = $pdo->prepare("DELETE FROM pedido_detalle WHERE pedido_id = ?");
        $stmt->execute([$pedidoId]);
        
        // Eliminar pedido
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
        $stmt->execute([$pedidoId]);
        
        $pdo->commit();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function editarPedido($input) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $pedidoId = $input['pedido_id'];
        $estado = $input['estado'];
        $ubicacion = $input['ubicacion'];
        $notas = $input['notas'];
        $descuento = $input['descuento'];
        $items = $input['items'];
        
        // Validar estado
        $estados_validos = ['pendiente', 'completado', 'cancelado'];
        if (!in_array($estado, $estados_validos)) {
            throw new Exception('Estado no válido');
        }
        
        // Calcular nuevo total
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }
        $total = max(0, $subtotal - $descuento);
        
        // Actualizar pedido
        $stmt = $pdo->prepare("
            UPDATE pedidos 
            SET estado = ?, ubicacion = ?, notas = ?, descuento = ?, total = ?
            WHERE id = ?
        ");
        $stmt->execute([$estado, $ubicacion, $notas, $descuento, $total, $pedidoId]);
        
        // Actualizar items del pedido
        foreach ($items as $item) {
            $stmt = $pdo->prepare("
                UPDATE pedido_detalle 
                SET cantidad = ?, precio_modificado = ?, notas_item = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $item['cantidad'],
                $item['precio'],
                $item['notas_item'],
                $item['id']
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>