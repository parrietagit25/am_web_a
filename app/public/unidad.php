<?php
/**
 * Página genérica para unidades de negocio personalizadas.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/business-units-registry.php';
require_once __DIR__ . '/../includes/article-content.php';

$contentService = new ContentService();
$siteGlobal = $contentService->get('global');
$units = am_merge_business_units($siteGlobal['business_units'] ?? []);
$unitKey = am_normalize_business_unit_key((string) ($_GET['u'] ?? ''));
$pageSlug = am_normalize_custom_unit_page_slug((string) ($_GET['p'] ?? ''));

if ($unitKey === '' || !isset($units[$unitKey]) || am_is_builtin_business_unit($unitKey)) {
    http_response_code(404);
    $activeUnit = 'rentacar';
    $seoOverride = ['title' => 'Unidad no encontrada | Automarket', 'robots' => 'noindex,nofollow'];
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Unidad no encontrada</h1><a href="/rent-a-car.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$unit = $units[$unitKey];
if ($pageSlug !== '' && !isset($unit['pages'][$pageSlug])) {
    $unit['pages'][$pageSlug] = [
        'label' => strtoupper(str_replace('-', ' ', $pageSlug)),
        'heroTitle' => '',
        'heroSubtitle' => '',
        'hero_image_url' => '',
        'body_html' => '',
    ];
}

$content = am_custom_unit_page_content($unit, $pageSlug);
$activeUnit = $unitKey;
$heroTitle = $content['heroTitle'] !== '' ? $content['heroTitle'] : (string) ($unit['label'] ?? '');
$heroSubtitle = $content['heroSubtitle'];
$heroImage = $content['hero_image_url'];
if ($heroImage === '') {
    $heroImage = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1920&auto=format&fit=crop';
}
$bodyHtml = trim($content['body_html']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero-wrapper text-center text-lg-start d-flex align-items-center" style="background: linear-gradient(135deg, rgba(8,16,38,0.82), rgba(8,16,38,0.45)), url('<?php echo esc($heroImage); ?>') no-repeat center center; background-size: cover; min-height: 360px;">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-8 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                <h1 class="display-4 fw-bold mb-3 font-montserrat"><?php echo esc($heroTitle); ?></h1>
                <?php if ($heroSubtitle !== ''): ?>
                <p class="fs-5 mb-0 opacity-90 font-poppins"><?php echo esc($heroSubtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="bg-white border rounded-4 shadow-sm p-4 p-md-5 font-poppins custom-unit-content">
                <?php if ($bodyHtml !== ''): ?>
                    <?php echo renderRacArticleContent($bodyHtml); ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Contenido de esta página. Edítelo desde el administrador en la sección de esta unidad de negocio.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.custom-unit-content img { max-width: 100%; height: auto; border-radius: 8px; }
.custom-unit-content h1, .custom-unit-content h2, .custom-unit-content h3 { color: #0b1f6b; margin-top: 1.5rem; }
.custom-unit-content h1:first-child, .custom-unit-content h2:first-child { margin-top: 0; }
.custom-unit-content ul, .custom-unit-content ol { padding-left: 1.25rem; }
.custom-unit-content p, .custom-unit-content li { line-height: 1.75; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
