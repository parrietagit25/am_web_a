<?php
/**
 * AJAX Controller for Dynamic Vehicle Filtering
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

$where = ["Status = 'DISPONIBLE'"];
$params = [];

// Apply filters
if (!empty($_GET['makes']) && is_array($_GET['makes'])) {
    $makesPlaceholders = [];
    foreach ($_GET['makes'] as $idx => $m) {
        if (empty($m)) continue;
        $paramName = ":make_" . $idx;
        $makesPlaceholders[] = $paramName;
        $params[$paramName] = $m;
    }
    if (!empty($makesPlaceholders)) {
        $where[] = "Make IN (" . implode(",", $makesPlaceholders) . ")";
    }
}

if (!empty($_GET['years']) && is_array($_GET['years'])) {
    $yearsPlaceholders = [];
    foreach ($_GET['years'] as $idx => $y) {
        if (empty($y)) continue;
        $paramName = ":year_" . $idx;
        $yearsPlaceholders[] = $paramName;
        $params[$paramName] = intval($y);
    }
    if (!empty($yearsPlaceholders)) {
        $where[] = "Year IN (" . implode(",", $yearsPlaceholders) . ")";
    }
}

if (!empty($_GET['types']) && is_array($_GET['types'])) {
    $typesPlaceholders = [];
    foreach ($_GET['types'] as $idx => $t) {
        if (empty($t)) continue;
        $paramName = ":type_" . $idx;
        $typesPlaceholders[] = $paramName;
        $params[$paramName] = $t;
    }
    if (!empty($typesPlaceholders)) {
        $where[] = "CarType IN (" . implode(",", $typesPlaceholders) . ")";
    }
}

if (!empty($_GET['compras']) && is_array($_GET['compras'])) {
    $comprasPlaceholders = [];
    foreach ($_GET['compras'] as $idx => $c) {
        if (empty($c)) continue;
        $paramName = ":compra_" . $idx;
        $comprasPlaceholders[] = $paramName;
        $params[$paramName] = $c;
    }
    if (!empty($comprasPlaceholders)) {
        $where[] = "tipo_compra IN (" . implode(",", $comprasPlaceholders) . ")";
    }
}

if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $where[] = "Price >= :price_min";
    $params[':price_min'] = floatval($_GET['price_min']);
}

if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $where[] = "Price <= :price_max";
    $params[':price_max'] = floatval($_GET['price_max']);
}

if (!empty($_GET['search'])) {
    $where[] = "(Make LIKE :search OR Model LIKE :search OR Description LIKE :search)";
    $params[':search'] = '%' . trim($_GET['search']) . '%';
}

$whereSql = implode(" AND ", $where);

// Count total matching records for pagination
$countSql = "SELECT COUNT(*) as count FROM Automarket_Invs_web WHERE $whereSql";
$totalCountRow = $db->selectOne($countSql, $params);
$totalMatches = intval($totalCountRow['count'] ?? 0);

// Sorting
$sort = $_GET['sort'] ?? 'price_asc';
$order = "Price ASC";
if ($sort === 'price_desc') {
    $order = "Price DESC";
} elseif ($sort === 'year_desc') {
    $order = "Year DESC";
} elseif ($sort === 'km_asc') {
    $order = "Km ASC";
}

// Pagination
$limit = 9; // 9 cards per page
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalMatches / $limit);

$sql = "SELECT * FROM Automarket_Invs_web WHERE $whereSql ORDER BY $order LIMIT $limit OFFSET $offset";
$vehicles = $db->select($sql, $params);

// Start output buffering to capture rendered HTML
ob_start();
if (empty($vehicles)): ?>
    <div class="col-12 text-center py-5">
        <div class="p-5 bg-white rounded-4 border-light-gray border">
            <i class="bi bi-car-front text-muted" style="font-size: 3.5rem;"></i>
            <h4 class="fw-bold mt-3 text-navy font-montserrat">No se encontraron vehículos</h4>
            <p class="text-muted">Prueba a cambiar tus criterios de búsqueda o limpiar los filtros activos.</p>
            <button class="btn btn-theme rounded-pill mt-2 px-4 py-2" onclick="clearAllFilters()">Limpiar Filtros</button>
        </div>
    </div>
<?php else:
    foreach ($vehicles as $vehicle):
        $photoUrl = !empty($vehicle['Photo']) ? $vehicle['Photo'] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600&auto=format&fit=crop';
        if (!empty($vehicle['foto_impel'])) {
            $photoUrl = $vehicle['foto_impel'];
        }
        $fullName = trim($vehicle['Make'] . ' ' . $vehicle['Model']);
        $priceVal = (float)$vehicle['Price'];
        $tipoCompra = !empty($vehicle['tipo_compra']) ? $vehicle['tipo_compra'] : 'Seminuevo';
        $transmission = !empty($vehicle['Transmission']) ? $vehicle['Transmission'] : 'AUTOMATICO';
        ?>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12 d-flex">
            <div class="card vehicle-card border-0 shadow-sm w-100 flex-column justify-content-between overflow-hidden position-relative">
                <?php 
                $badgeBgColor = '#1f347f'; // Navy blue for SEMINUEVOS
                if ($tipoCompra === 'GARANTIZADOS') {
                    $badgeBgColor = '#dc3545'; // Red for GARANTIZADOS
                } elseif ($tipoCompra === 'SIN GARANTIA') {
                    $badgeBgColor = '#6c757d'; // Grey
                }
                ?>
                <span class="position-absolute px-3 py-1.5 text-white fw-bold top-3 start-3 text-uppercase" style="background-color: <?php echo $badgeBgColor; ?>; font-size: 0.72rem; border-radius: 4px; z-index: 10; letter-spacing: 0.05em;">
                    <?php echo esc($tipoCompra); ?>
                </span>
                
                <a href="/detalle.php?placa=<?php echo urlencode($vehicle['LicensePlate']); ?>" class="vehicle-img-container overflow-hidden d-block">
                    <img src="<?php echo esc($photoUrl); ?>" alt="<?php echo esc($fullName); ?>">
                </a>
                
                <div class="card-body d-flex flex-column justify-content-between flex-grow-1">
                    <div>
                        <a href="/detalle.php?placa=<?php echo urlencode($vehicle['LicensePlate']); ?>" class="text-decoration-none">
                            <h5 class="fw-bold text-navy card-title mb-2 text-uppercase font-montserrat" style="font-size: 1.05rem; min-height: 2.7rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.3;">
                                <?php echo esc($fullName); ?> <?php echo esc($vehicle['Year']); ?>
                            </h5>
                        </a>
                        
                        <div class="card-spec-line">
                            <?php echo esc($vehicle['Year']); ?> | <?php echo number_format($vehicle['Km']); ?> <?php echo esc(t('inventory.km')); ?> | <?php echo esc($transmission); ?> | <?php echo esc($tipoCompra); ?>
                        </div>
                    </div>
                    
                    <div class="mt-auto">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="price-container">
                                <div class="text-muted" style="font-size: 0.7rem; font-weight: 500; font-family: 'Poppins', sans-serif; line-height: 1;"><?php echo esc(t('common.from')); ?></div>
                                <div class="card-price-balboa">
                                    B/. <?php echo number_format($priceVal, 0); ?><sup style="font-size: 0.6em; top: -0.4em; font-weight: 800;">.00</sup>
                                </div>
                            </div>
                            <a href="/detalle.php?placa=<?php echo urlencode($vehicle['LicensePlate']); ?>" class="card-cotizar-link text-decoration-none"><?php echo esc(t('common.quote_here')); ?></a>
                        </div>
                        <div class="price-subtext-muted"><?php echo esc(t('common.price_no_tax')); ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;
endif;
$html = ob_get_clean();

// Generate Pagination HTML
ob_start();
if ($totalPages > 1): ?>
    <ul class="pagination pagination-sm justify-content-center mt-4">
        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-page="<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i> Anterior</a>
        </li>
        <?php 
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $startPage + 4);
        if ($endPage - $startPage < 4) {
            $startPage = max(1, $endPage - 4);
        }
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-page="<?php echo $page + 1; ?>">Siguiente <i class="bi bi-chevron-right"></i></a>
        </li>
    </ul>
<?php endif;
$paginationHtml = ob_get_clean();

echo json_encode([
    'html' => $html,
    'count' => $totalMatches,
    'pagination' => $paginationHtml,
    'page' => $page,
    'totalPages' => $totalPages
]);
