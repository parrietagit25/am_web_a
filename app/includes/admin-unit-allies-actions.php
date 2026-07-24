<?php
/**
 * Acciones POST unificadas — Aliados / marcas por unidad.
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/AllyService.php';

$allyUnit = strtolower(trim((string) ($_POST['ally_unit'] ?? '')));

if ($action === 'save_unit_allies_meta') {
    $err = AllyService::applyMeta($siteData, $allyUnit, $_POST);
    if ($err !== null) {
        $errorMsg = $err;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Textos de la sección de aliados guardados.';
    } else {
        $errorMsg = 'Error al guardar los textos de aliados.';
    }
} elseif ($action === 'add_unit_ally') {
    $err = AllyService::applyAdd(
        $siteData,
        $allyUnit,
        $_POST,
        isset($_FILES['ally_logo']) && is_array($_FILES['ally_logo']) ? $_FILES['ally_logo'] : null,
        $contentService
    );
    if ($err !== null) {
        $errorMsg = $err;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Aliado agregado correctamente.';
    } else {
        $errorMsg = 'Error al guardar el aliado.';
    }
} elseif ($action === 'edit_unit_ally') {
    $err = AllyService::applyEdit(
        $siteData,
        $allyUnit,
        $_POST,
        isset($_FILES['ally_logo']) && is_array($_FILES['ally_logo']) ? $_FILES['ally_logo'] : null,
        $contentService
    );
    if ($err !== null) {
        $errorMsg = $err;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Aliado actualizado correctamente.';
    } else {
        $errorMsg = 'Error al actualizar el aliado.';
    }
} elseif ($action === 'delete_unit_ally') {
    $err = AllyService::applyDelete($siteData, $allyUnit, $_POST);
    if ($err !== null) {
        $errorMsg = $err;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Aliado eliminado correctamente.';
    } else {
        $errorMsg = 'Error al eliminar el aliado.';
    }
}
