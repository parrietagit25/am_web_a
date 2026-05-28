<?php
/**
 * Renting - Publicación (detalle)
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/renting-posts.php';

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = findRentingPostById($contentService, $postId);

if (!$article) {
    echo "<script>window.location.href='/renting.php';</script>";
    exit;
}
?>

<style>
.renting-publicacion-title { font-size: clamp(1.75rem, 4vw, 2.25rem); font-weight: 700; color: #111; }
.renting-publicacion-box { border: 1px solid #d8dde6; border-radius: 4px; background: #fff; }
.renting-publicacion-subheading { font-size: 1.35rem; font-weight: 700; color: #111; margin-bottom: 1.25rem; }
.renting-article-paragraph { color: #222; font-size: 1rem; line-height: 1.75; margin-bottom: 1.25rem; text-align: justify; }
.renting-check-icon { color: var(--theme-primary); font-size: 1.15rem; margin-top: 0.15rem; }
.renting-article-body section { margin-left: -1rem; margin-right: -1rem; }
@media (min-width: 768px) {
    .renting-article-body section { margin-left: -2rem; margin-right: -2rem; }
}
</style>

<section class="container py-5">
    <a href="/renting.php#publicaciones-renting" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-4">
        <i class="bi bi-arrow-left me-1"></i> Volver a Renting
    </a>

    <h1 class="renting-publicacion-title font-montserrat mb-4"><?php echo esc($article['title']); ?></h1>

    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10">
            <div class="renting-publicacion-box p-4 p-md-5 shadow-sm">
                <?php if (!empty($article['subheading'])): ?>
                    <h2 class="renting-publicacion-subheading font-montserrat"><?php echo esc($article['subheading']); ?></h2>
                <?php endif; ?>

                <?php if (!empty($article['description'])): ?>
                    <div class="renting-article-intro mb-3">
                        <?php
                        if (isRentingHtmlContent($article['description'])) {
                            echo renderRentingArticleContent($article['description']);
                        } else {
                            $descHtml = esc($article['description']);
                            $descHtml = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $descHtml);
                            echo '<p class="renting-article-paragraph">' . $descHtml . '</p>';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($article['content'])): ?>
                    <div class="renting-article-body font-poppins">
                        <?php echo renderRentingArticleContent($article['content']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
