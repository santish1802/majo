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

    case 'obtener_pedido':
        obtenerPedido($input['pedido_id']);
        break;
    case 'registrar_pago':
        registrarPago($input);
        break;
    case 'actualizar_perfil':
        actualizarPerfil($input);
        break;
    
    default:
        echo json_encode(['error' => 'Acción no válida']);
}

function registrarPago($input) {
    global $pdo;

    $pedidoId = $input['pedido_id'] ?? null;
    $pagos = $input['pagos'] ?? [];

    if (!$pedidoId || empty($pagos)) {
        echo json_encode(['success' => false, 'error' => 'Datos de pago incompletos.']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // 1. (Opcional pero recomendado) Borrar pagos anteriores para este pedido.
        // Esto evita duplicados si se intenta pagar de nuevo.
        $stmt = $pdo->prepare("DELETE FROM pedido_pagos WHERE pedido_id = ?");
        $stmt->execute([$pedidoId]);

        // 2. Insertar cada nuevo pago recibido
        $stmt = $pdo->prepare("INSERT INTO pedido_pagos (pedido_id, metodo_pago, monto) VALUES (?, ?, ?)");
        foreach ($pagos as $pago) {
            if (empty($pago['metodo_pago']) || !is_numeric($pago['monto'])) {
                throw new Exception('Cada pago debe tener un método y un monto válido.');
            }
            $stmt->execute([$pedidoId, $pago['metodo_pago'], $pago['monto']]);
        }

        // 3. Actualizar el estado del pedido principal a 'completado'
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'completado' WHERE id = ?");
        $stmt->execute([$pedidoId]);
        $pdo->commit();
                $stmt = $pdo->prepare("CALL descontar_stock_pedido(?)");
        $stmt->execute([$pedidoId]);
        // Si todo fue bien, confirmar los cambios
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Si algo falló, revertir todos los cambios
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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

        // Combinar los resultados en una sola lista
        $resultados = array_merge($productos);
        
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
            // Ignorar items que no sean productos (se quita soporte para combos)
            if (($item['tipo'] ?? 'producto') !== 'producto') {
            continue;
            }

            $productoId = $item['id'];
            $cantidad = $item['cantidad'];
            $precio_unitario = $item['precioOriginal'] ?? $item['precio'];
            $precio_modificado = (isset($item['modificado']) && $item['modificado']) ? $item['precio'] : null;
            $cantidad_modificada = $item['cantidadModificada'] ?? 0;
            $modificacion_tipo = $item['modificacion']['tipo'] ?? null;
            $modificacion_valor = $item['modificacion']['valor'] ?? 0;
            $notas_item = $item['notas'] ?? null;

            $stmt = $pdo->prepare("INSERT INTO pedido_detalle 
            (pedido_id, producto_id, cantidad, precio_unitario, 
             precio_modificado, cantidad_modificada, modificacion_tipo, modificacion_valor, notas_item) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
            $pedidoId,
            $productoId,
            $cantidad,
            $precio_unitario,
            $precio_modificado,
            $cantidad_modificada,
            $modificacion_tipo,
            $modificacion_valor,
            $notas_item
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'pedido_id' => $pedidoId]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
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
                p.precio as precio_unitario
            FROM pedido_detalle pd
            LEFT JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id = ?
            ORDER BY pd.id
            ");
            $stmt->execute([$pedido['id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pedido['items'] = array_map(function($item) {
            return [
                'id' => $item['id'],
                'nombre' => $item['nombre'],
                'cantidad' => $item['cantidad'],
                'cantidad_modificada' => $item['cantidad_modificada'] ?? 0,
                'precio_unitario' => $item['precio_unitario'],
                'precio_modificado' => $item['precio_modificado'],
                'modificacion_tipo' => $item['modificacion_tipo'],
                'modificacion_valor' => $item['modificacion_valor'],
                'es_descuento' => isset($item['modificacion_valor']) ? ($item['modificacion_valor'] < 0) : false,
                'notas_item' => $item['notas_item']
            ];
            }, $items);

            $stmt_pagos = $pdo->prepare("SELECT metodo_pago, monto FROM pedido_pagos WHERE pedido_id = ?");
            $stmt_pagos->execute([$pedido['id']]);
            $pedido['pagos'] = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);
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
            p.precio as precio_unitario
            FROM pedido_detalle pd
            LEFT JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id = ?
            ORDER BY pd.id
        ");
        $stmt->execute([$pedidoId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear items
        $pedido['items'] = array_map(function($item) {
            return [
            'id' => $item['id'],
            'nombre' => $item['nombre'],
            'cantidad' => $item['cantidad'],
            'cantidad_modificada' => $item['cantidad_modificada'] ?? 0,
            'precio_unitario' => $item['precio_unitario'],
            'precio_modificado' => $item['precio_modificado'],
            'modificacion_tipo' => $item['modificacion_tipo'],
            'modificacion_valor' => $item['modificacion_valor'],
            'es_descuento' => ($item['modificacion_valor'] < 0), // Se calcula dinámicamente
            'notas_item' => $item['notas_item']
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

        // Obtener estado actual
        $stmt = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
        $stmt->execute([$pedidoId]);
        $estadoActual = $stmt->fetchColumn();
        
        if (!$estadoActual) {
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
            return;
        }

        // Actualizar estado
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $pedidoId]);

        // Si cambia a pendiente y no estaba en pendiente, reponer stock
        if ($estado === 'pendiente' && $estadoActual !== 'pendiente') {
            $stmt = $pdo->prepare("CALL reponer_stock_pedido(?)");
            $stmt->execute([$pedidoId]);
        }
        
        echo json_encode(['success' => true]);
        
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
function actualizarPerfil($data) {
    global $pdo;
    
    session_start();
    
    // Verificar si el usuario ha iniciado sesión
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
        return;
    }
    
    $id_usuario_actual = $_SESSION['id_usuario'];
    $nombre_usuario = trim($data['nombre_usuario'] ?? '');
    $antigua_contrasena = $data['antigua_contrasena'] ?? '';
    $nueva_contrasena = $data['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $data['confirmar_contrasena'] ?? '';
    
    // Validar nombre de usuario
    if (empty($nombre_usuario)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario es obligatorio']);
        return;
    }
    
    try {
        // Obtener la contraseña actual de la base de datos
        $stmt_user = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id = :id");
        $stmt_user->bindParam(':id', $id_usuario_actual);
        $stmt_user->execute();
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_data) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }
        
        $contrasena_actual_db = $user_data['contrasena'];
        
        // Validaciones para cambio de contraseña
        if (!empty($nueva_contrasena)) {
            // Verificar contraseña antigua
            if ($antigua_contrasena !== $contrasena_actual_db) {
                echo json_encode(['success' => false, 'message' => 'La contraseña antigua es incorrecta']);
                return;
            }
            
            // Verificar que las nuevas contraseñas coincidan
            if ($nueva_contrasena !== $confirmar_contrasena) {
                echo json_encode(['success' => false, 'message' => 'Las nuevas contraseñas no coinciden']);
                return;
            }
            
            // Validar longitud de nueva contraseña
            if (strlen($nueva_contrasena) < 4) {
                echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 4 caracteres']);
                return;
            }
            
            // Actualizar nombre de usuario y contraseña
            $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario, contrasena = :contrasena WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_usuario', $nombre_usuario);
            $stmt->bindParam(':contrasena', $nueva_contrasena);
            $stmt->bindParam(':id', $id_usuario_actual);
            
            $mensaje_exito = '¡Perfil y contraseña actualizados correctamente!';
            
        } else {
            // Solo actualizar nombre de usuario
            $sql = "UPDATE usuarios SET nombre_usuario = :nombre_usuario WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_usuario', $nombre_usuario);
            $stmt->bindParam(':id', $id_usuario_actual);
            
            $mensaje_exito = '¡Perfil actualizado correctamente!';
        }
        
        // Verificar si el nombre de usuario ya existe (excepto para el usuario actual)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = :nombre_usuario AND id != :id");
        $stmt_check->bindParam(':nombre_usuario', $nombre_usuario);
        $stmt_check->bindParam(':id', $id_usuario_actual);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
            return;
        }
        
        // Ejecutar la actualización
        if ($stmt->execute()) {
            // Actualizar la sesión con el nuevo nombre de usuario
            $_SESSION['nombre_usuario'] = $nombre_usuario;
            
            echo json_encode([
                'success' => true, 
                'message' => $mensaje_exito,
                'nombre_usuario_actualizado' => true
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
}
?>