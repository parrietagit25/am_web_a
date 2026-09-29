<?php
/**
 * Admin — Dashboard de reservas Rent A Car.
 * Solo lectura. No modifica el flujo de reserva ni RentWorks.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/RacReservationDashboardService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!admin_can('rac_reservations') && !AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo 'Acceso denegado. Requiere permiso de Registro de reservas.';
    exit;
}

$allowedDays = [7, 30, 90, 365, 0];
$days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
if (!in_array($days, $allowedDays, true)) {
    $days = 30;
}

$fromInput = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
$toInput = isset($_GET['to']) ? trim((string) $_GET['to']) : '';
$useRange = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromInput) === 1
    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toInput) === 1;

$stats = (new RacReservationDashboardService())->build(
    $days,
    $useRange ? $fromInput : null,
    $useRange ? $toInput : null
);
$rangeCustom = !empty($stats['range_custom']);
$defaultAdminTab = 'rac-dashboard';

$money = static function (float $n): string {
    return '$' . number_format($n, 2);
};

$chart = [
    'labels' => array_column($stats['timeline'], 'label'),
    'total' => array_column($stats['timeline'], 'total'),
    'paid' => array_column($stats['timeline'], 'paid'),
    'unpaid' => array_column($stats['timeline'], 'unpaid'),
    'revenue' => array_column($stats['timeline'], 'revenue'),
    'locations' => $stats['locations'],
    'vehicles' => $stats['vehicles'],
    'weeks' => $stats['weeks'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Dashboard reservas | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root { --navy: #081026; --gray-bg: #f8f9fc; --border-color: #e3e6f0; --primary-red: #c51f17; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-bg); color: var(--navy); }
        .admin-sidebar { background: var(--navy); color: #fff; min-height: 100vh; }
        .admin-sidebar .nav-link, .admin-sidebar a.admin-sidebar-page-link { color: rgba(255,255,255,.7); text-decoration: none; margin: 4px 10px; padding: 12px 16px; border-radius: 8px; display: block; }
        #rentacar-submenu .nav-link, #rentacar-submenu a.admin-sidebar-page-link { padding-left: 28px; font-size: .85rem; }
        .admin-header { background: #fff; border-bottom: 1px solid var(--border-color); padding: 15px 30px; }
        .kpi { background: #fff; border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 20px; height: 100%; }
        .kpi .label { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
        .kpi .value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; }
        .panel { background: #fff; border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; }
        .chart-box { position: relative; height: 280px; }
        .chart-box.tall { height: 320px; }
    </style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-lg-3 col-md-4 p-0 admin-sidebar d-flex flex-column">
    <div class="p-4 text-center border-bottom border-secondary mb-3">
        <img src="/assets/img/logo.png" alt="Logo" height="32" style="filter:brightness(0) invert(1)">
        <span class="badge bg-danger mt-2">Administración</span>
    </div>
    <?php require __DIR__ . '/../../includes/admin-sidebar-nav.php'; ?>
    <div class="mt-auto p-4 border-top border-secondary text-center">
        <a href="/admin/logout.php" class="btn btn-sm btn-outline-danger w-100">Cerrar sesión</a>
    </div>
</div>
<div class="col-lg-9 col-md-8 p-0">
    <div class="admin-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Dashboard de reservas</h4>
            <p class="small text-muted mb-0">Rent A Car — cifras locales de la web. No modifica RentWorks.</p>
        </div>
        <div class="d-flex flex-column align-items-stretch align-items-md-end gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Periodo">
                <?php foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '12 meses', 0 => 'Todo'] as $d => $label): ?>
                    <a class="btn <?php echo !$rangeCustom && $days === $d ? 'btn-danger' : 'btn-outline-secondary'; ?>" href="?days=<?php echo (int) $d; ?>"><?php echo esc($label); ?></a>
                <?php endforeach; ?>
            </div>
            <form class="d-flex align-items-end gap-2 flex-wrap" method="get">
                <div>
                    <label class="form-label small mb-0" for="dash-from">Desde</label>
                    <input class="form-control form-control-sm" type="date" id="dash-from" name="from" value="<?php echo esc((string) ($stats['range_from'] ?? '')); ?>" required>
                </div>
                <div>
                    <label class="form-label small mb-0" for="dash-to">Hasta</label>
                    <input class="form-control form-control-sm" type="date" id="dash-to" name="to" value="<?php echo esc((string) ($stats['range_to'] ?? '')); ?>" required>
                </div>
                <button class="btn btn-sm btn-danger" type="submit">Aplicar</button>
            </form>
        </div>
    </div>
    <div class="p-4">
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Total reservas</div><div class="value"><?php echo (int) $stats['total']; ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Pagadas</div><div class="value text-success"><?php echo (int) $stats['paid']; ?></div><div class="small text-muted"><?php echo esc((string) $stats['paid_rate']); ?>% del total</div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">No pagadas</div><div class="value text-danger"><?php echo (int) $stats['unpaid']; ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Canceladas</div><div class="value"><?php echo (int) $stats['cancelled']; ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Promedio / día</div><div class="value"><?php echo esc(number_format((float) $stats['avg_per_day'], 2)); ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Promedio / semana</div><div class="value"><?php echo esc(number_format((float) $stats['avg_per_week'], 2)); ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Monto estimado</div><div class="value"><?php echo esc($money((float) $stats['revenue'])); ?></div></div></div>
            <div class="col-6 col-xl-3"><div class="kpi"><div class="label">Cobrado en línea</div><div class="value"><?php echo esc($money((float) $stats['revenue_paid'])); ?></div><div class="small text-muted">Ticket prom. <?php echo esc($money((float) $stats['avg_ticket'])); ?></div></div></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3">Línea de tiempo</h2>
                    <div class="chart-box tall"><canvas id="chartTimeline"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel h-100">
                    <h2 class="h6 fw-bold mb-3">Pagadas vs no pagadas</h2>
                    <div class="chart-box"><canvas id="chartPay"></canvas></div>
                    <ul class="list-unstyled small mb-0 mt-3">
                        <li>Tarjeta / en línea: <strong><?php echo (int) $stats['card']; ?></strong></li>
                        <li>Solo reserva / sucursal: <strong><?php echo (int) $stats['counter']; ?></strong></li>
                        <li>Cliente retiró: <strong><?php echo (int) $stats['picked_up']; ?></strong></li>
                        <li>Confirmadas: <strong><?php echo (int) $stats['confirmed']; ?></strong> · Pendientes: <strong><?php echo (int) $stats['pending']; ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3">Por semana</h2>
                    <div class="chart-box"><canvas id="chartWeeks"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3">Monto por día</h2>
                    <div class="chart-box"><canvas id="chartRevenue"></canvas></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3">Sucursal de retiro</h2>
                    <div class="chart-box"><canvas id="chartLoc"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3">Clase / SIPP</h2>
                    <div class="chart-box"><canvas id="chartVeh"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel">
                    <h2 class="h6 fw-bold mb-3">Promo codes</h2>
                    <?php if ($stats['promos'] === []): ?>
                        <p class="text-muted small mb-0">Sin códigos en este periodo.</p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Código</th><th class="text-end">Reservas</th></tr></thead>
                            <tbody>
                            <?php foreach ($stats['promos'] as $promo): ?>
                                <tr>
                                    <td><?php echo esc($promo['label']); ?></td>
                                    <td class="text-end"><?php echo (int) $promo['value']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div></div>
<script>
const data = <?php echo json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const navy = '#081026';
const red = '#c51f17';
const green = '#198754';
const grid = '#e9edf5';
Chart.defaults.font.family = 'Segoe UI, system-ui, sans-serif';
Chart.defaults.color = '#5c6570';

new Chart(document.getElementById('chartTimeline'), {
    type: 'line',
    data: {
        labels: data.labels,
        datasets: [
            { label: 'Total', data: data.total, borderColor: navy, backgroundColor: 'rgba(8,16,38,.08)', fill: true, tension: .3, pointRadius: 0 },
            { label: 'Pagadas', data: data.paid, borderColor: green, tension: .3, pointRadius: 0 },
            { label: 'No pagadas', data: data.unpaid, borderColor: red, tension: .3, pointRadius: 0 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('chartPay'), {
    type: 'doughnut',
    data: {
        labels: ['Pagadas', 'No pagadas'],
        datasets: [{ data: [<?php echo (int) $stats['paid']; ?>, <?php echo (int) $stats['unpaid']; ?>], backgroundColor: [green, red], borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
});

new Chart(document.getElementById('chartWeeks'), {
    type: 'bar',
    data: {
        labels: data.weeks.map(w => w.label),
        datasets: [
            { label: 'Reservas', data: data.weeks.map(w => w.value), backgroundColor: navy },
            { label: 'Pagadas', data: data.weeks.map(w => w.paid || 0), backgroundColor: green }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
        labels: data.labels,
        datasets: [{ label: 'USD', data: data.revenue, backgroundColor: 'rgba(197,31,23,.75)' }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: grid } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('chartLoc'), {
    type: 'bar',
    data: {
        labels: data.locations.map(x => x.label),
        datasets: [{ data: data.locations.map(x => x.value), backgroundColor: '#162447' }]
    },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }, y: { grid: { display: false } } } }
});

new Chart(document.getElementById('chartVeh'), {
    type: 'bar',
    data: {
        labels: data.vehicles.map(x => x.label),
        datasets: [{ data: data.vehicles.map(x => x.value), backgroundColor: '#c51f17' }]
    },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }, y: { grid: { display: false } } } }
});
</script>
<?php require __DIR__ . '/../../includes/admin-standalone-sidebar.php'; ?>
</body>
</html>
