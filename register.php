<?php require $_SERVER['DOCUMENT_ROOT'] . '/auth.php'; ?>
<?php
// 1. INCLUIR LA CONFIGURACIÓN DE LA BASE DE DATOS
// Este archivo debe contener tu conexión $pdo.
require_once 'config.php';

// Variables para mensajes de error o éxito
$mensaje = '';
$error = '';

// 2. PROCESAR EL FORMULARIO CUANDO SE ENVÍA
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger y limpiar los datos del formulario
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $contrasena = trim($_POST['contrasena']);
    $rol = trim($_POST['rol']);

    // Validar que los campos no estén vacíos
    if (empty($nombre_usuario) || empty($contrasena) || empty($rol)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        try {
            // 3. HASHEAR LA CONTRASEÑA (¡MUY IMPORTANTE PARA LA SEGURIDAD!)
            // Nunca guardes contraseñas en texto plano.
            $contrasena_hasheada = $contrasena;
            // 4. PREPARAR LA SENTENCIA SQL PARA EVITAR INYECCIÓN SQL
            $sql = "INSERT INTO usuarios (nombre_usuario, contrasena, rol) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            // 5. EJECUTAR LA SENTENCIA
            if ($stmt->execute([$nombre_usuario, $contrasena_hasheada, $rol])) {
                // Si tiene éxito, recargar la página para limpiar el formulario
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Hubo un error al crear el usuario. Inténtalo de nuevo.";
            }
        } catch (PDOException $e) {
            // Manejar errores, como un nombre de usuario duplicado
            if ($e->errorInfo[1] == 1062) { // 1062 es el código de error para entrada duplicada
                $error = "El nombre de usuario '$nombre_usuario' ya existe.";
            } else {
                $error = "Error de base de datos: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Usuario</title>
    <link href="/assets/scss/bootstrap.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="/assets/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="/assets/css/all.css" rel="stylesheet"> </head>
<body>

    <?php 
    // Incluir la barra de navegación
    // include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; 
    ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">
                                    <i class="fas fa-user"></i> Nombre de Usuario
                                </label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="contrasena" class="form-label">
                                    <i class="fas fa-key"></i> Contraseña
                                </label>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                            </div>
                            <div class="mb-3">
                                <label for="rol" class="form-label">
                                    <i class="fas fa-user-tag"></i> Rol
                                </label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="" disabled selected>Selecciona un rol...</option>
                                    <option value="admin">Administrador</option>
                                    <option value="user">Usuario</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>