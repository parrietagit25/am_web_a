<?php
/**
 * Automarket - Taller — Sobre Nosotros
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
$aboutContentService = new ContentService();
$aboutTaller = $aboutContentService->get('taller.sobre_nosotros', []);
if (array_key_exists('published', $aboutTaller) && empty($aboutTaller['published'])) {
    http_response_code(404);
    $activeUnit = 'taller';
    $seoOverride = ['title' => 'Página no encontrada | Automarket', 'robots' => 'noindex,nofollow'];
    require __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Página no encontrada</h1><a href="/taller.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}
$activeUnit = 'taller';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/UnitAboutService.php';

$sobre = $contentService->get('taller.sobre_nosotros', $aboutTaller);
$pageTitle = $sobre['page_title'] ?? 'Sobre Nosotros';
$sectionTitle = $sobre['section_title'] ?? 'Sobre Automarket Taller';
$rightTitle = $sobre['right_title'] ?? '';
$rightContent = $sobre['right_content'] ?? '';
$mainImage = $sobre['main_image_url'] ?? '';
$mainImageAlt = trim((string) ($sobre['main_image_alt'] ?? '')) ?: $pageTitle;
$bottomTitle = $sobre['bottom_title'] ?? '';
$stats = $sobre['stats'] ?? [];
while (count($stats) < 3) {
    $stats[] = ['image_url' => '', 'caption' => ''];
}

function tallerSobreContentIsHtml($raw) {
    return (bool) preg_match('/<\s*[a-z][a-z0-9]*\b/i', trim($raw ?? ''));
}
function renderTallerSobreContent($raw) {
    $raw = trim($raw ?? '');
    if ($raw === '') return '';
    if (tallerSobreContentIsHtml($raw)) {
        try {
            return UnitAboutService::sanitizeBodyHtml($raw);
        } catch (InvalidArgumentException | RuntimeException $e) {
            return '';
        }
    }
    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $html = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $html .= '<p class="text-muted mb-3">' . esc($line) . '</p>';
    }
    return $html;
}
?>

<section class="py-5" style="background-color:#f8f9fc;">
    <div class="container">
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size:2.3rem;"><?php echo esc($pageTitle); ?></h1>
    </div>
</section>

<section class="container py-4 pb-5">
    <div class="border rounded-3 bg-white p-4 p-md-5">
        <h2 class="text-center fw-bold font-montserrat mb-5" style="font-size:2rem;"><?php echo esc($sectionTitle); ?></h2>
        <div class="row g-4 align-items-start mb-5">
            <div class="col-lg-6">
                <?php if (!empty($mainImage)): ?>
                    <img src="<?php echo esc($mainImage); ?>" alt="<?php echo esc($mainImageAlt); ?>" class="w-100 rounded-2" style="object-fit:cover; max-height:430px;">
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <?php if (!empty($rightTitle)): ?><h3 class="fw-bold mb-3" style="font-size:1.55rem;"><?php echo esc($rightTitle); ?></h3><?php endif; ?>
                <div class="font-poppins" style="line-height:1.75;"><?php echo renderTallerSobreContent($rightContent); ?></div>
            </div>
        </div>

        <?php if (!empty($bottomTitle)): ?>
            <h4 class="text-center fw-bold font-montserrat mb-4" style="font-size:1.6rem;"><?php echo esc($bottomTitle); ?></h4>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <?php foreach (array_slice($stats, 0, 3) as $item): ?>
                <div class="col-md-4 text-center">
                    <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo esc($item['image_url']); ?>" alt="<?php echo esc($item['caption'] ?? ''); ?>" style="max-height:72px; width:auto; object-fit:contain;">
                    <?php endif; ?>
                    <?php if (!empty($item['caption'])): ?>
                        <div class="fw-bold mt-2" style="font-size:1.25rem;"><?php echo esc($item['caption']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $tallerCtaText = trim((string) ($sobre['cta_text'] ?? ''));
        $tallerCtaUrl = UnitAboutService::sanitizeCtaUrl((string) ($sobre['cta_url'] ?? ''));
        ?>
        <?php if ($tallerCtaText !== '' && $tallerCtaUrl !== ''): ?>
            <div class="text-center mt-4"><a class="btn btn-primary" href="<?php echo esc($tallerCtaUrl); ?>"><?php echo esc($tallerCtaText); ?></a></div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
