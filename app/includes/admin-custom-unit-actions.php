<?php
/**
 * Guardado de contenido (hero + HTML) para unidades de negocio personalizadas.
 */
if ($action === 'save_custom_unit_content') {
    require_once __DIR__ . '/business-units-registry.php';
    require_once __DIR__ . '/../services/HeaderBannerService.php';

    $unitKey = am_normalize_business_unit_key((string) ($_POST['unit_key'] ?? ''));
    $pageSlug = am_normalize_custom_unit_page_slug((string) ($_POST['page_slug'] ?? ''));
    $tabSlug = trim((string) ($_POST['tab_slug'] ?? ('unit-' . $unitKey . ($pageSlug !== '' ? '-' . $pageSlug : ''))));

    if ($unitKey === '' || am_is_builtin_business_unit($unitKey)) {
        $errorMsg = 'Unidad de negocio no válida.';
    } elseif (!isset($siteData['global']['business_units'][$unitKey]) || !is_array($siteData['global']['business_units'][$unitKey])) {
        $errorMsg = 'Unidad de negocio no encontrada. Guarde primero la configuración global.';
    } else {
        $heroTitle = trim((string) ($_POST['hero_title'] ?? ''));
        $heroSubtitle = trim((string) ($_POST['hero_subtitle'] ?? ''));
        $bodyHtml = (string) ($_POST['body_html'] ?? '');

        if ($pageSlug === '') {
            $siteData['global']['business_units'][$unitKey]['heroTitle'] = $heroTitle;
            $siteData['global']['business_units'][$unitKey]['heroSubtitle'] = $heroSubtitle;
            $siteData['global']['business_units'][$unitKey]['body_html'] = $bodyHtml;
            $hbPath = ['global', 'business_units', $unitKey];
        } else {
            if (!isset($siteData['global']['business_units'][$unitKey]['pages']) || !is_array($siteData['global']['business_units'][$unitKey]['pages'])) {
                $siteData['global']['business_units'][$unitKey]['pages'] = [];
            }
            $existing = $siteData['global']['business_units'][$unitKey]['pages'][$pageSlug] ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }

            $pageData = $existing;
            $pageData['heroTitle'] = $heroTitle;
            $pageData['heroSubtitle'] = $heroSubtitle;
            $pageData['body_html'] = $bodyHtml;
            if (empty($pageData['label'])) {
                $pageData['label'] = strtoupper(str_replace('-', ' ', $pageSlug));
            }

            $siteData['global']['business_units'][$unitKey]['pages'][$pageSlug] = $pageData;
            $hbPath = ['global', 'business_units', $unitKey, 'pages', $pageSlug];
        }

        if (empty($errorMsg)) {
            $hbSlugPart = $pageSlug !== '' ? $pageSlug : 'main';
            $hbPrefix = 'hb_unit_' . $unitKey . '_' . preg_replace('/[^a-z0-9_]/', '_', $hbSlugPart);
            $hbErr = HeaderBannerService::applyPostAtPath(
                $siteData,
                $hbPath,
                $hbPrefix,
                $_POST,
                $_FILES,
                $contentService,
                'unit_' . $unitKey . ($pageSlug !== '' ? '_' . $pageSlug : '') . '_hb_',
                'hero_image_url'
            );
            if ($hbErr !== null) {
                $errorMsg = $hbErr;
            }
        }

        if (empty($errorMsg) && $contentService->saveAll($siteData)) {
            $successMsg = 'Contenido de la unidad guardado correctamente.';
            $_GET['tab'] = $tabSlug;
        } elseif (empty($errorMsg)) {
            $detail = ContentService::getLastSaveError();
            $errorMsg = 'Error al guardar el contenido.'
                . ($detail !== '' ? ' ' . $detail : '');
        }
    }
}
