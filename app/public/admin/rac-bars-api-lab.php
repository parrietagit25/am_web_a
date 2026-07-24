<?php
/**
 * Admin — Laboratorio de APIs RAC (BARS SOAP + Partner Node).
 * Solo super admin. No forma parte del flujo público de reservas.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/BarsApiLabService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!AdminUserService::isSuperAdmin() && !admin_can('rac_bars_lab')) {
    http_response_code(403);
    echo 'Acceso denegado. Requiere super admin o permiso Lab BARS / Partner.';
    exit;
}

$status = BarsApiLabService::status();
$catalog = BarsApiLabService::catalog();
$defaultAdminTab = 'rac-bars-api-lab';
$csrf = admin_csrf_token();

$riskBadge = static function (string $risk): string {
    return match ($risk) {
        'safe' => '<span class="badge bg-success-subtle text-success border">SAFE</span>',
        'read' => '<span class="badge bg-info-subtle text-info border">READ</span>',
        'mutate' => '<span class="badge bg-danger-subtle text-danger border">MUTATE</span>',
        default => '<span class="badge bg-secondary">?</span>',
    };
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BARS / Partner API Lab | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --navy: #081026; --gray-bg: #f8f9fc; --border-color: #e3e6f0; --primary-red: #c51f17; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--gray-bg); color: var(--navy); }
        .admin-sidebar { background: var(--navy); color: #fff; min-height: 100vh; }
        .admin-sidebar .nav-link, .admin-sidebar a.admin-sidebar-page-link { color: rgba(255,255,255,.7); text-decoration: none; margin: 4px 10px; padding: 12px 16px; border-radius: 8px; display: block; }
        #rentacar-submenu .nav-link, #rentacar-submenu a.admin-sidebar-page-link { padding-left: 28px; font-size: .85rem; }
        .admin-header { background: #fff; border-bottom: 1px solid var(--border-color); padding: 15px 30px; }
        .admin-card { background: #fff; border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
        .btn-premium { background: var(--primary-red); border-color: var(--primary-red); color: #fff; }
        .api-row code { font-size: .78rem; word-break: break-all; }
        #lab-result { max-height: 520px; overflow: auto; white-space: pre-wrap; font-size: .82rem; }
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
    <div class="admin-header">
        <h4 class="fw-bold mb-0">BARS / Partner API Lab</h4>
        <p class="small text-muted mb-0">Mapa y pruebas por API. Solo diagnóstico — no es el flujo público de reservas.</p>
    </div>
    <div class="p-4">

        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Estado de configuración</h2>
            <div class="row g-2 small">
                <div class="col-md-6">
                    <strong>BARS SOAP:</strong>
                    <?php echo !empty($status['bars_configured']) ? '<span class="text-success">configurado</span>' : '<span class="text-danger">faltan credenciales</span>'; ?>
                    <div class="text-muted"><code><?php echo esc($status['bars_endpoint']); ?></code></div>
                </div>
                <div class="col-md-6">
                    <strong>Partner Node:</strong>
                    <?php echo !empty($status['partner_configured']) ? '<span class="text-success">configurado</span>' : '<span class="text-danger">faltan credenciales</span>'; ?>
                    <div class="text-muted"><code><?php echo esc($status['partner_base']); ?></code></div>
                </div>
            </div>
            <div class="alert alert-warning mt-3 mb-0 small">
                <strong>Mutate:</strong> cancel / modify / res / profilecreate crean o cambian datos reales en BARS.
                Por defecto el lab hace <em>dry-run</em>. Para enviar en vivo escribe <code>EJECUTAR</code> en el campo confirm.
            </div>
        </div>

        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Parámetros de prueba</h2>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small">Pickup loc</label>
                    <input type="text" id="p_pickup" class="form-control form-control-sm" value="PTY">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Return loc</label>
                    <input type="text" id="p_return" class="form-control form-control-sm" value="PTY">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Pickup datetime</label>
                    <input type="text" id="p_pickup_dt" class="form-control form-control-sm" value="<?php echo esc(date('Y-m-d\T10:00:00', strtotime('+7 days'))); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Return datetime</label>
                    <input type="text" id="p_return_dt" class="form-control form-control-sm" value="<?php echo esc(date('Y-m-d\T10:00:00', strtotime('+10 days'))); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Age</label>
                    <input type="text" id="p_age" class="form-control form-control-sm" value="25">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Reservation code (lookup)</label>
                    <input type="text" id="p_res_code" class="form-control form-control-sm" placeholder="ABC123">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Last name (lookup)</label>
                    <input type="text" id="p_last_name" class="form-control form-control-sm" placeholder="">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Confirm mutate</label>
                    <input type="text" id="p_confirm" class="form-control form-control-sm" placeholder="EJECUTAR">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="p_dry_run" checked>
                        <label class="form-check-label" for="p_dry_run">Forzar dry-run en SOAP</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small">OTA XML manual (para SOAP sin stub)</label>
                    <textarea id="p_ota_xml" class="form-control form-control-sm font-monospace" rows="4" placeholder="Pega aquí OTA_VehResRQ / Cancel / Modify…"></textarea>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Mapa de APIs</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle api-row">
                    <thead class="table-light">
                        <tr>
                            <th>API</th>
                            <th>Capa</th>
                            <th>Riesgo</th>
                            <th>Implementado en</th>
                            <th style="min-width:220px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($catalog as $item):
                        $id = (string) ($item['id'] ?? '');
                        $actions = is_array($item['actions'] ?? null) ? $item['actions'] : [];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc((string) ($item['label'] ?? $id)); ?></strong>
                                <div class="small text-muted"><?php echo esc((string) ($item['notes'] ?? '')); ?></div>
                                <code><?php echo esc((string) ($item['method'] ?? '')); ?> <?php echo esc((string) ($item['url'] ?? '')); ?></code>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo esc((string) ($item['layer'] ?? '')); ?></span></td>
                            <td><?php echo $riskBadge((string) ($item['risk'] ?? '')); ?></td>
                            <td class="small"><?php echo esc((string) ($item['implemented_in'] ?? '—')); ?>
                                <div class="text-muted">WSDL: <?php echo esc((string) ($item['bars_wsdl'] ?? '—')); ?></div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if (in_array('wsdl', $actions, true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary lab-btn" data-id="<?php echo esc($id); ?>" data-action="wsdl">WSDL</button>
                                    <?php endif; ?>
                                    <?php if (in_array('probe', $actions, true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-dark lab-btn" data-id="<?php echo esc($id); ?>" data-action="probe">Probe</button>
                                    <?php endif; ?>
                                    <?php if (in_array('run', $actions, true)): ?>
                                    <button type="button" class="btn btn-sm btn-premium lab-btn" data-id="<?php echo esc($id); ?>" data-action="run">Run</button>
                                    <?php endif; ?>
                                    <?php if (in_array('soap', $actions, true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger lab-btn" data-id="<?php echo esc($id); ?>" data-action="soap">SOAP</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 fw-bold mb-0">Resultado</h2>
                <span class="small text-muted" id="lab-meta">—</span>
            </div>
            <pre id="lab-result" class="bg-dark text-light rounded p-3 mb-0">Haz clic en una acción para probar.</pre>
        </div>

    </div>
</div>
</div></div>

<script>
(function () {
    const csrf = <?php echo json_encode($csrf, JSON_UNESCAPED_UNICODE); ?>;
    const resultEl = document.getElementById('lab-result');
    const metaEl = document.getElementById('lab-meta');

    function collectParams() {
        const pickupDt = document.getElementById('p_pickup_dt').value.trim();
        const returnDt = document.getElementById('p_return_dt').value.trim();
        const pickupDate = pickupDt.slice(0, 10);
        const returnDate = returnDt.slice(0, 10);
        const pickupTime = (pickupDt.split('T')[1] || '10:00').slice(0, 5);
        const returnTime = (returnDt.split('T')[1] || '10:00').slice(0, 5);
        return {
            pickup_location: document.getElementById('p_pickup').value.trim() || 'PTY',
            return_location: document.getElementById('p_return').value.trim() || 'PTY',
            pickup_datetime: pickupDt,
            return_datetime: returnDt,
            locationCode: document.getElementById('p_pickup').value.trim() || 'PTY',
            returnLocationCode: document.getElementById('p_return').value.trim() || 'PTY',
            pickupDate, pickupTime, returnDate, returnTime,
            age: document.getElementById('p_age').value.trim() || '25',
            reservation_code: document.getElementById('p_res_code').value.trim(),
            last_name: document.getElementById('p_last_name').value.trim(),
            confirm: document.getElementById('p_confirm').value.trim(),
            dry_run: document.getElementById('p_dry_run').checked ? 1 : 0,
            ota_xml: document.getElementById('p_ota_xml').value,
            debug: true
        };
    }

    async function runLab(apiId, action) {
        metaEl.textContent = apiId + ' · ' + action + ' · ejecutando…';
        resultEl.textContent = 'Cargando…';
        try {
            const res = await fetch('/api/bars-api-lab.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    admin_csrf_token: csrf,
                    api_id: apiId,
                    lab_action: action,
                    params: collectParams()
                })
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { data = { ok: false, error: 'Respuesta no JSON', raw: text.slice(0, 2000) }; }
            metaEl.textContent = apiId + ' · ' + action + ' · HTTP ' + res.status + (data.elapsed_ms ? (' · ' + data.elapsed_ms + ' ms') : '');
            resultEl.textContent = JSON.stringify(data, null, 2);
        } catch (err) {
            metaEl.textContent = 'Error de red';
            resultEl.textContent = String(err && err.message ? err.message : err);
        }
    }

    document.querySelectorAll('.lab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.getAttribute('data-id');
            const action = btn.getAttribute('data-action');
            if (action === 'soap' && !document.getElementById('p_dry_run').checked) {
                const confirmVal = document.getElementById('p_confirm').value.trim().toUpperCase();
                if (confirmVal !== 'EJECUTAR') {
                    if (!window.confirm('SOAP sin dry-run. Si la API es MUTATE y no escribiste EJECUTAR, el lab forzará dry-run. ¿Continuar?')) {
                        return;
                    }
                } else if (!window.confirm('Vas a ENVIAR SOAP en vivo a BARS (confirm=EJECUTAR). ¿Seguro?')) {
                    return;
                }
            }
            runLab(id, action);
        });
    });
})();
</script>
</body>
</html>
