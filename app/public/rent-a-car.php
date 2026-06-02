<?php
/**
 * Automarket - Rent A Car Homepage
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- 1. Hero Principal -->
<?php
$heroTitle = $contentService->get('homepage.hero.title', 'Te acompañamos a tu destino');
$heroSubtitle = $contentService->get('homepage.hero.subtitle', 'Reserva tu vehículo en línea en segundos con la flota más moderna');
$heroImage = $contentService->get('homepage.hero.image_url', 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?q=80&w=1920&auto=format&fit=crop');
?>
<section class="hero-wrapper text-center text-lg-start d-flex align-items-center" id="cta-hero" style="background: url('<?php echo esc($heroImage); ?>') no-repeat center center; background-size: cover;">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                <h1 class="display-3 fw-bold mb-3 font-montserrat leading-tight">
                    <?php echo nl2br(esc($heroTitle)); ?>
                </h1>
                <p class="fs-4 mb-0 opacity-90 font-poppins">
                    <?php echo esc($heroSubtitle); ?>
                </p>
            </div>
        </div>
    </div>
</section>

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
            <h4 class="fw-bold mb-0 font-montserrat">Vehículos Disponibles</h4>
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
$autoplayVal = ($fleetCarousel['autoplay'] ?? true) ? 'true' : 'false';
$directionVal = esc($fleetCarousel['direction'] ?? 'right');
$intervalVal = intval($fleetCarousel['interval'] ?? 3000);
?>
<section class="container py-5 mb-4" id="alquileres">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-danger-subtle text-danger rounded-pill fw-bold text-uppercase tracking-wider mb-2">Categorías</span>
        <h2 class="fw-bold text-navy font-montserrat text-uppercase tracking-wide" style="font-size: 1.75rem;">Descubre Nuestra Flota de Alquiler</h2>
        <p class="text-muted max-width-600 mx-auto">Selecciona la categoría que mejor se adapte a tus necesidades de viaje.</p>
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
                
                <?php foreach (($fleetCarousel['items'] ?? []) as $item):
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
            Ver todas las categorías
        </a>
    </div>
</section>

<!-- 4. Sección Destino del Mes / Feria de David -->
<?php
$featured = $contentService->get('homepage.featured');
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

<!-- 5. Últimas noticias -->
<?php
$noticiasShowOnHome = $contentService->get('homepage.noticias_show_on_home', true);
$noticiasShowOnHome = ($noticiasShowOnHome !== false && $noticiasShowOnHome !== 0 && $noticiasShowOnHome !== '0');
$noticias = $contentService->get('homepage.noticias', []);
?>
<?php if ($noticiasShowOnHome): ?>
<section class="container py-5 mb-5 border-top" id="blog">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-navy display-6 font-montserrat">Últimas Noticias</h2>
        <p class="text-muted">Mantente al día con nuestros anuncios, lanzamientos y consejos de viaje.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($noticias as $noticia): ?>
        <div class="col-lg-4 col-md-6 col-12 d-flex mb-4">
            <div class="card border-0 shadow-sm rounded-4 w-100 p-3 d-flex flex-column justify-content-between" style="background-color: #f1f3f7; transition: transform 0.3s ease; border-radius: 16px;">
                <div class="position-relative rounded-3 overflow-hidden mb-3">
                    <?php if (!empty($noticia['thumbnail'])): ?>
                        <img src="<?php echo esc($noticia['thumbnail']); ?>" alt="<?php echo esc($noticia['title'] ?? ''); ?>" class="w-100" style="height: 220px; object-fit: cover; border-radius: 12px;">
                    <?php else: ?>
                        <div class="bg-light-gray text-center py-5" style="height: 220px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                            <i class="bi bi-file-earmark-text text-muted opacity-50 fs-1"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0 d-flex flex-column justify-content-between flex-grow-1">
                    <div>
                        <h5 class="fw-bold text-navy mb-2" style="font-size: 1.15rem; line-height: 1.4; font-family: 'Montserrat', sans-serif;">
                            <?php echo esc($noticia['title'] ?? ''); ?>
                        </h5>
                        <p class="text-muted text-sm font-poppins mb-3" style="font-size: 0.9rem; line-height: 1.5;">
                            <?php echo esc($noticia['desc'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="text-end mt-2">
                        <a href="/noticia.php?id=<?php echo intval($noticia['id']); ?>" class="fw-bold text-decoration-none text-sm d-inline-flex align-items-center gap-1" style="color: #c51f17; font-family: 'Poppins', sans-serif;">
                            <?php echo esc($noticia['link_text'] ?? 'Ver Más'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- 6. Testimonios -->
<?php
$opiniones = $contentService->get('homepage.opiniones', []);
?>
<section class="container py-5 mb-5 border-top">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-navy display-6 font-montserrat">Opiniones de Nuestros Clientes</h2>
        <p class="text-muted">Conoce la experiencia de quienes viajan y confían en nosotros todos los días.</p>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
