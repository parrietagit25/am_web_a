<?php
/**
 * Automarket - RAC Confirmation (step 6, solo después de pago aprobado)
 */
$activeUnit = 'rentacar';
$racStep = 6;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/rac-stepper.php';

$code = strtoupper(trim($_GET['code'] ?? ''));
?>

<section class="container my-5" id="confirmPage">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow rounded-4 p-4 p-md-5 bg-white text-center" id="confirmCard">
                <div class="text-success mb-3">
                    <div class="rounded-circle bg-success-subtle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                        <i class="bi bi-check-lg fs-1 text-success"></i>
                    </div>
                </div>
                <h1 class="fw-bold text-navy font-montserrat mb-2">¡Reserva Confirmada!</h1>
                <p class="text-muted mb-4" id="confirmEmailNote">Te enviamos los detalles a tu correo electrónico.</p>

                <div class="bg-light rounded-4 p-4 text-start mb-4" id="confirmDetails">
                    <p class="text-muted small text-uppercase mb-1">Número de confirmación</p>
                    <p class="fw-bold text-danger fs-2 mb-3 font-montserrat" id="confirmCode"><?php echo esc($code); ?></p>
                    <div id="confirmMeta" class="small text-navy"></div>
                </div>

                <div class="alert alert-light border small text-start mb-4">
                    <i class="bi bi-info-circle text-success me-1"></i>
                    Al recoger el vehículo, presenta tu <strong>número de confirmación</strong>, una <strong>licencia válida</strong> y la <strong>tarjeta de crédito</strong> a nombre del conductor principal.
                </div>

                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="/rent-a-car.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold">Nueva reserva</a>
                    <button type="button" class="btn btn-theme rounded-pill px-4 py-2 fw-semibold text-white" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Imprimir comprobante
                    </button>
                    <a href="/mi-reserva.php" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-semibold">Mi reserva</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style media="print">
    header, footer, .stepper-container, .btn, nav { display: none !important; }
    #confirmCard { box-shadow: none !important; border: 1px solid #ddd !important; }
</style>

<script src="/assets/js/rac-flow.js?v=3"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    let code = params.get('code') || '';
    let last = null;
    try { last = JSON.parse(sessionStorage.getItem('lastConfirmation') || 'null'); } catch {}
    if (!code && last) code = last.confirmation_code || last.reservation_code || '';
    if (code) document.getElementById('confirmCode').textContent = code.toUpperCase();

    if (last && last.customer_email_sent) {
        document.getElementById('confirmEmailNote').innerHTML =
            'Te enviamos los detalles a <strong>tu correo</strong>. Si no lo ves, revisa spam.';
    }
    if (last && last.partial) {
        document.getElementById('confirmEmailNote').innerHTML +=
            '<br><span class="text-warning">Su solicitud fue registrada; un asesor confirmará los detalles finales.</span>';
    }

    const lastName = last?.last_name || '';

    if (code && lastName) {
        const qs = new URLSearchParams({ code: code, lastName: lastName });
        fetch('/api/rac-reservation-lookup.php?' + qs.toString())
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.reservation) return;
                const r = data.reservation;
                const meta = document.getElementById('confirmMeta');
                meta.innerHTML = `
                    <p class="mb-1"><strong>Vehículo:</strong> ${r.vehicleName || r.vehicle?.name || '—'}</p>
                    <p class="mb-1"><strong>Recogida:</strong> ${r.pickupLocation || r.pickupBranch || '—'} — ${formatDt(r.pickupDateTime)}</p>
                    <p class="mb-0"><strong>Devolución:</strong> ${r.returnLocation || r.returnBranch || '—'} — ${formatDt(r.returnDateTime)}</p>`;
            })
            .catch(() => {});
    }

    function formatDt(v) {
        if (!v) return '—';
        if (v.includes('T')) {
            const [d, t] = v.split('T');
            return window.RAC_FLOW.formatDateDisplay(d) + ' ' + window.RAC_FLOW.formatTimeDisplay(t);
        }
        return v;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
