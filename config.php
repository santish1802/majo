<?php
date_default_timezone_set('America/Lima'); // Establece la zona horaria a Lima, Perú


$host     = '127.0.0.1';
$port     = '3308';
$db       = 'fdrcsdqs_majocafe_system';
$user     = 'fdrcsdqs_santiago';
$pass     = 'P75609967p';


$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '$charset' COLLATE '{$charset}_unicode_ci'",
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET NAMES '$charset' COLLATE '{$charset}_unicode_ci'");
    $pdo->exec("SET time_zone = '-05:00'"); // Hora de Lima (UTC-5)

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// Función para formatear precios
function formatPrecio($precio) {
    return 'S/. ' . number_format($precio, 2);
}
?>
