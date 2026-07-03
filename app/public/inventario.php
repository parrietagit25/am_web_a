<?php
/**
 * Automarket - Vehículos Seminuevos en Inventario
 */
$activeUnit = 'seminuevos';
$seoOverride = [
    'title'       => 'Inventario de autos seminuevos | Automarket Panamá',
    'description' => 'Explora el inventario de autos seminuevos disponibles en Automarket Panamá.',
];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/InventoryHighlightService.php';
require_once __DIR__ . '/../includes/seminuevos-public-copy.php';

$seminuevosPageData = $contentService->get('seminuevos', []);
$inventoryPageTitle = seminuevos_inventory_page_title($seminuevosPageData);
$inventoryPageSubtitle = seminuevos_inventory_page_subtitle($seminuevosPageData);

$db = Database::getInstance();
$inventoryHighlightAssignments = InventoryHighlightService::getAssignments($contentService->get('seminuevos', []));

// Load filter options dynamically
$distinctMakes = $db->select("SELECT DISTINCT Make FROM Automarket_Invs_web WHERE Make IS NOT NULL AND Make != '' ORDER BY Make");
$distinctYears = $db->select("SELECT DISTINCT Year FROM Automarket_Invs_web WHERE Year IS NOT NULL ORDER BY Year DESC");
$distinctTypes = $db->select("SELECT DISTINCT CarType FROM Automarket_Invs_web WHERE CarType IS NOT NULL AND CarType != '' ORDER BY CarType");
$distinctCompras = $db->select("SELECT DISTINCT tipo_compra FROM Automarket_Invs_web WHERE tipo_compra IS NOT NULL AND tipo_compra != '' ORDER BY tipo_compra");

// Initial query (first page, default order by price asc)
// LIMIT interpolado tras cast/clamp a entero: PDO bindea :params como string
// por defecto, y MySQL (a diferencia de SQLite) rechaza un string en LIMIT.
$limit = 9;
$limit = max(1, min(100, (int) $limit));
$vehicles = $db->select("SELECT * FROM Automarket_Invs_web WHERE Status = 'DISPONIBLE' ORDER BY Price ASC LIMIT {$limit}");
$totalMatchesRow = $db->selectOne("SELECT COUNT(*) as count FROM Automarket_Invs_web WHERE Status = 'DISPONIBLE'");
$totalMatches = intval($totalMatchesRow['count'] ?? 0);
$totalPages = ceil($totalMatches / $limit);
?>

<style>
/* Custom Style layer for Inventory */
.inventory-sidebar {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(8, 16, 38, 0.02);
}
.filter-section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 14px;
    border-bottom: 2px solid var(--theme-primary);
    padding-bottom: 6px;
}
.filter-checkbox-label {
    font-size: 0.85rem;
    color: var(--navy-light);
    cursor: pointer;
    font-weight: 500;
}
.filter-checkbox-input:checked + .filter-checkbox-label {
    color: var(--theme-primary);
    font-weight: 600;
}
.price-range-slider-wrapper {
    padding: 10px 0;
}

/* Double range slider pure styling */
.slider-container {
    position: relative;
    width: 100%;
    height: 6px;
    margin-top: 15px;
    margin-bottom: 25px;
}
.slider-track {
    position: absolute;
    height: 6px;
    background-color: #eaecf0;
    width: 100%;
    border-radius: 3px;
    z-index: 1;
}
.slider-range-bar {
    position: absolute;
    height: 6px;
    background-color: var(--theme-primary);
    border-radius: 3px;
    z-index: 2;
    left: 0%;
    right: 0%;
}
.price-slider-input {
    position: absolute;
    width: 100%;
    height: 6px;
    background: none;
    pointer-events: none;
    -webkit-appearance: none;
    appearance: none;
    z-index: 3;
    margin: 0;
    top: 0;
    left: 0;
}
.price-slider-input::-webkit-slider-thumb {
    height: 18px;
    width: 18px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid var(--theme-primary);
    pointer-events: auto;
    -webkit-appearance: none;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    transition: transform 0.1s ease;
}
.price-slider-input::-webkit-slider-thumb:hover {
    transform: scale(1.15);
}
.price-slider-input::-moz-range-thumb {
    height: 18px;
    width: 18px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid var(--theme-primary);
    pointer-events: auto;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    transition: transform 0.1s ease;
}
.price-slider-input::-moz-range-thumb:hover {
    transform: scale(1.15);
}

