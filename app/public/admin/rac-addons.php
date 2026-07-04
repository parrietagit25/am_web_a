<?php
/**
 * Admin — Protecciones y Extras RAC (AM-RAC-BARS-RAC-3C).
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/BranchDataService.php';
require_once __DIR__ . '/../../services/RacAddonService.php';
require_once __DIR__ . '/../../services/RacPublicRateService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!admin_can('rac_reservations') && !admin_can('vehicles')) {
    http_response_code(403);
    echo 'Sin permiso.';
    exit;
}

$addonService = new RacAddonService();
$tab = ($_GET['tab'] ?? 'protections') === 'extras' ? 'extras' : 'protections';
$successMsg = '';
$errorMsg = '';

if (!empty($_GET['saved'])) {
    $successMsg = 'Cambios guardados correctamente.';
}

function rac_addons_branches(): array
{
    $branches = [];
    foreach (BranchDataService::getSucursales() as $branch) {
        $code = strtoupper(trim((string) ($branch['code'] ?? '')));
        if ($code !== '') {
            $branches[] = ['code' => $code, 'name' => (string) ($branch['name'] ?? $code)];
        }
    }

    return $branches;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $postTab = ($_POST['tab'] ?? 'protections') === 'extras' ? 'extras' : 'protections';
    try {
        if ($action === 'save_protection') {
            $id = (int) ($_POST['id'] ?? 0);
            $data = $_POST;
            if ($id > 0) {
                $addonService->updateProtection($id, $data);
                header('Location: /admin/rac-addons.php?tab=protections&saved=1&edit=' . $id);
            } else {
                $newId = $addonService->createProtection($data);
                header('Location: /admin/rac-addons.php?tab=protections&saved=1&edit=' . $newId);
            }
            exit;
        }
        if ($action === 'save_extra') {
            $id = (int) ($_POST['id'] ?? 0);
            $data = $_POST;
            if ($id > 0) {
                $addonService->updateExtra($id, $data);
                header('Location: /admin/rac-addons.php?tab=extras&saved=1&edit=' . $id);
            } else {
                $newId = $addonService->createExtra($data);
                header('Location: /admin/rac-addons.php?tab=extras&saved=1&edit=' . $newId);
            }
            exit;
        }
        if ($action === 'toggle_protection') {
            $addonService->setProtectionEnabled((int) ($_POST['id'] ?? 0), (string) ($_POST['enabled'] ?? '') === '1');
            header('Location: /admin/rac-addons.php?tab=protections&saved=1');
            exit;
        }
        if ($action === 'toggle_extra') {
            $addonService->setExtraEnabled((int) ($_POST['id'] ?? 0), (string) ($_POST['enabled'] ?? '') === '1');
            header('Location: /admin/rac-addons.php?tab=extras&saved=1');
            exit;
        }
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
        $tab = $postTab;
    }
}

$protections = $addonService->getAdminProtections();
$extras = $addonService->getAdminExtras();
$barsVehicleCatalog = RacPublicRateService::listBarsVehicleCatalog();
$barsVehicleNames = RacPublicRateService::listBarsVehicleNames();
$branches = rac_addons_branches();

$editProtection = null;
$editExtra = null;
if ($tab === 'protections' && !empty($_GET['edit'])) {
    $editProtection = $addonService->getProtection((int) $_GET['edit']);
}
if ($tab === 'extras' && !empty($_GET['edit'])) {
    $editExtra = $addonService->getExtra((int) $_GET['edit']);
}

$formProtection = $editProtection ?? [
    'id' => 0, 'code' => '', 'name' => '', 'description' => '', 'enabled' => 1,
    'price_type' => 'fixed_daily', 'price_amount' => 0, 'currency' => 'USD', 'applies_per' => 'day',
    'vehicle_code' => '', 'vehicle_name' => '', 'sort_order' => 100, 'visible_public' => 1, 'is_default' => 0,
];
$formExtra = $editExtra ?? [
    'id' => 0, 'code' => '', 'name' => '', 'description' => '', 'enabled' => 1,
    'price_type' => 'fixed_total', 'price_amount' => 0, 'currency' => 'USD', 'applies_per' => 'rental',
    'max_quantity' => 1, 'vehicle_code' => '', 'vehicle_name' => '', 'sort_order' => 100, 'visible_public' => 1,
];

$defaultAdminTab = 'rac-addons';
require __DIR__ . '/rac-addons-view.php';
