<?php
/**
 * Automarket - Renting — Sobre Nosotros
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/renting-posts.php';

$renting = $contentService->get('renting', []);
$sobre = $renting['sobre_nosotros'] ?? [];

$pageTitle = $sobre['page_title'] ?? 'Sobre Nosotros';
$heading = $sobre['heading'] ?? 'Quiénes Somos';
$paragraphs = $sobre['paragraphs'] ?? [];
$introHtmlRaw = getRentingSectionIntroRaw($sobre);
$useFullHtml = $introHtmlRaw !== '';

$gallery = $sobre['gallery'] ?? [];
while (count($gallery) < 3) {
    $gallery[] = ['image_url' => '', 'alt' => ''];
}
$gallery = array_slice($gallery, 0, 3);
$hasGalleryImages = false;
foreach ($gallery as $img) {
    if (!empty($img['image_url'])) {
        $hasGalleryImages = true;
        break;
    }
}
?>

<style>
.renting-sobre-page-title {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    color: #111;
    font-family: 'Montserrat', sans-serif;
}
.renting-sobre-box {
    border: 1px solid #d8dde6;
    border-radius: 4px;
    background: #fff;
}
.renting-sobre-heading {
    font-size: clamp(1.25rem, 2.5vw, 1.65rem);
    font-weight: 700;
    color: var(--theme-primary);
    font-family: 'Montserrat', sans-serif;
    line-height: 1.35;
}
.renting-sobre-paragraph {
    color: #5c6578;
    font-size: 1rem;
    line-height: 1.75;
    margin-bottom: 1.25rem;
    text-align: justify;
    font-family: 'Poppins', sans-serif;
}
.renting-sobre-gallery {
    margin-top: 2.5rem;
    padding-top: 0.5rem;
}
.renting-sobre-gallery img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 6px;
    display: block;
    box-shadow: 0 4px 14px rgba(8, 16, 38, 0.08);
}
.renting-sobre-html section {
    margin-left: -1rem;
    margin-right: -1rem;
}
@media (min-width: 768px) {
    .renting-sobre-html section {
        margin-left: -2rem;
        margin-right: -2rem;
    }
}
</style>

<section class="container py-5">
    <a href="/renting.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-4">
        <i class="bi bi-arrow-left me-1"></i> Volver a Renting
    </a>

    <h1 class="renting-sobre-page-title mb-4"><?php echo esc($pageTitle); ?></h1>

    <div class="renting-sobre-box p-4 p-md-5 shadow-sm">
        <?php if ($useFullHtml): ?>
            <div class="renting-sobre-html font-poppins">
                <?php echo renderRentingArticleContent($introHtmlRaw); ?>
            </div>
        <?php else: ?>
            <?php if (!empty($heading)): ?>
                <h2 class="renting-sobre-heading text-center mb-4"><?php echo esc($heading); ?></h2>
            <?php endif; ?>

            <?php foreach ($paragraphs as $paragraph): ?>
                <?php if (trim($paragraph) !== ''): ?>
                    <?php echo renderRentingServiciosField($paragraph); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($hasGalleryImages && !$useFullHtml): ?>
            <div class="row g-3 renting-sobre-gallery">
                <?php foreach ($gallery as $img): ?>
                    <?php if (!empty($img['image_url'])): ?>
                    <div class="col-md-4 col-sm-6">
                        <img
                            src="<?php echo esc($img['image_url']); ?>"
                            alt="<?php echo esc($img['alt'] ?? 'Automarket Renting'); ?>"
                            loading="lazy"
                        >
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
