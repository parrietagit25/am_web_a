<?php
/**
 * Automarket - Venta de Autos Homepage
 */
$activeUnit = 'seminuevos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/HeaderBannerService.php';
require_once __DIR__ . '/../services/VehicleSlugHelper.php';
require_once __DIR__ . '/../services/InventoryHighlightService.php';

require_once __DIR__ . '/../includes/seminuevos-public-copy.php';

// Fetch Seminuevos data from content service
$seminuevosData = $contentService->get('seminuevos', []);
$inventoryHighlightAssignments = InventoryHighlightService::getAssignments($seminuevosData);
$inventoryHighlightMetadata = InventoryHighlightService::getMetadata($seminuevosData);
$semiCopyDefaults = seminuevos_public_copy_defaults();
$semiHeroTitle = seminuevos_public_copy($seminuevosData, 'hero_title', $semiCopyDefaults['hero_title']);
$semiHeroSubtitle = seminuevos_public_copy($seminuevosData, 'hero_subtitle', $semiCopyDefaults['hero_subtitle']);
require_once __DIR__ . '/../includes/hero-text-colors.php';
$semiHeroTitleColor = am_normalize_hex_color($seminuevosData['hero_title_color'] ?? '');
$semiHeroSubtitleColor = am_normalize_hex_color($seminuevosData['hero_subtitle_color'] ?? '');
$semiHeroTitleColorAttr = am_hero_text_color_attr($semiHeroTitleColor);
$semiHeroSubtitleColorAttr = am_hero_text_color_attr($semiHeroSubtitleColor);
$semiHeroTitleClass = 'display-5 fw-bold font-montserrat' . ($semiHeroTitleColor === '' ? ' text-navy' : '');
$semiHeroSubtitleClass = $semiHeroSubtitleColor === '' ? 'text-muted' : '';
$semiInventoryEyebrow = seminuevos_public_copy($seminuevosData, 'inventory_eyebrow', $semiCopyDefaults['inventory_eyebrow']);
$semiAnatomyEyebrow = seminuevos_public_copy($seminuevosData, 'anatomy_eyebrow', $semiCopyDefaults['anatomy_eyebrow']);
$semiAnatomyTitle = seminuevos_public_copy($seminuevosData, 'anatomy_title', $semiCopyDefaults['anatomy_title']);
$semiAnatomySubtitle = seminuevos_public_copy($seminuevosData, 'anatomy_subtitle', $semiCopyDefaults['anatomy_subtitle']);
$semiAnatomyImageAlt = seminuevos_public_copy($seminuevosData, 'anatomy_image_alt', $semiCopyDefaults['anatomy_image_alt']);
$semiOpinionesSection = seminuevos_opiniones_section_copy($seminuevosData);
$hbConfig = HeaderBannerService::normalizeFromNode($seminuevosData, 'banner_image_url');
$anatomyImage = $seminuevosData['anatomy_image_url'] ?? 'https://dev.automarket.com.pa/images/anatomia-sn.webp';
$anatomyPoints = $seminuevosData['anatomy_points'] ?? [];
$opiniones = $seminuevosData['opiniones'] ?? [];

// Fetch 20 random available vehicles from database
$db = Database::getInstance();
$randKeyword = $db->getRandomKeyword();
$vehicles = $db->select("SELECT * FROM Automarket_Invs_web WHERE Status = 'DISPONIBLE' ORDER BY $randKeyword LIMIT 20");
?>

<style>
.anatomy-container {
    position: relative;
    max-width: 900px;
    margin: 0 auto;
}
.anatomy-img {
    width: 100%;
    height: auto;
    display: block;
}
.punto-anatomia {
    position: absolute;
    width: 24px;
    height: 24px;
    background-color: #f43f5e; /* Premium crimson color */
    border-radius: 50%;
    border: 2px solid #ffffff;
    cursor: pointer;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.4);
    transform: translate(-50%, -50%);
    z-index: 10;
    transition: transform 0.2s ease, background-color 0.2s ease;
}
.punto-anatomia:hover {
    transform: translate(-50%, -50%) scale(1.25);
    background-color: #081026; /* Deep navy */
}
/* Pulse animation */
.punto-anatomia::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: 50%;
    border: 2px solid rgba(244, 63, 94, 0.5);
    animation: pulse 2s infinite;
    z-index: -1;
}
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(2);
        opacity: 0;
    }
}
/* Custom styling for tooltip text */
.anatomia-azul {
    color: #38bdf8;
    font-weight: 700;
    font-size: 0.85rem;
}
.anatomia-rojo {
    color: #f43f5e;
    font-weight: 700;
    font-size: 0.85rem;
}
.tooltip-inner {
    background-color: #081026 !important;
    color: #ffffff !important;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px !important;
    padding: 10px 14px !important;
    font-family: 'Poppins', sans-serif !important;
    text-align: center !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
}
.tooltip-arrow::before {
    border-top-color: #081026 !important;
    border-bottom-color: #081026 !important;
}
#cta-hero {
    min-height: 450px;
    padding: 0;
    transition: all 0.3s ease;
}
@media (max-width: 768px) {
    #cta-hero {
        min-height: 250px;
    }
}

