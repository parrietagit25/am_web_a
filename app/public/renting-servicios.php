<?php
/**
 * Automarket - Renting — Nuestros Servicios
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/renting-posts.php';

$renting = $contentService->get('renting', []);
$servicios = $renting['servicios'] ?? [];

$pageTitle = $servicios['page_title'] ?? 'Nuestros Servicios';
$heading = $servicios['heading'] ?? '';
$paragraphs = $servicios['paragraphs'] ?? [];
$planTitle = $servicios['plan_title'] ?? 'Lo que incluye tu plan';
$introHtmlRaw = getRentingServiciosIntroRaw($servicios);
$useFullHtml = $introHtmlRaw !== '';

$planItems = array_values(array_filter($servicios['items'] ?? [], function ($item) {
    if (isset($item['active']) && ($item['active'] === false || $item['active'] === 'false' || $item['active'] == 0)) {
        return false;
    }
    return !empty(trim($item['title'] ?? ''));
}));
usort($planItems, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});
?>

<style>
.renting-servicios-page-title {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    color: #111;
    font-family: 'Montserrat', sans-serif;
}
.renting-servicios-box {
    border: 1px solid #d8dde6;
    border-radius: 4px;
    background: #fff;
}
.renting-servicios-heading {
    font-size: clamp(1.25rem, 2.5vw, 1.65rem);
    font-weight: 700;
    color: var(--theme-primary);
    font-family: 'Montserrat', sans-serif;
    line-height: 1.35;
}
.renting-servicios-paragraph {
    color: #5c6578;
    font-size: 1rem;
    line-height: 1.75;
    margin-bottom: 1.25rem;
    text-align: justify;
    font-family: 'Poppins', sans-serif;
}
.renting-servicios-plan-title {
    font-size: clamp(1.2rem, 2.2vw, 1.45rem);
    font-weight: 700;
    color: var(--theme-primary);
    font-family: 'Montserrat', sans-serif;
}
.renting-servicios-feature-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #9a7b2e;
    font-family: 'Montserrat', sans-serif;
    margin-bottom: 0.75rem;
}
.renting-servicios-feature-text {
    color: #5c6578;
    font-size: 0.98rem;
    line-height: 1.7;
    text-align: justify;
    margin-bottom: 0;
}
.renting-servicios-feature-img {
    width: 100%;
    border-radius: 6px;
    object-fit: cover;
    max-height: 280px;
}
.renting-servicios-item + .renting-servicios-item {
    margin-top: 2.5rem;
    padding-top: 2.5rem;
    border-top: 1px solid #e8ebf2;
}
.renting-servicios-html section {
    margin-left: -1rem;
    margin-right: -1rem;
}
@media (min-width: 768px) {
    .renting-servicios-html section {
        margin-left: -2rem;
        margin-right: -2rem;
    }
}
</style>

<section class="container py-5">
    <a href="/renting.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-4">
        <i class="bi bi-arrow-left me-1"></i> Volver a Renting
    </a>

    <h1 class="renting-servicios-page-title mb-4"><?php echo esc($pageTitle); ?></h1>

    <div class="renting-servicios-box p-4 p-md-5 shadow-sm">
        <?php if ($useFullHtml): ?>
            <div class="renting-servicios-html font-poppins">
                <?php echo renderRentingArticleContent($introHtmlRaw); ?>
            </div>
        <?php else: ?>
            <?php if (!empty($heading)): ?>
                <h2 class="renting-servicios-heading text-center mb-4"><?php echo esc($heading); ?></h2>
            <?php endif; ?>

            <?php foreach ($paragraphs as $paragraph): ?>
                <?php if (trim($paragraph) !== ''): ?>
                    <?php echo renderRentingServiciosField($paragraph); ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($planItems)): ?>
                <h3 class="renting-servicios-plan-title text-center my-5"><?php echo esc($planTitle); ?></h3>

                <?php foreach ($planItems as $index => $item):
                    $reverse = ($index % 2 === 1);
                    $hasImage = !empty($item['image_url']);
                    $descRaw = $item['description'] ?? '';
                ?>
                    <div class="renting-servicios-item">
                        <div class="row align-items-center g-4">
                            <div class="col-md-6 <?php echo $reverse ? 'order-md-2' : ''; ?>">
                                <?php if (isRentingHtmlContent($descRaw)): ?>
                                    <div class="renting-servicios-feature-text"><?php echo renderRentingArticleContent($descRaw); ?></div>
                                <?php else: ?>
                                    <h4 class="renting-servicios-feature-title"><?php echo esc($item['title']); ?></h4>
                                    <p class="renting-servicios-feature-text font-poppins"><?php echo nl2br(esc($descRaw)); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($hasImage): ?>
                            <div class="col-md-6 <?php echo $reverse ? 'order-md-1' : ''; ?>">
                                <img
                                    src="<?php echo esc($item['image_url']); ?>"
                                    alt="<?php echo esc($item['title']); ?>"
                                    class="renting-servicios-feature-img shadow-sm"
                                    loading="lazy"
                                >
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
