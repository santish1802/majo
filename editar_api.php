<?php
// Incluir la configuración de la base de datos
require_once 'config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/config/funStock.php";

// Establecer la cabecera para devolver contenido JSON
header('Content-Type: application/json');

// Obtener el cuerpo de la solicitud (request body)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['error' => 'Acción no válida o datos no recibidos.']);
    exit;
}

$action = $input['action'];

switch ($action) {
    case 'obtener_pedido':
        obtenerPedido($input['id']);
        break;

    case 'buscar_productos':
        buscarProductos($input['query']);
        break;

    case 'actualizar_pedido':
        actualizarPedido($input['pedido']);
        break;

    default:
        echo json_encode(['error' => 'Acción desconocida.']);
        break;
}

/**
 * Obtiene los detalles completos de un pedido para su edición (solo productos)
 */
function obtenerPedido($pedidoId) {
    global $pdo;
    
    if (empty($pedidoId)) {
        echo json_encode(['error' => 'ID de pedido no proporcionado.']);
        exit;
    }

    try {
        // 1. Obtener información general del pedido
        $stmt_info = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt_info->execute([$pedidoId]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$info) {
            echo json_encode(['error' => 'Pedido no encontrado.']);
            exit;
        }

        // 2. Obtener los items del pedido (solo productos)
        $sql_items = "
            SELECT 
                pd.*,
                p.nombre AS nombre,
                p.precio AS precio_base
            FROM 
                pedido_detalle pd
            LEFT JOIN 
                productos p ON pd.producto_id = p.id
            WHERE 
                pd.pedido_id = ?
            ORDER BY pd.id
        ";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([$pedidoId]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['info' => $info, 'items' => $items]);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Busca productos activos en la base de datos
 */
function buscarProductos($query) {
    global $pdo;
    
    if (empty($query)) {
        echo json_encode([]);
        exit;
    }
    
    try {
        $searchTerm = '%' . $query . '%';

        $stmt_productos = $pdo->prepare("
            SELECT id, nombre, precio, 'producto' AS tipo 
            FROM productos 
            WHERE nombre LIKE ? AND activo = TRUE 
            ORDER BY nombre 
            LIMIT 10
        ");
        $stmt_productos->execute([$searchTerm]);
        $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($productos);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Actualiza un pedido existente (solo productos)
 */
function actualizarPedido($pedido) {
    global $pdo;

    if (empty($pedido) || empty($pedido['pedido_id'])) {
        echo json_encode(['error' => 'Datos del pedido incompletos.']);
        exit;
    }

    try {
        // 1️⃣ Verificar el estado actual del pedido (fuera de la transacción)
        $stmt_estado = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
        $stmt_estado->execute([$pedido['pedido_id']]);
        $pedidoInfo = $stmt_estado->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedidoInfo) {
            throw new Exception('Pedido no encontrado.');
        }

        $estadoPedido = $pedidoInfo['estado'];

        // 2️⃣ Si el pedido está COMPLETADO, reponer stock antes de modificar
        if ($estadoPedido === 'completado') {
            reponerStockPedido($pdo, (int)$pedido['pedido_id']);
        }

        // === 3️⃣ Iniciar transacción principal ===
        $pdo->beginTransaction();

        // 4️⃣ Actualizar información general del pedido
        $sql_pedido = "
            UPDATE pedidos 
            SET ubicacion = ?, notas = ?, descuento = ?, total = ? 
            WHERE id = ?
        ";
        $stmt_pedido = $pdo->prepare($sql_pedido);
        $stmt_pedido->execute([
            $pedido['ubicacion'],
            $pedido['notas'],
            $pedido['descuento'],
            $pedido['total'],
            $pedido['pedido_id']
        ]);

        // 5️⃣ Eliminar todos los items antiguos del pedido
        $stmt_delete = $pdo->prepare("DELETE FROM pedido_detalle WHERE pedido_id = ?");
        $stmt_delete->execute([$pedido['pedido_id']]);

        // 6️⃣ Insertar los nuevos items del pedido
        $sql_item = "
            INSERT INTO pedido_detalle 
            (pedido_id, producto_id, cantidad, precio_unitario, precio_modificado, 
             cantidad_modificada, modificacion_tipo, modificacion_valor, notas_item) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt_item = $pdo->prepare($sql_item);

        foreach ($pedido['items'] as $item) {
            $productoId = $item['id'];

            $precioModificado = null;
            $cantidadModificada = 0;
            $modificacionTipo = null;
            $modificacionValor = 0;

            if (isset($item['modificado']) && $item['modificado'] && isset($item['modificacion'])) {
                $precioModificado = $item['precio'];
                $cantidadModificada = $item['cantidadModificada'] ?? $item['cantidad'];
                $modificacionTipo = $item['modificacion']['tipo'] ?? null;

                if ($modificacionTipo === 'soles' || $modificacionTipo === 'porcentaje') {
                    $modificacionValor = $item['modificacion']['esDescuento']
                        ? -abs($item['modificacion']['valor'])
                        : abs($item['modificacion']['valor']);
                } elseif ($modificacionTipo === 'fijo') {
                    $modificacionValor = $item['modificacion']['valor'];
                }
            }

            $stmt_item->execute([
                $pedido['pedido_id'],
                $productoId,
                $item['cantidad'],
                $item['precioOriginal'],
                $precioModificado,
                $cantidadModificada,
                $modificacionTipo,
                $modificacionValor,
                $item['notas'] ?? null
            ]);
        }

        // 7️⃣ Confirmar transacción de actualización de pedido
        $pdo->commit();

        // 8️⃣ Si el pedido estaba COMPLETADO, descontar stock actualizado
        if ($estadoPedido === 'completado') {
            procesarStockPedido($pdo, (int)$pedido['pedido_id']);
        }

        echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente']);

    } catch (Exception $e) {
        // Revertir cambios en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['error' => 'Error al actualizar el pedido: ' . $e->getMessage()]);
    }
}


?>