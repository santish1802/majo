<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php"; 
header('Content-Type: application/json');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    die(json_encode([
        "status" => "error", 
        "message" => "Error de configuración: La conexión PDO (\$pdo) no está disponible."
    ]));
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (empty($data['fcm_token']) || empty($data['device_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos."]);
    exit;
}

$fcm_token = trim($data['fcm_token']); 
$device_id = trim($data['device_id']); 

try {
    $sql = "
        INSERT INTO fcm_tokens (device_id, fcm_token, created_at, updated_at)
        VALUES (:device_id, :fcm_token, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            fcm_token = IF(fcm_tokens.fcm_token <> VALUES(fcm_token), VALUES(fcm_token), fcm_tokens.fcm_token),
            updated_at = IF(fcm_tokens.fcm_token <> VALUES(fcm_token), NOW(), fcm_tokens.updated_at)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':device_id', $device_id);
    $stmt->bindParam(':fcm_token', $fcm_token);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "Token insertado o actualizado solo si era distinto.",
        "device_id" => $device_id
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar token (PDO): " . $e->getMessage()
    ]);
}
?>