/* Testimonials Carousel styles */
.opiniones-carousel-container {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 50px;
}
.opiniones-carousel-wrapper {
    overflow: hidden;
    width: 100%;
}
.opiniones-carousel-track {
    display: flex;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
}
.opiniones-carousel-item {
    flex: 0 0 33.3333%;
    padding: 12px;
    box-sizing: border-box;
}
@media (max-width: 991px) {
    .opiniones-carousel-item {
        flex: 0 0 50%;
    }
}
@media (max-width: 767px) {
    .opiniones-carousel-item {
        flex: 0 0 100%;
    }
    .opiniones-carousel-container {
        padding: 0 15px;
    }
}
.opiniones-carousel-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
    color: #081026;
}
.opiniones-carousel-control:hover {
    background-color: #081026;
    color: #ffffff;
    border-color: #081026;
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}
.opiniones-carousel-control.prev {
    left: 0;
}
.opiniones-carousel-control.next {
    right: 0;
}

/* Vehicle Carousel Styles */
.inventory-carousel-container {
    position: relative;
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 40px;
}
.inventory-carousel-wrapper {
    overflow: hidden;
    width: 100%;
    padding: 10px 0;
}
.inventory-carousel-track {
    display: flex;
    transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
    will-change: transform;
}
.inventory-carousel-item {
    flex: 0 0 33.3333%;
    padding: 12px;
    box-sizing: border-box;
}
@media (max-width: 991px) {
    .inventory-carousel-item {
        flex: 0 0 50%;
    }
    .inventory-carousel-container {
        padding: 0 30px;
    }
}
@media (max-width: 767px) {
    .inventory-carousel-item {
        flex: 0 0 100%;
    }
    .inventory-carousel-container {
        padding: 0 15px;
    }
}
.carousel-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.25s ease;
    color: #081026;
}
.carousel-nav-btn:hover {
    background-color: #081026;
    color: #ffffff;
    border-color: #081026;
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}
.carousel-nav-btn.prev {
    left: -10px;
}
.carousel-nav-btn.next {
    right: -10px;
}
@media (max-width: 767px) {
    .carousel-nav-btn {
        display: none;
    }
}

.hero-banner-slider { min-height: 360px; }
</style>
<?php require __DIR__ . '/../includes/inventory-vehicle-card-styles.php'; ?>
<?php require __DIR__ . '/../includes/inventory-highlight-styles.php'; ?>

<?php
$hbSectionId = 'cta-hero';
$hbSkipContainer = true;
$hbInnerHtml = '';
require __DIR__ . '/../includes/render-header-banner.php';
?>

