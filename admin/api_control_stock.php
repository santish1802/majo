<?php
// /admin/api_control_stock.php
require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? $input['action'] ?? null;

function json_error($msg){
    echo json_encode(["success" => false, "error" => $msg]);
    exit;
}

if (!$pdo) json_error("No hay conexión a la base de datos.");

try {
    switch ($action) {
        // LISTAR INGREDIENTES (excepto frutas)
        case "listar_ingredientes_stock":
            $sql = "SELECT id, nombre, stock_actual, stock_minimo, unidad_medida 
                    FROM ingredientes 
                    WHERE unidad_medida != 'fruta'
                    ORDER BY nombre ASC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $data]);
            break;

        // LISTAR FRUTAS
        case "listar_frutas_stock":
            $sql = "SELECT id, nombre, stock_actual, stock_minimo, unidad_medida 
                    FROM ingredientes 
                    WHERE unidad_medida = 'fruta'
                    ORDER BY nombre ASC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $data]);
            break;

        // LISTAR PRODUCTOS CON STOCK POR UNIDAD
        case "listar_productos_stock":
            $sql = "SELECT p.id, p.nombre, p.categoria, 
                    COALESCE(psu.stock_actual, 0) as stock_actual,
                    COALESCE(psu.stock_minimo, 0) as stock_minimo
                    FROM productos p
                    LEFT JOIN producto_stock_unidad psu ON p.id = psu.producto_id
                    WHERE p.tipo_stock = 'unidad'
                    ORDER BY p.nombre ASC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $data]);
            break;

        // ACTUALIZAR STOCK
        case "actualizar_stock":
            $id = intval($input['id'] ?? 0);
            $tipo = $input['tipo'] ?? '';
            $stock_actual = floatval($input['stock_actual'] ?? 0);
            $stock_minimo = floatval($input['stock_minimo'] ?? 0);
            
            if (!$id) json_error("ID requerido.");
            if (!in_array($tipo, ['ingrediente', 'fruta', 'producto'])) json_error("Tipo inválido.");

            if ($tipo === 'producto') {
                // Verificar si existe
                $stmt = $pdo->prepare("SELECT id FROM producto_stock_unidad WHERE producto_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE producto_stock_unidad SET stock_actual = ?, stock_minimo = ? WHERE producto_id = ?");
                    $stmt->execute([$stock_actual, $stock_minimo, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO producto_stock_unidad (producto_id, stock_actual, stock_minimo) VALUES (?, ?, ?)");
                    $stmt->execute([$id, $stock_actual, $stock_minimo]);
                }
            } else {
                // ingrediente o fruta
                $stmt = $pdo->prepare("UPDATE ingredientes SET stock_actual = ?, stock_minimo = ? WHERE id = ?");
                $stmt->execute([$stock_actual, $stock_minimo, $id]);
            }
            
            echo json_encode(["success" => true]);
            break;

        default:
            json_error("Acción no válida: " . ($action ?? 'null'));
    }

} catch (Exception $e) {
    json_error($e->getMessage());
}