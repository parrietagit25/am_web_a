<?php
/**
 * Automarket - Taller Homepage
 */
$activeUnit = 'taller';
require_once __DIR__ . '/../includes/header.php';

$taller = $contentService->get('taller', []);
require_once __DIR__ . '/../services/HeaderBannerService.php';
$hbConfig = HeaderBannerService::normalizeFromNode($taller['hero'] ?? []);
$servicesTitle = $taller['services_title'] ?? 'Conoce Nuestros Servicios';
$servicesSubtitle = $taller['services_subtitle'] ?? 'Algunos de los Servicios que Ofrecemos en Nuestros Talleres son';
$teamTitle1 = $taller['team_title_line_1'] ?? 'Tenemos un equipo de';
$teamTitle2 = $taller['team_title_line_2'] ?? 'MECÁNICOS CERTIFICADOS Y ALTAMENTE CAPACITADOS';
$brandsTitle = $taller['brands_title'] ?? 'PERSONAL TÉCNICO Y TALLER CERTIFICADO';
$brandsText = $taller['brands_text'] ?? '';
$opinionsTitle = $taller['opinions_title'] ?? 'Lo que opinan nuestros clientes de nosotros...';

$serviceCards = array_values(array_filter($taller['service_cards'] ?? [], function ($item) {
    return ($item['active'] ?? true) !== false;
}));
usort($serviceCards, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});

$teamImages = $taller['team']['images'] ?? [];
$teamImages = array_values(array_filter($teamImages, function ($img) {
    return !empty($img);
}));

$brands = array_values(array_filter($taller['brands'] ?? [], function ($b) {
    return ($b['active'] ?? true) !== false;
}));
usort($brands, function ($a, $b) {
    return intval($a['sort_order'] ?? 999) - intval($b['sort_order'] ?? 999);
});

$opiniones = array_values(array_filter($taller['opiniones'] ?? [], function ($o) {
    return ($o['active'] ?? true) !== false;
}));
?>

