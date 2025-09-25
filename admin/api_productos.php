<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php"; 
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'obtener_productos_dt':
            obtenerProductosParaDataTables($pdo, $input);
            break;
        case 'crear_producto':
            crearProducto($pdo, $input['nombre'], $input['precio'], $input['detalle'], $input['notas'], $input['categoria']);
            break;
        case 'actualizar_producto':
            actualizarProducto($pdo, $input['id'], $input['nombre'], $input['precio'], $input['detalle'], $input['notas'], $input['categoria']);
            break;
        case 'eliminar_producto':
            eliminarProducto($pdo, $input['id']);
            break;
        case 'actualizar_notas_producto':
            actualizarNotasProducto($pdo, $input['id'], $input['notas']);
            break;
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida: ' . $action,
                'request_data' => $input
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la operación: ' . $e->getMessage()
    ]);
}

/**
 * Obtiene los productos para DataTables, con paginación y búsqueda del lado del servidor.
 */
function obtenerProductosParaDataTables($pdo, $request) {
    $draw = $request['draw'] ?? 1;
    $start = $request['start'] ?? 0;
    $length = $request['length'] ?? 10;
    $searchValue = $request['search']['value'] ?? '';

    // Consulta base para contar el total de registros activos
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
    $recordsTotal = $stmt_total->fetchColumn();

    $query = "SELECT * FROM productos WHERE activo = 1";
    $params = [];

    // Lógica de búsqueda
    if (!empty($searchValue)) {
        $query .= " AND (nombre LIKE ? OR categoria LIKE ? OR detalle LIKE ?)";
        $params[] = '%' . $searchValue . '%';
        $params[] = '%' . $searchValue . '%';
        $params[] = '%' . $searchValue . '%';
    }

    // Consulta para contar los registros filtrados
    $stmt_filtered = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*)', $query));
    $stmt_filtered->execute($params);
    $recordsFiltered = $stmt_filtered->fetchColumn();

    // Lógica de ordenamiento
    $orderColumnIndex = $request['order'][0]['column'] ?? 0;
    $orderDir = $request['order'][0]['dir'] ?? 'asc';
    $columns = ['id', 'nombre', 'categoria', 'precio'];
    $orderColumn = $columns[$orderColumnIndex] ?? 'id';
    $orderDir = ($orderDir === 'asc') ? 'ASC' : 'DESC';
    $query .= " ORDER BY {$orderColumn} {$orderDir}";

    // Lógica de paginación
    $query .= " LIMIT ?, ?";

    $stmt = $pdo->prepare($query);

    // Enlazar los parámetros de búsqueda si existen
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex, $param, PDO::PARAM_STR);
        $paramIndex++;
    }

    // Enlazar los parámetros de LIMIT como enteros
    $stmt->bindValue($paramIndex++, (int)$start, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, (int)$length, PDO::PARAM_INT);

    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'draw' => (int)$draw,
        'recordsTotal' => (int)$recordsTotal,
        'recordsFiltered' => (int)$recordsFiltered,
        'data' => $productos,
    ]);
}

function crearProducto($pdo, $nombre, $precio, $detalle, $notas, $categoria) {
    $stmt = $pdo->prepare("INSERT INTO productos (nombre, precio, detalle, notas, categoria) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$nombre, $precio, $detalle, $notas, $categoria])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('No se pudo crear el producto.');
    }
}

function actualizarProducto($pdo, $id, $nombre, $precio, $detalle, $notas, $categoria) {
    $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, precio = ?, detalle = ?, notas = ?, categoria = ? WHERE id = ?");
    if ($stmt->execute([$nombre, $precio, $detalle, $notas, $categoria, $id])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('No se pudo actualizar el producto.');
    }
}

function actualizarNotasProducto($pdo, $id, $notas) {
    $stmt = $pdo->prepare("UPDATE productos SET notas = ? WHERE id = ?");
    if ($stmt->execute([$notas, $id])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('No se pudieron actualizar las notas del producto.');
    }
}

function eliminarProducto($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('No se pudo eliminar el producto.');
    }
}
?>