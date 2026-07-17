<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/UnitAboutService.php';

function adj06_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$defaults = UnitAboutService::normalize([]);
adj06_assert($defaults['published'] === false, 'Missing page defaults to unpublished');
adj06_assert($defaults['title'] === '' && $defaults['body_html'] === '', 'Defaults never invent content');

$safe = '<h2>Historia</h2><p>Texto <strong>real</strong>.</p><ul><li>Uno</li></ul><a href="/contactos.php">Contacto</a>';
adj06_assert(UnitAboutService::sanitizeBodyHtml($safe) === $safe, 'Allowed editorial HTML is preserved');
foreach ([
    '<script>alert(1)</script>',
    '<iframe src="https://example.com"></iframe>',
    '<form><input></form>',
    '<p onclick="alert(1)">Texto</p>',
    '<a href="javascript:alert(1)">Texto</a>',
] as $unsafe) {
    $rejected = false;
    try {
        UnitAboutService::sanitizeBodyHtml($unsafe);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj06_assert($rejected, 'Executable HTML is rejected');
}

$siteData = [
    'homepage' => ['about_page' => [
        'published' => true,
        'title' => 'Rent A Car',
        'subtitle' => 'Movilidad',
        'body_html' => '<p>Contenido RAC.</p>',
        'main_image_url' => '/assets/img/rac.webp',
        'main_image_alt' => 'Equipo RAC',
        'cta_text' => 'Contactar',
        'cta_url' => '/contactos.php?unit=rentacar',
    ]],
    'seminuevos' => [],
    'leasing' => ['about_page' => [
        'published' => false,
        'title' => 'Leasing',
    ]],
    'renting' => [
        'about_page' => ['published' => true, 'title' => 'No debe ganar', 'body_html' => '<p>Nuevo</p>'],
        'sobre_nosotros' => [
            'published' => true,
            'page_title' => 'Renting legacy',
            'heading' => 'Trayectoria',
            'intro_html' => '<p>Contenido legacy.</p>',
            'gallery' => [],
        ],
    ],
    'taller' => ['sobre_nosotros' => [
        'published' => false,
        'page_title' => 'Taller',
        'right_content' => '<p>Taller legacy.</p>',
    ]],
    'global' => ['business_units' => [
        'unidad_demo' => ['about_page' => [
            'published' => true,
            'title' => 'Unidad Demo',
            'body_html' => '<p>Contenido propio.</p>',
        ]],
        'sin_contenido' => [],
    ]],
];

$rac = UnitAboutService::resolve($siteData, 'rentacar');
adj06_assert(($rac['source'] ?? '') === 'about_page' && ($rac['title'] ?? '') === 'Rent A Car', 'Official common page resolves');
adj06_assert(UnitAboutService::resolve($siteData, 'seminuevos') === null, 'Official unit without content is absent');
adj06_assert(UnitAboutService::resolve($siteData, 'leasing') === null, 'Unpublished common page is absent');

$renting = UnitAboutService::resolve($siteData, 'renting');
adj06_assert(($renting['source'] ?? '') === 'legacy_renting', 'Renting legacy has priority');
adj06_assert(($renting['body_html'] ?? '') === '<p>Contenido legacy.</p>', 'Renting legacy body is preserved');
adj06_assert(UnitAboutService::resolve($siteData, 'taller') === null, 'Explicitly unpublished legacy page is absent');

$custom = UnitAboutService::resolve($siteData, 'unidad_demo');
adj06_assert(($custom['source'] ?? '') === 'about_page' && ($custom['title'] ?? '') === 'Unidad Demo', 'Custom page is isolated');
adj06_assert(UnitAboutService::resolve($siteData, 'sin_contenido') === null, 'Custom without page is absent');
adj06_assert(UnitAboutService::resolve($siteData, 'inexistente') === null, 'Unknown unit is absent');

adj06_assert(UnitAboutService::sanitizeCtaUrl('/contactos.php') === '/contactos.php', 'Internal CTA accepted');
adj06_assert(UnitAboutService::sanitizeCtaUrl('#contacto') === '#contacto', 'Anchor CTA accepted');
adj06_assert(UnitAboutService::sanitizeCtaUrl('https://example.com') === 'https://example.com', 'HTTPS CTA accepted');
adj06_assert(UnitAboutService::sanitizeCtaUrl('javascript:alert(1)') === '', 'Executable CTA rejected');

adj06_assert(UnitAboutService::publicUrl('rentacar') === '/sobre-nosotros.php?unit=rentacar', 'Public URL is deterministic');

echo "AM-ADJ-06 unit about tests: OK\n";
