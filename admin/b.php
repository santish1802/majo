<?php
header("Content-Type: application/json");
require $_SERVER['DOCUMENT_ROOT'] . '/config.php'; // Debe contener $pdo
require $_SERVER['DOCUMENT_ROOT'] . '/push/send.php';


$result = sendMulticastNotification(
    "Hola 👋",
    "Este es un mensaje de prueba",
    "https://cdn.shopify.com/s/files/1/1061/1924/files/Sunglasses_Emoji.png"
);
echo json_encode($result);
