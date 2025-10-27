<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// --- Si no hay sesión, intentar restaurarla desde cookie ---
if (!isset($_SESSION['rol']) && isset($_COOKIE['recordar_usuario'])) {
    $token = $_COOKIE['recordar_usuario'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token_recordar = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Restaurar sesión
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['rol'] = $usuario['rol'];
    }
}

// --- Verificación de acceso ---
if (!isset($_SESSION['rol'])) {
    if ($current_page != 'login.php') {
        header('Location: /login.php');
        exit();
    }
} else {
    if ($current_page == 'login.php') {
        header('Location: /index.php');
        exit();
    }

    // --- Verificar permisos de administrador ---
    if (
        strpos($current_path, '/admin/productos.php') !== false ||
        strpos($current_path, '/admin/panel.php') !== false ||
        strpos($current_path, '/admin/combos.php') !== false
    ) {
        if ($_SESSION['rol'] != 'admin') {
            header('Location: /index.php');
            exit();
        }
    }
}
?>
