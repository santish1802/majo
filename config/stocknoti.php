<?php
header("Content-Type: application/json; charset=UTF-8");

require $_SERVER['DOCUMENT_ROOT'] . '/config.php'; // Debe contener $pdo
require $_SERVER['DOCUMENT_ROOT'] . '/push/send.php'; // Debe contener sendMulticastNotification()

/**
 * Recupera una lista unificada de todos los artículos (Ingredientes/Frutas y Productos Unidad) 
 * cuyo stock actual es menor o igual al stock mínimo.
 */
function getStockCritico(PDO $pdo): array
{
    $sql_ingredientes = "SELECT i.id, i.nombre, i.unidad_medida, 
                         'Ingrediente/Fruta' as tipo_articulo, 
                         i.stock_actual, i.stock_minimo
                         FROM ingredientes i
                         WHERE i.stock_actual <= i.stock_minimo
                         AND i.stock_actual > 0 
                         ORDER BY i.nombre ASC";

    $stmt_ingredientes = $pdo->prepare($sql_ingredientes);
    $stmt_ingredientes->execute();
    $ingredientes_criticos = $stmt_ingredientes->fetchAll(PDO::FETCH_ASSOC);

    $sql_productos = "SELECT p.id, p.nombre, 'Unidad' as unidad_medida, 
                      'Producto Unidad' as tipo_articulo, 
                      psu.stock_actual, psu.stock_minimo
                      FROM productos p
                      INNER JOIN producto_stock_unidad psu ON p.id = psu.producto_id
                      WHERE p.tipo_stock = 'unidad' 
                      AND psu.stock_actual <= psu.stock_minimo
                      AND psu.stock_actual > 0 
                      ORDER BY p.nombre ASC";

    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute();
    $productos_criticos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    return array_merge($ingredientes_criticos, $productos_criticos);
}

try {
    // 1. Obtener stock crítico
    $stock_critico = getStockCritico($pdo);
    $total_critico = count($stock_critico);

    $notificacion_result = ["success" => true, "message" => "No hay stock crítico, no se envió notificación."];

    if ($total_critico > 0) {

        // Obtener solo los nombres de los artículos
        $nombres_actuales = array_column($stock_critico, 'nombre');
        $json_actual = json_encode($nombres_actuales);

        // 2. Consultar última notificación enviada
        $stmt = $pdo->query("SELECT articulos FROM stock_notificaciones ORDER BY fecha_envio DESC LIMIT 1");
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimo_articulos = $ultimo ? json_decode($ultimo['articulos'], true) : [];

        //  @c-red CONDICION PARA ENVIAR NOTIFICACION SOLO SI HAY CAMBIOS
        if ($ultimo_articulos !== $nombres_actuales) {

            $title = "Stock Crítico";
            $max_display = 5;
            $display_items = array_slice($nombres_actuales, 0, $max_display);
            $body = implode("\n", $display_items);
            if ($total_critico > $max_display) {
                $body .= "\n...y " . ($total_critico - $max_display) . " más";
            }
            $imageUrl = "/assets/img/logo.png";

            // 4. Enviar notificación push
            $notificacion_result = sendMulticastNotification($title, $body, $imageUrl);

            // 5. Guardar registro de envío
            $stmt_insert = $pdo->prepare("INSERT INTO stock_notificaciones (articulos, total_items) VALUES (:articulos, :total)");
            $stmt_insert->execute([
                ':articulos' => $json_actual,
                ':total' => $total_critico
            ]);
        }
    }

    // 6. Preparar respuesta final
    $response = [
        "success" => true,
        "mensaje" => "Ejecución de stock crítico completada.",
        "total_items_criticos" => $total_critico,
        "data" => $stock_critico,
        "notificacion" => $notificacion_result
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Error al ejecutar la función: " . $e->getMessage()]);
}
