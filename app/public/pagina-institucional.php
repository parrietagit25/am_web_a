<?php
/**
 * Páginas institucionales del pie de página (grupo).
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/FooterService.php';

$contentService = new ContentService();
$footerService = new FooterService($contentService);

$p = trim($_GET['p'] ?? '');
$key = str_replace('-', '_', $p);
if (!in_array($key, FooterService::PAGE_KEYS, true)) {
    http_response_code(404);
    $seoOverride = ['title' => 'Página no encontrada | Automarket', 'robots' => 'noindex,nofollow'];
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Página no encontrada</h1><a href="/rent-a-car.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$page = $footerService->getPage($key);
if (!$page) {
    http_response_code(404);
    $seoOverride = ['title' => 'Página no disponible | Automarket', 'robots' => 'noindex,nofollow'];
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Contenido no disponible</h1></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$seoOverride = [
    'title' => ($page['title'] ?? 'Automarket') . ' | Automarket',
    'description' => strip_tags(substr($page['content_html'] ?? '', 0, 160)),
    'robots' => 'index,follow',
];

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Automarket</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($page['title']); ?></li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0"><?php echo esc($page['title']); ?></h1>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="bg-white border rounded-4 shadow-sm p-4 p-md-5 institutional-content font-poppins">
                <?php echo $page['content_html']; ?>
            </div>
        </div>
    </div>
</section>

<style>
.institutional-content img { max-width: 100%; height: auto; border-radius: 8px; }
.institutional-content h2, .institutional-content h3 { color: #0b1f6b; margin-top: 1.5rem; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
