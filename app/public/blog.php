<?php
/**
 * Automarket - Blog (estilo HubSpot)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/unit-content-frontend.php';

$preContent = new ContentService();
$unitKey = unit_content_resolve_unit_key($preContent, 'rentacar');
$activeUnit = $unitKey;

require_once __DIR__ . '/../services/SeoService.php';
$blogUnitLabels = [
    'rentacar'   => 'Rent a Car',
    'seminuevos' => 'Seminuevos',
    'leasing'    => 'Leasing Operativo',
    'renting'    => 'Renting',
    'taller'     => 'Taller',
];
$blogLabel = $blogUnitLabels[$unitKey] ?? 'Automarket';
$seoOverride = [
    'title'       => 'Blog ' . $blogLabel . ' | Automarket',
    'description' => 'Noticias y artículos de Automarket ' . $blogLabel . ' en Panamá.',
];

require_once __DIR__ . '/../includes/header.php';

$unitLabel = unit_content_unit_label($contentService, $unitKey);
$unitHome = unit_content_unit_home_url($contentService, $unitKey);
$items = unit_content_get_items($contentService, $unitKey, 'blog');

$ucActiveType = 'blog';
require __DIR__ . '/../includes/unit-content-page-shell.php';
require __DIR__ . '/../includes/unit-content-list-blog.php';
require_once __DIR__ . '/../includes/footer.php';
