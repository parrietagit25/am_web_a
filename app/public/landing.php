<?php
/**
 * Landing page pública dinámica
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';

$activeUnit = 'rentacar';
$contentService = new ContentService();
$siteData = $contentService->getAll();
$slug = trim($_GET['slug'] ?? '');
$landings = $siteData['landings'] ?? [];

$landing = null;
foreach ($landings as $item) {
    if (($item['slug'] ?? '') === $slug && (($item['active'] ?? false) === true || ($item['active'] ?? false) === 1 || ($item['active'] ?? false) === '1')) {
        $landing = $item;
        break;
    }
}

if (!$landing) {
    http_response_code(404);
    $seoOverride = [
        'title' => 'Landing no encontrada | Automarket',
        'description' => 'La landing solicitada no existe o no está activa.',
        'robots' => 'noindex,nofollow',
    ];
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <section class="container py-5 my-5">
        <div class="text-center">
            <h1 class="fw-bold mb-3">Landing no encontrada</h1>
            <p class="text-muted">La página no existe o está inactiva.</p>
            <a href="/rent-a-car.php" class="btn btn-theme rounded-pill px-4">Volver al inicio</a>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$seo = $landing['seo'] ?? [];
$seoOverride = [
    'title' => trim($seo['title'] ?? ''),
    'description' => trim($seo['description'] ?? ($landing['excerpt'] ?? '')),
    'keywords' => trim($seo['keywords'] ?? ''),
    'robots' => trim($seo['robots'] ?? 'index,follow'),
    'canonical' => trim($seo['canonical_url'] ?? ''),
    'og_title' => trim($seo['og_title'] ?? ''),
    'og_description' => trim($seo['og_description'] ?? ''),
    'og_image' => trim($seo['og_image'] ?? ($landing['image_url'] ?? '')),
];

if ($seoOverride['canonical'] === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $seoOverride['canonical'] = $scheme . '://' . $host . '/landing.php?slug=' . urlencode($landing['slug'] ?? '');
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.landing-hero {
    min-height: 300px;
    background-size: cover;
    background-position: center;
    border-radius: 16px;
}
.landing-content {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 28px;
}
</style>

<section class="container py-5">
    <?php if (!empty($landing['image_url'] ?? '')): ?>
        <div class="landing-hero mb-4 d-flex align-items-end p-4 text-white" style="background-image: linear-gradient(180deg, rgba(10,20,35,.2), rgba(10,20,35,.65)), url('<?php echo esc($landing['image_url']); ?>');">
            <h1 class="fw-bold mb-0"><?php echo esc($landing['title'] ?? 'Landing'); ?></h1>
        </div>
    <?php else: ?>
        <h1 class="fw-bold mb-4"><?php echo esc($landing['title'] ?? 'Landing'); ?></h1>
    <?php endif; ?>

    <?php if (!empty($landing['excerpt'] ?? '')): ?>
        <p class="lead text-muted mb-4"><?php echo esc($landing['excerpt']); ?></p>
    <?php endif; ?>

    <div class="landing-content mb-4">
        <?php echo $landing['content_html'] ?? ''; ?>
    </div>

    <?php if (!empty($landing['cta_text'] ?? '') && !empty($landing['cta_url'] ?? '')): ?>
        <div class="text-center">
            <a href="<?php echo esc($landing['cta_url']); ?>" class="btn btn-theme btn-lg rounded-pill px-5"><?php echo esc($landing['cta_text']); ?></a>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

