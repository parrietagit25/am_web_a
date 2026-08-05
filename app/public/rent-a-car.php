<?php
/**
 * Automarket - Rent A Car Homepage
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/HeaderBannerService.php';

$homepageHero = $contentService->get('homepage.hero', []);
$homepageData = $contentService->get('homepage', []);
require_once __DIR__ . '/../includes/rentacar-public-copy.php';
$racFleetSection = rentacar_fleet_section_copy(is_array($homepageData) ? $homepageData : []);
$racSearchResultsTitle = rentacar_search_results_title(is_array($homepageData) ? $homepageData : []);
$racOpinionesSection = rentacar_opiniones_section_copy(is_array($homepageData) ? $homepageData : []);
$hbConfig = HeaderBannerService::normalizeFromNode(is_array($homepageHero) ? $homepageHero : []);
$heroTitle = $homepageHero['title'] ?? 'Te acompañamos a tu destino';
$heroSubtitle = $homepageHero['subtitle'] ?? 'Reserva tu vehículo en línea en segundos con la flota más moderna';
require_once __DIR__ . '/../includes/hero-text-colors.php';
$heroTitleColorAttr = am_hero_text_color_attr(is_array($homepageHero) ? ($homepageHero['title_color'] ?? '') : '');
$heroSubtitleColorAttr = am_hero_text_color_attr(is_array($homepageHero) ? ($homepageHero['subtitle_color'] ?? '') : '');
?>

<style>
.hero-banner-slider { min-height: 360px; }
</style>

<?php
$hbSectionId = 'cta-hero';
$hbInnerHtml = '<div class="row align-items-center"><div class="col-lg-7 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">'
    . '<h1 class="display-3 fw-bold mb-3 font-montserrat leading-tight"' . $heroTitleColorAttr . '>' . nl2br(esc($heroTitle)) . '</h1>'
    . '<p class="fs-4 mb-0 opacity-90 font-poppins"' . $heroSubtitleColorAttr . '>' . esc($heroSubtitle) . '</p>'
    . '</div></div>';
require __DIR__ . '/../includes/render-header-banner.php';
?>

<div id="search-anchor"></div>

<!-- 2. Buscador de Reserva Overlay Section -->
<section class="container mb-5 pb-3">
    <div class="row justify-content-center">
        <div class="col-xl-11 col-12">
            <?php require_once __DIR__ . '/../includes/reservation-search.php'; ?>
        </div>
    </div>
</section>

<!-- RESULTADOS DE BÚSQUEDA (Simulado, revelado por JS) -->
<section id="searchResultsSection" class="container mb-5 py-5 border-top d-none">
    
    <!-- Stepper Visual -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="stepper-container">
                <div class="stepper-line"></div>
                <div class="stepper-line-active"></div>
                
                <div class="step-item completed">
                    <div class="step-badge"><i class="bi bi-check-lg"></i></div>
                    <span class="step-title">1. Fecha y Lugar</span>
                </div>
                <div class="step-item active">
                    <div class="step-badge">2</div>
                    <span class="step-title">2. Vehículo</span>
                </div>
                <div class="step-item">
                    <div class="step-badge">3</div>
                    <span class="step-title">3. Adicionales</span>
                </div>
                <div class="step-item">
                    <div class="step-badge">4</div>
                    <span class="step-title">4. Confirmación</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results Header Summary -->
    <div class="bg-navy text-white rounded-4 p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="badge bg-danger mb-2">Disponibilidad en Vivo</span>
            <h4 class="fw-bold mb-0 font-montserrat"><?php echo esc($racSearchResultsTitle); ?></h4>
            <small id="searchSummaryText" class="opacity-75"></small>
        </div>
        <button id="modifySearchBtn" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
            <i class="bi bi-pencil-square me-2"></i>Modificar Búsqueda
        </button>
    </div>

    <!-- Category Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex overflow-x-auto text-nowrap gap-2 pb-2 scrollbar-hidden">
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn active" data-category="all">Todos</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="SUV">SUV</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="Sedanes">Sedanes</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="Pick Up">Pick Up</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="Comerciales">Comerciales</button>
                <button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="Promociones">Promociones</button>
            </div>
        </div>
    </div>

    <!-- Dynamic Cards Grid Container -->
    <div id="resultsVehiclesContainer" class="row">
        <!-- Rendered dynamically by JavaScript -->
    </div>
</section>

<!-- 3. Descubre nuestra flota de alquiler -->
<?php
$fleetCarousel = $contentService->get('homepage.fleet_carousel', [
    'autoplay' => true,
    'direction' => 'right',
    'interval' => 3000,
    'items' => []
]);
require_once __DIR__ . '/../includes/fleet-categories.php';
$fleetCategoryItems = am_fleet_categories_sorted($fleetCarousel['items'] ?? []);
$autoplayVal = ($fleetCarousel['autoplay'] ?? true) ? 'true' : 'false';
$directionVal = esc($fleetCarousel['direction'] ?? 'right');
$intervalVal = intval($fleetCarousel['interval'] ?? 3000);
?>
<section class="container py-5 mb-4" id="alquileres">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-danger-subtle text-danger rounded-pill fw-bold text-uppercase tracking-wider mb-2"><?php echo esc($racFleetSection['eyebrow']); ?></span>
        <h2 class="fw-bold text-navy font-montserrat text-uppercase tracking-wide" style="font-size: 1.75rem;"><?php echo esc($racFleetSection['title']); ?></h2>
        <p class="text-muted max-width-600 mx-auto"><?php echo esc($racFleetSection['subtitle']); ?></p>
    </div>

    <!-- Carousel Container -->
    <div class="position-relative px-md-5 my-5">
        <!-- Left Control Button -->
        <button class="fleet-carousel-control prev" aria-label="Anterior" id="fleet-prev-btn">
            <i class="bi bi-chevron-left"></i>
        </button>

        <!-- Slider Wrapper -->
        <div class="fleet-carousel-wrapper">
            <div class="fleet-carousel-track" id="fleet-carousel-track" 
                 data-autoplay="<?php echo $autoplayVal; ?>" 
                 data-direction="<?php echo $directionVal; ?>" 
                 data-interval="<?php echo $intervalVal; ?>">
                
                <?php foreach ($fleetCategoryItems as $item):
                    $fleetCat = trim((string)($item['category'] ?? $item['label'] ?? ''));
                    $fleetCatUrl = $fleetCat !== '' ? '/flota.php?categoria=' . rawurlencode($fleetCat) : '/flota.php';
                ?>
                <div class="fleet-carousel-item">
                    <a href="<?php echo esc($fleetCatUrl); ?>" class="fleet-category-link text-decoration-none" data-category="<?php echo esc($fleetCat); ?>">
                        <div class="fleet-category-image-container">
                            <img src="<?php echo esc($item['image_url'] ?? ''); ?>" alt="<?php echo esc($item['label'] ?? ''); ?>" class="img-fluid fleet-category-img">
                        </div>
                        <h4 class="fleet-category-title text-uppercase font-montserrat"><?php echo esc($item['label'] ?? ''); ?></h4>
                    </a>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- Right Control Button -->
        <button class="fleet-carousel-control next" aria-label="Siguiente" id="fleet-next-btn">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- Extra CTA button to view all categories -->
    <div class="text-center mt-5">
        <a href="/flota.php" class="btn btn-theme px-5 py-3 rounded-pill fw-bold text-white shadow text-uppercase">
            <?php echo esc($racFleetSection['cta_text']); ?>
        </a>
    </div>
</section>

<?php
require_once __DIR__ . '/../services/AllyService.php';
$racAlliesSiteData = is_array($siteData ?? null) ? $siteData : $contentService->getAll();
$alliesItems = AllyService::listForUnit($racAlliesSiteData, 'rentacar');
$alliesMeta = AllyService::metaForUnit($racAlliesSiteData, 'rentacar');
$alliesTitle = $alliesMeta['title'];
$alliesSubtitle = $alliesMeta['subtitle'];
$alliesText = $alliesMeta['text'];
$alliesLayout = $alliesMeta['layout'];
$alliesSectionId = 'aliados-rac';
$alliesTitleClass = 'fw-bold text-navy font-montserrat text-uppercase text-center mb-4';
require __DIR__ . '/../includes/unit-allies-section.php';
?>

<!-- 4. Sección Destino del Mes / Feria de David -->
<?php
$featured = $contentService->get('homepage.featured');
$featuredActive = ($featured['active'] ?? true) !== false;
if ($featuredActive):
$featuredBadge = $featured['badge'] ?? 'Recomendado';
$featuredTitle = $featured['title'] ?? 'Feria de David 2026';
$featuredHeading = $featured['heading'] ?? 'Feria Internacional de David 2026: tradición, desarrollo y crecimiento en Chiriquí';
$featuredDescription = $featured['description'] ?? '';
$featuredBtnText = $featured['button_text'] ?? 'Ver mas: Feria de David 2026';
$featuredBtnLink = trim((string)($featured['button_link'] ?? ''));
$featuredImageUrl = $featured['image_url'] ?? '/assets/img/feria_david.webp';
?>
<section class="container py-5 mb-5">
    <div class="destiny-banner bg-white border">
        <div class="row g-0">
            <!-- Left Information Column -->
            <div class="col-lg-6 p-5 d-flex flex-column justify-content-center">
                <span class="badge bg-danger-subtle text-danger align-self-start px-3 py-2 rounded-pill fw-bold text-uppercase mb-3"><?php echo esc($featuredBadge); ?></span>
                <h2 class="display-6 fw-bold text-navy font-montserrat mb-3"><?php echo esc($featuredTitle); ?></h2>
                <h4 class="fw-bold text-danger font-montserrat mb-3" style="font-size: 1.25rem;">
                    <?php echo esc($featuredHeading); ?>
                </h4>
                <p class="text-muted mb-4 font-poppins">
                    <?php echo nl2br(esc($featuredDescription)); ?>
                </p>
                <?php if ($featuredBtnLink !== ''): ?>
                <a href="<?php echo esc($featuredBtnLink); ?>" class="btn btn-theme px-4 py-3 rounded-pill fw-bold align-self-start shadow-sm text-uppercase"<?php echo preg_match('#^https?://#i', $featuredBtnLink) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
                    <?php echo esc($featuredBtnText); ?>
                </a>
                <?php else: ?>
                <span class="btn btn-theme px-4 py-3 rounded-pill fw-bold align-self-start shadow-sm text-uppercase disabled" aria-disabled="true">
                    <?php echo esc($featuredBtnText); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Right Image Column -->
            <div class="col-lg-6 position-relative min-height-350 bg-light-gray destiny-img-holder">
                <img src="<?php echo esc($featuredImageUrl); ?>" class="destiny-img w-100" alt="<?php echo esc($featuredTitle); ?>">
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 5. Destacado en home (rotación o único) + contenido más reciente -->
<?php
$ucUnitKey = 'rentacar';
require __DIR__ . '/../includes/unit-content-home-sections.php';
?>

<!-- 6. Testimonios -->
<?php
$opiniones = $contentService->get('homepage.opiniones', []);
?>
<section class="container py-5 mb-5 border-top">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-navy display-6 font-montserrat"><?php echo esc($racOpinionesSection['title']); ?></h2>
        <p class="text-muted"><?php echo esc($racOpinionesSection['subtitle']); ?></p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php foreach ($opiniones as $opinion): ?>
        <div class="col-lg-4 col-md-6 col-12">
            <div class="testimonial-card p-4 shadow-sm h-100 d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php if (strpos($opinion['avatar'] ?? '', '/') === 0 || strpos($opinion['avatar'] ?? '', 'http') === 0): ?>
                        <img src="<?php echo esc($opinion['avatar']); ?>" alt="<?php echo esc($opinion['name'] ?? ''); ?>" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                    <?php else: ?>
                        <div class="avatar bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 50px; height: 50px;">
                            <?php echo esc($opinion['avatar'] ?? 'U'); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h6 class="fw-bold text-navy mb-0"><?php echo esc($opinion['name'] ?? ''); ?></h6>
                        <small class="text-muted"><?php echo esc($opinion['sucursal'] ?? ''); ?></small>
                    </div>
                </div>
                <div class="stars mb-3 text-warning">
                    <?php 
                    $stars = intval($opinion['stars'] ?? 5);
                    for ($i = 0; $i < 5; $i++): 
                    ?>
                        <i class="bi <?php echo ($i < $stars) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <p class="text-muted font-poppins text-sm mb-0">
                    <?php echo esc($opinion['text'] ?? ''); ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Custom CSS utility mapping for category card grid layout -->
<style>
@media (min-width: 992px) {
    .col-lg-2-4 {
        flex: 0 0 20%;
        max-width: 20%;
    }
}
</style>

<?php
$_racHome = $contentService->get('homepage', []);
$_legacyRac = $contentService->get('rentacar', []);
$_racFaqs = $_racHome['faqs'] ?? ($_legacyRac['faqs'] ?? []);
$_sfItems  = $_racFaqs;
$_ufsItems = $_racFaqs;
$_upsUnitSocialLinks = $_racHome['social_links'] ?? ($_legacyRac['social_links'] ?? []);
$_upsShowPayments = ($_racHome['show_payment_methods'] ?? true) !== false;
$_upsUnitContact = $_racHome['contact'] ?? [];
$_upsUnitData = $_racHome;
unset($_racHome, $_legacyRac, $_racFaqs);
require __DIR__ . '/../includes/schema-faq.php';
?>
<?php
require __DIR__ . '/../includes/unit-faq-section.php';
?>

<?php require __DIR__ . '/../includes/unit-payment-social.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
