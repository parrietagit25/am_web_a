<?php
/**
 * Acciones POST del admin — Pie de página
 * Incluir desde admin/index.php dentro del bloque POST.
 */
require_once __DIR__ . '/../services/FooterService.php';

if (!isset($siteData['footer']) || !is_array($siteData['footer'])) {
    $siteData['footer'] = [];
}

if ($action === 'save_footer_general') {
    if (!isset($siteData['footer']['general'])) {
        $siteData['footer']['general'] = [];
    }
    $g = &$siteData['footer']['general'];
    $g['tagline'] = trim($_POST['footer_tagline'] ?? '');
    $g['address'] = trim($_POST['footer_address'] ?? '');
    $g['phone_display'] = trim($_POST['footer_phone'] ?? '');
    $g['email'] = trim($_POST['footer_email'] ?? '');
    $g['copyright'] = trim($_POST['footer_copyright'] ?? '');
    $g['privacy_url'] = trim($_POST['footer_privacy_url'] ?? '/pagina-institucional.php?p=privacidad');
    $g['cookies_url'] = trim($_POST['footer_cookies_url'] ?? '/pagina-institucional.php?p=cookies');
    $g['resources_title'] = trim($_POST['footer_resources_title'] ?? 'Recursos');
    $g['also_know_title'] = trim($_POST['footer_also_know_title'] ?? 'Conoce también');
    $g['follow_title'] = trim($_POST['footer_follow_title'] ?? 'Síguenos');
    $g['payment_title'] = trim($_POST['footer_payment_title'] ?? 'Medios de pago');
    $g['payment_badges_html'] = trim($_POST['footer_payment_badges_html'] ?? '');

    if (isset($_FILES['footer_logo']) && $_FILES['footer_logo']['error'] === UPLOAD_ERR_OK) {
        $uploaded = $contentService->uploadImage($_FILES['footer_logo'], 'footer_logo_');
        if ($uploaded) {
            $g['logo_url'] = $uploaded;
        }
    } elseif (!empty(trim($_POST['footer_logo_url'] ?? ''))) {
        $g['logo_url'] = trim($_POST['footer_logo_url']);
    }

    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Pie de página — Generales guardado correctamente.';
    } else {
        $errorMsg = 'Error al guardar la configuración del pie de página.';
    }
}
elseif ($action === 'save_footer_page') {
    $pageKey = trim($_POST['page_key'] ?? '');
    if (!in_array($pageKey, FooterService::PAGE_KEYS, true)) {
        $errorMsg = 'Página no válida.';
    } else {
        if (!isset($siteData['footer']['pages'])) {
            $siteData['footer']['pages'] = [];
        }
        $siteData['footer']['pages'][$pageKey] = [
            'title' => trim($_POST['page_title'] ?? FooterService::PAGE_LABELS[$pageKey]),
            'content_html' => trim($_POST['page_content_html'] ?? ''),
            'active' => isset($_POST['page_active']) && $_POST['page_active'] === '1',
        ];
        if ($contentService->saveAll($siteData)) {
            $successMsg = 'Contenido de "' . FooterService::PAGE_LABELS[$pageKey] . '" guardado correctamente.';
        } else {
            $errorMsg = 'Error al guardar el contenido de la página.';
        }
    }
}
elseif ($action === 'save_footer_also_know') {
    $links = [];
    $labels = $_POST['also_label'] ?? [];
    $urls = $_POST['also_url'] ?? [];
    $orders = $_POST['also_order'] ?? [];
    $ids = $_POST['also_id'] ?? [];
    $actives = $_POST['also_active'] ?? [];

    foreach ($labels as $i => $label) {
        $label = trim((string) $label);
        $url = trim((string) ($urls[$i] ?? ''));
        if ($label === '' || $url === '') {
            continue;
        }
        $links[] = [
            'id' => trim((string) ($ids[$i] ?? '')) ?: ('ak_' . $i . '_' . time()),
            'label' => $label,
            'url' => $url,
            'sort_order' => intval($orders[$i] ?? 99),
            'active' => isset($actives[$i]),
        ];
    }
    usort($links, fn($a, $b) => $a['sort_order'] - $b['sort_order']);
    $siteData['footer']['also_know'] = $links;
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Enlaces "Conoce también" actualizados.';
    } else {
        $errorMsg = 'Error al guardar los enlaces.';
    }
}
elseif ($action === 'save_footer_social') {
    $networks = [];
    $labels = $_POST['social_label'] ?? [];
    $icons = $_POST['social_icon'] ?? [];
    $urls = $_POST['social_url'] ?? [];
    $orders = $_POST['social_order'] ?? [];
    $ids = $_POST['social_id'] ?? [];
    $actives = $_POST['social_active'] ?? [];

    foreach ($labels as $i => $label) {
        $label = trim((string) $label);
        $url = trim((string) ($urls[$i] ?? ''));
        if ($label === '') {
            continue;
        }
        $entry = [
            'id' => trim((string) ($ids[$i] ?? '')) ?: ('soc_' . $i . '_' . time()),
            'label' => $label,
            'icon' => trim((string) ($icons[$i] ?? 'bi-link-45deg')) ?: 'bi-link-45deg',
            'url' => $url !== '' ? $url : '#',
            'sort_order' => intval($orders[$i] ?? 99),
            'active' => isset($actives[$i]),
        ];
        $networks[] = FooterService::normalizeSocialEntry($entry);
    }
    usort($networks, fn($a, $b) => $a['sort_order'] - $b['sort_order']);
    $siteData['footer']['social'] = $networks;
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Redes sociales actualizadas.';
    } else {
        $errorMsg = 'Error al guardar redes sociales.';
    }
}
elseif ($action === 'save_footer_sucursal') {
    $sucId = trim($_POST['sucursal_id'] ?? '');
    $isEdit = $sucId !== '';
    if (!isset($siteData['footer']['sucursales'])) {
        $siteData['footer']['sucursales'] = [];
    }
    $entry = [
        'id' => $isEdit ? $sucId : (time() . '_' . rand(100, 999)),
        'unit' => trim($_POST['sucursal_unit'] ?? 'grupo'),
        'name' => trim($_POST['sucursal_name'] ?? ''),
        'location' => trim($_POST['sucursal_location'] ?? ''),
        'address' => trim($_POST['sucursal_address'] ?? ''),
        'schedule' => trim($_POST['sucursal_schedule'] ?? ''),
        'phone' => trim($_POST['sucursal_phone'] ?? ''),
        'lat' => trim($_POST['sucursal_lat'] ?? ''),
        'lng' => trim($_POST['sucursal_lng'] ?? ''),
        'sort_order' => intval($_POST['sucursal_sort_order'] ?? 99),
        'active' => isset($_POST['sucursal_active']) && $_POST['sucursal_active'] === '1',
    ];
    if ($entry['name'] === '') {
        $errorMsg = 'El nombre de la sucursal es obligatorio.';
    } else {
        $found = false;
        foreach ($siteData['footer']['sucursales'] as $idx => $s) {
            if (($s['id'] ?? '') === $sucId) {
                $siteData['footer']['sucursales'][$idx] = $entry;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $siteData['footer']['sucursales'][] = $entry;
        }
        if ($contentService->saveAll($siteData)) {
            $successMsg = $isEdit ? 'Sucursal actualizada.' : 'Sucursal agregada al pie de página.';
        } else {
            $errorMsg = 'Error al guardar la sucursal.';
        }
    }
}
elseif ($action === 'delete_footer_sucursal') {
    $sucId = trim($_POST['sucursal_id'] ?? '');
    if (!isset($siteData['footer']['sucursales'])) {
        $siteData['footer']['sucursales'] = [];
    }
    $siteData['footer']['sucursales'] = array_values(array_filter(
        $siteData['footer']['sucursales'],
        fn($s) => ($s['id'] ?? '') !== $sucId
    ));
    if ($contentService->saveAll($siteData)) {
        $successMsg = 'Sucursal eliminada.';
    } else {
        $errorMsg = 'Error al eliminar la sucursal.';
    }
}
elseif ($action === 'sync_footer_sucursales') {
    if (!isset($siteData['footer']['sucursales'])) {
        $siteData['footer']['sucursales'] = [];
    }
    $existingIds = [];
    foreach ($siteData['footer']['sucursales'] as $s) {
        $existingIds[$s['id'] ?? ''] = true;
    }
    $imported = 0;
    $sources = [
        ['unit' => 'rentacar', 'list' => $siteData['homepage']['sucursales'] ?? []],
        ['unit' => 'seminuevos', 'list' => $siteData['seminuevos']['sucursales'] ?? []],
        ['unit' => 'leasing', 'list' => $siteData['leasing']['sucursales'] ?? []],
        ['unit' => 'renting', 'list' => $siteData['renting']['sucursales'] ?? []],
        ['unit' => 'taller', 'list' => $siteData['taller']['sucursales'] ?? []],
    ];
    foreach ($sources as $src) {
        foreach ($src['list'] as $s) {
            $id = 'import_' . $src['unit'] . '_' . ($s['id'] ?? uniqid());
            if (isset($existingIds[$id])) {
                continue;
            }
            $siteData['footer']['sucursales'][] = [
                'id' => $id,
                'unit' => $src['unit'],
                'name' => $s['name'] ?? '',
                'location' => $s['location'] ?? '',
                'address' => $s['address'] ?? '',
                'schedule' => $s['schedule'] ?? '',
                'phone' => $s['phone'] ?? '',
                'lat' => (string) ($s['lat'] ?? ''),
                'lng' => (string) ($s['lng'] ?? ''),
                'sort_order' => intval($s['sort_order'] ?? 99),
                'active' => ($s['active'] ?? true) !== false,
            ];
            $imported++;
        }
    }
    if ($contentService->saveAll($siteData)) {
        $successMsg = "Importación completada: {$imported} sucursal(es) agregada(s) desde las unidades.";
    } else {
        $errorMsg = 'Error al importar sucursales.';
    }
}
