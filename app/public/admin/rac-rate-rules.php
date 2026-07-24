<?php
/**
 * Admin — Reglas comerciales de tarifas RAC.
 * AM-RAC-BARS-PRICING-2A / AM-RAC-BARS-RAC-3C
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AdminUserService.php';
require_once __DIR__ . '/../../services/BarsRateCacheService.php';
require_once __DIR__ . '/../../services/BranchDataService.php';
require_once __DIR__ . '/../../services/RacPublicRateService.php';
require_once __DIR__ . '/../../services/RacRateRuleService.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

AdminUserService::ensureSchema();
admin_require_login();

if (!admin_can_any(['rac_rate_rules', 'rac_reservations', 'vehicles'])) {
    http_response_code(403);
    echo 'Sin permiso.';
    exit;
}

$ruleService = new RacRateRuleService();
$successMsg = '';
$errorMsg = '';
$previewRows = [];
$editRule = null;

if (!empty($_GET['saved'])) {
    $successMsg = 'Cambios guardados correctamente.';
}
if (!empty($_GET['recalculated'])) {
    $successMsg = 'Tarifas finales recalculadas.';
}
if (!empty($_SESSION['rac_rules_preview_rows']) && !empty($_GET['preview'])) {
    $previewRows = $_SESSION['rac_rules_preview_rows'];
    unset($_SESSION['rac_rules_preview_rows']);
}

function rac_rules_branches(): array
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

function rac_rules_parse_targets(string $targetType, string $targetValue): array
{
    if ($targetType === 'all') {
        return [['target_type' => 'all', 'target_value' => '*']];
    }
    $targetValue = trim($targetValue);
    if ($targetValue === '') {
        throw new InvalidArgumentException('Debe seleccionar un target.');
    }
    if ($targetType === 'vehicle_code') {
        return [['target_type' => 'vehicle_code', 'target_value' => strtoupper($targetValue)]];
    }
    if ($targetType === 'vehicle_name') {
        return [['target_type' => 'vehicle_name', 'target_value' => $targetValue]];
    }

    return [['target_type' => $targetType, 'target_value' => $targetValue]];
}

function rac_rules_validate_target(string $targetType, string $targetValue): void
{
    if ($targetType === 'vehicle_code') {
        $codes = array_column(RacPublicRateService::listBarsVehicleCatalog(), 'vehicle_code');
        if ($codes !== [] && !in_array(strtoupper(trim($targetValue)), $codes, true)) {
            throw new InvalidArgumentException('Código BARS no válido o no registrado en caché local.');
        }
    }
}

function rac_rules_form_from_post(): array
{
    return [
        'id' => (int) ($_POST['rule_id'] ?? 0),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'enabled' => !empty($_POST['enabled']) ? 1 : 0,
        'priority' => (int) ($_POST['priority'] ?? 100),
        'stackable' => !empty($_POST['stackable']) ? 1 : 0,
        'stop_processing' => !empty($_POST['stop_processing']) ? 1 : 0,
        'rule_type' => (string) ($_POST['rule_type'] ?? 'promotion'),
        'adjustment_type' => (string) ($_POST['adjustment_type'] ?? 'percent_discount'),
        'adjustment_value' => (float) ($_POST['adjustment_value'] ?? 0),
        'currency' => 'USD',
        'valid_from' => trim((string) ($_POST['valid_from'] ?? '')),
        'valid_to' => trim((string) ($_POST['valid_to'] ?? '')),
        'min_rental_days' => trim((string) ($_POST['min_rental_days'] ?? '')),
        'max_rental_days' => trim((string) ($_POST['max_rental_days'] ?? '')),
        'pickup_location' => strtoupper(trim((string) ($_POST['pickup_location'] ?? ''))),
        'return_location' => strtoupper(trim((string) ($_POST['return_location'] ?? ''))),
        'rate_qualifier' => trim((string) ($_POST['rate_qualifier'] ?? '')) ?: 'WEB',
        'applies_to' => (string) ($_POST['target_type'] ?? 'all'),
        'badge_enabled' => !empty($_POST['badge_enabled']) ? 1 : 0,
        'badge_text' => trim((string) ($_POST['badge_text'] ?? '')),
        'badge_type' => (string) ($_POST['badge_type'] ?? 'promo'),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    try {
        if ($action === 'save_rule') {
            $data = rac_rules_form_from_post();
            $targetType = (string) ($_POST['target_type'] ?? 'all');
            $targetValue = (string) ($_POST['target_value'] ?? '*');
            rac_rules_validate_target($targetType, $targetValue);
            $targets = rac_rules_parse_targets($targetType, $targetValue);
            if ((int) ($data['id'] ?? 0) > 0) {
                $ruleService->updateRule((int) $data['id'], $data, $targets);
                header('Location: /admin/rac-rate-rules.php?saved=1&edit=' . (int) $data['id']);
            } else {
                $newId = $ruleService->createRule($data, $targets);
                header('Location: /admin/rac-rate-rules.php?saved=1&edit=' . $newId);
            }
            exit;
        }
        if ($action === 'toggle_rule') {
            $id = (int) ($_POST['rule_id'] ?? 0);
            $enabled = (string) ($_POST['enabled'] ?? '') === '1';
            if ($enabled) {
                $ruleService->enableRule($id);
            } else {
                $ruleService->disableRule($id);
            }
            header('Location: /admin/rac-rate-rules.php?saved=1');
            exit;
        }
        if ($action === 'recalculate_all') {
            $result = $ruleService->recalculateAllActive();
            $_SESSION['rac_rules_recalc_msg'] = 'Tarifas finales recalculadas: ' . (int) ($result['calculated'] ?? 0) . ' registros.';
            header('Location: /admin/rac-rate-rules.php?recalculated=1');
            exit;
        }
        if ($action === 'preview_rule') {
            $data = rac_rules_form_from_post();
            $targets = rac_rules_parse_targets(
                (string) ($_POST['target_type'] ?? 'all'),
                (string) ($_POST['target_value'] ?? '*')
            );
            $_SESSION['rac_rules_preview_rows'] = $ruleService->previewRule($data, $targets, [
                'pickup_location' => strtoupper(trim((string) ($_POST['preview_pickup_location'] ?? 'PTY'))),
                'return_location' => strtoupper(trim((string) ($_POST['preview_return_location'] ?? 'PTY'))),
                'pickup_datetime' => BarsRateCacheService::normalizeOtaDatetime(str_replace(' ', 'T', trim((string) ($_POST['preview_pickup_datetime'] ?? '2026-07-15T10:00:00')))),
                'return_datetime' => BarsRateCacheService::normalizeOtaDatetime(str_replace(' ', 'T', trim((string) ($_POST['preview_return_datetime'] ?? '2026-07-18T10:00:00')))),
                'rate_qualifier' => 'WEB',
            ]);
            header('Location: /admin/rac-rate-rules.php?preview=1');
            exit;
        }
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
    }
}

if (!empty($_SESSION['rac_rules_recalc_msg'])) {
    $successMsg = (string) $_SESSION['rac_rules_recalc_msg'];
    unset($_SESSION['rac_rules_recalc_msg']);
}

if (!empty($_GET['edit'])) {
    $editRule = $ruleService->getRule((int) $_GET['edit']);
}

$rules = $ruleService->listRules(true);
$branches = rac_rules_branches();
$barsVehicleCatalog = RacPublicRateService::listBarsVehicleCatalog();
$barsVehicleNames = RacPublicRateService::listBarsVehicleNames();
$formDefaults = $editRule ?? [
    'id' => 0,
    'name' => '',
    'description' => '',
    'enabled' => 1,
    'priority' => 100,
    'stackable' => 1,
    'stop_processing' => 0,
    'rule_type' => 'seasonal',
    'adjustment_type' => 'percent_discount',
    'adjustment_value' => 15,
    'valid_from' => '',
    'valid_to' => '',
    'min_rental_days' => null,
    'max_rental_days' => null,
    'pickup_location' => '',
    'return_location' => '',
    'rate_qualifier' => 'WEB',
    'badge_enabled' => 0,
    'badge_text' => '',
    'badge_type' => 'promo',
    'targets' => [['target_type' => 'all', 'target_value' => '*']],
];
$targetType = (string) (($formDefaults['targets'][0]['target_type'] ?? 'all'));
$targetValue = (string) (($formDefaults['targets'][0]['target_value'] ?? '*'));
if ($targetType === 'all') {
    $targetValue = '*';
} elseif (count($formDefaults['targets'] ?? []) > 1) {
    $targetValue = (string) ($formDefaults['targets'][0]['target_value'] ?? '');
}

$defaultAdminTab = 'rac-rate-rules';
require __DIR__ . '/rac-rate-rules-view.php';
