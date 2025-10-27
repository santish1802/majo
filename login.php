<?php
session_start();
require 'config.php';

// --- Verificar si ya hay cookie activa ---
if (isset($_COOKIE['recordar_usuario'])) {
    $token = $_COOKIE['recordar_usuario'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE token_recordar = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['rol'] = $usuario['rol'];
        header("Location: /index.php");
        exit();
    }
}

// --- Procesar formulario ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['nombre_usuario'];
    $contrasena = $_POST['contrasena'];
    $recordar = isset($_POST['recordar']); // Checkbox

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = :nombre_usuario");
    $stmt->bindParam(':nombre_usuario', $nombre_usuario);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario['contrasena'] == $contrasena) {
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
        $_SESSION['rol'] = $usuario['rol'];

        // Si el usuario quiere recordar la sesión
        if ($recordar) {
            $token = bin2hex(random_bytes(16)); // Genera un token aleatorio
            $stmt = $pdo->prepare("UPDATE usuarios SET token_recordar = :token WHERE id = :id");
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':id', $usuario['id']);
            $stmt->execute();

            // Cookie válida por 30 días
            setcookie('recordar_usuario', $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
        }

        header("Location: /index.php");
        exit();
    } else {
        $error = "Nombre de usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon.php"; ?>
    <link href="/assets/scss/bootstrap.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form action="login.php" method="post">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="recordar" name="recordar">
                                <label class="form-check-label" for="recordar">Recordar sesión</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
