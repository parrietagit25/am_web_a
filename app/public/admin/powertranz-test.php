<?php
/**
 * Admin — Prueba aislada Powertranz HPP/3DS (AM-RAC-PAY-POWERTRANZ-0A).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/PowertranzClient.php';
require_once __DIR__ . '/../../services/PowertranzPaymentService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!AdminUserService::isSuperAdmin()) {
    http_response_code(403);
    echo 'Acceso denegado. Solo super admin.';
    exit;
}

$client = new PowertranzClient();
$configured = PowertranzClient::isConfigured();
$enabled = PowertranzClient::isEnabled();
$merchantUrl = PowertranzPaymentService::merchantResponseUrl();
$service = new PowertranzPaymentService($client);
$lastPayment = $service->getLastTestPayment();

$lastPaymentExpired = false;
$lastPaymentHppReady = false;
$lastPaymentFrameUrl = '';
if (is_array($lastPayment)) {
    $lastStatus = (string) ($lastPayment['status'] ?? '');
    $lastIso = strtoupper((string) ($lastPayment['iso_response_code'] ?? ''));
    $lastPaymentHppReady = $lastStatus === 'redirect_ready'
        && !empty($lastPayment['has_redirect_data'])
        && in_array($lastIso, ['SP4', 'SP1', '3D0', '00'], true);
    if ($lastPaymentHppReady && !empty($lastPayment['payment_id'])) {
        $lastPaymentFrameUrl = '/admin/powertranz-payment-frame.php?payment_id=' . (int) $lastPayment['payment_id'];
        $updatedAt = strtotime((string) ($lastPayment['updated_at'] ?? $lastPayment['created_at'] ?? ''));
        if ($updatedAt !== false && (time() - $updatedAt) > 300) {
            $lastPaymentExpired = true;
        }
    }
}

$defaultAdminTab = 'powertranz-test';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Powertranz Test | Admin</title>
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
        #ptz-hpp-section { scroll-margin-top: 24px; }
        #ptz-frame { width: 100%; min-height: 720px; border: 1px solid var(--border-color); border-radius: 12px; background: #fff; }
        .status-pill { font-size: .85rem; }
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
        <h4 class="fw-bold mb-0">Powertranz Test (HPP / 3DS)</h4>
        <p class="small text-muted mb-0">Módulo aislado — no conectado a reservas RAC reales.</p>
    </div>
    <div class="p-4">
        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Configuración</h2>
            <div class="row g-2 small">
                <div class="col-md-4"><strong>ENABLED:</strong> <?php echo $enabled ? '<span class="text-success">sí</span>' : '<span class="text-danger">no</span>'; ?></div>
                <div class="col-md-4"><strong>POWERTRANZ_ENV:</strong> <?php echo defined('POWERTRANZ_ENV') ? esc((string) POWERTRANZ_ENV) : '<span class="text-danger">no definido</span>'; ?></div>
                <div class="col-md-4"><strong>BASE_URL:</strong> <?php echo esc($client->getBaseUrl() !== '' ? $client->getBaseUrl() : '—'); ?></div>
                <div class="col-md-4"><strong>ID:</strong> <?php echo defined('POWERTRANZ_ID') && trim((string) POWERTRANZ_ID) !== '' ? 'definido' : '<span class="text-danger">falta</span>'; ?></div>
                <div class="col-md-4"><strong>PASSWORD:</strong> <?php echo defined('POWERTRANZ_PASSWORD') && trim((string) POWERTRANZ_PASSWORD) !== '' ? 'definido' : '<span class="text-danger">falta</span>'; ?></div>
                <div class="col-md-4"><strong>CURRENCY:</strong> <?php echo esc(PowertranzClient::currencyCode()); ?></div>
                <div class="col-md-4"><strong>HPP PageSet:</strong> <?php echo defined('POWERTRANZ_HPP_PAGE_SET') && trim((string) POWERTRANZ_HPP_PAGE_SET) !== '' ? 'definido' : '<span class="text-warning">falta</span>'; ?></div>
                <div class="col-md-4"><strong>HPP PageName:</strong> <?php echo defined('POWERTRANZ_HPP_PAGE_NAME') && trim((string) POWERTRANZ_HPP_PAGE_NAME) !== '' ? 'definido' : '<span class="text-warning">falta</span>'; ?></div>
                <div class="col-md-12"><strong>MerchantResponseUrl:</strong> <code><?php echo esc($merchantUrl); ?></code></div>
            </div>
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-dark btn-sm" id="ptz-alive-btn"<?php echo $enabled ? '' : ' disabled'; ?>>
                    <i class="bi bi-heart-pulse me-1"></i> Probar alive
                </button>
            </div>
            <div id="ptz-alive-result" class="mt-3 p-3 bg-light rounded d-none"></div>
            <?php if (!$configured): ?>
                <div class="alert alert-warning mt-3 mb-0">Complete POWERTRANZ_ID y POWERTRANZ_PASSWORD en <code>app/config/config.php</code> (archivo privado, no commitear).</div>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h2 class="h5 fw-bold mb-3">Iniciar pago de prueba</h2>
            <form id="ptz-init-form" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Monto (USD)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="1.00" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modo</label>
                    <select name="mode" class="form-select">
                        <option value="sale" selected>sale</option>
                        <option value="auth">auth</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-premium" id="ptz-init-btn"<?php echo $configured ? '' : ' disabled'; ?>>
                        <i class="bi bi-credit-card me-1"></i> Iniciar pago
                    </button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-secondary" id="ptz-status-btn" disabled>Consultar estado</button>
                </div>
            </form>
            <div id="ptz-init-alert" class="alert d-none mt-3"></div>
        </div>

        <div class="admin-card" id="ptz-result-card">
            <h2 class="h5 fw-bold mb-3">Resultado</h2>
            <div class="row g-2 small mb-3">
                <div class="col-md-3"><strong>payment_id:</strong> <span id="ptz-payment-id"><?php echo $lastPaymentHppReady && !$lastPaymentExpired ? esc((string) ($lastPayment['payment_id'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-3"><strong>test_reference:</strong> <span id="ptz-reference"><?php echo $lastPayment ? esc((string) ($lastPayment['test_reference'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-3"><strong>status:</strong> <span id="ptz-status" class="badge status-pill bg-secondary"><?php echo $lastPayment ? esc((string) ($lastPayment['status'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-3"><strong>ISO:</strong> <span id="ptz-iso"><?php echo $lastPayment ? esc((string) ($lastPayment['iso_response_code'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-3"><strong>Auth code:</strong> <span id="ptz-auth"><?php echo $lastPayment ? esc((string) ($lastPayment['authorization_code'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-12"><strong>Mensaje:</strong> <span id="ptz-message"><?php echo $lastPayment ? esc((string) ($lastPayment['response_message'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-6"><strong>order_identifier:</strong> <span id="ptz-order"><?php echo $lastPayment ? esc((string) ($lastPayment['order_identifier'] ?? '—')) : '—'; ?></span></div>
                <div class="col-md-6"><strong>transaction_identifier:</strong> <span id="ptz-txn"><?php echo $lastPayment ? esc((string) ($lastPayment['transaction_identifier'] ?? '—')) : '—'; ?></span></div>
            </div>

            <div id="ptz-hpp-section" class="border-top pt-3 mt-2">
                <div id="ptz-expiry-notice" class="alert alert-info d-none mb-3">
                    <i class="bi bi-clock me-1"></i>
                    Complete este paso en menos de <strong>5 minutos</strong>. Si expira, inicie un pago nuevo.
                </div>
                <div id="ptz-expired-notice" class="alert alert-warning d-none mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Este intento probablemente expiró. Inicie un nuevo pago.
                </div>
                <div id="ptz-hpp-actions" class="d-none mb-3">
                    <a href="#" id="ptz-open-hpp-btn" class="btn btn-primary btn-lg" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Abrir HPP / Completar pago
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-lg ms-2" id="ptz-scroll-hpp-btn">
                        <i class="bi bi-arrows-collapse me-1"></i> Ver HPP abajo
                    </button>
                </div>
                <h3 class="h6 fw-bold mb-2">Página HPP / 3DS</h3>
                <iframe id="ptz-frame" title="Powertranz HPP" sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"<?php
                    if ($lastPaymentHppReady && !$lastPaymentExpired && $lastPaymentFrameUrl !== '') {
                        echo ' src="' . esc($lastPaymentFrameUrl) . '"';
                    }
                ?>></iframe>
            </div>
        </div>
    </div>
</div>
</div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const SPI_TTL_SECONDS = 300;
    const aliveBtn = document.getElementById('ptz-alive-btn');
    const aliveResult = document.getElementById('ptz-alive-result');
    const form = document.getElementById('ptz-init-form');
    const initBtn = document.getElementById('ptz-init-btn');
    const statusBtn = document.getElementById('ptz-status-btn');
    const alertBox = document.getElementById('ptz-init-alert');
    const frame = document.getElementById('ptz-frame');
    const hppSection = document.getElementById('ptz-hpp-section');
    const hppActions = document.getElementById('ptz-hpp-actions');
    const openHppBtn = document.getElementById('ptz-open-hpp-btn');
    const scrollHppBtn = document.getElementById('ptz-scroll-hpp-btn');
    const expiryNotice = document.getElementById('ptz-expiry-notice');
    const expiredNotice = document.getElementById('ptz-expired-notice');
    let currentPaymentId = <?php echo ($lastPaymentHppReady && !$lastPaymentExpired && !empty($lastPayment['payment_id'])) ? (int) $lastPayment['payment_id'] : 0; ?>;
    let redirectReadyAt = <?php echo ($lastPaymentHppReady && !$lastPaymentExpired) ? (int) (strtotime((string) ($lastPayment['updated_at'] ?? $lastPayment['created_at'] ?? '')) ?: 0) : 0; ?>;

    function setField(id, value) {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.tagName === 'SPAN' && el.classList.contains('badge')) {
            el.textContent = value || '—';
            return;
        }
        el.textContent = value || '—';
    }

    function isHppReady(data) {
        const status = (data.status || '').toLowerCase();
        const iso = (data.iso_response_code || '').toUpperCase();
        const hasRedirect = !!(data.has_redirect_data || data.frame_url);
        return status === 'redirect_ready' && hasRedirect && (iso === 'SP4' || iso === 'SP1' || iso === '3D0' || iso === '00');
    }

    function isExpired(ts) {
        if (!ts) return false;
        return (Math.floor(Date.now() / 1000) - ts) > SPI_TTL_SECONDS;
    }

    function frameUrlForPaymentId(id) {
        return id ? '/admin/powertranz-payment-frame.php?payment_id=' + encodeURIComponent(id) : '';
    }

    function updateStatusBadge(data) {
        const badge = document.getElementById('ptz-status');
        if (!badge) return;
        let cls = 'secondary';
        if (data.approved) {
            cls = 'success';
        } else if (data.status === 'declined') {
            cls = 'danger';
        } else if (isHppReady(data)) {
            cls = 'info';
        } else if (data.status === 'error') {
            cls = 'danger';
        }
        badge.className = 'badge status-pill bg-' + cls;
        badge.textContent = data.status || '—';
    }

    function showHppUi(data, expired) {
        const ready = isHppReady(data) && !expired;
        const url = data.frame_url || frameUrlForPaymentId(data.payment_id || currentPaymentId);
        hppActions.classList.toggle('d-none', !ready);
        expiryNotice.classList.toggle('d-none', !ready);
        expiredNotice.classList.toggle('d-none', !expired);
        if (ready && url) {
            openHppBtn.href = url;
            frame.src = url;
        }
    }

    function showAlertForPayment(data, expired) {
        if (isHppReady(data) && !expired) {
            showAlert('success', 'Pago iniciado correctamente. Continúe en el HPP/3DS para completar la prueba.');
            return;
        }
        if (isHppReady(data) && expired) {
            showAlert('warning', 'Este intento probablemente expiró. Inicie un nuevo pago.');
            return;
        }
        if (data.approved) {
            showAlert('success', data.response_message || 'Pago aprobado.');
            return;
        }
        if (data.status === 'declined') {
            showAlert('warning', data.response_message || 'Pago rechazado.');
            return;
        }
        if (data.status === 'error' || data.ok === false) {
            showAlert('danger', data.response_message || data.message || data.error || 'No se pudo iniciar el pago.');
            return;
        }
        showAlert('info', data.response_message || data.message || 'Estado actualizado.');
    }

    function showAlert(type, message) {
        alertBox.className = 'alert alert-' + type + ' mt-3';
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function applyPayment(data) {
        if (!data) return;
        currentPaymentId = data.payment_id || currentPaymentId;
        setField('ptz-payment-id', currentPaymentId || '—');
        setField('ptz-reference', data.test_reference || data.payment_reference || '—');
        setField('ptz-iso', data.iso_response_code || '—');
        setField('ptz-auth', data.authorization_code || '—');
        setField('ptz-message', data.response_message || data.message || '—');
        setField('ptz-order', data.order_identifier || '—');
        setField('ptz-txn', data.transaction_identifier || '—');
        updateStatusBadge(data);
        statusBtn.disabled = !currentPaymentId;

        if (isHppReady(data)) {
            if (!redirectReadyAt || data._freshInit) {
                redirectReadyAt = Math.floor(Date.now() / 1000);
            }
        }

        const expired = isHppReady(data) && isExpired(redirectReadyAt);
        showHppUi(data, expired);
        showAlertForPayment(data, expired);

        if (isHppReady(data) && !expired) {
            hppSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    async function fetchStatus() {
        if (!currentPaymentId) return;
        const res = await fetch('/api/powertranz-payment-status.php?payment_id=' + encodeURIComponent(currentPaymentId), {
            credentials: 'same-origin'
        });
        const data = await res.json();
        applyPayment(data);
    }

    scrollHppBtn.addEventListener('click', function () {
        hppSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    aliveBtn.addEventListener('click', async function () {
        aliveBtn.disabled = true;
        aliveResult.classList.remove('d-none');
        aliveResult.textContent = 'Consultando alive...';
        try {
            const res = await fetch('/api/powertranz-test-alive.php', { credentials: 'same-origin' });
            const data = await res.json();
            aliveResult.innerHTML = '<strong>Alive:</strong> HTTP ' + (data.http_code || 0) + ' — ' + (data.ok ? '<span class="text-success">OK</span>' : '<span class="text-danger">Error</span>') + (data.error ? '<div class="text-danger small">' + data.error + '</div>' : '');
        } catch (e) {
            aliveResult.innerHTML = '<span class="text-danger">Error de red al consultar alive.</span>';
        } finally {
            aliveBtn.disabled = false;
        }
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        initBtn.disabled = true;
        alertBox.classList.add('d-none');
        expiredNotice.classList.add('d-none');
        const fd = new FormData(form);
        const payload = {
            amount: parseFloat(fd.get('amount') || '1'),
            mode: fd.get('mode') || 'sale'
        };
        try {
            const res = await fetch('/api/powertranz-test-init.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            data._freshInit = true;
            if (!data.frame_url && data.has_redirect_data && data.payment_id) {
                data.frame_url = frameUrlForPaymentId(data.payment_id);
            }
            applyPayment(data);
            if (data.hpp_warning) {
                const warn = document.createElement('div');
                warn.className = 'small text-muted mt-2';
                warn.textContent = data.hpp_warning;
                alertBox.appendChild(warn);
            }
        } catch (err) {
            showAlert('danger', 'Error de red al iniciar pago.');
            frame.removeAttribute('src');
            hppActions.classList.add('d-none');
        } finally {
            initBtn.disabled = false;
        }
    });

    statusBtn.addEventListener('click', fetchStatus);

    window.addEventListener('message', function (ev) {
        if (!ev.data || ev.data.type !== 'powertranz-result') return;
        if (ev.data.payment_id) currentPaymentId = ev.data.payment_id;
        fetchStatus();
    });

    if (currentPaymentId) {
        statusBtn.disabled = false;
        <?php if ($lastPaymentHppReady && !$lastPaymentExpired): ?>
        hppActions.classList.remove('d-none');
        expiryNotice.classList.remove('d-none');
        openHppBtn.href = <?php echo json_encode($lastPaymentFrameUrl, JSON_UNESCAPED_UNICODE); ?>;
        showAlert('success', 'Pago iniciado correctamente. Continúe en el HPP/3DS para completar la prueba.');
        <?php elseif ($lastPaymentExpired): ?>
        expiredNotice.classList.remove('d-none');
        showAlert('warning', 'Este intento probablemente expiró. Inicie un nuevo pago.');
        <?php endif; ?>
    }
})();
</script>
</body>
</html>
