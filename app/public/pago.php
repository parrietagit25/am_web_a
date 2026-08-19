<?php
/**
 * RAC paso 5 — pago con tarjeta (PowerTranz HPP). Sin pago no hay confirmación.
 */
$activeUnit = 'rentacar';
$racStep = 5;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/rac-stepper.php';

$token = preg_replace('/[^a-z0-9_]/i', '', (string) ($_GET['token'] ?? ''));
?>

<section class="container mb-5" id="payNoToken" <?php echo $token !== '' ? 'hidden' : ''; ?>>
    <div class="card border-0 shadow-sm p-5 text-center rounded-4">
        <h4 class="fw-bold text-navy">No hay un pago pendiente</h4>
        <p class="text-muted">Complete los datos del conductor para continuar al cobro.</p>
        <a href="/reservar.php" class="btn btn-theme rounded-pill px-4 text-white">Ir a datos de reserva</a>
    </div>
</section>

<section class="container mb-5 <?php echo $token === '' ? 'd-none' : ''; ?>" id="payMain">
    <div class="mb-3">
        <a href="/reservar.php" class="text-muted text-decoration-none small fw-semibold">
            <i class="bi bi-arrow-left"></i> Volver a datos del conductor
        </a>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h3 class="fw-bold text-navy mb-2">Pago seguro</h3>
                <p class="text-muted small mb-3">El cargo se procesa en PowerTranz (3-D Secure). Automarket no almacena el número de tarjeta.</p>
                <div id="payLoader" class="text-center py-5">
                    <div class="spinner-border text-danger"></div>
                    <p class="mt-3 mb-0 text-muted">Preparando formulario de pago…</p>
                </div>
                <div id="payError" class="alert alert-danger d-none" role="alert"></div>
                <iframe id="payFrame" title="Pago con tarjeta" class="w-100 border rounded-3 d-none" style="min-height:520px;background:#fff"></iframe>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-navy mb-3">Total a pagar</h5>
                <p class="fs-3 fw-bold text-danger mb-1" id="payAmount">—</p>
                <p class="small text-muted mb-0">Si cancela o el banco rechaza el cobro, la reserva no se confirma en RentWorks.</p>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    const token = new URLSearchParams(location.search).get('token') || sessionStorage.getItem('racCheckoutToken') || '';
    if (!token) return;
    sessionStorage.setItem('racCheckoutToken', token);

    const loader = document.getElementById('payLoader');
    const errBox = document.getElementById('payError');
    const frame = document.getElementById('payFrame');
    const amountEl = document.getElementById('payAmount');

    function showError(msg) {
        loader.classList.add('d-none');
        errBox.textContent = msg || 'No se pudo iniciar el pago.';
        errBox.classList.remove('d-none');
    }

    fetch('/api/rac-checkout-pay.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: token })
    })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ data }) => {
            if (data.already_paid && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (!data.success) {
                showError(data.message);
                return;
            }
            if (data.amount != null) {
                amountEl.textContent = '$' + Number(data.amount).toFixed(2);
            }
            if (data.frame_url) {
                loader.classList.add('d-none');
                frame.classList.remove('d-none');
                frame.src = data.frame_url;
                return;
            }
            if (data.redirect_html) {
                loader.classList.add('d-none');
                frame.classList.remove('d-none');
                frame.srcdoc = data.redirect_html;
                return;
            }
            showError('PowerTranz no devolvió el formulario de pago.');
        })
        .catch(() => showError('Error de conexión al iniciar el pago.'));

    window.addEventListener('message', function (ev) {
        const msg = ev.data || {};
        if (msg.type !== 'powertranz-result') return;
        if (msg.approved) {
            pollConfirmation();
            return;
        }
        const st = String(msg.status || '');
        const detail = String(msg.message || '');
        if (st === 'hpp_error' || detail.indexOf('757') !== -1) {
            showError('PowerTranz no encontró la Hosted Page. Debe coincidir Page Set/Page Name publicados (Payment / Payment) y el mismo merchant ID.');
            return;
        }
        showError('El pago no fue aprobado. La reserva no se confirmó.');
    });

    function pollConfirmation() {
        loader.classList.remove('d-none');
        frame.classList.add('d-none');
        loader.querySelector('p').textContent = 'Pago aprobado. Confirmando reserva…';
        let tries = 0;
        const timer = setInterval(function () {
            tries += 1;
            fetch('/api/rac-checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'status', token: token })
            })
                .then(r => r.json())
                .then(function (d) {
                    if (d.redirect) {
                        clearInterval(timer);
                        sessionStorage.removeItem('selectedVehicle');
                        sessionStorage.removeItem('selectedRateType');
                        sessionStorage.removeItem('extrasSelection');
                        window.location.href = d.redirect;
                    }
                })
                .catch(function () {});
            if (tries > 20) {
                clearInterval(timer);
                showError('El pago se registró, pero la confirmación tarda. Conserve este enlace y contacte a la sucursal.');
            }
        }, 1500);
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
