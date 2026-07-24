<?php
/**
 * Acciones POST — Pie de página por unidad de negocio.
 */
declare(strict_types=1);

require_once __DIR__ . '/../services/UnitFooterService.php';

if ($action === 'save_unit_footer') {
    $ufUnitKey = strtolower(trim((string) ($_POST['uf_unit'] ?? '')));
    $ufError = UnitFooterService::apply($siteData, $ufUnitKey, $_POST);
    if ($ufError !== null) {
        $errorMsg = $ufError;
    } elseif ($contentService->saveAll($siteData)) {
        $successMsg = 'Pie de página de la unidad guardado correctamente.';
    } else {
        $errorMsg = 'Error al guardar el pie de página de la unidad.';
    }
}
