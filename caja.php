<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . "/config.php";

// --- FUNCIONES DE SEGURIDAD ---
function generar_token_csrf()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function verificar_token_csrf($token)
{
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("Error de seguridad: Token CSRF no válido.");
    }
}

generar_token_csrf();

// --- VERIFICACIÓN DE SESIÓN ---
if (!isset($_SESSION['rol'])) {
    header("Location: /login.php");
    exit;
}

// --- GESTIÓN DE MENSAJES ---
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// --- LÓGICA DE FILTRADO POR FECHA ---
$fecha_seleccionada = date('Y-m-d');
if (isset($_GET['fecha_filtro']) && !empty($_GET['fecha_filtro'])) {
    $fecha_temp = DateTime::createFromFormat('Y-m-d', $_GET['fecha_filtro']);
    if ($fecha_temp && $fecha_temp->format('Y-m-d') === $_GET['fecha_filtro']) {
        $fecha_seleccionada = $_GET['fecha_filtro'];
    }
}

$redirect_url = $_SERVER['PHP_SELF'] . "?fecha_filtro=" . urlencode($fecha_seleccionada);

// --- LÓGICA DE ACCIONES (POST Y GET) PARA MOVIMIENTOS MANUALES ---

// Lógica para AGREGAR movimiento manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    verificar_token_csrf($_POST['csrf_token']);
    $tipo = $_POST['tipo'];
    $monto = floatval($_POST['monto']);
    $descripcion = trim($_POST['descripcion']);
    $usuario_db = $_SESSION['rol'];

    $movimientos_admin = ['retiro_efectivo', 'gasto_admin', 'ingreso_inicial'];
    if (in_array($tipo, $movimientos_admin) && $_SESSION['rol'] !== 'admin') {
        $_SESSION['mensaje'] = "Error: No tienes permisos para realizar este tipo de movimiento.";
    } else {
        if ($tipo === 'retiro_efectivo' || $tipo === 'gasto_admin') {
            $monto = -$monto;
        }
        try {
            $sql = "INSERT INTO caja_movimientos (tipo, monto, descripcion, usuario) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tipo, $monto, $descripcion, $usuario_db]);
            $_SESSION['mensaje'] = "Movimiento agregado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['mensaje'] = "Error al agregar el movimiento: " . $e->getMessage();
        }
    }
    header("Location: " . $redirect_url);
    exit;
}

// Lógica para ELIMINAR movimiento manual
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) {
    if ($_SESSION['rol'] !== 'admin') {
        $_SESSION['mensaje'] = "Acceso denegado: No tienes permisos para eliminar.";
    } else {
        try {
            $id = intval($_GET['id']);
            $sql = "DELETE FROM caja_movimientos WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['mensaje'] = "Movimiento eliminado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['mensaje'] = "Error al eliminar el movimiento: " . $e->getMessage();
        }
    }
    header("Location: " . $redirect_url);
    exit;
}

// --- OBTENCIÓN DE DATOS Y CÁLCULO DE SALDO ---
$stmt_saldo_manual = $pdo->prepare("SELECT SUM(monto) as saldo FROM caja_movimientos WHERE DATE(fecha) = ?");
$stmt_saldo_manual->execute([$fecha_seleccionada]);
$saldo_manual = $stmt_saldo_manual->fetch(PDO::FETCH_ASSOC)['saldo'] ?? 0;

$stmt_pedidos = $pdo->prepare("SELECT SUM(total) as ingresos FROM pedidos WHERE metodo_pago = 'Efectivo' AND DATE(fecha_pedido) = ?");
$stmt_pedidos->execute([$fecha_seleccionada]);
$ingresos_pedidos = $stmt_pedidos->fetch(PDO::FETCH_ASSOC)['ingresos'] ?? 0;

$saldo_total = $saldo_manual + $ingresos_pedidos;

$stmt_movimientos = $pdo->prepare("SELECT * FROM caja_movimientos WHERE DATE(fecha) = ? ORDER BY fecha DESC");
$stmt_movimientos->execute([$fecha_seleccionada]);
$movimientos = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Caja por Fecha</title>
    <link href="/assets/scss/bootstrap.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">

</head>
<body>
        <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="border-bottom border-primary border-2 pb-2">Control de Caja</h1>
            </div>
        </div>

        <?php if ($mensaje) : ?>
            <div class="alert <?= strpos(strtolower($mensaje), 'error') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card bg-light mb-4">
            <div class="card-body">
                <form method="GET" action="" class="d-flex flex-wrap align-items-center gap-3">
                    <label for="fecha_filtro" class="form-label fw-bold mb-0">Mostrando datos para la fecha:</label>
                    <input type="date" class="form-control" style="max-width: 200px;" name="fecha_filtro" id="fecha_filtro" value="<?= htmlspecialchars($fecha_seleccionada) ?>">
                    <button type="submit" class="btn btn-success">Filtrar</button>
                </form>
            </div>
        </div>

        <div class="card text-center border-success">
            <div class="card-header bg-success text-white fs-4">
                Resumen del Día
            </div>
            <div class="card-body resumen-caja">
                <p class="card-text">Saldo Base (Mov. Manuales): <strong class="text-body">S/.<?= number_format($saldo_manual, 2) ?></strong></p>
                <p class="card-text">+ Ingresos por Pedidos (Efectivo): <strong class="text-body">S/.<?= number_format($ingresos_pedidos, 2) ?></strong></p>
                <hr>
                <h4 class="card-title mb-0">Saldo Total en Caja del Día</h4>
                <p class="fs-2 fw-bold text-success mb-0">S/.<?= number_format($saldo_total, 2) ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-5 mt-4">
                <h2 class="h3 mb-3">Agregar Movimiento Manual</h2>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?= $redirect_url ?>">
                            <input type="hidden" name="accion" value="agregar">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo de movimiento:</label>
                                <select name="tipo" id="tipo" class="form-select" required>
                                    <option value="ingreso_efectivo">Ingreso de efectivo (Manual)</option>
                                    <?php if ($_SESSION['rol'] === 'admin') : ?>
                                        <option value="ingreso_inicial">Ingreso inicial</option>
                                        <option value="retiro_efectivo">Retiro de efectivo</option>
                                        <option value="gasto_admin">Gasto administrativo</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="monto" class="form-label">Monto:</label>
                                <input type="number" step="0.01" name="monto" id="monto" class="form-control" min="0" required>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción:</label>
                                <input type="text" name="descripcion" id="descripcion" class="form-control" placeholder="(Opcional)">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Agregar Movimiento</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-5 mt-4">
                <h2 class="h3 mb-3">Historial de Movimientos Manuales</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover border">
                        <thead class="bg-primary text-black">
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Descripción</th>
                                <th>Usuario</th>
                                <?php if ($_SESSION['rol'] === 'admin') : ?><th>Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $m) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['id']) ?></td>
                                    <td><?= htmlspecialchars($m['fecha']) ?></td>
                                    <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($m['tipo']))) ?></td>
                                    <td class="fw-bold <?= $m['monto'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        S/.<?= number_format($m['monto'], 2) ?>
                                    </td>
                                    <td><?= htmlspecialchars($m['descripcion']) ?></td>
                                    <td><?= htmlspecialchars($m['usuario']) ?></td>
                                    <?php if ($_SESSION['rol'] === 'admin') : ?>
                                        <td>
                                            <a href="?accion=eliminar&id=<?= htmlspecialchars($m['id']) ?>&fecha_filtro=<?= htmlspecialchars($fecha_seleccionada) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este movimiento?')">
                                                Eliminar
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>