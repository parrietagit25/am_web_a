<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/HeaderBannerService.php';
require_once __DIR__ . '/../app/services/UnitContentService.php';
require_once __DIR__ . '/../app/services/ContentService.php';
require_once __DIR__ . '/../app/includes/business-units-registry.php';

function adj03_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

if (!function_exists('esc')) {
    function esc(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function adj03_render_banner(array $config, string $innerHtml = '', array $overrides = []): string
{
    $hbConfig = $config;
    $hbSectionClass = 'hero-wrapper';
    $hbSectionId = 'adj03-test';
    $hbInnerHtml = $innerHtml;
    $hbSectionExtraStyle = '';
    $hbSkipContainer = false;
    $hbBackgroundOverlay = '';
    $hbDefaultLinkUrl = $overrides['default_link_url'] ?? '';
    $hbDefaultLinkText = $overrides['default_link_text'] ?? '';

    ob_start();
    require __DIR__ . '/../app/includes/render-header-banner.php';

    return (string) ob_get_clean();
}

$legacy = HeaderBannerService::normalize([
    'mode' => HeaderBannerService::MODE_STATIC,
    'image_url' => '/assets/legacy.webp',
]);
adj03_assert($legacy['enabled'] === true, 'Legacy banners default to enabled');
adj03_assert($legacy['link_url'] === '', 'Legacy banners default to no link');

$configured = HeaderBannerService::normalize([
    'enabled' => false,
    'mode' => HeaderBannerService::MODE_SLIDER,
    'image_url' => '/assets/static.webp',
    'alt' => 'Banner institucional',
    'title' => 'Título',
    'subtitle' => 'Subtítulo',
    'link_text' => 'Conocer más',
    'link_url' => '/leasing.php',
    'slider' => [
        'slides' => [[
            'enabled' => false,
            'image_url' => '/assets/slide.webp',
            'alt' => 'Slide',
            'title' => 'Slide title',
            'subtitle' => 'Slide subtitle',
            'link_text' => 'Ver slide',
            'link_url' => 'https://example.com/info',
        ]],
    ],
]);
adj03_assert($configured['enabled'] === false, 'Configured banner can be disabled');
adj03_assert($configured['alt'] === 'Banner institucional', 'Static alt is preserved');
adj03_assert($configured['link_url'] === '/leasing.php', 'Internal link is preserved');
adj03_assert($configured['slider']['slides'][0]['enabled'] === false, 'Slide state is preserved');
adj03_assert($configured['slider']['slides'][0]['link_url'] === 'https://example.com/info', 'Slide HTTPS link is preserved');

$validLinks = ['/leasing.php', '#soluciones', '#configuración', 'https://automarket.com.pa/'];
foreach ($validLinks as $link) {
    adj03_assert(HeaderBannerService::sanitizeLinkUrl($link) === $link, "Valid link accepted: {$link}");
}

$invalidLinks = [
    'javascript:alert(1)',
    'data:text/html,test',
    'http://example.com',
    '//evil.example',
    '"><script>alert(1)</script>',
];
foreach ($invalidLinks as $link) {
    adj03_assert(HeaderBannerService::sanitizeLinkUrl($link) === '', "Invalid link rejected: {$link}");
}
adj03_assert(HeaderBannerService::sanitizeImageUrl('/assets/banner.webp') === '/assets/banner.webp', 'Internal image URL accepted');
adj03_assert(HeaderBannerService::sanitizeImageUrl('https://example.com/banner.webp') === 'https://example.com/banner.webp', 'HTTPS image URL accepted');
adj03_assert(HeaderBannerService::sanitizeImageUrl('javascript:alert(1)') === '', 'Unsafe image URL rejected');
adj03_assert(HeaderBannerService::sanitizeImageUrl('#banner') === '', 'Anchor is not accepted as image URL');

$pageHeader = UnitContentService::normalizePageHeader([
    'banner' => '/assets/page.webp',
    'enabled' => false,
    'alt' => 'Novedades Automarket',
    'button_text' => 'Leer noticias',
    'button_url' => '/noticias.php',
], 'news');
adj03_assert($pageHeader['enabled'] === false, 'Page header can be disabled');
adj03_assert($pageHeader['alt'] === 'Novedades Automarket', 'Page header alt is preserved');
adj03_assert($pageHeader['button_text'] === 'Leer noticias', 'Page header button text is preserved');
adj03_assert($pageHeader['button_url'] === '/noticias.php', 'Page header internal URL is preserved');

$invalidPageHeader = UnitContentService::normalizePageHeader([
    'button_text' => 'Ataque',
    'button_url' => 'javascript:alert(1)',
], 'blog');
adj03_assert($invalidPageHeader['button_url'] === '', 'Page header rejects unsafe URLs');
adj03_assert($invalidPageHeader['button_text'] === '', 'Button without valid URL is removed');

$disabledEmpty = adj03_render_banner(['enabled' => false]);
adj03_assert(trim($disabledEmpty) === '', 'Disabled empty banner leaves no container');

$disabledWithHeading = adj03_render_banner(['enabled' => false], '<h1>Título principal</h1>');
adj03_assert(str_contains($disabledWithHeading, '<h1>Título principal</h1>'), 'Disabled banner preserves page H1');
adj03_assert(!str_contains($disabledWithHeading, '<img'), 'Disabled banner renders no image');

$staticHtml = adj03_render_banner([
    'enabled' => true,
    'mode' => 'static',
    'image_url' => '/assets/banner.webp',
    'alt' => 'Banner accesible',
    'title' => 'Promoción',
    'subtitle' => 'Descripción',
    'link_text' => 'Reservar',
    'link_url' => '/reservar.php',
]);
adj03_assert(str_contains($staticHtml, 'alt="Banner accesible"'), 'Static banner exposes alt text');
adj03_assert(str_contains($staticHtml, '<h2'), 'Static banner uses H2');
adj03_assert(str_contains($staticHtml, 'href="/reservar.php"'), 'Static banner renders semantic link');
adj03_assert(!str_contains($staticHtml, 'onclick='), 'Static banner avoids onclick navigation');
adj03_assert(substr_count($staticHtml, 'class="container') === 1, 'New caption without legacy content uses one container');

$legacyPriorityHtml = adj03_render_banner([
    'enabled' => true,
    'mode' => 'static',
    'image_url' => '/assets/banner.webp',
    'title' => 'Título nuevo',
    'subtitle' => 'Subtítulo nuevo',
    'link_text' => 'CTA nuevo',
    'link_url' => '/nuevo.php',
], '<h1>Hero legacy</h1><a class="btn" href="/legacy.php">CTA legacy</a>');
adj03_assert(str_contains($legacyPriorityHtml, 'Hero legacy'), 'Legacy content is preserved');
adj03_assert(str_contains($legacyPriorityHtml, 'CTA legacy'), 'Legacy CTA is preserved');
adj03_assert(!str_contains($legacyPriorityHtml, 'hb-static-caption'), 'New caption is suppressed when legacy content exists');
adj03_assert(!str_contains($legacyPriorityHtml, 'href="/nuevo.php"'), 'New CTA is suppressed when legacy content exists');
adj03_assert(substr_count($legacyPriorityHtml, 'class="container') === 1, 'Legacy content and new caption do not create duplicate containers');
adj03_assert(substr_count($legacyPriorityHtml, 'class="btn') === 1, 'Legacy content and new caption do not create duplicate CTAs');

$sliderHtml = adj03_render_banner([
    'enabled' => true,
    'mode' => 'slider',
    'slider' => [
        'slides' => [
            ['enabled' => false, 'image_url' => '/assets/off.webp', 'title' => 'Oculto'],
            ['enabled' => true, 'image_url' => '/assets/on.webp', 'title' => 'Visible', 'link_url' => '#destino'],
        ],
    ],
]);
adj03_assert(!str_contains($sliderHtml, 'Oculto'), 'Disabled slide is not rendered');
adj03_assert(str_contains($sliderHtml, 'Visible'), 'Enabled slide is rendered');
adj03_assert(str_contains($sliderHtml, 'href="#destino"'), 'Slider link is keyboard-accessible');

$allDisabledSlider = adj03_render_banner([
    'enabled' => true,
    'mode' => 'slider',
    'slider' => [
        'slides' => [
            ['enabled' => false, 'image_url' => '/assets/off.webp', 'title' => 'Oculto'],
        ],
    ],
], '<h1>Hero conservado</h1>');
adj03_assert(str_contains($allDisabledSlider, '<h1>Hero conservado</h1>'), 'Empty active slider preserves page H1');
adj03_assert(!str_contains($allDisabledSlider, '/assets/off.webp'), 'Disabled slide does not fall back as static image');

$contentService = new ContentService();
$postSiteData = [
    'demo' => [
        'image_url' => '/assets/legacy.webp',
    ],
];
$invalidPostError = HeaderBannerService::applyPostAtPath(
    $postSiteData,
    ['demo'],
    'hb_demo',
    [
        'hb_demo_enabled' => '1',
        'hb_demo_mode' => 'static',
        'hb_demo_static_url' => '/assets/legacy.webp',
        'hb_demo_link_url' => 'javascript:alert(1)',
    ],
    [],
    $contentService
);
adj03_assert($invalidPostError !== null, 'POST rejects unsafe banner URL');

$resetError = HeaderBannerService::applyPostAtPath(
    $postSiteData,
    ['demo'],
    'hb_demo',
    [
        'hb_demo_enabled' => '0',
        'hb_demo_mode' => 'static',
        'hb_demo_static_url' => '/assets/legacy.webp',
        'hb_demo_remove_static' => '1',
        'hb_demo_title' => '',
        'hb_demo_subtitle' => '',
        'hb_demo_link_url' => '',
    ],
    [],
    $contentService
);
adj03_assert($resetError === null, 'Disabled banner can remove its image');
adj03_assert($postSiteData['demo']['header_banner']['enabled'] === false, 'Disabled state persists');
adj03_assert($postSiteData['demo']['header_banner']['image_url'] === '', 'Removed image is empty');
adj03_assert($postSiteData['demo']['image_url'] === '', 'Legacy image key stays synchronized');

$customUnit = am_normalize_custom_business_unit('demo', [
    'header_banner' => [
        'enabled' => false,
        'mode' => 'static',
        'image_url' => '/assets/custom.webp',
    ],
]);
adj03_assert(isset($customUnit['header_banner']), 'Custom unit preserves header_banner');
adj03_assert($customUnit['header_banner']['enabled'] === false, 'Custom unit preserves banner state');

echo "AM-ADJ-03 unit tests: OK\n";
