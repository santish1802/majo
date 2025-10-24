<?php
require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

// --- HEADERS ---
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// --- CONEXIÓN BD ---
$host = 'localhost';
$db   = 'majocafe_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_COLUMN,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die(json_encode(["success" => false, "message" => "Error BD: ".$e->getMessage()]));
}

// --- OBTENER TOKENS ---
$stmt = $pdo->query("SELECT fcm_token FROM fcm_tokens"); // tabla con columna fcm_token
$tokens = $stmt->fetchAll();

if (!$tokens) {
    die(json_encode(["success" => false, "message" => "No hay tokens en la base de datos"]));
}

// --- CONFIGURACIÓN FIREBASE ---
$serviceAccount = __DIR__ . '/pvKey.json';

$factory = (new Factory())
    ->withServiceAccount($serviceAccount)
    ->withProjectId('majo-19e66');

$messaging = $factory->createMessaging();

// --- MENSAJE ---
$title = "Multicast desde BD 🔔";
$body = "Mensaje enviado a todos los dispositivos registrados";

$imageUrl = "https://cdn.shopify.com/s/files/1/1061/1924/files/Sunglasses_Emoji.png?2976903553660223024"; // URL de tu imagen

$notification = Notification::create($title, $body, $imageUrl);
$message = CloudMessage::new()
    ->withNotification($notification)
    ->withWebPushConfig([
        'fcm_options' => ['link' => 'https://majocafe.site']
    ]);

try {
    // --- ENVÍO MULTICAST ---
    $report = $messaging->sendMulticast($message, $tokens);

    $successCount = $report->successes()->count();
    $failureCount = $report->failures()->count();

    $errors = [];
    foreach ($report->failures()->getItems() as $failure) {
        $errors[] = [
            'token' => $failure->target()->value(),
            'error' => $failure->error()->getMessage(),
        ];
    }

    echo json_encode([
        "success" => true,
        "sent" => $successCount,
        "failed" => $failureCount,
        "errors" => $errors,
        "message" => "Notificación multicast enviada desde BD"
    ]);

} catch (MessagingException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de mensajería: " . $e->getMessage()
    ]);
} catch (FirebaseException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error de Firebase: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error general: " . $e->getMessage()
    ]);
}
?>
