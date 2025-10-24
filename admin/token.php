<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "majocafe_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Conexión fallida: " . $conn->connect_error]));
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['fcm_token']) || !isset($data['device_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos."]);
    exit;
}

$fcm_token = $conn->real_escape_string($data['fcm_token']);
$device_id = $conn->real_escape_string($data['device_id']);

// Usamos INSERT ... ON DUPLICATE KEY UPDATE
$sql = "INSERT INTO fcm_tokens (device_id, fcm_token, created_at, updated_at)
        VALUES ('$device_id', '$fcm_token', NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            fcm_token = VALUES(fcm_token),
            updated_at = NOW()";

if ($conn->query($sql) === TRUE) {
    echo json_encode([
        "status" => "success",
        "message" => "Token insertado o actualizado correctamente.",
        "device_id" => $device_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar token: " . $conn->error]);
}

$conn->close();
?>
