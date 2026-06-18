<?php
/**
 * Página genérica para unidades de negocio personalizadas.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/business-units-registry.php';

$contentService = new ContentService();
$siteGlobal = $contentService->get('global');
$units = am_merge_business_units($siteGlobal['business_units'] ?? []);
$unitKey = am_normalize_business_unit_key((string) ($_GET['u'] ?? ''));

if ($unitKey === '' || !isset($units[$unitKey])) {
    http_response_code(404);
    $activeUnit = 'rentacar';
    $seoOverride = ['title' => 'Unidad no encontrada | Automarket', 'robots' => 'noindex,nofollow'];
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="container py-5 my-5 text-center"><h1>Unidad no encontrada</h1><a href="/rent-a-car.php" class="btn btn-theme mt-3">Inicio</a></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$unit = $units[$unitKey];
$activeUnit = $unitKey;
$heroTitle = trim((string) ($unit['heroTitle'] ?? $unit['label'] ?? ''));
$heroSubtitle = trim((string) ($unit['heroSubtitle'] ?? ''));
$heroImage = trim((string) ($unit['hero_image_url'] ?? ''));
if ($heroImage === '') {
    $heroImage = 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1920&auto=format&fit=crop';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero-wrapper text-center text-lg-start d-flex align-items-center" style="background: linear-gradient(135deg, rgba(8,16,38,0.82), rgba(8,16,38,0.45)), url('<?php echo esc($heroImage); ?>') no-repeat center center; background-size: cover; min-height: 360px;">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-8 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                <h1 class="display-4 fw-bold mb-3 font-montserrat"><?php echo esc($heroTitle); ?></h1>
                <?php if ($heroSubtitle !== ''): ?>
                <p class="fs-5 mb-0 opacity-90 font-poppins"><?php echo esc($heroSubtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="bg-white border rounded-4 shadow-sm p-4 p-md-5 font-poppins">
                <?php if (!empty(trim((string) ($unit['body_html'] ?? '')))): ?>
                    <?php echo $unit['body_html']; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Contenido de esta unidad de negocio. Puedes configurar el título y subtítulo del hero desde el administrador.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
