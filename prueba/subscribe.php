<?php

require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;

// Ruta al archivo de credenciales JSON
$serviceAccount = __DIR__ . '/majo-19e66-firebase-adminsdk-fbsvc-8ff7f75488.json';

// Inicializar la instancia de Firebase
$factory = (new Factory())->withServiceAccount($serviceAccount);
$messaging = $factory->createMessaging();

// Definir los tokens de los dispositivos destinatarios
$deviceTokens = [
    'dr5bPq3-C2TpqR4yf4oLIi:APA91bH-wJBKRhJhkdOIZ-HHgavTu1uiHNeOhxeEQRuXMlvRCSNcJcM2u_z2CuDRi8eA8NAX_ezho44_AFS-4inwbr3PoSE0v7UFqdxvVcxq-Q0yvtsvX7M'
    // Agregar más tokens según sea necesario
];

// Crear el mensaje
$message = CloudMessage::new()
    ->withNotification(['title' => 'Título de la Notificación', 'body' => 'Contenido del mensaje'])
    ->withData(['key' => 'value']); // Datos adicionales opcionales

// Enviar el mensaje a múltiples dispositivos
try {
    $sendReport = $messaging->sendMulticast($message, $deviceTokens);

    // Procesar el reporte de envío
    foreach ($sendReport->successes() as $index => $result) {
        echo "Mensaje enviado exitosamente al token {$deviceTokens[$index]}: {$result->messageId()}\n";
    }

    foreach ($sendReport->failures() as $index => $error) {
        echo "Error al enviar mensaje al token {$deviceTokens[$index]}: {$error->getMessage()}\n";
    }
} catch (Exception $e) {
    echo "Error al enviar notificaciones: " . $e->getMessage();
}
?>
