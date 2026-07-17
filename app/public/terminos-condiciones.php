<?php
/**
 * Términos y condiciones por unidad.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/UnitTermsService.php';
require_once __DIR__ . '/../services/SeoService.php';
require_once __DIR__ . '/../includes/business-units-registry.php';

$contentService = new ContentService();
$siteData = $contentService->getAll();
$businessUnits = am_merge_business_units($siteData['global']['business_units'] ?? []);
$requestedUnit = strtolower(trim((string) ($_GET['unit'] ?? 'rentacar')));

function am_render_unit_terms_404(): never
{
    global $activeUnit, $seoOverride;

    http_response_code(404);
    $activeUnit = 'rentacar';
    $seoOverride = [
        'title' => 'Página no encontrada | Automarket',
        'robots' => 'noindex,nofollow',
    ];
    require __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Página no encontrada</h1><a href="/rent-a-car.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if (!isset($businessUnits[$requestedUnit])
    || !UnitContentService::isSupportedUnit($requestedUnit, $siteData)) {
    am_render_unit_terms_404();
}

$termsPage = UnitTermsService::resolve($siteData, $requestedUnit);
if ($termsPage === null) {
    am_render_unit_terms_404();
}

try {
    $termsHtml = UnitTermsService::sanitizeBodyHtml((string) ($termsPage['body_html'] ?? ''));
} catch (InvalidArgumentException | RuntimeException $e) {
    am_render_unit_terms_404();
}
if ($termsHtml === '') {
    am_render_unit_terms_404();
}

$activeUnit = $requestedUnit;
$unitLabel = UnitContentService::unitLabel($siteData, $requestedUnit);
$pageTitle = trim((string) ($termsPage['title'] ?? '')) ?: 'Términos y Condiciones';
$pageSubtitle = trim((string) ($termsPage['subtitle'] ?? ''));
$seoOverride = [
    'title' => $pageTitle . ' | Automarket',
    'description' => $pageSubtitle !== '' ? $pageSubtitle : $pageTitle . ' de ' . $unitLabel . '.',
    'canonical' => SeoService::canonicalBaseFromSiteData($siteData)
        . UnitTermsService::publicUrl($requestedUnit),
];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="<?php echo esc(UnitContentService::unitHomePath($siteData, $requestedUnit)); ?>" class="text-danger text-decoration-none fw-semibold"><?php echo esc($unitLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Términos y Condiciones</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;"><?php echo esc($pageTitle); ?></h1>
        <?php if ($pageSubtitle !== ''): ?>
            <p class="text-muted font-poppins mt-2 mb-0"><?php echo esc($pageSubtitle); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-12">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                <article class="terms-content font-poppins text-navy fs-6 lh-lg">
                    <?php echo $termsHtml; ?>
                </article>
            </div>
        </div>
    </div>
</section>

<style>
    .terms-content { overflow-wrap: anywhere; }
    .terms-content .subtitulo2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: var(--navy);
        font-size: 1.45rem;
        margin-top: 2rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 8px;
    }
    .terms-content .subtitulo3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        color: var(--theme-primary);
        font-size: 1.15rem;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .terms-content p {
        margin-bottom: 1.25rem;
        color: #4a5568;
        text-align: justify;
    }
    .terms-content ul {
        margin-bottom: 1.5rem;
        padding-left: 20px;
    }
    .terms-content li {
        margin-bottom: 0.5rem;
        color: #4a5568;
    }
    .terms-content .lista-puntos-rojos li {
        list-style-type: none;
        position: relative;
        padding-left: 20px;
    }
    .terms-content .lista-puntos-rojos li::before {
        content: "•";
        color: var(--theme-primary);
        font-weight: bold;
        font-size: 1.25rem;
        position: absolute;
        left: 0;
        top: -2px;
    }
    .terms-content table {
        width: 100%;
        display: block;
        overflow-x: auto;
    }
    .terms-content a:focus-visible {
        outline: 3px solid currentColor;
        outline-offset: 3px;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
