<?php
/**
 * Automarket - Contenido más reciente (estilo Expedia Travel Blog)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/unit-content-frontend.php';

$preContent = new ContentService();
$unitKey = unit_content_resolve_unit_key($preContent, 'rentacar');
$activeUnit = $unitKey;
require_once __DIR__ . '/../includes/header.php';

$unitLabel = unit_content_unit_label($contentService, $unitKey);
$unitHome = unit_content_unit_home_url($contentService, $unitKey);
$items = unit_content_get_items($contentService, $unitKey, 'latest');

$ucLayoutClass = 'expedia';
$ucPageTitle = 'Contenido más reciente';
$ucPageSubtitle = 'Promociones, eventos e información destacada de ' . $unitLabel . '.';
$ucActiveType = 'latest';
require __DIR__ . '/../includes/unit-content-page-shell.php';
require __DIR__ . '/../includes/unit-content-list-latest.php';
require_once __DIR__ . '/../includes/footer.php';
