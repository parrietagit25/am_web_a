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
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="suv">SUV / Camionetas</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="sedan">Sedanes</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="comercial">Comerciales</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="promo">Promociones</button>
            </div>
        </div>
    </div>
</section>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Read from sessionStorage
    const searchResultsRaw = sessionStorage.getItem('searchResults');
    const searchCriteriaRaw = sessionStorage.getItem('searchCriteria');
    
    const resultsLoader = document.getElementById('resultsLoader');
    const noSearchWarning = document.getElementById('noSearchWarning');
    const resultsVehiclesGrid = document.getElementById('resultsVehiclesGrid');
    
    if (!searchResultsRaw || !searchCriteriaRaw) {
        if (resultsLoader) resultsLoader.classList.add('d-none');
        if (noSearchWarning) noSearchWarning.classList.remove('d-none');
        return;
    }
    
    if (resultsLoader) resultsLoader.classList.add('d-none');
    
    const searchResults = JSON.parse(searchResultsRaw);
    const searchCriteria = JSON.parse(searchCriteriaRaw);
    const vehicles = searchResults.vehicles || [];
    
    // Fill summary text
    const searchSummaryText = document.getElementById('searchSummaryText');
    if (searchSummaryText) {
        const loc = searchCriteria.locationCode || 'PTY';
        const retLoc = searchCriteria.returnLocationCode || loc;
        const diffText = (loc !== retLoc) ? ` (Devolución en ${retLoc})` : '';
        searchSummaryText.innerHTML = `<i class="bi bi-geo-alt-fill text-accent-light me-1"></i> Retiro en <strong>${loc}</strong>${diffText} | <i class="bi bi-calendar-check-fill text-accent-light me-1"></i> del <strong>${searchCriteria.pickupDate} ${searchCriteria.pickupTime}</strong> al <strong>${searchCriteria.returnDate} ${searchCriteria.returnTime}</strong>`;
    }
    
    // Debug info toggle if url has debug=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('debug') === '1' || sessionStorage.getItem('debug') === '1') {
        sessionStorage.setItem('debug', '1'); // Keep debug active in session
        const debugBadges = document.getElementById('debugBadges');
        const debugSource = document.getElementById('debugSource');
        const debugCache = document.getElementById('debugCache');
        if (debugBadges) debugBadges.classList.remove('d-none');
        if (debugSource) debugSource.innerText = searchResults.source || 'N/A';
        if (debugCache) debugCache.innerText = searchResults.xCache || 'N/A';
    }
    
    // Render initial vehicle cards list
    renderVehiclesList(vehicles);
    
    // Bind filters action clicks
    setupResultsFilters(vehicles);
    
    /**
     * Map vehicles array to HTML cards
     */
    function renderVehiclesList(list) {
        resultsVehiclesGrid.innerHTML = '';
        
        if (list.length === 0) {
            resultsVehiclesGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
                    <h4 class="fw-bold mt-3 text-navy">No hay autos disponibles para estas fechas</h4>
                    <p class="text-muted">Intenta ajustando tus fechas o seleccionando otra sucursal de retiro.</p>
                </div>
            `;
            return;
        }
        
        const hostUrl = "https://automarket-rentacar-fme3z.ondigitalocean.app";
        
        list.forEach(vehicle => {
            const cardCol = document.createElement('div');
            cardCol.className = 'col-lg-4 col-md-6 col-12 d-flex';
            
            // Build image source
            let imgSrc = '';
            if (vehicle.image) {
                if (vehicle.image.startsWith('http')) {
                    imgSrc = vehicle.image;
                } else {
                    imgSrc = hostUrl + vehicle.image;
                }
            }
            
            // Build passengers & transmission info
            const paxCount = vehicle.passengers || 5;
            const trans = vehicle.transmission || 'Automática';
            const acText = vehicle.ac ? 'A/C' : 'No A/C';
            
            // Large & small bags counters
            const bagsLarge = vehicle.bagsLarge || 1;
            const bagsSmall = vehicle.bagsSmall || 1;
            
            // Daily pricing and estimated total values
            const priceBase = vehicle.pricing?.rateBasePerDay || vehicle.priceWeb || 0;
            const priceEstTotal = vehicle.priceTotalEstimated || vehicle.priceTotal || (priceBase * 3);
            
            // Check image availability or fallback
            const imageHtml = vehicle.image ? 
                `<img src="${imgSrc}" class="img-fluid vehicle-image-card" alt="${vehicle.name}" style="max-height: 140px; object-fit: contain;">` :
                `<div class="py-4"><i class="bi bi-car-front text-muted opacity-25" style="font-size: 5.5rem;"></i></div>`;
            
            cardCol.innerHTML = `
                <div class="card vehicle-card border-0 shadow-sm rounded-4 w-100 flex-column justify-content-between overflow-hidden position-relative">
                    
                    <!-- Category Badge -->
                    <span class="category-badge position-absolute bg-white px-3 py-1 text-navy rounded-pill fw-bold shadow-sm top-3 start-3 text-uppercase z-index-2">
                        ${vehicle.category || 'General'}
                    </span>
                    
                    <!-- Image Area -->
                    <div class="card-image-wrapper bg-light-gray p-4 text-center d-flex align-items-center justify-content-center" style="height: 180px;">
                        ${imageHtml}
                    </div>
                    
                    <!-- Card Body -->
                    <div class="card-body px-4 py-4 d-flex flex-column justify-content-between">
                        <div>
                            <h4 class="card-title fw-bold text-navy mb-3 fs-5">${vehicle.name}</h4>
                            
                            <!-- Specs list -->
                            <div class="specs-grid d-flex flex-wrap gap-2 mb-4 text-muted text-sm font-poppins">
                                <div class="badge bg-light text-dark border d-flex align-items-center gap-1 py-2 px-2">
                                    <i class="bi bi-people-fill text-danger"></i>
                                    <span>${paxCount} Pax</span>
                                </div>
                                <div class="badge bg-light text-dark border d-flex align-items-center gap-1 py-2 px-2">
                                    <i class="bi bi-gear-wide-connected text-danger"></i>
                                    <span>${trans}</span>
                                </div>
                                <div class="badge bg-light text-dark border d-flex align-items-center gap-1 py-2 px-2">
                                    <i class="bi bi-snow text-danger"></i>
                                    <span>${acText}</span>
                                </div>
                                <div class="badge bg-light text-dark border d-flex align-items-center gap-1 py-2 px-2">
                                    <i class="bi bi-suitcase-lg-fill text-danger"></i>
                                    <span>${bagsLarge} G</span>
                                </div>
                                <div class="badge bg-light text-dark border d-flex align-items-center gap-1 py-2 px-2">
                                    <i class="bi bi-suitcase-fill text-danger"></i>
                                    <span>${bagsSmall} P</span>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="border-light-gray my-3">
                        
                        <!-- Pricing Details and Action -->
                        <div class="d-flex align-items-end justify-content-between mt-auto">
                            <div>
                                <span class="text-muted text-sm d-block font-poppins mb-1">Precio Total Estimado:</span>
                                <span class="fs-3 fw-bold text-navy font-poppins leading-none">$${parseFloat(priceEstTotal).toFixed(2)}</span>
                                <span class="text-muted text-sm font-poppins">USD</span>
                                <small class="d-block text-muted" style="font-size:0.75rem;">$${parseFloat(priceBase).toFixed(2)}/día base</small>
                            </div>
                            <button class="btn btn-theme px-4 py-3 rounded-pill fw-bold text-white shadow-sm select-vehicle-btn">
                                Reservar <i class="bi bi-arrow-right-short ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Bind booking action logic to button
            cardCol.querySelector('.select-vehicle-btn').addEventListener('click', function() {
                selectVehicleForCheckout(vehicle);
            });
            
            resultsVehiclesGrid.appendChild(cardCol);
        });
    }
    
    /**
     * Save chosen vehicle details and route to checkout form
     */
    function selectVehicleForCheckout(vehicle) {
        sessionStorage.setItem('selectedVehicle', JSON.stringify(vehicle));
        window.location.href = '/reservar.php';
    }
    
    /**
     * Setup dynamic filters
     */
    function setupResultsFilters(allVehicles) {
        const filterLinks = document.querySelectorAll('.filter-category-btn');
        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                filterLinks.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const target = this.getAttribute('data-category');
                
                if (target === 'all') {
                    renderVehiclesList(allVehicles);
                } else if (target === 'suv') {
                    // Match SUV or Full Size categories
                    const filtered = allVehicles.filter(v => {
                        const cat = (v.category || '').toLowerCase();
                        return cat.includes('suv') || cat.includes('full') || cat.includes('prado') || cat.includes('camioneta');
                    });
                    renderVehiclesList(filtered);
                } else if (target === 'sedan') {
                    // Match Economic, Compact, Sedan, or Mini categories
                    const filtered = allVehicles.filter(v => {
                        const cat = (v.category || '').toLowerCase();
                        return cat.includes('econ') || cat.includes('compac') || cat.includes('sedan') || cat.includes('mini') || cat.includes('mediano');
                    });
                    renderVehiclesList(filtered);
                } else if (target === 'comercial') {
                    // Match Commercial, Pick Up, Cargo, or Van categories
                    const filtered = allVehicles.filter(v => {
                        const cat = (v.category || '').toLowerCase();
                        return cat.includes('comerc') || cat.includes('pick') || cat.includes('panel') || cat.includes('carga') || cat.includes('van');
                    });
                    renderVehiclesList(filtered);
                } else if (target === 'promo') {
                    // Match promos
                    const filtered = allVehicles.filter(v => v.promo === true || (v.category || '').toLowerCase().includes('promo'));
                    renderVehiclesList(filtered);
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
