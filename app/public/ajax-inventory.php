<?php
/**
 * AJAX Controller for Dynamic Vehicle Filtering
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/InventoryHighlightService.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$contentService = new ContentService();
$inventoryHighlightAssignments = InventoryHighlightService::getAssignments($contentService->get('seminuevos', []));

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
$limit = 9;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalMatches / $limit);

$sql = "SELECT * FROM Automarket_Invs_web WHERE $whereSql ORDER BY $order LIMIT $limit OFFSET $offset";
$vehicles = $db->select($sql, $params);

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
        require __DIR__ . '/../includes/inventory-vehicle-card.php';
    endforeach;
endif;
$html = ob_get_clean();

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
