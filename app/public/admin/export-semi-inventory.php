<?php
/**
 * Exporta el inventario seminuevos a CSV (compatible con Excel).
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/ContentService.php';
require_once __DIR__ . '/../../services/Database.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/InventoryHighlightService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!admin_can('semi_inventory')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No tiene permiso para exportar el inventario.';
    exit;
}

$search = trim($_GET['q'] ?? '');
$db = Database::getInstance();

$whereClause = '';
$queryParams = [];
if ($search !== '') {
    $whereClause = 'WHERE Make LIKE :search OR Model LIKE :search OR LocationName LIKE :search OR LicensePlate LIKE :search OR VIN LIKE :search OR id LIKE :search';
    $queryParams[':search'] = '%' . $search . '%';
}

$vehicles = $db->select(
    "SELECT * FROM Automarket_Invs_web $whereClause ORDER BY id DESC",
    $queryParams
);

$contentService = new ContentService();
$seminuevos = $contentService->get('seminuevos', []);
$highlightCatalog = InventoryHighlightService::catalog();
$highlightAssignments = InventoryHighlightService::getAssignments($seminuevos);

$filename = 'inventario-seminuevos-' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit;
}

// BOM para que Excel abra acentos correctamente
fwrite($out, "\xEF\xBB\xBF");

$headers = [
    'ID',
    'Placa',
    'VIN',
    'Marca',
    'Modelo',
    'Año',
    'Kilometraje',
    'Precio (USD)',
    'Precio con ITBMS',
    'Transmisión',
    'Categoría',
    'Combustible',
    'Color',
    'Ubicación',
    'Estado',
    'Etiqueta resaltado',
    'URL foto',
];
fputcsv($out, $headers);

foreach ($vehicles as $vehicle) {
    $highlightKey = InventoryHighlightService::resolveBadgeKey($vehicle, $highlightAssignments);
    $highlightLabel = $highlightKey !== '' ? ($highlightCatalog[$highlightKey]['label'] ?? $highlightKey) : '';

    $photo = trim((string) ($vehicle['foto_impel'] ?? ''));
    if ($photo === '') {
        $photo = trim((string) ($vehicle['Photo'] ?? ''));
    }

    fputcsv($out, [
        $vehicle['id'] ?? '',
        $vehicle['LicensePlate'] ?? '',
        $vehicle['VIN'] ?? '',
        $vehicle['Make'] ?? '',
        $vehicle['Model'] ?? '',
        $vehicle['Year'] ?? '',
        $vehicle['Km'] ?? '',
        $vehicle['Price'] ?? '',
        $vehicle['PriceTax'] ?? '',
        $vehicle['Transmission'] ?? '',
        $vehicle['CarType'] ?? '',
        $vehicle['Fuel'] ?? '',
        $vehicle['Color'] ?? '',
        $vehicle['LocationName'] ?? '',
        $vehicle['Status'] ?? '',
        $highlightLabel,
        $photo,
    ]);
}

fclose($out);
exit;
