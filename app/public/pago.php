<?php
/**
 * RAC paso 5 — pago con tarjeta (PowerTranz HPP). Sin pago no hay confirmación.
 */
$activeUnit = 'rentacar';
$racStep = 5;
$omitPublicCaptcha = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/rentacar-reservation-ui.php';
require_once __DIR__ . '/../includes/rac-stepper.php';

$token = preg_replace('/[^a-z0-9_]/i', '', (string) ($_GET['token'] ?? ''));
?>

<section class="container mb-5" id="payNoToken" <?php echo $token !== '' ? 'hidden' : ''; ?>>
    <div class="card border-0 shadow-sm p-5 text-center rounded-4">
        <h4 class="fw-bold text-navy"><?php echo esc(rac_ui('payment_empty_title')); ?></h4>
        <p class="text-muted"><?php echo esc(rac_ui('payment_empty_text')); ?></p>
        <a href="/reservar.php" class="btn btn-theme rounded-pill px-4 text-white"><?php echo esc(rac_ui('payment_empty_cta')); ?></a>
    </div>
</section>

<section class="container mb-5 <?php echo $token === '' ? 'd-none' : ''; ?>" id="payMain">
    <div class="mb-3">
        <a href="/reservar.php" class="text-muted text-decoration-none small fw-semibold">
            <i class="bi bi-arrow-left"></i> <?php echo esc(rac_ui('payment_back')); ?>
        </a>
    </div>
    <div class="row g-5 align-items-start">
        <div class="col-lg-5 col-12">
            <div class="p-4 p-md-5 rounded-4 shadow-sm bg-white border sticky-lg-top" style="top: 100px;">
                <h2 class="fw-bold font-montserrat text-navy mb-3" style="font-size: 1.35rem;">
                    <i class="bi bi-receipt text-danger me-2"></i><?php echo esc(rac_ui('payment_total')); ?>
                </h2>
                <p class="display-6 fw-bold text-danger mb-3" id="payAmount" aria-live="polite">—</p>
                <hr class="my-3">
                <div class="text-muted small mb-0 rac-pay-restrictions"><?php echo nl2br(esc(rac_ui('payment_cancel_note')), false); ?></div>
            </div>
        </div>
        <div class="col-lg-7 col-12">
            <div class="p-4 p-md-5 rounded-4 shadow-sm bg-white border">
                <h3 class="fw-bold font-montserrat text-navy mb-2" style="font-size: 1.25rem;">
                    <?php echo esc(rac_ui('payment_heading')); ?>
                </h3>
                <p class="text-muted small mb-4"><?php echo esc(rac_ui('payment_intro')); ?></p>
                <div id="payLoader" class="text-center py-5">
                    <div class="spinner-border text-danger"></div>
                    <p class="mt-3 mb-0 text-muted"><?php echo esc(rac_ui('payment_preparing')); ?></p>
                </div>
                <div id="payError" class="alert alert-danger rounded-3 d-none" role="alert"></div>
                <iframe id="payFrame" title="Pago con tarjeta" class="w-100 border rounded-3 d-none" style="min-height:520px;background:#fff"></iframe>
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
        frame.classList.add('d-none');
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
            if ((data.already_paid || data.already_paid) && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.already_paid || data.already_paid) {
                pollConfirmation();
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
        const detail = String(msg.message || '');
        if (detail.indexOf('757') !== -1) {
            showError('PowerTranz no encontró la Hosted Page. Page Set/Page Name deben ser Payment / Payment en el mismo merchant.');
            return;
        }
        showError(detail !== '' ? detail : 'El pago no fue aprobado. La reserva no se confirmó.');
    });

    function pollConfirmation() {
        loader.classList.remove('d-none');
        frame.classList.add('d-none');
        loader.querySelector('p').textContent = 'Pago aprobado. Confirmando reserva…';
        let tries = 0;
        const timer = setInterval(function () {
            tries += 1;
            fetch('/api/rac-checkout.php?action=status&token=' + encodeURIComponent(token))
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
