<?php
/**
 * Automarket - Paga tu Reserva (reconciliación previa, sin cobro)
 * AM-ADJ-14
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';

$prefillCode = strtoupper(trim((string) ($_GET['ref'] ?? $_GET['id'] ?? $_GET['code'] ?? '')));
if (strlen($prefillCode) > 64) {
    $prefillCode = '';
}
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Paga tu Reserva</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Paga tu Reserva</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Consulte y verifique el monto de su reserva. El cobro en línea todavía no está habilitado.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row g-5">
        <div class="col-lg-7 col-12">
            <div class="p-4 p-md-5 rounded-4 shadow-sm bg-white border">
                <h2 class="fw-bold font-montserrat text-navy mb-3" style="font-size: 1.35rem;">
                    <i class="bi bi-receipt text-danger me-2"></i>Verificar monto de reserva
                </h2>
                <p class="text-muted small mb-4">Por seguridad necesitamos el número de confirmación y el apellido del conductor principal. No solicitamos datos de tarjeta.</p>

                <form id="reconcileForm" class="row g-3" novalidate>
                    <div class="col-md-6">
                        <label for="reserva_id" class="form-label fw-semibold text-navy">Número de confirmación <span class="text-danger">*</span></label>
                        <input type="text" id="reserva_id" name="reserva_id" maxlength="64" class="form-control form-control-premium py-3" placeholder="Ej: PCR-123456" required value="<?php echo esc($prefillCode); ?>" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold text-navy">Apellido del conductor <span class="text-danger">*</span></label>
                        <input type="text" id="last_name" name="last_name" maxlength="80" class="form-control form-control-premium py-3" placeholder="Como figura en la reserva" required autocomplete="off">
                    </div>
                    <div class="col-12">
                        <button type="submit" id="reconcileBtn" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white">
                            Verificar monto
                        </button>
                    </div>
                </form>

                <div id="reconcileLoader" class="text-center py-3 d-none" aria-live="polite">
                    <div class="spinner-border spinner-border-sm text-danger"></div>
                    <span class="text-muted small ms-2">Reconciliando tarifa…</span>
                </div>
                <div id="reconcileError" class="alert alert-danger rounded-3 mt-3 d-none" role="alert" aria-live="assertive"></div>
                <div id="reconcileStatus" class="alert alert-secondary rounded-3 mt-3 d-none" role="status" aria-live="polite"></div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="p-4 rounded-4 shadow-sm bg-white border sticky-lg-top" style="top: 100px;">
                <h3 class="fw-bold text-navy mb-3" style="font-size: 1.15rem;">Resumen</h3>
                <div id="summaryEmpty" class="text-muted small">Ingrese su confirmación para ver el monto reconciliado.</div>
                <div id="summaryBox" class="d-none">
                    <div class="small mb-2"><span class="text-muted">Confirmación</span><br><strong id="sumCode">—</strong></div>
                    <div class="small mb-2"><span class="text-muted">Estado</span><br><strong id="sumStatus">—</strong></div>
                    <div class="small mb-2"><span class="text-muted">Vehículo</span><br><strong id="sumVehicle">—</strong></div>
                    <div class="small mb-2"><span class="text-muted">Cliente</span><br><strong id="sumCustomer">—</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Monto registrado</span><span id="sumStored">—</span></div>
                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Monto vigente</span><span id="sumRecalc">—</span></div>
                    <div class="d-flex justify-content-between fw-bold fs-5 text-navy mt-2">
                        <span>Monto a pagar (referencia)</span>
                        <span id="sumDue" aria-live="polite">—</span>
                    </div>
                    <p class="text-muted small mt-3 mb-0" id="sumNote"></p>
                    <button type="button" id="payDisabledBtn" class="btn btn-secondary w-100 py-3 rounded-pill fw-bold mt-3" disabled aria-disabled="true">
                        Pago en línea no disponible
                    </button>
                    <a href="/mi-reserva.php" class="btn btn-outline-secondary w-100 rounded-pill mt-2">Ir a Mi Reserva</a>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="/assets/js/rac-flow.js?v=7"></script>
<script>
(function () {
    'use strict';
    const form = document.getElementById('reconcileForm');
    const btn = document.getElementById('reconcileBtn');
    const loader = document.getElementById('reconcileLoader');
    const errBox = document.getElementById('reconcileError');
    const statusBox = document.getElementById('reconcileStatus');
    let inFlight = false;

    function money(v) {
        if (window.RAC_FLOW && window.RAC_FLOW.fmtMoney) return window.RAC_FLOW.fmtMoney(v);
        return '$' + (Number(v || 0).toFixed(2));
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (inFlight) return;
        const code = (document.getElementById('reserva_id').value || '').trim().toUpperCase();
        const lastName = (document.getElementById('last_name').value || '').trim();
        if (!code || !lastName) return;

        inFlight = true;
        btn.disabled = true;
        errBox.classList.add('d-none');
        statusBox.classList.add('d-none');
        loader.classList.remove('d-none');

        fetch('/api/rac-reservation-reconcile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code, lastName: lastName })
        })
            .then(function (r) { return r.json().then(function (j) { return { http: r.status, body: j }; }); })
            .then(function (res) {
                loader.classList.add('d-none');
                inFlight = false;
                btn.disabled = false;
                const data = res.body || {};
                if (!data.success) {
                    errBox.textContent = data.message || 'No encontramos una reserva con esos datos.';
                    errBox.classList.remove('d-none');
                    document.getElementById('summaryBox').classList.add('d-none');
                    document.getElementById('summaryEmpty').classList.remove('d-none');
                    return;
                }
                const rsv = data.reservation || {};
                document.getElementById('summaryEmpty').classList.add('d-none');
                document.getElementById('summaryBox').classList.remove('d-none');
                document.getElementById('sumCode').textContent = rsv.confirmation_number || code;
                document.getElementById('sumStatus').textContent = rsv.status || '—';
                document.getElementById('sumVehicle').textContent = rsv.vehicle_name || '—';
                document.getElementById('sumCustomer').textContent = rsv.customer_name_masked || '—';
                document.getElementById('sumStored').textContent = money(data.amount_stored);
                document.getElementById('sumRecalc').textContent = data.amount_recalculated != null ? money(data.amount_recalculated) : '—';
                document.getElementById('sumDue').textContent = money(data.amount_due);
                document.getElementById('sumNote').textContent = data.message || '';
                statusBox.textContent = data.payment_available
                    ? 'Pago disponible.'
                    : 'Pago en línea no disponible. Este resumen es solo de verificación.';
                statusBox.classList.remove('d-none');
            })
            .catch(function () {
                loader.classList.add('d-none');
                inFlight = false;
                btn.disabled = false;
                errBox.textContent = 'Error de conexión. Intente nuevamente.';
                errBox.classList.remove('d-none');
            });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
