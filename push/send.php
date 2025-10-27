<?php
// push_functions.php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Envía una notificación push multicast a todos los tokens registrados en la BD.
 *
 * @param string $title Título de la notificación.
 * @param string $body  Cuerpo de la notificación.
 * @param string|null $imageUrl URL opcional de la imagen de la notificación.
 * @return array Resultado del envío: success, sent, failed, errors.
 */
function sendMulticastNotification($title, $body, $imageUrl = null) {
    global $pdo;

    // --- OBTENER TOKENS ---
    $stmt = $pdo->query("SELECT fcm_token FROM fcm_tokens");
    $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$tokens) {
        return ["success" => false, "message" => "No hay tokens en la base de datos"];
    }

    // --- CONFIGURACIÓN FIREBASE ---
    $serviceAccount = $_SERVER['DOCUMENT_ROOT'] . '/push/pvKey.json';

    $factory = (new Factory())
        ->withServiceAccount($serviceAccount)
        ->withProjectId('majo-19e66');

    $messaging = $factory->createMessaging();

    // --- MENSAJE ---
    $notification = Notification::create($title, $body, $imageUrl);
    $message = CloudMessage::new()
        ->withNotification($notification)
        ->withWebPushConfig([
            'fcm_options' => ['link' => 'https://majocafe.site']
        ]);

    try {
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

        return [
            "success" => true,
            "sent" => $successCount,
            "failed" => $failureCount,
            "errors" => $errors,
            "message" => "Notificación multicast enviada desde BD"
        ];

    } catch (MessagingException $e) {
        return ["success" => false, "message" => "Error de mensajería: " . $e->getMessage()];
    } catch (FirebaseException $e) {
        return ["success" => false, "message" => "Error de Firebase: " . $e->getMessage()];
    } catch (Exception $e) {
        return ["success" => false, "message" => "Error general: " . $e->getMessage()];
    }
}


// $title = "Notificación de prueba";
// $body = "Este es un mensaje de prueba para verificar las notificaciones push";
// $imageUrl = "https://ejemplo.com/imagen.jpg"; // opcional, puedes dejarlo como null

// $result = sendMulticastNotification($title, $body, $imageUrl);

// // Mostrar el resultado
// print_r($result);