<!-- Content Sections -->
<div class="container py-5" id="inventario">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-primary-subtle text-primary rounded-pill fw-bold text-uppercase tracking-wider mb-2"><?php echo esc($semiInventoryEyebrow); ?></span>
        <h1 class="<?php echo esc($semiHeroTitleClass); ?>"<?php echo $semiHeroTitleColorAttr; ?>>
            <?php echo esc($semiHeroTitle); ?>
        </h1>
        <p class="<?php echo esc($semiHeroSubtitleClass); ?>"<?php echo $semiHeroSubtitleColorAttr; ?>><?php echo esc($semiHeroSubtitle); ?></p>
    </div>

    <!-- Inventory list dynamic autoplaying carousel -->
    <div class="inventory-carousel-container position-relative mb-5">
        <!-- Controls -->
        <button class="carousel-nav-btn prev" id="inv-prev-btn" aria-label="Anterior">
            <i class="bi bi-chevron-left fs-4"></i>
        </button>
        <button class="carousel-nav-btn next" id="inv-next-btn" aria-label="Siguiente">
            <i class="bi bi-chevron-right fs-4"></i>
        </button>

        <!-- Wrapper -->
        <div class="inventory-carousel-wrapper">
            <div class="inventory-carousel-track" id="inv-carousel-track">
                <?php if (empty($vehicles)): ?>
                    <div class="w-100 text-center py-5">
                        <p class="text-muted">No hay vehículos disponibles en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <?php
                        $inventoryCardWrapper = 'carousel';
                        require __DIR__ . '/../includes/inventory-vehicle-card.php';
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- Anatomy Section -->
<section class="py-5 bg-white border-top border-bottom" id="anatomia">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge px-3 py-2 bg-danger-subtle text-danger rounded-pill fw-bold text-uppercase tracking-wider mb-2"><?php echo esc($semiAnatomyEyebrow); ?></span>
            <h2 class="display-5 fw-bold text-navy font-montserrat"><?php echo esc($semiAnatomyTitle); ?></h2>
            <p class="text-muted max-w-600 mx-auto"><?php echo esc($semiAnatomySubtitle); ?></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="anatomy-container">
                    <div class="position-relative overflow-hidden w-100" style="min-height: 250px;">
                        <!-- Vehicle Blueprint Image -->
                        <img src="<?php echo esc($anatomyImage); ?>" alt="<?php echo esc($semiAnatomyImageAlt); ?>" class="anatomy-img mx-auto rounded-3">
                        
                        <!-- Hotspots -->
                        <div class="punto-anatomia" style="left: 2%; top: 48%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto1'] ?? ''); ?>"></div>
                        <div class="punto-anatomia" style="left: 88%; top: 20%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto2'] ?? ''); ?>"></div>
                        <div class="punto-anatomia" style="left: 35%; top: 40%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto3'] ?? ''); ?>"></div>
                        <div class="punto-anatomia" style="left: 30%; top: 70%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto4'] ?? ''); ?>"></div>
                        <div class="punto-anatomia" style="left: 75%; top: 23%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto5'] ?? ''); ?>"></div>
                        <div class="punto-anatomia" style="left: 73%; top: 68%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto6'] ?? ''); ?>"></div>
                        <div class="punto-anatomia" style="left: 95%; top: 60%;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="<?php echo esc($anatomyPoints['punto7'] ?? ''); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="container py-5 mb-5 border-top" id="testimonios">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-navy display-6 font-montserrat"><?php echo esc($semiOpinionesSection['title']); ?></h2>
        <p class="text-muted"><?php echo esc($semiOpinionesSection['subtitle']); ?></p>
    </div>

    <?php if (empty($opiniones)): ?>
        <div class="text-center py-4">
            <p class="text-muted">Aún no hay opiniones registradas para Seminuevos.</p>
        </div>
    <?php else: ?>
        <div class="opiniones-carousel-container">
            <!-- Navigation Controls -->
            <button class="opiniones-carousel-control prev" id="opiniones-prev-btn" aria-label="Anterior">
                <i class="bi bi-chevron-left fs-5"></i>
            </button>
            <button class="opiniones-carousel-control next" id="opiniones-next-btn" aria-label="Siguiente">
                <i class="bi bi-chevron-right fs-5"></i>
            </button>

            <!-- Carousel Wrapper -->
            <div class="opiniones-carousel-wrapper">
                <div class="opiniones-carousel-track" id="opiniones-carousel-track">
                    <?php foreach ($opiniones as $opinion): ?>
                    <div class="opiniones-carousel-item">
                        <div class="testimonial-card p-4 shadow-sm h-100 d-flex flex-column" style="background-color: #fff; border-radius: 16px; border: 1px solid #eee;">
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
            </div>
        </div>
    <?php endif; ?>
</section>

<?php $ucUnitKey = 'seminuevos'; require __DIR__ . '/../includes/unit-content-home-sections.php'; ?>

<!-- Scripts hooks for subpages forms -->
<script>

