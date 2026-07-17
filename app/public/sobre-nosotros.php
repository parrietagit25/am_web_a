<?php
/**
 * Sobre Nosotros compartido para unidades sin página legacy dedicada.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/UnitAboutService.php';
require_once __DIR__ . '/../includes/business-units-registry.php';

$contentService = new ContentService();
$siteData = $contentService->getAll();
$businessUnits = am_merge_business_units($siteData['global']['business_units'] ?? []);
$requestedUnit = strtolower(trim((string) ($_GET['unit'] ?? '')));
$allowedOfficial = ['rentacar', 'seminuevos', 'leasing'];
$isAllowedCustom = isset($businessUnits[$requestedUnit]) && !empty($businessUnits[$requestedUnit]['is_custom']);

function am_render_unit_about_404(): never
{
    global $activeUnit, $seoOverride;
    http_response_code(404);
    $activeUnit = 'rentacar';
    $seoOverride = ['title' => 'Página no encontrada | Automarket', 'robots' => 'noindex,nofollow'];
    require __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Página no encontrada</h1><a href="/rent-a-car.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ((!in_array($requestedUnit, $allowedOfficial, true) && !$isAllowedCustom)
    || !isset($businessUnits[$requestedUnit])) {
    am_render_unit_about_404();
}

$aboutPage = UnitAboutService::resolve($siteData, $requestedUnit);
if ($aboutPage === null) {
    am_render_unit_about_404();
}

$activeUnit = $requestedUnit;
$unitLabel = UnitContentService::unitLabel($siteData, $requestedUnit);
$pageTitle = trim((string) ($aboutPage['title'] ?? '')) ?: ('Sobre ' . $unitLabel);
$pageSubtitle = trim((string) ($aboutPage['subtitle'] ?? ''));
$seoOverride = [
    'title' => $pageTitle . ' | Automarket',
    'description' => $pageSubtitle !== '' ? $pageSubtitle : $pageTitle,
];
require_once __DIR__ . '/../includes/header.php';

try {
    $safeBody = UnitAboutService::sanitizeBodyHtml((string) ($aboutPage['body_html'] ?? ''));
} catch (InvalidArgumentException | RuntimeException $e) {
    $safeBody = '';
}
$imageUrl = trim((string) ($aboutPage['main_image_url'] ?? ''));
$imageAlt = trim((string) ($aboutPage['main_image_alt'] ?? '')) ?: $pageTitle;
$ctaText = trim((string) ($aboutPage['cta_text'] ?? ''));
$ctaUrl = UnitAboutService::sanitizeCtaUrl((string) ($aboutPage['cta_url'] ?? ''));
?>
<style>
.unit-about-main { padding: clamp(48px, 7vw, 90px) 0; }
.unit-about-image { width: 100%; max-height: 560px; object-fit: cover; border-radius: 18px; }
.unit-about-content { overflow-wrap: anywhere; }
.unit-about-content img { max-width: 100%; height: auto; }
.unit-about-content a:focus-visible, .unit-about-cta:focus-visible { outline: 3px solid currentColor; outline-offset: 3px; }
</style>

<main class="unit-about-main">
    <div class="container">
        <header class="mx-auto text-center mb-5" style="max-width: 850px;">
            <h1 class="display-5 fw-bold text-navy font-montserrat"><?php echo esc($pageTitle); ?></h1>
            <?php if ($pageSubtitle !== ''): ?>
                <p class="lead text-muted mb-0"><?php echo esc($pageSubtitle); ?></p>
            <?php endif; ?>
        </header>

        <?php if ($imageUrl !== ''): ?>
            <figure class="mb-5">
                <img src="<?php echo esc($imageUrl); ?>" alt="<?php echo esc($imageAlt); ?>" class="unit-about-image" loading="lazy">
            </figure>
        <?php endif; ?>

        <?php if ($safeBody !== ''): ?>
            <article class="unit-about-content mx-auto" style="max-width: 920px;">
                <?php echo $safeBody; ?>
            </article>
        <?php endif; ?>

        <?php if ($ctaText !== '' && $ctaUrl !== ''): ?>
            <div class="text-center mt-5">
                <a href="<?php echo esc($ctaUrl); ?>" class="btn btn-primary btn-lg unit-about-cta"><?php echo esc($ctaText); ?></a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
