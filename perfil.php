<?php require $_SERVER['DOCUMENT_ROOT'] . '/auth.php'; ?>
<?php
require 'config.php';

// 1. VERIFICAR SI EL USUARIO HA INICIADO SESIÓN
if (!isset($_SESSION['id_usuario'])) {
    header('Location: /login.php');
    exit();
}

$id_usuario_actual = $_SESSION['id_usuario'];

// 3. OBTENER LOS DATOS ACTUALES PARA MOSTRARLOS EN EL FORMULARIO
$stmt = $pdo->prepare("SELECT nombre_usuario, rol FROM usuarios WHERE id = :id");
$stmt->bindParam(':id', $id_usuario_actual);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <?php 
    include $_SERVER['DOCUMENT_ROOT'] . "/assets/img/favicon.php";
    ?>   
    <?php $version = date('YmdHi'); ?>
    <link href="/assets/scss/bootstrap.css?v=<?php echo $version; ?>" rel="stylesheet">
    <link href="/assets/style.css?v=<?php echo $version; ?>" rel="stylesheet">
</head>

<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h4>Modificar mi Perfil</h4>
                    </div>
                    <div class="card-body">

                        <!-- Área para mostrar mensajes -->
                        <div id="mensaje-area"></div>

                        <div>
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                                    value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol</label>
                                <input type="text" class="form-control" id="rol" name="rol"
                                    value="<?php echo htmlspecialchars($usuario['rol']); ?>" readonly>
                            </div>

                            <hr class="my-4">
                            <p class="text-muted">Para cambiar tu contraseña, completa los tres campos siguientes.</p>

                            <div class="mb-3">
                                <label for="antigua_contrasena" class="form-label">Contraseña Antigua</label>
                                <input type="password" class="form-control" id="antigua_contrasena"
                                    name="antigua_contrasena"
                                    <?php echo ($usuario['rol'] === 'user') ? 'disabled' : ''; ?>>
                            </div>

                            <div class="mb-3">
                                <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="nueva_contrasena"
                                    name="nueva_contrasena"
                                    <?php echo ($usuario['rol'] === 'user') ? 'disabled' : ''; ?>>
                            </div>

                            <div class="mb-3">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="confirmar_contrasena"
                                    name="confirmar_contrasena"
                                    <?php echo ($usuario['rol'] === 'user') ? 'disabled' : ''; ?>>
                            </div>

                            <button type="button" class="btn btn-primary w-100" id="btn-guardar">
                                <span class="spinner-border spinner-border-sm d-none" id="loading-spinner"></span>
                                <span id="btn-text">Guardar Cambios</span>
                            </button>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert fijo superior -->
    <div id="alert-container"
        style="position: fixed; top: 2.8125rem; left: 50%; transform: translateX(-50%); z-index: 1050; width: 90%; max-width: 500px;">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para obtener datos de los inputs
        function obtenerDatos() {
            const rol = document.getElementById('rol').value;

            return {
                action: 'actualizar_perfil',
                nombre_usuario: document.getElementById('nombre_usuario').value.trim(),
                antigua_contrasena: rol === 'user' ? '' : document.getElementById('antigua_contrasena').value,
                nueva_contrasena: rol === 'user' ? '' : document.getElementById('nueva_contrasena').value,
                confirmar_contrasena: rol === 'user' ? '' : document.getElementById('confirmar_contrasena').value
            };
        }

        // Función para enviar datos
        function enviarDatos() {
            const btnGuardar = document.getElementById('btn-guardar');
            const loadingSpinner = document.getElementById('loading-spinner');
            const btnText = document.getElementById('btn-text');

            // Mostrar spinner y cambiar texto
            loadingSpinner.classList.remove('d-none');
            btnText.textContent = 'Guardando...';

            const datos = obtenerDatos();

            fetch('/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                })
                .then(response => response.text())
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error("Respuesta no es JSON válido: " + text);
                    }
                    return data;
                })
                .then(data => {
                    if (data.success) {
                        mostrarAlerta(data.message, 'success');
                        document.getElementById('antigua_contrasena').value = '';
                        document.getElementById('nueva_contrasena').value = '';
                        document.getElementById('confirmar_contrasena').value = '';
                    } else {
                        mostrarAlerta(data.message || 'Error desconocido', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarAlerta('Error de conexión o respuesta inválida del servidor.', 'danger');
                })
                .finally(() => {
                    // Restaurar texto y ocultar spinner
                    loadingSpinner.classList.add('d-none');
                    btnText.textContent = 'Guardar Cambios';
                });
        }

        // Función para mostrar alertas fijas
        function mostrarAlerta(mensaje, tipo = 'danger') {
            const alertContainer = document.getElementById('alert-container');

            // Limpiar alertas anteriores
            alertContainer.innerHTML = '';

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${tipo} alert-dismissible fade show shadow-lg`;
            alertDiv.style.margin = '0';
            alertDiv.innerHTML = `
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            alertContainer.appendChild(alertDiv);

            // Auto-ocultar después de 3 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    const alert = new bootstrap.Alert(alertDiv);
                    alert.close();
                }
            }, 3000);
        }

        // Escuchar click en el botón
        document.getElementById('btn-guardar').addEventListener('click', enviarDatos);

        // Escuchar Enter en todos los inputs
        document.querySelectorAll('input[type="text"], input[type="password"]').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    enviarDatos();
                }
            });
        });
    </script>
</body>
</html>
