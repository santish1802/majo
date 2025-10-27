<?php 
function reponerStockPedido(PDO $pdo, int $pedidoId)
{
    // No iniciar ni confirmar transacción aquí
    $sql = "SELECT pd.producto_id, pd.cantidad, p.tipo_stock
            FROM pedido_detalle pd
            INNER JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id = :pedido_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pedido_id' => $pedidoId]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as $producto) {
        $productoId = (int)$producto['producto_id'];
        $cantidad = (int)$producto['cantidad'];
        $tipoStock = $producto['tipo_stock'];

        switch ($tipoStock) {
            // CASO 1: Stock por unidad
            case 'unidad':
                $update = $pdo->prepare("
                    UPDATE producto_stock_unidad 
                    SET stock_actual = stock_actual + :cantidad
                    WHERE producto_id = :producto_id
                ");
                $update->execute([
                    'cantidad' => $cantidad,
                    'producto_id' => $productoId
                ]);

                if ($update->rowCount() === 0) {
                    $insert = $pdo->prepare("
                        INSERT INTO producto_stock_unidad (producto_id, stock_actual, stock_minimo)
                        VALUES (:producto_id, :stock_actual, 0)
                    ");
                    $insert->execute([
                        'producto_id' => $productoId,
                        'stock_actual' => $cantidad
                    ]);
                }
                break;

            // CASO 2: Stock por ingredientes
            case 'ingredientes':
                $updateIng = $pdo->prepare("
                    UPDATE ingredientes i
                    INNER JOIN producto_ingredientes pi ON i.id = pi.ingrediente_id
                    SET i.stock_actual = i.stock_actual + (pi.cantidad_por_unidad * :cantidad)
                    WHERE pi.producto_id = :producto_id
                ");
                $updateIng->execute([
                    'cantidad' => $cantidad,
                    'producto_id' => $productoId
                ]);
                break;

            // CASO 3: Sin control de stock
            default:
                // No hacer nada
                break;
        }
    }
}

function procesarStockPedido(PDO $pdo, int $pedidoId)
{
    // No iniciar ni confirmar transacción aquí
    $sql = "SELECT pd.producto_id, pd.cantidad, p.tipo_stock
            FROM pedido_detalle pd
            INNER JOIN productos p ON pd.producto_id = p.id
            WHERE pd.pedido_id = :pedido_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pedido_id' => $pedidoId]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as $producto) {
        $productoId = (int)$producto['producto_id'];
        $cantidad = (int)$producto['cantidad'];
        $tipoStock = $producto['tipo_stock'];

        switch ($tipoStock) {
            // CASO 1: Stock por unidad
            case 'unidad':
                $update = $pdo->prepare("
                    UPDATE producto_stock_unidad 
                    SET stock_actual = stock_actual - :cantidad
                    WHERE producto_id = :producto_id
                ");
                $update->execute([
                    'cantidad' => $cantidad,
                    'producto_id' => $productoId
                ]);

                if ($update->rowCount() === 0) {
                    $insert = $pdo->prepare("
                        INSERT INTO producto_stock_unidad (producto_id, stock_actual, stock_minimo)
                        VALUES (:producto_id, :stock_actual, 0)
                    ");
                    $insert->execute([
                        'producto_id' => $productoId,
                        'stock_actual' => -$cantidad
                    ]);
                }
                break;

            // CASO 2: Stock por ingredientes
            case 'ingredientes':
                $updateIng = $pdo->prepare("
                    UPDATE ingredientes i
                    INNER JOIN producto_ingredientes pi ON i.id = pi.ingrediente_id
                    SET i.stock_actual = i.stock_actual - (pi.cantidad_por_unidad * :cantidad)
                    WHERE pi.producto_id = :producto_id
                ");
                $updateIng->execute([
                    'cantidad' => $cantidad,
                    'producto_id' => $productoId
                ]);
                break;

            // CASO 3: Sin control de stock
            default:
                // No hacer nada
                break;
        }
    }
}

?>