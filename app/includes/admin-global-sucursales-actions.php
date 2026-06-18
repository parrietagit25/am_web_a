<?php
/**
 * Acciones POST — Sucursales globales (Generales).
 */

if ($action === 'add_global_sucursal') {
    $name = trim((string) ($_POST['global_sucursal_name'] ?? ''));
    $lat = trim((string) ($_POST['global_sucursal_lat'] ?? ''));
    $lng = trim((string) ($_POST['global_sucursal_lng'] ?? ''));

    if ($name === '') {
        $errorMsg = 'El nombre de la sucursal es obligatorio.';
    } else {
        if (!isset($siteData['global']['sucursales']) || !is_array($siteData['global']['sucursales'])) {
            $siteData['global']['sucursales'] = [];
        }

        $imageUrl = '';
        if (isset($_FILES['global_sucursal_image']) && $_FILES['global_sucursal_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedPath = $contentService->uploadImage($_FILES['global_sucursal_image'], 'global_sucursal_');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            } elseif (empty($errorMsg)) {
                $errorMsg = 'No se pudo subir la foto de la sucursal.';
            }
        }

        if (empty($errorMsg)) {
            $existingIds = array_map('intval', array_column($siteData['global']['sucursales'], 'id'));
            $newId = !empty($existingIds) ? max($existingIds) + 1 : 1;

            $siteData['global']['sucursales'][] = [
                'id' => $newId,
                'name' => $name,
                'image_url' => $imageUrl,
                'lat' => $lat,
                'lng' => $lng,
            ];

            if ($contentService->saveAll($siteData)) {
                $successMsg = 'Sucursal agregada correctamente.';
                $_GET['tab'] = 'global-sucursales';
            } else {
                $errorMsg = 'Error al guardar la sucursal.';
            }
        }
    }
} elseif ($action === 'edit_global_sucursal') {
    $id = (int) ($_POST['global_sucursal_id'] ?? 0);
    $name = trim((string) ($_POST['global_sucursal_name'] ?? ''));
    $lat = trim((string) ($_POST['global_sucursal_lat'] ?? ''));
    $lng = trim((string) ($_POST['global_sucursal_lng'] ?? ''));

    if ($name === '') {
        $errorMsg = 'El nombre de la sucursal es obligatorio.';
    } elseif ($id <= 0) {
        $errorMsg = 'Sucursal no válida.';
    } else {
        if (!isset($siteData['global']['sucursales']) || !is_array($siteData['global']['sucursales'])) {
            $siteData['global']['sucursales'] = [];
        }

        $foundIdx = -1;
        foreach ($siteData['global']['sucursales'] as $idx => $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                $foundIdx = $idx;
                break;
            }
        }

        if ($foundIdx === -1) {
            $errorMsg = 'Sucursal no encontrada.';
        } else {
            $existing = $siteData['global']['sucursales'][$foundIdx];
            $imageUrl = trim((string) ($existing['image_url'] ?? ''));

            if (isset($_FILES['global_sucursal_image']) && $_FILES['global_sucursal_image']['error'] === UPLOAD_ERR_OK) {
                $uploadedPath = $contentService->uploadImage($_FILES['global_sucursal_image'], 'global_sucursal_');
                if ($uploadedPath) {
                    $imageUrl = $uploadedPath;
                } elseif (empty($errorMsg)) {
                    $errorMsg = 'No se pudo subir la foto de la sucursal.';
                }
            }

            if (empty($errorMsg)) {
                $siteData['global']['sucursales'][$foundIdx] = [
                    'id' => $id,
                    'name' => $name,
                    'image_url' => $imageUrl,
                    'lat' => $lat,
                    'lng' => $lng,
                ];

                if ($contentService->saveAll($siteData)) {
                    $successMsg = 'Sucursal actualizada correctamente.';
                    $_GET['tab'] = 'global-sucursales';
                } else {
                    $errorMsg = 'Error al actualizar la sucursal.';
                }
            }
        }
    }
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
            $successMsg = 'Sucursal eliminada correctamente.';
            $_GET['tab'] = 'global-sucursales';
        } else {
            $errorMsg = 'Error al eliminar la sucursal.';
        }
    }
}
