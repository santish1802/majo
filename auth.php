<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// Verificar si hay sesión iniciada
if (!isset($_SESSION['rol'])) {
    if ($current_page != 'login.php') {
        header('Location: login.php');
        exit();
    }
} else {
    if ($current_page == 'login.php') {
        header('Location: /index.php');
        exit();
    }

    // Verificar permisos para páginas de admin
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