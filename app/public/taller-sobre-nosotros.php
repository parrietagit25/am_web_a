<?php
/**
 * Automarket - Taller — Sobre Nosotros
 */
$activeUnit = 'taller';
require_once __DIR__ . '/../includes/header.php';

$sobre = $contentService->get('taller.sobre_nosotros', []);
$pageTitle = $sobre['page_title'] ?? 'Sobre Nosotros';
$sectionTitle = $sobre['section_title'] ?? 'Sobre Automarket Taller';
$rightTitle = $sobre['right_title'] ?? '';
$rightContent = $sobre['right_content'] ?? '';
$mainImage = $sobre['main_image_url'] ?? '';
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
        $raw = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $raw);
        $raw = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $raw);
        $raw = preg_replace('/\s+on\w+\s*=\s*("([^"]*)"|\'([^\']*)\'|[^\s>]+)/i', '', $raw);
        return $raw;
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
                    <img src="<?php echo esc($mainImage); ?>" alt="Sobre Automarket Taller" class="w-100 rounded-2" style="object-fit:cover; max-height:430px;">
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
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
