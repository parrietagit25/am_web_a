<?php
/**
 * Página experimental (Maestro → Experimental) — URL pública /px/{slug}.
 * Independiente de /p/{slug} (Editor de Páginas). Base para page builder.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/ExperimentalPageService.php';
require_once __DIR__ . '/../services/GenericPageService.php';
require_once __DIR__ . '/../services/UnitContentService.php';
require_once __DIR__ . '/../services/SeoService.php';
require_once __DIR__ . '/../includes/business-units-registry.php';

$contentService = new ContentService();
$siteData = $contentService->getAll();

$slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
if ($slug === '' && !empty($_SERVER['PATH_INFO'])) {
    $slug = strtolower(trim((string) $_SERVER['PATH_INFO'], '/'));
}

function am_render_experimental_page_404(): never
{
    global $activeUnit, $seoOverride;

    http_response_code(404);
    $activeUnit = $activeUnit ?: 'rentacar';
    $seoOverride = [
        'title' => 'Página no encontrada | Automarket',
        'robots' => 'noindex,nofollow',
    ];
    require __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Página no encontrada</h1><a href="/rent-a-car.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$businessUnits = am_merge_business_units($siteData['global']['business_units'] ?? []);
$requestedUnit = strtolower(trim((string) ($_GET['unit'] ?? 'rentacar')));
$activeUnit = (isset($businessUnits[$requestedUnit]) && UnitContentService::isSupportedUnit($requestedUnit, $siteData))
    ? $requestedUnit
    : 'rentacar';

if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    am_render_experimental_page_404();
}

$page = ExperimentalPageService::findBySlug($siteData, $slug, true);
if ($page === null) {
    am_render_experimental_page_404();
}

$pageTitle = trim((string) ($page['title'] ?? '')) ?: 'Automarket';
$pageSubtitle = trim((string) ($page['subtitle'] ?? ''));
$pageHtml = GenericPageService::sanitizeContentHtml((string) ($page['content_html'] ?? ''));
$unitLabel = UnitContentService::unitLabel($siteData, $activeUnit);

$seoDescription = $pageSubtitle !== ''
    ? $pageSubtitle
    : trim(preg_replace('/\s+/', ' ', strip_tags($pageHtml)) ?? '');
if (mb_strlen($seoDescription, 'UTF-8') > 155) {
    $seoDescription = mb_substr($seoDescription, 0, 152, 'UTF-8') . '...';
}
$seoOverride = [
    'title' => $pageTitle . ' | Automarket',
    'description' => $seoDescription !== '' ? $seoDescription : $pageTitle,
    'canonical' => SeoService::canonicalBaseFromSiteData($siteData) . ExperimentalPageService::publicPath($slug),
    'robots' => 'noindex,nofollow',
];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="<?php echo esc(UnitContentService::unitHomePath($siteData, $activeUnit)); ?>" class="text-danger text-decoration-none fw-semibold"><?php echo esc($unitLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($pageTitle); ?></li>
            </ol>
        </nav>
        <p class="small text-warning fw-semibold mb-2"><i class="bi bi-flask me-1"></i>Página experimental</p>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;"><?php echo esc($pageTitle); ?></h1>
        <?php if ($pageSubtitle !== ''): ?>
            <p class="text-muted font-poppins mt-2 mb-0"><?php echo esc($pageSubtitle); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="container pt-4 pb-5 mb-5">
    <article class="generic-page-content font-poppins text-navy fs-6 lh-lg" data-experimental-page="1">
        <?php echo $pageHtml; ?>
    </article>
</section>

<style>
    .generic-page-content { overflow-wrap: anywhere; }
    .generic-page-content h2,
    .generic-page-content h3,
    .generic-page-content h4 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: var(--navy);
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    .generic-page-content h2 { font-size: 1.45rem; }
    .generic-page-content h3 { font-size: 1.15rem; color: var(--theme-primary); font-weight: 600; }
    .generic-page-content p {
        margin-bottom: 1.25rem;
        color: #4a5568;
    }
    .generic-page-content ul,
    .generic-page-content ol {
        margin-bottom: 1.5rem;
        padding-left: 20px;
    }
    .generic-page-content li {
        margin-bottom: 0.5rem;
        color: #4a5568;
    }
    .generic-page-content img { max-width: 100%; height: auto; }
    .generic-page-content table {
        width: 100%;
        display: block;
        overflow-x: auto;
    }
    .generic-page-content a:focus-visible {
        outline: 3px solid currentColor;
        outline-offset: 3px;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
