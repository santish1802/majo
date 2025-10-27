<?php
// ¡SOLO REQUERIMOS LA CONFIGURACIÓN!
// Se ASUME que $pdo (el objeto de conexión PDO) se crea y está disponible
// como una variable global dentro de este archivo después de este require.
require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php"; 

header('Content-Type: application/json');

// NO SE REALIZA NINGUNA CONEXIÓN AQUÍ, se usa la que viene de config.php ($pdo)

// 1. Verificación de la conexión (Opcional, pero buena práctica si no estás seguro de config.php)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    die(json_encode([
        "status" => "error", 
        "message" => "Error de configuración: La conexión PDO (\$pdo) no está disponible."
    ]));
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['fcm_token']) || !isset($data['device_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos."]);
    exit;
}

$fcm_token = $data['fcm_token']; 
$device_id = $data['device_id']; 

// 2. Uso de Declaración Preparada (Prepared Statement)
$sql = "INSERT INTO fcm_tokens (device_id, fcm_token, created_at, updated_at)
        VALUES (:device_id, :fcm_token, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            fcm_token = :fcm_token_update,
            updated_at = NOW()";

try {
    // 3. Preparar la declaración usando el objeto $pdo
    $stmt = $pdo->prepare($sql);

    // 4. Asignar los parámetros (Binding)
    $stmt->bindParam(':device_id', $device_id);
    $stmt->bindParam(':fcm_token', $fcm_token);
    $stmt->bindParam(':fcm_token_update', $fcm_token);

    // 5. Ejecutar la declaración
    $stmt->execute();

    // 6. Respuesta de éxito
    echo json_encode([
        "status" => "success",
        "message" => "Token insertado o actualizado correctamente (PDO).",
        "device_id" => $device_id
    ]);

} catch (\PDOException $e) {
    // 7. Manejo de errores de la consulta
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar token (PDO): " . $e->getMessage()
    ]);
}

// Nota: Con PDO, no es necesario llamar a $pdo->close() ni a $stmt->closeCursor().
// La conexión se maneja automáticamente.
?>