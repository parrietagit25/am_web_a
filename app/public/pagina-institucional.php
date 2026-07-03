<?php
/**
 * Páginas institucionales del pie de página (grupo).
 */

/**
 * @return list<array{question:string,answer:string}>
 */
function am_institutional_faq_from_html(string $html): array
{
    $items = [];
    if (trim($html) === '') {
        return $items;
    }

    if (!preg_match_all('/<strong[^>]*>(.*?)<\/strong>(.*?(?=<strong[^>]*>|$))/is', $html, $matches, PREG_SET_ORDER)) {
        return $items;
    }

    foreach ($matches as $match) {
        $question = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $answer = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($question !== '' && $answer !== '') {
            $items[] = ['question' => $question, 'answer' => $answer];
        }
    }

    return $items;
}

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
require_once __DIR__ . '/../includes/article-content.php';
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
                <?php echo renderRacArticleContent($page['content_html'] ?? ''); ?>
            </div>
        </div>
    </div>
</section>

<?php
if ($key === 'faq') {
    $_sfItems = am_institutional_faq_from_html((string) ($page['content_html'] ?? ''));
    if ($_sfItems !== []) {
        require_once __DIR__ . '/../includes/schema-faq.php';
    }
    unset($_sfItems);
}
?>

<style>
.institutional-content img { max-width: 100%; height: auto; border-radius: 8px; }
.institutional-content section { margin-bottom: 0; }
.institutional-content h1, .institutional-content h2, .institutional-content h3 { color: #0b1f6b; margin-top: 1.5rem; }
.institutional-content h1:first-child, .institutional-content h2:first-child { margin-top: 0; }
.institutional-content ul, .institutional-content ol { padding-left: 1.25rem; }
.institutional-content p, .institutional-content li { line-height: 1.75; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
