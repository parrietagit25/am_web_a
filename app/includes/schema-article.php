<?php
/**
 * Partial: Schema.org Article / BlogPosting / NewsArticle JSON-LD
 *   $_saArticle   (array)  — ítem normalizado de contenido editorial.
 *   $_saType      (string) — latest|blog|news
 *   $_saCanonical (string) — URL absoluta canónica del artículo.
 *   $_saPublisher (string) — nombre del sitio / organización.
 */

require_once __DIR__ . '/../services/UnitContentService.php';
require_once __DIR__ . '/schema-organization-helper.php';

$_saArticle   = $_saArticle ?? [];
$_saType      = trim((string) ($_saType ?? 'news'));
$_saCanonical = trim((string) ($_saCanonical ?? ''));
$_saPublisher = trim((string) ($_saPublisher ?? 'Automarket Panamá'));

$_saHeadline = trim((string) ($_saArticle['title'] ?? ''));
if ($_saHeadline === '' || $_saCanonical === '') {
    unset($_saArticle, $_saType, $_saCanonical, $_saPublisher, $_saHeadline);
    return;
}

$_saSchemaType = 'Article';
if ($_saType === 'blog') {
    $_saSchemaType = 'BlogPosting';
} elseif ($_saType === 'news') {
    $_saSchemaType = 'NewsArticle';
}

$_saSchema = [
    '@context' => 'https://schema.org',
    '@type'    => $_saSchemaType,
    'headline' => $_saHeadline,
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id'   => $_saCanonical,
    ],
    'url' => $_saCanonical,
    'publisher' => [
        '@type' => 'Organization',
        '@id'   => am_schema_organization_id(am_schema_canonical_base()),
        'name'  => $_saPublisher,
    ],
];

$_saDescription = UnitContentService::articleDescription($_saArticle);
if ($_saDescription !== '') {
    $_saSchema['description'] = $_saDescription;
}

$_saDatePublished = UnitContentService::articleIsoDate($_saArticle);
if ($_saDatePublished !== null) {
    $_saSchema['datePublished'] = $_saDatePublished;
    $_saSchema['dateModified'] = $_saDatePublished;
}

$_saImage = trim((string) ($_saArticle['banner'] ?? ($_saArticle['thumbnail'] ?? '')));
if ($_saImage !== '') {
    if (str_starts_with($_saImage, '/')) {
        $_saImage = 'https://www.automarket.com.pa' . $_saImage;
    }
    $_saSchema['image'] = [$_saImage];
}

echo '<script type="application/ld+json">' . json_encode(
    $_saSchema,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
) . '</script>' . "\n";

unset($_saArticle, $_saType, $_saCanonical, $_saPublisher, $_saHeadline, $_saSchemaType, $_saSchema, $_saDescription, $_saDatePublished, $_saImage);
