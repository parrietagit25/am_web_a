<?php
/**
 * Acciones POST — iconos de medios de pago por unidad.
 *
 * @var string $action
 * @var array<string, mixed> $siteData
 * @var ContentService $contentService
 * @var string $successMsg
 * @var string $errorMsg
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/UnitPaymentMethodsService.php';

$pmActions = ['add_unit_payment_method', 'edit_unit_payment_method', 'delete_unit_payment_method'];
if (!in_array($action, $pmActions, true)) {
    return;
}

$pmUnit = strtolower(trim((string) ($_POST['payment_unit'] ?? '')));
$pmCfg = UnitPaymentMethodsService::unitConfig($pmUnit);
if ($pmCfg === null) {
    $errorMsg = 'Unidad no válida para medios de pago.';
    return;
}

$pmId = trim((string) ($_POST['payment_id'] ?? ''));
$pmAlt = trim((string) ($_POST['payment_alt'] ?? ''));
$pmTitle = trim((string) ($_POST['payment_title'] ?? ''));
$file = isset($_FILES['payment_image']) && is_array($_FILES['payment_image']) ? $_FILES['payment_image'] : null;

$result = ['ok' => false, 'error' => 'Acción no reconocida.'];

if ($action === 'add_unit_payment_method') {
    if ($file === null) {
        $errorMsg = 'Debes seleccionar una imagen ' . UnitPaymentMethodsService::ICON_WIDTH . '×' . UnitPaymentMethodsService::ICON_HEIGHT . ' px.';
        return;
    }
    $result = UnitPaymentMethodsService::add($siteData, $pmUnit, $contentService, $file, $pmAlt, $pmTitle);
} elseif ($action === 'edit_unit_payment_method') {
    if ($pmId === '') {
        $errorMsg = 'Falta el identificador del icono.';
        return;
    }
    $result = UnitPaymentMethodsService::update($siteData, $pmUnit, $contentService, $pmId, $pmAlt, $pmTitle, $file);
} elseif ($action === 'delete_unit_payment_method') {
    if ($pmId === '') {
        $errorMsg = 'Falta el identificador del icono.';
        return;
    }
    $result = UnitPaymentMethodsService::delete($siteData, $pmUnit, $pmId);
}

if (empty($result['ok'])) {
    $errorMsg = (string) ($result['error'] ?? 'No se pudo guardar el medio de pago.');
    return;
}

if ($contentService->saveAll($siteData)) {
    if ($action === 'delete_unit_payment_method') {
        $successMsg = 'Icono de medio de pago eliminado.';
    } elseif ($action === 'edit_unit_payment_method') {
        $successMsg = 'Icono de medio de pago actualizado.';
    } else {
        $successMsg = 'Icono de medio de pago agregado.';
    }
} else {
    $errorMsg = 'Error al guardar los medios de pago.';
}
