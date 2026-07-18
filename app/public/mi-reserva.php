<?php
/**
 * Automarket - Mi Reserva (consulta pública)
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background-color:#f8f9fc;">
    <div class="container">
        <h1 class="display-6 fw-bold text-navy font-montserrat mb-2">Mi Reserva</h1>
        <p class="text-muted font-poppins">Consulte el estado de su reserva con su número de confirmación.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                <form id="lookupForm" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="lookupCode" class="form-label fw-semibold text-navy">Número de confirmación</label>
                        <input type="text" id="lookupCode" class="form-control form-control-premium py-3" placeholder="Ej: PCR-123456" required
                            value="<?php echo esc(strtoupper(trim($_GET['id'] ?? $_GET['code'] ?? ''))); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="lookupLastName" class="form-label fw-semibold text-navy">Apellido del conductor</label>
                        <input type="text" id="lookupLastName" class="form-control form-control-premium py-3" placeholder="Como figura en la reserva" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white">Buscar</button>
                    </div>
                </form>
                <p class="text-muted small mt-2 mb-0">Por seguridad, necesitamos el número de confirmación y el apellido del conductor principal.</p>
                <div id="lookupError" class="alert alert-danger rounded-3 mt-3 d-none" role="alert"></div>
            </div>

            <div id="lookupLoader" class="text-center py-4 d-none">
                <div class="spinner-border text-danger"></div>
                <p class="text-muted mt-2">Buscando reserva…</p>
            </div>

            <div id="lookupResult" class="card border-0 shadow rounded-4 p-4 p-md-5 bg-white d-none">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                    <div>
                        <p class="text-muted small text-uppercase mb-1">Confirmación</p>
                        <h2 class="fw-bold text-danger font-montserrat mb-0" id="resCode">—</h2>
                    </div>
                    <span class="badge bg-success fs-6" id="resStatus">—</span>
                </div>
                <div class="row g-3 small font-poppins" id="resDetails"></div>
                <hr class="my-4">
                <div class="d-flex justify-content-between fw-bold fs-5 text-navy">
                    <span>Total estimado</span>
                    <span id="resTotal">—</span>
                </div>
                <p id="resPayNote" class="text-muted small mt-2 mb-0" aria-live="polite"></p>
                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Imprimir
                    </button>
                    <a id="resPayLink" href="/pago-seguro.php" class="btn btn-outline-danger rounded-pill">Verificar monto / Paga tu reserva</a>
                    <a href="/rent-a-car.php" class="btn btn-theme rounded-pill text-white">Nueva reserva</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style media="print">
    header, footer, #lookupForm, .btn:not(.d-none) { display: none !important; }
</style>

<script src="/assets/js/rac-flow.js?v=3"></script>
<script>
function lookupReservation(code, lastName) {
    code = (code || '').trim().toUpperCase();
    lastName = (lastName || '').trim();
    if (!code || !lastName) return;

    document.getElementById('lookupError').classList.add('d-none');
    document.getElementById('lookupResult').classList.add('d-none');
    document.getElementById('lookupLoader').classList.remove('d-none');

    const qs = new URLSearchParams({ code: code, lastName: lastName });
    fetch('/api/rac-reservation-lookup.php?' + qs.toString())
        .then(r => r.json())
        .then(data => {
            document.getElementById('lookupLoader').classList.add('d-none');
            if (!data.success) {
                const err = document.getElementById('lookupError');
                err.textContent = data.message || 'No encontramos esta reserva.';
                err.classList.remove('d-none');
                return;
            }
            showReservation(data.reservation);
            history.replaceState(null, '', '?id=' + encodeURIComponent(code));
        })
        .catch(() => {
            document.getElementById('lookupLoader').classList.add('d-none');
            const err = document.getElementById('lookupError');
            err.textContent = 'Error de conexión. Intente nuevamente.';
            err.classList.remove('d-none');
        });
}

function showReservation(r) {
    const code = r.confirmationNumber || r.confirmation_code || '—';
    document.getElementById('resCode').textContent = code;
    const status = (r.status || 'Confirmada').toString();
    document.getElementById('resStatus').textContent = status;
    document.getElementById('resStatus').className = 'badge fs-6 ' + (
        status.toLowerCase().includes('cancel') ? 'bg-secondary' : 'bg-success'
    );

    const total = r.totalAmount != null ? r.totalAmount : (r.total || 0);
    document.getElementById('resTotal').textContent = window.RAC_FLOW.fmtMoney(total);
    const payNote = document.getElementById('resPayNote');
    if (payNote) {
        payNote.textContent = 'El pago en línea aún no está disponible. Puede verificar el monto en Paga tu reserva.';
    }
    const payLink = document.getElementById('resPayLink');
    if (payLink && code && code !== '—') {
        payLink.href = '/pago-seguro.php?ref=' + encodeURIComponent(code);
    }

    document.getElementById('resDetails').innerHTML = `
        <div class="col-md-6"><strong>Cliente</strong><br>${esc(r.customerName || r.customer_name || '—')}</div>
        <div class="col-md-6"><strong>Email</strong><br>${esc(r.customerEmail || r.customer_email || '—')}</div>
        <div class="col-md-6"><strong>Vehículo</strong><br>${esc(r.vehicleName || r.vehicle_name || '—')}</div>
        <div class="col-md-6"><strong>Protección</strong><br>${esc(r.coverageName || r.coverage_name || '—')}</div>
        <div class="col-md-6"><strong>Recogida</strong><br>${esc(r.pickupLocation || r.pickupBranch || '—')}<br>${formatDt(r.pickupDateTime)}</div>
        <div class="col-md-6"><strong>Devolución</strong><br>${esc(r.returnLocation || r.returnBranch || '—')}<br>${formatDt(r.returnDateTime)}</div>`;

    document.getElementById('lookupResult').classList.remove('d-none');
}

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function formatDt(v) {
    if (!v) return '—';
    if (String(v).includes('T')) {
        const [d, t] = String(v).split('T');
        return window.RAC_FLOW.formatDateDisplay(d) + ' ' + window.RAC_FLOW.formatTimeDisplay(t);
    }
    return v;
}

document.getElementById('lookupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    lookupReservation(
        document.getElementById('lookupCode').value,
        document.getElementById('lookupLastName').value
    );
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
