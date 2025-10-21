<?php
// /admin/api_stock.php
require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
session_start();
header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? $input['action'] ?? null;

function json_error($msg)
{
    echo json_encode(["success" => false, "error" => $msg]);
    exit;
}

if (!$pdo) json_error("No hay conexión a la base de datos.");

try {
    switch ($action) {
        // LISTAR TODOS LOS INGREDIENTES
        // OBTENER STOCK POR UNIDAD
// OBTENER STOCK POR UNIDAD
        case "obtener_stock_unidad":
            $producto_id = intval($_GET['producto_id'] ?? $input['producto_id'] ?? 0);
            if (!$producto_id) json_error("producto_id requerido.");
            
            $stmt = $pdo->prepare("SELECT stock_actual, stock_minimo, stock_maximo FROM producto_stock_unidad WHERE producto_id = ?");
            $stmt->execute([$producto_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) {
                // Si no existe, devolver valores por defecto sin crear registro
                $data = ['stock_actual' => 0, 'stock_minimo' => 0, 'stock_maximo' => null];
            }
            
            echo json_encode(["success" => true, "data" => $data]);
            break;

        // ACTUALIZAR STOCK POR UNIDAD
        case "actualizar_stock_unidad":


            $producto_id = intval($input['producto_id'] ?? 0);
            $stock_actual = intval($input['stock_actual'] ?? 0);
            $stock_minimo = intval($input['stock_minimo'] ?? 0);
            $stock_maximo = isset($input['stock_maximo']) && $input['stock_maximo'] !== '' ? intval($input['stock_maximo']) : null;

            if (!$producto_id) json_error("producto_id requerido.");

            // Verificar si existe el registro
            $stmt = $pdo->prepare("SELECT id FROM producto_stock_unidad WHERE producto_id = ?");
            $stmt->execute([$producto_id]);

            if ($stmt->fetch()) {
                // Actualizar
                $stmt = $pdo->prepare("UPDATE producto_stock_unidad SET stock_actual = ?, stock_minimo = ?, stock_maximo = ? WHERE producto_id = ?");
                $stmt->execute([$stock_actual, $stock_minimo, $stock_maximo, $producto_id]);
            } else {
                // Insertar
                $stmt = $pdo->prepare("INSERT INTO producto_stock_unidad (producto_id, stock_actual, stock_minimo, stock_maximo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$producto_id, $stock_actual, $stock_minimo, $stock_maximo]);
            }

            echo json_encode(["success" => true]);
            break;

        case "listar_ingredientes":
            $sql = "SELECT id, nombre, stock_actual, unidad_medida FROM ingredientes ORDER BY nombre ASC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $data]);
            break;

        // CREAR/ACTUALIZAR INGREDIENTE
        case "guardar_ingrediente":
            $id = isset($input['id']) ? intval($input['id']) : 0;
            $nombre = trim($input['nombre'] ?? '');
            $stock = isset($input['stock']) ? floatval($input['stock']) : 0;
            $unidad = $input['unidad'] ?? 'unidad';

            if ($nombre === '') json_error("Nombre requerido.");

            if ($id) {
                $stmt = $pdo->prepare("UPDATE ingredientes SET nombre = ?, stock_actual = ?, unidad_medida = ? WHERE id = ?");
                $stmt->execute([$nombre, $stock, $unidad, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO ingredientes (nombre, stock_actual, unidad_medida) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $stock, $unidad]);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(["success" => true, "id" => $id]);
            break;

        // LISTAR INGREDIENTES ASIGNADOS A UN PRODUCTO
        case "listar_ingredientes_producto":
            $producto_id = intval($_GET['producto_id'] ?? $input['producto_id'] ?? 0);
            if (!$producto_id) json_error("producto_id requerido.");

            $sql = "SELECT pi.id, pi.ingrediente_id, i.nombre, pi.cantidad_por_unidad
                    FROM producto_ingredientes pi
                    JOIN ingredientes i ON pi.ingrediente_id = i.id
                    WHERE pi.producto_id = ?
                    ORDER BY i.nombre ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$producto_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $data]);
            break;

        // OBTENER TIPO DE STOCK (unidad | ingredientes | sin_stock)
        case "obtener_tipo_stock":
            $producto_id = intval($_GET['producto_id'] ?? $input['producto_id'] ?? 0);
            if (!$producto_id) json_error("producto_id requerido.");

            $stmt = $pdo->prepare("SELECT tipo_stock FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $tipo = $res['tipo_stock'] ?? 'sin_stock';
            echo json_encode(["success" => true, "tipo_stock" => $tipo]);
            break;

        // ACTUALIZAR TIPO DE STOCK
        case "actualizar_tipo_stock":


            $producto_id = intval($input['producto_id'] ?? 0);
            $tipo = $input['tipo'] ?? 'sin_stock';

            if (!$producto_id) json_error("producto_id requerido.");
            if (!in_array($tipo, ['unidad', 'ingredientes', 'sin_stock'])) json_error("tipo inválido.");

            $stmt = $pdo->prepare("UPDATE productos SET tipo_stock = ? WHERE id = ?");
            $stmt->execute([$tipo, $producto_id]);
            echo json_encode(["success" => true]);
            break;

        // ASIGNAR INGREDIENTES A UN PRODUCTO (reemplaza asignaciones previas)
        case "asignar_ingredientes":


            $producto_id = intval($input['producto_id'] ?? 0);
            $ingredientes = $input['ingredientes'] ?? [];
            if (!$producto_id) json_error("producto_id requerido.");

            // Empezamos transacción
            $pdo->beginTransaction();

            try {
                // Borrar asignaciones previas
                $stmt = $pdo->prepare("DELETE FROM producto_ingredientes WHERE producto_id = ?");
                $stmt->execute([$producto_id]);

                // Insertar nuevas asignaciones
                $ins = $pdo->prepare("INSERT INTO producto_ingredientes (producto_id, ingrediente_id, cantidad_por_unidad) VALUES (?, ?, ?)");

                foreach ($ingredientes as $ing) {
                    $ing_id = intval($ing['ingrediente_id'] ?? $ing['id'] ?? 0);
                    $cant = floatval($ing['cantidad'] ?? $ing['cantidad_por_unidad'] ?? 0);

                    if ($ing_id <= 0 || $cant <= 0) continue;

                    $ins->execute([$producto_id, $ing_id, $cant]);
                }

                $pdo->commit();
                echo json_encode(["success" => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // OPCIONAL: obtener ingrediente por id
        case "obtener_ingrediente":
            $id = intval($_GET['id'] ?? 0);
            if (!$id) json_error("id requerido.");

            $stmt = $pdo->prepare("SELECT id, nombre, stock_actual, unidad_medida FROM ingredientes WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "data" => $res]);
            break;

        // ELIMINAR INGREDIENTE
        case "eliminar_ingrediente":


            $id = intval($input['id'] ?? 0);
            if (!$id) json_error("id requerido.");

            // Verificar si está siendo usado en productos
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM producto_ingredientes WHERE ingrediente_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                json_error("No se puede eliminar. El ingrediente está siendo usado en " . $result['count'] . " producto(s).");
            }

            $stmt = $pdo->prepare("DELETE FROM ingredientes WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true]);
            break;

        default:
            json_error("Acción no válida: " . ($action ?? 'null'));
    }
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error($e->getMessage());
}
