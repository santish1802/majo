<?php
require $_SERVER['DOCUMENT_ROOT'] . "/config.php";

/**
 * Función principal para obtener datos estadísticos para un período específico o un rango de fechas.
 */
function getPeriodData($pdo, $period, $startDate = null, $endDate = null) {
    $data = [];
    $params = [];

    $simpleDateCondition = "";
    $joinDateCondition = "";
    $periodName = "";

    switch($period) {
        case 'day':
            $simpleDateCondition = "WHERE DATE(fecha_pedido) = CURDATE()";
            $joinDateCondition = "AND DATE(p.fecha_pedido) = CURDATE()";
            $periodName = "Hoy";
            break;
        case 'week':
            $simpleDateCondition = "WHERE YEARWEEK(fecha_pedido, 1) = YEARWEEK(CURDATE(), 1)";
            $joinDateCondition = "AND YEARWEEK(p.fecha_pedido, 1) = YEARWEEK(CURDATE(), 1)";
            $periodName = "Esta Semana";
            break;
        case 'month':
            $simpleDateCondition = "WHERE YEAR(fecha_pedido) = YEAR(CURDATE()) AND MONTH(fecha_pedido) = MONTH(CURDATE())";
            $joinDateCondition = "AND YEAR(p.fecha_pedido) = YEAR(CURDATE()) AND MONTH(p.fecha_pedido) = MONTH(CURDATE())";
            $periodName = "Este Mes";
            break;
        case 'custom':
            if ($startDate && $endDate) {
                $simpleDateCondition = "WHERE DATE(fecha_pedido) BETWEEN ? AND ?";
                $joinDateCondition = "AND DATE(p.fecha_pedido) BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
                $periodName = "Del " . date("d/m/Y", strtotime($startDate)) . " al " . date("d/m/Y", strtotime($endDate));
            }
            break;
        default:
            $simpleDateCondition = "WHERE DATE(fecha_pedido) = CURDATE()";
            $joinDateCondition = "AND DATE(p.fecha_pedido) = CURDATE()";
            $periodName = "Hoy";
    }

    // Total de ventas y pedidos
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(total), 0) AS total_ventas,
            COUNT(*) AS total_pedidos
        FROM pedidos
        " . ($simpleDateCondition ?: "WHERE 1=1") . " AND estado = 'completado'
    ");
    $stmt->execute($params);
    $ventas = $stmt->fetch();
    $data['total_ventas'] = $ventas['total_ventas'];
    $data['total_pedidos'] = $ventas['total_pedidos'];
    $data['period_name'] = $periodName;

    // Top 5 productos (MODIFICADO)
    $stmt = $pdo->prepare("
        SELECT pr.nombre, pr.categoria, SUM(pd.cantidad) AS total_vendido, SUM(pd.cantidad * pd.precio_unitario) AS ingresos
        FROM pedido_detalle pd
        JOIN productos pr ON pd.producto_id = pr.id
        JOIN pedidos p ON pd.pedido_id = p.id
        WHERE p.estado = 'completado' AND LOWER(pr.categoria) != 'baño' " . $joinDateCondition . "
        GROUP BY pd.producto_id, pr.nombre, pr.categoria
        ORDER BY total_vendido DESC
        LIMIT 5
    ");
    $stmt->execute($params);
    $data['top_productos'] = $stmt->fetchAll();

    // Ventas por categoría
    $stmt = $pdo->prepare("
        SELECT pr.categoria, SUM(pd.cantidad * pd.precio_unitario) AS total_ventas
        FROM pedido_detalle pd
        JOIN productos pr ON pd.producto_id = pr.id
        JOIN pedidos p ON pd.pedido_id = p.id
        WHERE p.estado = 'completado' " . $joinDateCondition . "
        GROUP BY pr.categoria
        ORDER BY total_ventas DESC
    ");
    $stmt->execute($params);
    $data['categorias'] = $stmt->fetchAll();

    // Horarios pico (unificado)
    $stmt = $pdo->prepare("
        SELECT HOUR(fecha_pedido) AS hora, COUNT(*) AS pedidos, SUM(total) AS ventas
        FROM pedidos
        " . ($simpleDateCondition ?: "WHERE 1=1") . " AND estado = 'completado'
        GROUP BY HOUR(fecha_pedido)
        ORDER BY hora ASC
    ");
    $stmt->execute($params);
    $data['horas_pico'] = $stmt->fetchAll(); // Se unifica en un solo array

    return $data;
}

/**
 * Obtiene las ventas y pedidos agrupados por día para un rango de fechas.
 */
function getSalesByDay($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT
            DATE(fecha_pedido) AS fecha,
            COALESCE(SUM(total), 0) AS ventas,
            COUNT(*) AS pedidos
        FROM pedidos
        WHERE DATE(fecha_pedido) BETWEEN ? AND ? AND estado = 'completado'
        GROUP BY DATE(fecha_pedido)
        ORDER BY fecha ASC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

/**
 * Obtiene las ventas semanales del mes actual.
 */
function getWeeklySalesOfMonth($pdo) {
    $stmt = $pdo->prepare("
        SELECT
            (WEEK(fecha_pedido, 1) - WEEK(DATE_SUB(fecha_pedido, INTERVAL DAYOFMONTH(fecha_pedido) - 1 DAY), 1) + 1) AS semana,
            MIN(DATE(fecha_pedido)) as inicio_semana,
            MAX(DATE(fecha_pedido)) as fin_semana,
            COALESCE(SUM(total), 0) AS ventas,
            COUNT(*) AS pedidos
        FROM pedidos
        WHERE MONTH(fecha_pedido) = MONTH(CURDATE()) AND YEAR(fecha_pedido) = YEAR(CURDATE()) AND estado = 'completado'
        GROUP BY semana
        ORDER BY semana ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}
// --- LÓGICA PRINCIPAL DE LA PÁGINA ---

$section = $_GET['section'] ?? 'daily';
$periodData = [];
$chartData = [];

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

switch ($section) {
    case 'weekly':
        $periodData = getPeriodData($pdo, 'week');
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        $chartData = getSalesByDay($pdo, $startOfWeek, $endOfWeek);
        break;
    case 'monthly':
        $periodData = getPeriodData($pdo, 'month');
        $chartData = getWeeklySalesOfMonth($pdo);
        break;
    case 'custom':
        $periodData = getPeriodData($pdo, 'custom', $startDate, $endDate);
        $chartData = getSalesByDay($pdo, $startDate, $endDate);
        break;
    case 'daily':
    default:
        $section = 'daily';
        $periodData = getPeriodData($pdo, 'day');
        break;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Estadísticas - Cafetería MAJO</title>
    <link href="/assets/scss/bootstrap.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #022238;
            --secondary: #617889;
            --success: #10b981;
            --info: #6395f1;
            --warning: #fbbf24;
            --danger: #ef4444;
            --light: #e2e8f0;
            --dark: #1f2937;
            --bs-body-bg: #f8fafc; /* Color de fondo general más claro */
        }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bs-body-bg); }
        .main-content { padding: 20px; }
        .card {
            border: 1px solid var(--light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-radius: 0.75rem;
        }
        .stats-card {
            color: #fff;
            padding: 25px;
            text-align: center;
            border: none;
        }
        .stats-card.sales { background: linear-gradient(45deg, var(--success), #15d194); }
        .stats-card.orders { background: linear-gradient(45deg, var(--info), #82abf3); }
        .stat-number { font-size: 2.5rem; font-weight: 700; }
        .stat-label { font-size: 1rem; }
        .chart-container { position: relative; height: 320px; }
        .table-hover tbody tr:hover { background-color: #f5f5f5; }

        /* Estilos para la nueva navegación de botones */
        .nav-buttons .btn {
            background-color: #fff;
            color: var(--secondary);
            border: 1px solid var(--light);
        }
        .nav-buttons .btn.active {
            background-color: var(--primary);
            color: #fff;
            border-color: var(--primary);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . "/nav.php"; ?>

    <div class="container-fluid">
        <main class="main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                <h1 class="h2">Panel de Estadísticas MAJO</h1>
            </div>

            <div class="btn-group nav-buttons mb-4 shadow-sm" role="group">
                <a href="?section=daily" class="btn <?= $section == 'daily' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-day fa-fw me-2"></i>Diario
                </a>
                <a href="?section=weekly" class="btn <?= $section == 'weekly' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-week fa-fw me-2"></i>Semanal
                </a>
                <a href="?section=monthly" class="btn <?= $section == 'monthly' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt fa-fw me-2"></i>Mensual
                </a>
                <a href="?section=custom" class="btn <?= $section == 'custom' ? 'active' : '' ?>">
                    <i class="fas fa-sliders-h fa-fw me-2"></i>Personalizado
                </a>
            </div>

            <?php if ($section === 'custom'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <form class="row g-3 align-items-center">
                        <input type="hidden" name="section" value="custom">
                        <div class="col-auto">
                            <label for="start_date" class="form-label">Desde:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                        </div>
                        <div class="col-auto">
                            <label for="end_date" class="form-label">Hasta:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                        </div>
                        <div class="col-auto mt-auto pt-3">
                            <button type="submit" class="btn btn-primary" style="background-color: var(--primary); border-color: var(--primary);"><i class="fas fa-search me-1"></i> Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card stats-card sales">
                        <div class="stat-number">S/ <?= number_format($periodData['total_ventas'], 2) ?></div>
                        <div class="stat-label"><i class="fas fa-coins me-1"></i> Ventas Totales (<?= $periodData['period_name'] ?>)</div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card stats-card orders">
                        <div class="stat-number"><?= $periodData['total_pedidos'] ?></div>
                        <div class="stat-label"><i class="fas fa-receipt me-1"></i> Pedidos Completados (<?= $periodData['period_name'] ?>)</div>
                    </div>
                </div>
            </div>

            <?php if (in_array($section, ['weekly', 'monthly', 'custom'])): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>
                            <?php
                                if ($section === 'weekly') echo 'Ventas Diarias (Esta Semana)';
                                if ($section === 'monthly') echo 'Ventas por Semana (Este Mes)';
                                if ($section === 'custom') echo 'Ventas Diarias (Rango Seleccionado)';
                            ?>
                        </h5></div>
                        <div class="card-body"><div class="chart-container"><canvas id="mainChart"></canvas></div></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-trophy me-2"></i>Top Productos (<?= $periodData['period_name'] ?>)</h5></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr><th>Producto</th><th>Vendidos</th><th>Ingresos</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($periodData['top_productos'])): ?>
                                            <tr><td colspan="3" class="text-center text-muted p-4">No hay datos de productos para este período.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($periodData['top_productos'] as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($product['nombre']) ?></div>
                                                    <small class="text-muted"><?= ucfirst(htmlspecialchars($product['categoria'])) ?></small>
                                                </td>
                                                <td class="align-middle"><span class="badge rounded-pill" style="background-color: var(--primary);"><?= $product['total_vendido'] ?></span></td>
                                                <td class="align-middle"><span class="fw-bold" style="color: var(--success);">S/ <?= number_format($product['ingresos'], 2) ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Ventas por Categoría (<?= $periodData['period_name'] ?>)</h5></div>
                        <div class="card-body"><div class="chart-container"><canvas id="categoryChart"></canvas></div></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                         <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Pedidos por Hora (<?= $periodData['period_name'] ?>)</h5></div>
                         <div class="card-body"><div class="chart-container"><canvas id="peakHoursChart"></canvas></div></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // --- CONFIGURACIÓN GLOBAL DE GRÁFICOS ---
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#555';

    // Nueva paleta de colores para los gráficos
    const chartColors = ['#022238', '#10b981', '#fbbf24', '#6395f1', '#617889', '#ef4444', '#1f2937'];

    // --- GRÁFICO PRINCIPAL (SEMANAL, MENSUAL O RANGO) ---
    const mainChartCtx = document.getElementById('mainChart');
    const currentSection = '<?= $section ?>';
    const mainChartData = <?= json_encode($chartData) ?>;

    if (mainChartCtx && mainChartData.length > 0) {
        let chartConfig = {};

        if (currentSection === 'weekly' || currentSection === 'custom') {
            chartConfig = {
                data: {
                    labels: mainChartData.map(item => new Date(item.fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' })),
                    datasets: [
                        { type: 'line', label: 'Ventas (S/)', data: mainChartData.map(item => parseFloat(item.ventas)), borderColor: chartColors[0], backgroundColor: 'rgba(2, 34, 56, 0.1)', tension: 0.3, fill: true, yAxisID: 'y', },
                        { type: 'bar', label: 'N° de Pedidos', data: mainChartData.map(item => parseInt(item.pedidos)), backgroundColor: 'rgba(97, 120, 137, 0.5)', borderColor: chartColors[4], yAxisID: 'y1', }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false, },
                    scales: {
                        y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Ventas (S/)' }, beginAtZero: true },
                        y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'N° de Pedidos' }, grid: { drawOnChartArea: false }, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            };
        }
        else if (currentSection === 'monthly') {
             chartConfig = {
                data: {
                    labels: mainChartData.map(item => `Semana ${item.semana}`),
                    datasets: [
                        { type: 'line', label: 'Ventas (S/)', data: mainChartData.map(item => parseFloat(item.ventas)), borderColor: chartColors[0], backgroundColor: 'rgba(2, 34, 56, 0.1)', tension: 0.3, fill: true, yAxisID: 'y', },
                        { type: 'bar', label: 'N° de Pedidos', data: mainChartData.map(item => parseInt(item.pedidos)), backgroundColor: 'rgba(97, 120, 137, 0.5)', borderColor: chartColors[4], yAxisID: 'y1', }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false, },
                    scales: {
                        y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Ventas (S/)' }, beginAtZero: true },
                        y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'N° de Pedidos' }, grid: { drawOnChartArea: false, }, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            };
        }
        if (Object.keys(chartConfig).length > 0) new Chart(mainChartCtx, chartConfig);
    }

    // --- GRÁFICO DE CATEGORÍAS (DOUGHNUT) ---
    const categoryCtx = document.getElementById('categoryChart');
    const categoryData = <?= json_encode($periodData['categorias']) ?>;
    if (categoryCtx && categoryData.length > 0) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.categoria.charAt(0).toUpperCase() + c.categoria.slice(1)),
                datasets: [{ data: categoryData.map(c => parseFloat(c.total_ventas)), backgroundColor: chartColors, borderColor: '#fff', borderWidth: 2, }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // --- GRÁFICO UNIFICADO DE HORAS PICO (BARRAS) ---
    const peakHoursCtx = document.getElementById('peakHoursChart');
    const peakHoursData = <?= json_encode(array_values($periodData['horas_pico'])) ?>;
    if (peakHoursCtx && peakHoursData.length > 0) {
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: peakHoursData.map(item => `${parseInt(item.hora) % 24}:00`),
                datasets: [{
                    label: 'N° de Pedidos',
                    data: peakHoursData.map(item => parseInt(item.pedidos)),
                    backgroundColor: chartColors[3], // Usando el color 'info' de la paleta
                    borderColor: chartColors[0],
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
</script>
</body>
</html>