<?php
/**
 * Schema.org ItemList JSON-LD para listados de sucursales (AM-SEO-3C-F2).
 *
 * Variables de entrada:
 *   $_schemaLocationList (array) — filas de sucursal con slug y name (cards del listado)
 */
$_schemaLocationList = is_array($_schemaLocationList ?? null) ? $_schemaLocationList : [];

$_sliSiteUrl = 'https://www.automarket.com.pa';
$_sliElements = [];
$_sliSeenSlugs = [];
$_sliPosition = 0;

foreach ($_schemaLocationList as $_sliRow) {
    if (!is_array($_sliRow)) {
        continue;
    }

    $_sliSlug = trim((string) ($_sliRow['slug'] ?? ''));
    if ($_sliSlug === '' || isset($_sliSeenSlugs[$_sliSlug])) {
        continue;
    }

    $_sliUrl = am_location_canonical_url($_sliSlug);
    if ($_sliUrl === '') {
        continue;
    }

    $_sliSeenSlugs[$_sliSlug] = true;
    $_sliPosition++;

    $_sliItem = [
        '@type'    => 'ListItem',
        'position' => $_sliPosition,
        'url'      => $_sliUrl,
    ];

    $_sliName = trim((string) ($_sliRow['name'] ?? ''));
    if ($_sliName !== '') {
        $_sliItem['name'] = $_sliName;
    }

    $_sliElements[] = $_sliItem;
}

if ($_sliElements === []) {
    unset($_schemaLocationList, $_sliSiteUrl, $_sliElements, $_sliSeenSlugs, $_sliPosition, $_sliRow, $_sliSlug, $_sliUrl, $_sliItem, $_sliName);
    return;
}

$_sliSchema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'itemListElement' => $_sliElements,
];

$_sliJson = json_encode(
    $_sliSchema,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);

if ($_sliJson === false) {
    unset($_schemaLocationList, $_sliSiteUrl, $_sliElements, $_sliSeenSlugs, $_sliPosition, $_sliRow, $_sliSlug, $_sliUrl, $_sliItem, $_sliName, $_sliSchema, $_sliJson);
    return;
}

echo '<script type="application/ld+json">' . "\n" . $_sliJson . "\n" . '</script>' . "\n";

unset($_schemaLocationList, $_sliSiteUrl, $_sliElements, $_sliSeenSlugs, $_sliPosition, $_sliRow, $_sliSlug, $_sliUrl, $_sliItem, $_sliName, $_sliSchema, $_sliJson);