.results-header-bar {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 20px;
    box-shadow: 0 4px 12px rgba(8, 16, 38, 0.02);
}

/* Card Badge overrides */
.category-badge {
    top: 12px;
    left: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 50px;
    letter-spacing: 0.04em;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Loading overlay for fluid search */
.inventory-grid-container {
    position: relative;
}
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(248, 249, 252, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100;
    backdrop-filter: blur(1px);
}
.spinner-premium {
    border: 3px solid #eaecf0;
    border-top: 3px solid var(--theme-primary);
    border-radius: 50%;
    width: 45px;
    height: 45px;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.vehicle-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: #ffffff;
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(8, 16, 38, 0.05);
}
.vehicle-card .card-body {
    background-color: #eef2f7;
    padding: 24px 20px !important;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    border-top: 1px solid #e2e8f0;
}
.vehicle-card .vehicle-img-container {
    display: block;
    width: 100%;
    height: 220px;
    overflow: hidden;
    padding: 0;
    margin: 0;
    line-height: 0;
    background-color: #ffffff;
    border: none;
}
.vehicle-card .vehicle-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
    display: block;
    transition: transform 0.3s ease;
}
.vehicle-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(8, 16, 38, 0.12) !important;
}
.vehicle-card:hover .vehicle-img-container img {
    transform: scale(1.03);
}
.card-spec-line {
    font-size: 0.76rem;
    color: #64748b;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    border-bottom: 2px solid #cbd5e1;
    padding-bottom: 10px;
    margin-bottom: 14px;
}
.card-price-balboa {
    color: #1f347f;
    font-size: 1.55rem;
    font-weight: 800;
    font-family: 'Poppins', sans-serif;
    line-height: 1.1;
}
.card-cotizar-link {
    color: #c51f17;
    font-weight: 700;
    font-size: 0.88rem;
    font-family: 'Poppins', sans-serif;
    transition: color 0.2s ease;
}
.card-cotizar-link:hover {
    color: #081026 !important;
    text-decoration: underline !important;
}
.price-subtext-muted {
    font-size: 0.68rem;
    color: #64748b;
    margin-top: 4px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
}