// Initialize Bootstrap Tooltips
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Testimonials Carousel Logic
document.addEventListener('DOMContentLoaded', function () {
    const track = document.getElementById('opiniones-carousel-track');
    const prevBtn = document.getElementById('opiniones-prev-btn');
    const nextBtn = document.getElementById('opiniones-next-btn');
    
    if (track && prevBtn && nextBtn) {
        const items = track.querySelectorAll('.opiniones-carousel-item');
        const totalItems = items.length;
        let index = 0;
        
        function getItemsPerView() {
            if (window.innerWidth >= 992) return 3;
            if (window.innerWidth >= 768) return 2;
            return 1;
        }
        
        function updateCarousel() {
            const itemsPerView = getItemsPerView();
            const maxIndex = Math.max(0, totalItems - itemsPerView);
            if (index > maxIndex) index = maxIndex;
            
            const itemWidth = items[0].getBoundingClientRect().width;
            const offset = -index * itemWidth;
            track.style.transform = `translateX(${offset}px)`;
            
            // Disable buttons at boundaries
            prevBtn.style.opacity = index === 0 ? '0.3' : '1';
            prevBtn.style.pointerEvents = index === 0 ? 'none' : 'auto';
            
            nextBtn.style.opacity = index >= maxIndex ? '0.3' : '1';
            nextBtn.style.pointerEvents = index >= maxIndex ? 'none' : 'auto';
        }
        
        prevBtn.addEventListener('click', function () {
            if (index > 0) {
                index--;
                updateCarousel();
            }
        });
        
        nextBtn.addEventListener('click', function () {
            const itemsPerView = getItemsPerView();
            const maxIndex = Math.max(0, totalItems - itemsPerView);
            if (index < maxIndex) {
                index++;
                updateCarousel();
            }
        });
        
        window.addEventListener('resize', updateCarousel);
        // Delay initialization slightly to let browser layout stabilize
        setTimeout(updateCarousel, 200);
    }
});

// Vehicle Carousel Autoplay Logic
document.addEventListener('DOMContentLoaded', function () {
    const track = document.getElementById('inv-carousel-track');
    const prevBtn = document.getElementById('inv-prev-btn');
    const nextBtn = document.getElementById('inv-next-btn');
    
    if (track && prevBtn && nextBtn) {
        const items = track.querySelectorAll('.inventory-carousel-item');
        const totalItems = items.length;
        if (totalItems === 0) return;
        
        let index = 0;
        let autoplayInterval;
        
        function getItemsPerView() {
            if (window.innerWidth >= 992) return 3;
            if (window.innerWidth >= 768) return 2;
            return 1;
        }
        
        function updateCarousel() {
            const itemsPerView = getItemsPerView();
            const maxIndex = Math.max(0, totalItems - itemsPerView);
            
            if (index > maxIndex) index = 0; // Wrap around
            if (index < 0) index = maxIndex;
            
            const itemWidth = items[0].getBoundingClientRect().width;
            const offset = -index * itemWidth;
            track.style.transform = `translateX(${offset}px)`;
        }
        
        function startAutoplay() {
            stopAutoplay();
            autoplayInterval = setInterval(function () {
                const itemsPerView = getItemsPerView();
                const maxIndex = Math.max(0, totalItems - itemsPerView);
                
                if (index >= maxIndex) {
                    index = 0; // Wrap around
                } else {
                    index++;
                }
                updateCarousel();
            }, 4000); // Auto slide every 4 seconds
        }
        
        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
            }
        }
        
        prevBtn.addEventListener('click', function () {
            stopAutoplay();
            index--;
            updateCarousel();
            startAutoplay();
        });
        
        nextBtn.addEventListener('click', function () {
            stopAutoplay();
            index++;
            updateCarousel();
            startAutoplay();
        });
        
        // Touch events for mobile swiping
        let startX = 0;
        let currentX = 0;
        
        track.addEventListener('touchstart', function (e) {
            stopAutoplay();
            startX = e.touches[0].clientX;
        }, { passive: true });
        
        track.addEventListener('touchmove', function (e) {
            currentX = e.touches[0].clientX;
        }, { passive: true });
        
        track.addEventListener('touchend', function () {
            const diffX = startX - currentX;
            if (Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    index++;
                } else {
                    index--;
                }
                updateCarousel();
            }
            startAutoplay();
        });
        
        window.addEventListener('resize', function () {
            updateCarousel();
        });
        
        // Hover over track pauses autoplay
        track.addEventListener('mouseenter', stopAutoplay);
        track.addEventListener('mouseleave', startAutoplay);
        
        // Delay initialization slightly to let browser layout stabilize
        setTimeout(function() {
            updateCarousel();
            startAutoplay();
        }, 300);
    }
});
</script>

<?php
$_sfItems  = $seminuevosData['faqs'] ?? [];
require __DIR__ . '/../includes/schema-faq.php';
?>
<?php
$_ufsItems = $seminuevosData['faqs'] ?? [];
require __DIR__ . '/../includes/unit-faq-section.php';
?>

<?php
require_once __DIR__ . '/../includes/unit-footer-prepare.php';
am_unit_footer_prepare($seminuevosData);
require __DIR__ . '/../includes/unit-payment-social.php';
?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