<style>
.taller-section-title { font-size: clamp(1.6rem, 3.2vw, 2.1rem); font-weight: 800; color: var(--theme-primary); text-transform: uppercase; letter-spacing: 0.4px; }
.taller-service-card {
    border-radius: 8px;
    min-height: 170px;
    color: #fff;
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: end;
    box-shadow: 0 6px 18px rgba(8, 16, 38, 0.1);
    background-size: cover;
    background-position: center;
}
.taller-service-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(16, 31, 90, 0.32) 0%, rgba(16, 31, 90, 0.85) 100%);
}
.taller-service-card-content { position: relative; z-index: 1; padding: 18px 16px; }
.taller-team-title-top { color: #cc2c2c; font-weight: 800; font-size: clamp(1.55rem, 3vw, 2.25rem); }
.taller-team-title-bottom { color: #8d8ab8; font-weight: 800; font-size: clamp(1.5rem, 2.6vw, 2.1rem); text-transform: uppercase; }
.taller-team-img { width: 100%; border-radius: 12px; object-fit: cover; height: 250px; box-shadow: 0 6px 16px rgba(8,16,38,0.08); }
.taller-brand-logo { max-height: 58px; width: auto; max-width: 140px; object-fit: contain; filter: grayscale(100%); opacity: 0.85; }
.taller-op-card { background:#fff; border:1px solid #e8ebf2; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(8,16,38,0.04); height:100%; }
.taller-op-avatar { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; }
.taller-op-stars { color: #2563eb; letter-spacing: 1px; }
.hero-banner-slider { min-height: 360px; }
</style>

<?php
$hbSectionId = 'cta-hero';
$hbInnerHtml = '<div class="row align-items-center"><div class="col-lg-8 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">'
    . '<h1 class="display-4 fw-bold mb-3 font-montserrat">Automarket Taller</h1>'
    . '<p class="fs-5 mb-4 opacity-90 font-poppins">Servicio de mantenimiento certificado, mecánicos capacitados y repuestos originales.</p>'
    . '<a href="#servicios" class="btn btn-theme btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase shadow-lg">Ver Servicios</a>'
    . '</div></div>';
require __DIR__ . '/../includes/render-header-banner.php';
?>

<section class="container py-5" id="servicios">
    <div class="text-center mb-4">
        <h2 class="taller-section-title font-montserrat mb-2"><?php echo esc($servicesTitle); ?></h2>
        <p class="text-muted fw-semibold mb-0"><?php echo esc($servicesSubtitle); ?></p>
    </div>
    <div class="row g-3 justify-content-center">
        <?php foreach (array_slice($serviceCards, 0, 3) as $card): ?>
            <div class="col-lg-4 col-md-6">
                <article class="taller-service-card" style="background-image:url('<?php echo esc($card['image_url'] ?? ''); ?>');">
                    <div class="taller-service-card-content">
                        <h5 class="fw-bold mb-1"><?php echo esc($card['title'] ?? ''); ?></h5>
                        <p class="small mb-0"><?php echo esc($card['description'] ?? ''); ?></p>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="py-5 bg-white" id="equipo-taller">
    <div class="container text-center">
        <h3 class="taller-team-title-top font-montserrat mb-1"><?php echo esc($teamTitle1); ?></h3>
        <h2 class="taller-team-title-bottom font-montserrat mb-4"><?php echo esc($teamTitle2); ?></h2>
        <div class="row g-4 justify-content-center">
            <?php foreach (array_slice($teamImages, 0, 3) as $img): ?>
                <div class="col-lg-4 col-md-6">
                    <img src="<?php echo esc($img); ?>" alt="Equipo técnico de taller" class="taller-team-img" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-white" id="marcas-taller">
    <div class="container text-center">
        <h2 class="taller-section-title font-montserrat mb-3"><?php echo esc($brandsTitle); ?></h2>
        <?php if (!empty($brandsText)): ?><p class="text-muted mx-auto mb-4" style="max-width: 980px;"><?php echo esc($brandsText); ?></p><?php endif; ?>
        <?php if (!empty($brands)): ?>
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-4">
                <?php foreach ($brands as $brand): ?>
                    <img src="<?php echo esc($brand['image_url'] ?? ''); ?>" alt="<?php echo esc($brand['name'] ?? 'Marca'); ?>" class="taller-brand-logo" loading="lazy">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5" id="opiniones-taller" style="background:#f5f6f8;">
    <div class="container">
        <h2 class="h2 mb-4 font-montserrat"><?php echo esc($opinionsTitle); ?></h2>
        <div class="row g-4">
            <?php foreach ($opiniones as $op): ?>
                <div class="col-lg-4 col-md-6">
                    <article class="taller-op-card">
                        <div class="d-flex gap-3 mb-3">
                            <?php if (!empty($op['avatar']) && (str_starts_with((string)$op['avatar'], '/') || str_starts_with((string)$op['avatar'], 'http'))): ?>
                                <img src="<?php echo esc($op['avatar']); ?>" alt="<?php echo esc($op['name'] ?? 'Cliente'); ?>" class="taller-op-avatar">
                            <?php else: ?>
                                <div class="taller-op-avatar bg-secondary-subtle d-flex align-items-center justify-content-center fw-bold text-secondary"><?php echo esc(substr((string)($op['avatar'] ?? 'U'), 0, 2)); ?></div>
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1 fw-bold"><?php echo esc($op['name'] ?? ''); ?></h5>
                                <div class="taller-op-stars small"><?php for ($i = 0; $i < intval($op['stars'] ?? 5); $i++) echo '★'; ?></div>
                                <?php if (!empty($op['date'])): ?><div class="small text-muted"><?php echo esc($op['date']); ?></div><?php endif; ?>
                                <?php if (!empty($op['branch'])): ?><div class="small fw-semibold"><?php echo esc($op['branch']); ?></div><?php endif; ?>
                            </div>
                        </div>
                        <p class="mb-0 text-muted"><?php echo esc($op['text'] ?? ''); ?></p>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