/* Etiquetas de resaltado (estilo marketplace) */
.inv-highlight-tag {
    position: absolute;
    z-index: 11;
    display: inline-block;
    color: #ffffff;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    line-height: 1.15;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
    font-family: 'Montserrat', sans-serif;
    max-width: 92%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.inv-highlight-tag--card {
    top: 10px;
    right: 0;
    padding: 5px 12px 5px 16px;
    border-radius: 999px 0 0 999px;
}
.inv-highlight-tag--detail {
    position: relative;
    top: auto;
    right: auto;
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 999px;
    margin-bottom: 12px;
    font-size: 0.72rem;
    max-width: 100%;
    white-space: normal;
}
.inv-highlight--nuevo {
    background: linear-gradient(135deg, #059669 0%, #10b981 55%, #34d399 100%);
}
.inv-highlight--ultimas {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 55%, #f97316 100%);
}
.inv-highlight--pocas {
    background: linear-gradient(135deg, #c2410c 0%, #ea580c 55%, #fb923c 100%);
}
.inv-highlight--oferta {
    background: linear-gradient(135deg, #be123c 0%, #e11d48 55%, #f43f5e 100%);
}
.inv-highlight--destacado {
    background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 55%, #a78bfa 100%);
}
.inv-highlight-preview {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
</style>

<!-- Banner Header -->
<div class="py-4 bg-navy text-white mb-5 border-bottom border-secondary">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 fw-bold font-montserrat mb-1 text-uppercase"><?php echo esc($inventoryPageTitle); ?></h1>
            <p class="text-white-50 text-sm mb-0"><?php echo esc($inventoryPageSubtitle); ?></p>
        </div>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-white-50 text-decoration-none"><?php echo esc(t('inventory.breadcrumb_home')); ?></a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?php echo esc(t('inventory.breadcrumb')); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Sidebar Filters Column (Desktop) -->
        <div class="col-lg-3 d-none d-lg-block">
            <form id="filtersForm">
                <div class="inventory-sidebar">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <h5 class="fw-bold mb-0 text-navy font-montserrat"><?php echo esc(t('common.filters')); ?></h5>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-muted" onclick="clearAllFilters()"><?php echo esc(t('common.clear')); ?></button>
                    </div>

                    <!-- Filter by Price -->
                    <div class="mb-4">
                        <h6 class="filter-section-title font-montserrat"><?php echo esc(t('inventory.price')); ?></h6>
                        <div class="price-range-slider-wrapper">
                            <div class="slider-container">
                                <div class="slider-track"></div>
                                <div class="slider-range-bar" id="slider-range-bar"></div>
                                <input type="range" min="8000" max="120000" value="8000" class="price-slider-input" id="price-min-input" oninput="updatePriceSlider()">
                                <input type="range" min="8000" max="120000" value="120000" class="price-slider-input" id="price-max-input" oninput="updatePriceSlider()">
                            </div>
                            <div class="d-flex justify-content-between text-navy text-xs fw-semibold font-poppins">
                                <span>Min: $<span id="price-min-label">8,000</span></span>
                                <span>Max: $<span id="price-max-label">120,000</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter by Year -->
                    <div class="mb-4">
                        <h6 class="filter-section-title font-montserrat"><?php echo esc(t('inventory.year')); ?></h6>
                        <div class="d-flex flex-column gap-2 overflow-y-auto scrollbar-hidden" style="max-height: 200px;">
                            <?php foreach ($distinctYears as $row): ?>
                                <div class="form-check">
                                    <input class="form-check-input filter-checkbox" type="checkbox" name="years[]" value="<?php echo esc($row['Year']); ?>" id="year-<?php echo esc($row['Year']); ?>" onchange="filterInventory()">
                                    <label class="form-check-label filter-checkbox-label" for="year-<?php echo esc($row['Year']); ?>">
                                        <?php echo esc($row['Year']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Brand -->
                    <div class="mb-4">
                        <h6 class="filter-section-title font-montserrat"><?php echo esc(t('inventory.make')); ?></h6>
                        <div class="d-flex flex-column gap-2 overflow-y-auto scrollbar-hidden" style="max-height: 250px;">
                            <?php foreach ($distinctMakes as $row): ?>
                                <div class="form-check">
                                    <input class="form-check-input filter-checkbox" type="checkbox" name="makes[]" value="<?php echo esc($row['Make']); ?>" id="make-<?php echo esc($row['Make']); ?>" onchange="filterInventory()">
                                    <label class="form-check-label filter-checkbox-label text-uppercase" for="make-<?php echo esc($row['Make']); ?>">
                                        <?php echo esc($row['Make']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by Tipo Venta -->
                    <div class="mb-4">
                        <h6 class="filter-section-title font-montserrat"><?php echo esc(t('inventory.sale_type')); ?></h6>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($distinctCompras as $row): ?>
                                <div class="form-check">
                                    <input class="form-check-input filter-checkbox" type="checkbox" name="compras[]" value="<?php echo esc($row['tipo_compra']); ?>" id="compra-<?php echo esc($row['tipo_compra']); ?>" onchange="filterInventory()">
                                    <label class="form-check-label filter-checkbox-label text-uppercase" for="compra-<?php echo esc($row['tipo_compra']); ?>">
                                        <?php echo esc($row['tipo_compra']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filter by CarType -->
                    <div class="mb-4">
                        <h6 class="filter-section-title font-montserrat"><?php echo esc(t('inventory.car_type')); ?></h6>
                        <div class="d-flex flex-column gap-2 overflow-y-auto scrollbar-hidden" style="max-height: 200px;">
                            <?php foreach ($distinctTypes as $row): ?>
                                <div class="form-check">
                                    <input class="form-check-input filter-checkbox" type="checkbox" name="types[]" value="<?php echo esc($row['CarType']); ?>" id="type-<?php echo esc($row['CarType']); ?>" onchange="filterInventory()">
                                    <label class="form-check-label filter-checkbox-label text-capitalize" for="type-<?php echo esc($row['CarType']); ?>">
                                        <?php echo esc($row['CarType']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Grid Cards Column (Desktop & Mobile) -->
        <div class="col-lg-9 col-12">
            <!-- Header bar / Sorting and search -->
            <div class="results-header-bar mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center justify-content-between w-100 w-md-auto">
                    <span class="text-navy fw-semibold font-poppins"><span id="results-count"><?php echo $totalMatches; ?></span> <?php echo esc(t('inventory.vehicles_found')); ?></span>
                    <!-- Mobile Filter Toggle Button -->
                    <button class="btn btn-outline-dark btn-sm d-lg-none rounded-pill px-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileFiltersOffcanvas" aria-controls="mobileFiltersOffcanvas">
                        <i class="bi bi-funnel-fill me-1"></i> <?php echo esc(t('common.filters')); ?>
                    </button>
                </div>
                
                <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto align-items-md-center">
                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="max-width: 250px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-premium form-control-sm border-start-0" id="searchKeyword" placeholder="<?php echo esc(t('inventory.search_placeholder')); ?>" oninput="filterInventory()">
                    </div>

                    <!-- Sorting selector -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-xs text-muted text-nowrap font-poppins"><?php echo esc(t('inventory.sort')); ?>:</span>
                        <select class="form-select form-select-sm form-control-premium py-1" id="sortSelect" onchange="filterInventory()" style="width: 180px;">
                            <option value="price_asc" selected><?php echo esc(t('inventory.sort_price_asc')); ?></option>
                            <option value="price_desc"><?php echo esc(t('inventory.sort_price_desc')); ?></option>
                            <option value="year_desc"><?php echo esc(t('inventory.sort_year_desc')); ?></option>
                            <option value="km_asc"><?php echo esc(t('inventory.sort_km_asc')); ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Grid and Loader Container -->
            <div class="inventory-grid-container">
                <!-- Loader overlay -->
                <div class="loading-overlay" id="gridLoadingOverlay">
                    <div class="spinner-premium"></div>
                </div>

                <div class="row g-4" id="vehiclesGrid">
                    <!-- Cards will be populated here dynamically via AJAX -->
                    <?php if (empty($vehicles)): ?>
                        <div class="col-12 text-center py-5">
                            <div class="p-5 bg-white rounded-4 border-light-gray border">
                                <i class="bi bi-car-front text-muted" style="font-size: 3.5rem;"></i>
                                <h4 class="fw-bold mt-3 text-navy font-montserrat"><?php echo esc(t('inventory.no_vehicles_title')); ?></h4>
                                <p class="text-muted"><?php echo esc(t('inventory.no_vehicles_hint')); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <?php require __DIR__ . '/../includes/inventory-vehicle-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination Container -->
                <div id="paginationContainer" class="d-flex justify-content-center mt-5">
                    <?php if ($totalPages > 1): ?>
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" data-page="0"><i class="bi bi-chevron-left"></i> Anterior</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i === 1) ? 'active' : ''; ?>">
                                    <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($totalPages <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="#" data-page="2">Siguiente <i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Offcanvas Filters Drawer -->
<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="mobileFiltersOffcanvas" aria-labelledby="mobileFiltersOffcanvasLabel">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-navy font-montserrat" id="mobileFiltersOffcanvasLabel"><i class="bi bi-funnel-fill me-1 text-primary"></i> Filtros de Búsqueda</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <!-- We clone/render the same filters inside offcanvas body but with unique inputs or using the same ones -->
        <form id="mobileFiltersForm">
            <!-- Mobile Price Filter -->
            <div class="mb-4">
                <h6 class="filter-section-title font-montserrat">Precio</h6>
                <div class="price-range-slider-wrapper">
                    <div class="slider-container">
                        <div class="slider-track"></div>
                        <div class="slider-range-bar" id="slider-range-bar-mobile"></div>
                        <input type="range" min="8000" max="120000" value="8000" class="price-slider-input" id="price-min-input-mobile" oninput="updatePriceSlider(true)">
                        <input type="range" min="8000" max="120000" value="120000" class="price-slider-input" id="price-max-input-mobile" oninput="updatePriceSlider(true)">
                    </div>
                    <div class="d-flex justify-content-between text-navy text-xs fw-semibold font-poppins">
                        <span>Min: $<span id="price-min-label-mobile">8,000</span></span>
                        <span>Max: $<span id="price-max-label-mobile">120,000</span></span>
                    </div>
                </div>
            </div>

            <!-- Mobile Year Filter -->
            <div class="mb-4">
                <h6 class="filter-section-title font-montserrat">Año</h6>
                <div class="d-flex flex-column gap-2 overflow-y-auto scrollbar-hidden" style="max-height: 200px;">
                    <?php foreach ($distinctYears as $row): ?>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox-mobile" type="checkbox" name="years[]" value="<?php echo esc($row['Year']); ?>" id="year-m-<?php echo esc($row['Year']); ?>" onchange="syncAndFilter(this, 'year-<?php echo esc($row['Year']); ?>')">
                            <label class="form-check-label filter-checkbox-label" for="year-m-<?php echo esc($row['Year']); ?>">
                                <?php echo esc($row['Year']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mobile Brand Filter -->
            <div class="mb-4">
                <h6 class="filter-section-title font-montserrat">Marca</h6>
                <div class="d-flex flex-column gap-2 overflow-y-auto scrollbar-hidden" style="max-height: 220px;">
                    <?php foreach ($distinctMakes as $row): ?>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox-mobile" type="checkbox" name="makes[]" value="<?php echo esc($row['Make']); ?>" id="make-m-<?php echo esc($row['Make']); ?>" onchange="syncAndFilter(this, 'make-<?php echo esc($row['Make']); ?>')">
                            <label class="form-check-label filter-checkbox-label text-uppercase" for="make-m-<?php echo esc($row['Make']); ?>">
                                <?php echo esc($row['Make']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mobile Tipo Venta Filter -->
            <div class="mb-4">
                <h6 class="filter-section-title font-montserrat">Tipo Venta</h6>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($distinctCompras as $row): ?>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox-mobile" type="checkbox" name="compras[]" value="<?php echo esc($row['tipo_compra']); ?>" id="compra-m-<?php echo esc($row['tipo_compra']); ?>" onchange="syncAndFilter(this, 'compra-<?php echo esc($row['tipo_compra']); ?>')">
                            <label class="form-check-label filter-checkbox-label text-uppercase" for="compra-m-<?php echo esc($row['tipo_compra']); ?>">
                                <?php echo esc($row['tipo_compra']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mobile CarType Filter -->
            <div class="mb-4">
                <h6 class="filter-section-title font-montserrat">Tipo de Auto</h6>
                <div class="d-flex flex-column gap-2 overflow-y-auto scrollbar-hidden" style="max-height: 200px;">
                    <?php foreach ($distinctTypes as $row): ?>
                        <div class="form-check">
                            <input class="form-check-input filter-checkbox-mobile" type="checkbox" name="types[]" value="<?php echo esc($row['CarType']); ?>" id="type-m-<?php echo esc($row['CarType']); ?>" onchange="syncAndFilter(this, 'type-<?php echo esc($row['CarType']); ?>')">
                            <label class="form-check-label filter-checkbox-label text-capitalize" for="type-m-<?php echo esc($row['CarType']); ?>">
                                <?php echo esc($row['CarType']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer p-3 bg-light border-top d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary w-50 rounded-pill" onclick="clearAllFilters()" data-bs-dismiss="offcanvas">Limpiar</button>
        <button type="button" class="btn btn-theme w-50 rounded-pill" data-bs-dismiss="offcanvas">Aplicar</button>
    </div>
</div>

<script>
let activePage = 1;
const priceGap = 5000;

// Price Slider Sync Logic
function updatePriceSlider(isMobile = false) {
    const minInput = document.getElementById(isMobile ? 'price-min-input-mobile' : 'price-min-input');
    const maxInput = document.getElementById(isMobile ? 'price-max-input-mobile' : 'price-max-input');
    const minLabel = document.getElementById(isMobile ? 'price-min-label-mobile' : 'price-min-label');
    const maxLabel = document.getElementById(isMobile ? 'price-max-label-mobile' : 'price-max-label');
    const rangeBar = document.getElementById(isMobile ? 'slider-range-bar-mobile' : 'slider-range-bar');

    let minVal = parseInt(minInput.value);
    let maxVal = parseInt(maxInput.value);

    // Maintain gap
    if (maxVal - minVal < priceGap) {
        if (event && event.target === minInput) {
            minInput.value = maxVal - priceGap;
            minVal = maxVal - priceGap;
        } else {
            maxInput.value = minVal + priceGap;
            maxVal = minVal + priceGap;
        }
    }

    minLabel.textContent = minVal.toLocaleString();
    maxLabel.textContent = maxVal.toLocaleString();

    // Update track fill color range
    const percentMin = ((minVal - minInput.min) / (minInput.max - minInput.min)) * 100;
    const percentMax = ((maxVal - maxInput.min) / (maxInput.max - maxInput.min)) * 100;

    rangeBar.style.left = percentMin + "%";
    rangeBar.style.width = (percentMax - percentMin) + "%";

    // Sync mobile and desktop price range inputs
    if (isMobile) {
        document.getElementById('price-min-input').value = minVal;
        document.getElementById('price-max-input').value = maxVal;
        document.getElementById('price-min-label').textContent = minVal.toLocaleString();
        document.getElementById('price-max-label').textContent = maxVal.toLocaleString();
        
        const deskRangeBar = document.getElementById('slider-range-bar');
        deskRangeBar.style.left = percentMin + "%";
        deskRangeBar.style.width = (percentMax - percentMin) + "%";
    } else {
        document.getElementById('price-min-input-mobile').value = minVal;
        document.getElementById('price-max-input-mobile').value = maxVal;
        document.getElementById('price-min-label-mobile').textContent = minVal.toLocaleString();
        document.getElementById('price-max-label-mobile').textContent = maxVal.toLocaleString();
        
        const mobRangeBar = document.getElementById('slider-range-bar-mobile');
        mobRangeBar.style.left = percentMin + "%";
        mobRangeBar.style.width = (percentMax - percentMin) + "%";
    }

    filterInventory();
}

// Sync mobile inputs with desktop inputs
function syncAndFilter(checkbox, targetId) {
    const targetCheckbox = document.getElementById(targetId);
    if (targetCheckbox) {
        targetCheckbox.checked = checkbox.checked;
    }
    filterInventory();
}

// Fetch filtered inventory from API
function filterInventory(page = 1) {
    activePage = page;
    
    // Show grid loading spinner
    document.getElementById('gridLoadingOverlay').style.display = 'flex';

    // Build query params from desktop form (synced automatically with mobile)
    const form = document.getElementById('filtersForm');
    const formData = new FormData(form);
    const searchParams = new URLSearchParams();

    // Checkbox arrays
    for (const [key, value] of formData.entries()) {
        searchParams.append(key, value);
    }

    // Price values
    const priceMin = document.getElementById('price-min-input').value;
    const priceMax = document.getElementById('price-max-input').value;
    searchParams.append('price_min', priceMin);
    searchParams.append('price_max', priceMax);

    // Search and Sort
    const keyword = document.getElementById('searchKeyword').value;
    searchParams.append('search', keyword);
    
    const sort = document.getElementById('sortSelect').value;
    searchParams.append('sort', sort);

    // Active Page
    searchParams.append('page', page);

    // Call API
    fetch('/ajax-inventory.php?' + searchParams.toString())
        .then(response => response.json())
        .then(data => {
            // Update Grid HTML
            document.getElementById('vehiclesGrid').innerHTML = data.html;
            
            // Update matched count
            document.getElementById('results-count').textContent = data.count;
            
            // Update pagination
            document.getElementById('paginationContainer').innerHTML = data.pagination;

            // Hide grid spinner
            document.getElementById('gridLoadingOverlay').style.display = 'none';

            // Re-bind pagination clicks
            bindPaginationClicks();
        })
        .catch(err => {
            console.error('Error fetching inventory:', err);
            document.getElementById('gridLoadingOverlay').style.display = 'none';
        });
}

function bindPaginationClicks() {
    const pagLinks = document.querySelectorAll('#paginationContainer .page-link');
    pagLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const pageNum = parseInt(this.getAttribute('data-page'));
            if (pageNum > 0) {
                filterInventory(pageNum);
                // Scroll to top of grid area smoothly
                document.querySelector('.results-header-bar').scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

function clearAllFilters() {
    // Reset inputs
    document.querySelectorAll('.filter-checkbox, .filter-checkbox-mobile').forEach(cb => {
        cb.checked = false;
    });

    document.getElementById('searchKeyword').value = '';
    document.getElementById('sortSelect').value = 'price_asc';

    // Reset Sliders
    const minInput = document.getElementById('price-min-input');
    const maxInput = document.getElementById('price-max-input');
    minInput.value = 8000;
    maxInput.value = 120000;

    updatePriceSlider(false);
}

// Initialize double price range slider display and pagination on load
document.addEventListener('DOMContentLoaded', function() {
    updatePriceSlider(false);
    bindPaginationClicks();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
