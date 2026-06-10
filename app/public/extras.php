<?php
/**
 * Automarket - RAC Extras (step 3)
 */
$activeUnit = 'rentacar';
$racStep = 3;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/rac-stepper.php';
?>

<section class="container mb-5" id="extrasNoVehicle">
    <div class="card border-0 shadow-sm p-5 text-center rounded-4">
        <p class="text-muted">Redirigiendo al buscador…</p>
    </div>
</section>

<section class="container mb-5 d-none" id="extrasMain">
    <div class="mb-3">
        <a href="/resultados.php" class="text-muted text-decoration-none small fw-semibold" id="extrasBackLink">
            <i class="bi bi-arrow-left"></i> Volver a Escoger Auto
        </a>
    </div>

    <div id="extrasRefreshLoader" class="text-center py-3 d-none">
        <div class="spinner-border spinner-border-sm text-danger"></div>
        <span class="text-muted small ms-2">Actualizando precios…</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" id="extrasVehicleHeader"></div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h3 class="fw-bold text-navy mb-3"><i class="bi bi-shield-check text-danger me-2"></i>Nivel de protección</h3>
                <div id="protectionOptions" class="d-flex flex-column gap-2"></div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h3 class="fw-bold text-navy mb-3"><i class="bi bi-plus-circle text-danger me-2"></i>¿Quieres agregar algún extra?</h3>
                <div id="equipmentOptions" class="d-flex flex-column gap-2"></div>
            </div>

            <section id="alternativesSection" class="mb-4">
                <h4 class="fw-bold text-navy mb-1"><i class="bi bi-star-fill text-warning me-2"></i>También te puede interesar</h4>
                <p class="text-muted small mb-3">Mismas fechas · cambio sin complicaciones</p>
                <div class="row g-3" id="alternativesRow"></div>
            </section>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white position-sticky" style="top:100px;">
                <div id="extrasBookingSummary" class="mb-4"></div>
                <h5 class="fw-bold text-navy mb-3 fs-6">Resumen de cargos</h5>
                <div class="d-flex flex-column gap-2 small font-poppins mb-3">
                    <div class="d-flex justify-content-between"><span class="text-muted">Tarifa base</span><span id="sumBase">$0.00</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">SAF</span><span id="sumSaf">$0.00</span></div>
                    <div id="sumMandatoryRows" class="d-flex flex-column gap-2"></div>
                    <div class="d-flex justify-content-between" id="sumCoverageRow"><span class="text-muted" id="sumCoverageLabel">Protección</span><span id="sumCoverage">$0.00</span></div>
                    <div class="d-flex justify-content-between d-none" id="sumDriverRow">
                        <span class="text-muted" id="sumDriverLabel">Conductor adicional</span><span id="sumDriver">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between d-none" id="sumExtrasRow">
                        <span class="text-muted">Otros extras</span><span id="sumExtras">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between"><span class="text-muted">ITBMS (7%)</span><span id="sumItbms">$0.00</span></div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-bold text-navy">Total</span>
                    <span class="fw-bold text-navy fs-4" id="sumTotal">$0.00</span>
                </div>
                <button type="button" id="btnContinueExtras" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white">
                    Continuar <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<script src="/assets/js/rac-flow.js?v=3"></script>
<script src="/assets/js/rac-extras.js?v=10"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const c = window.RAC_FLOW && window.RAC_FLOW.getCriteria();
    const link = document.getElementById('extrasBackLink');
    if (link && c) link.href = window.RAC_FLOW.buildResultsUrl(c);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
