<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener la página actual
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];


// Verificar si hay sesión iniciada
if (!isset($_SESSION['rol'])) {
    // No hay sesión iniciada
    if ($current_page != 'login.php') {
        // Si no está en login.php, redirigir a login
        header('Location: login.php');
        exit();
    }
} else {
    // Hay sesión iniciada
    if ($current_page == 'login.php') {
        // Si está en login.php y ya está logueado, redirigir a index
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
            // No es admin, redirigir a index
            header('Location: /index.php');
            exit();
        }
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid bg-primary">
        <a class="navbar-brand" href="/">
            <img id="majo" src="/assets/img/majo2.svg" alt="Logo" class="d-inline-block align-text-top">
            <div class="d-flex flex-column ms-2 text-logo" style="line-height: 1;">
                <div>MAJO</div>
                <div>CAFETERÍA</div>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/index.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pedidos.php">Pedidos</a>
                </li>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'user'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/productos.php">Productos</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="/caja.php">Caja</a>
                </li>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/panel.php">Estadísticas</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/stock.php">Stock</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/productos.php">Productos</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex mb-3 mb-lg-0">
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-white dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill"></i> ¡Hola, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>!
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/perfil.php">Mi Perfil</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>


