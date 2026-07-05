<?php
/**
 * Acciones POST — Sucursales globales (Generales).
 */

if ($action === 'add_global_sucursal') {
    $errorMsg = 'La creación manual está deshabilitada. Cree sucursales en Generales → Sucursales maestro y use «Sincronizar desde maestro».';
} elseif ($action === 'edit_global_sucursal') {
    $errorMsg = 'La edición de datos maestros está deshabilitada en Global. Use «Editar en maestro» en la fila o abra Generales → Sucursales maestro.';
} elseif ($action === 'delete_global_sucursal') {
    $id = (int) ($_POST['global_sucursal_id'] ?? 0);

    if ($id <= 0) {
        $errorMsg = 'Sucursal no válida.';
    } else {
        $list = $siteData['global']['sucursales'] ?? [];
        if (!is_array($list)) {
            $list = [];
        }

        $siteData['global']['sucursales'] = array_values(array_filter($list, function ($item) use ($id) {
            return (int) ($item['id'] ?? 0) !== $id;
        }));

        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Referencia global eliminada. La ubicación maestra no fue modificada.';
            $_GET['tab'] = 'global-sucursales';
        } else {
            $errorMsg = 'Error al eliminar la referencia global.';
        }
    }
} elseif ($action === 'sync_global_sucursales') {
    $errorMsg = 'La importación desde otras unidades está deshabilitada. Use «Sincronizar desde maestro» para actualizar el catálogo global.';
}
