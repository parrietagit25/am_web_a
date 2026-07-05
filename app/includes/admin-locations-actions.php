<?php
/**
 * Acciones POST — Maestro de ubicaciones (Generales).
 */

if ($action === 'save_location') {
    require_once __DIR__ . '/../services/LocationAdminService.php';

    $result = LocationAdminService::saveFromPost($siteData, $_POST);

    if (!$result['ok']) {
        $errorMsg = $result['error'];
    } else {
        if ($contentService->saveAll($siteData)) {
            $backupNote = $result['backup'] !== ''
                ? ' Backup: ' . basename($result['backup']) . '.'
                : '';
            $successMsg = 'Ubicación guardada correctamente.' . $backupNote;
            $_GET['tab'] = 'locations-master';
            $_GET['location_id'] = $result['location_id'];
            $_POST['admin_tab'] = 'locations-master';
        } else {
            $detail = ContentService::getLastSaveError();
            $errorMsg = 'Error al guardar site_data.json.'
                . ($detail !== '' ? ' ' . $detail : '')
                . ($result['backup'] !== '' ? ' Backup disponible: ' . basename($result['backup']) . '.' : '');
        }
    }
}

if ($action === 'create_location') {
    require_once __DIR__ . '/../services/LocationAdminService.php';

    $result = LocationAdminService::createFromPost($siteData, $_POST);

    if (!$result['ok']) {
        $errorMsg = $result['error'];
    } else {
        if ($contentService->saveAll($siteData)) {
            $backupNote = $result['backup'] !== ''
                ? ' Backup: ' . basename($result['backup']) . '.'
                : '';
            $successMsg = 'Nueva ubicación creada correctamente.' . $backupNote;
            $_GET['tab'] = 'locations-master';
            $_GET['location_id'] = $result['location_id'];
            $_POST['admin_tab'] = 'locations-master';
        } else {
            $detail = ContentService::getLastSaveError();
            $errorMsg = 'Error al guardar site_data.json.'
                . ($detail !== '' ? ' ' . $detail : '')
                . ($result['backup'] !== '' ? ' Backup disponible: ' . basename($result['backup']) . '.' : '');
        }
    }
}
