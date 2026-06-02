<?php
/**
 * Leasing Operativo - Publicación (detalle)
 */
$activeUnit = 'leasing';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/leasing-posts.php';

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = findLeasingPostById($contentService, $postId);

if (!$article) {
    echo "<script>window.location.href='/leasing.php';</script>";
    exit;
}
?>

<style>
.leasing-publicacion-title {
    font-size: clamp(1.75rem, 4vw, 2.25rem);
    font-weight: 700;
    color: #111;
}
.leasing-publicacion-box {
    border: 1px solid #d8dde6;
    border-radius: 4px;
    background: #fff;
}
.leasing-publicacion-subheading {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111;
    margin-bottom: 1.25rem;
}
.leasing-article-paragraph {
    color: #222;
    font-size: 1rem;
    line-height: 1.75;
    margin-bottom: 1.25rem;
    text-align: justify;
}
.leasing-check-icon {
    color: #2e9b4d;
    font-size: 1.15rem;
    margin-top: 0.15rem;
}
.leasing-article-rich img { max-width: 100%; height: auto; }
.leasing-article-rich section { margin-bottom: 1.5rem; }
.leasing-article-rich h1, .leasing-article-rich h2, .leasing-article-rich h3 { margin-top: 1rem; margin-bottom: .75rem; }
</style>

<section class="container py-5">
    <a href="/leasing.php#publicaciones-leasing" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-4">
        <i class="bi bi-arrow-left me-1"></i> Volver a Leasing Operativo
    </a>

    <h1 class="leasing-publicacion-title font-montserrat mb-4"><?php echo esc($article['title']); ?></h1>

    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10">
            <div class="leasing-publicacion-box p-4 p-md-5 shadow-sm">
                <?php if (!empty($article['subheading'])): ?>
                    <h2 class="leasing-publicacion-subheading font-montserrat"><?php echo esc($article['subheading']); ?></h2>
                <?php endif; ?>

                <?php if (!empty($article['description'])): ?>
                    <div class="leasing-article-intro leasing-article-rich font-poppins mb-3">
                        <?php echo renderLeasingArticleContent($article['description']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($article['content'])): ?>
                    <div class="leasing-article-body leasing-article-rich font-poppins">
                        <?php echo renderLeasingArticleContent($article['content']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
