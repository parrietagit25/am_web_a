<?php
/**
 * Automarket - Rent A Car Search Results
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Stepper Navigation Progress Bar -->
<section class="container mt-4 pt-2">
    <div class="row">
        <div class="col-12">
            <div class="stepper-container">
                <div class="stepper-line"></div>
                <div class="stepper-line-active" style="width: 35%;"></div>
                
                <div class="step-item completed">
                    <div class="step-badge" style="cursor: pointer;" onclick="window.location.href='/rent-a-car.php'"><i class="bi bi-check-lg"></i></div>
                    <span class="step-title">1. Fecha y Lugar</span>
                </div>
                <div class="step-item active">
                    <div class="step-badge">2</div>
                    <span class="step-title">2. Vehículo</span>
                </div>
                <div class="step-item">
                    <div class="step-badge">3</div>
                    <span class="step-title">3. Adicionales</span>
                </div>
                <div class="step-item">
                    <div class="step-badge">4</div>
                    <span class="step-title">4. Confirmación</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Summary Header -->
<section class="container mt-4 mb-4">
    <div class="bg-navy text-white rounded-4 p-4 d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative overflow-hidden">
        <div style="z-index: 2;">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-danger">Disponibilidad en Vivo</span>
                <!-- Debug Badges (Revealed dynamically via JS if debug=1 is requested) -->
                <span id="debugBadges" class="d-none badge bg-warning text-dark">
                    Source: <span id="debugSource">-</span> | Cache: <span id="debugCache">-</span>
                </span>
            </div>
            <h2 class="fw-bold mb-1 font-montserrat fs-3">Elige tu Vehículo</h2>
            <p id="searchSummaryText" class="opacity-80 font-poppins text-sm mb-0">Cargando criterios de búsqueda...</p>
        </div>
        <a href="/rent-a-car.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold" style="z-index: 2;">
            <i class="bi bi-pencil-square me-2"></i>Modificar Búsqueda
        </a>
        <div class="position-absolute end-0 bottom-0 opacity-10" style="font-size: 8rem; transform: translate(20px, 30px); pointer-events: none; z-index: 1;">
            <i class="bi bi-car-front-fill"></i>
        </div>
    </div>
</section>

<!-- Category Filtering Tabs -->
<section class="container mb-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex overflow-x-auto text-nowrap gap-2 pb-2 scrollbar-hidden">
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn active" data-category="all">Todos</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="suv">SUV</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="compacto">Compacto</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="econ">Económico</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="van">Van / Comercial</button>
            </div>
        </div>
    </div>
</section>

<div id="racResultsStatus" class="container alert d-none rounded-4 mb-3" role="status"></div>

<!-- Vehicles Results Grid -->
<section class="container mb-5">
    <!-- Loader placeholder while page loads -->
    <div id="resultsLoader" class="text-center py-5">
        <div class="spinner-border text-danger" role="status">
            <span class="visually-hidden">Procesando...</span>
        </div>
        <p class="mt-2 text-muted">Cargando disponibilidad...</p>
    </div>

    <!-- Empty search warning -->
    <div id="noSearchWarning" class="card border-0 shadow-sm p-5 text-center rounded-4 d-none">
        <i class="bi bi-search text-muted opacity-50" style="font-size: 4rem;"></i>
        <h4 class="fw-bold text-navy mt-3">No has realizado ninguna búsqueda</h4>
        <p class="text-muted mb-4 font-poppins">Para ver vehículos disponibles, primero dinos cuándo y dónde necesitas retirar tu auto.</p>
        <a href="/rent-a-car.php" class="btn btn-theme px-4 py-2 rounded-pill fw-bold text-white shadow-sm">
            Ir al Buscador
        </a>
    </div>

    <!-- Vehicles Container -->
    <div id="resultsVehiclesGrid" class="row g-4">
        <!-- Injected via JavaScript -->
    </div>
</section>

<script src="/assets/js/rac-results.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
