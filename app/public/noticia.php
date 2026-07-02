<?php
/**
 * Detalle de artículo por unidad de negocio
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/unit-content-frontend.php';

$preContent = new ContentService();
$unitKey = unit_content_resolve_unit_key($preContent, 'rentacar');
$activeUnit = $unitKey;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/article-content.php';

$newsId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = trim($_GET['type'] ?? 'news');
if (!in_array($type, ['latest', 'blog', 'news'], true)) {
    $type = 'news';
}

$article = unit_content_find_article($contentService, $unitKey, $type, $newsId);
$unitHome = unit_content_unit_home_url($contentService, $unitKey);
$unitQuery = $unitKey !== 'rentacar' ? ('?unit=' . rawurlencode($unitKey)) : '';

if (!$article) {
    echo "<script>window.location.href='" . addslashes($unitHome) . "';</script>";
    exit;
}

if ($type === 'blog') {
    $backUrl = '/blog.php' . $unitQuery;
    $backLabel = 'Volver al Blog';
} elseif ($type === 'latest') {
    $backUrl = '/contenido-reciente.php' . $unitQuery;
    $backLabel = 'Volver a Novedades';
} else {
    $backUrl = '/noticias.php' . $unitQuery;
    $backLabel = 'Volver a Noticias';
}
?>

<style>
.article-rich-content img { max-width: 100%; height: auto; }
.article-rich-content section { margin-bottom: 1.5rem; }
.article-rich-content h1, .article-rich-content h2, .article-rich-content h3 { margin-top: 1rem; margin-bottom: .75rem; }
</style>

<section class="container py-5 mt-4">
    <div class="text-center mb-4">
        <a href="<?php echo esc($backUrl); ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i> <?php echo esc($backLabel); ?>
        </a>
        <h1 class="display-5 fw-bold text-navy font-montserrat px-3"><?php echo esc($article['title']); ?></h1>
        <small class="text-muted font-poppins d-block mt-2"><i class="bi bi-calendar-event me-1"></i> Publicado el <?php echo esc($article['date']); ?></small>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card border-1 rounded-4 shadow-sm overflow-hidden bg-white p-3 p-md-5">
                <?php if (!empty($article['banner'])): ?>
                    <div class="mb-5 rounded-3 overflow-hidden text-center">
                        <img src="<?php echo esc($article['banner']); ?>" alt="<?php echo esc($article['title']); ?>" class="img-fluid w-100" style="max-height: 480px; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <div class="px-2 px-md-4">
                    <?php if (!empty($article['subheading'])): ?>
                        <h3 class="fw-bold text-navy mb-4 font-montserrat"><?php echo esc($article['subheading']); ?></h3>
                    <?php endif; ?>

                    <?php if (!empty($article['description'])): ?>
                        <div class="fs-5 text-muted font-poppins mb-4 lh-lg article-rich-content" style="text-align: justify;">
                            <?php echo renderRacArticleContent($article['description']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($article['content'])): ?>
                        <div class="article-body-content article-rich-content text-navy font-poppins fs-6 lh-lg" style="text-align: justify;">
                            <?php echo renderRacArticleContent($article['content']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
