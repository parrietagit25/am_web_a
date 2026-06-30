<?php
/**
 * Automarket - Venta de Autos Homepage
 */
$activeUnit = 'seminuevos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/Database.php';
require_once __DIR__ . '/../services/HeaderBannerService.php';

// Fetch Seminuevos data from content service
$seminuevosData = $contentService->get('seminuevos', []);
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

/* Vehicle card custom style */
.vehicle-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background: #ffffff;
    border: 1px solid #f1f5f9;
}
.vehicle-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(8, 16, 38, 0.08) !important;
}
.vehicle-card:hover .vehicle-img-container img {
    transform: scale(1.05);
}
.badge-grey-pill {
    background-color: #f1f5f9;
    color: #64748b;
    font-size: 0.72rem;
    padding: 5px 12px;
    border-radius: 50px;
    font-weight: 600;
    display: inline-block;
}
.card-price-blue {
    color: #2563eb; /* Royal blue */
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1;
}
.price-subtext {
    font-size: 0.72rem;
    color: #94a3b8;
    margin-top: 3px;
    font-weight: 500;
}
.hero-banner-slider { min-height: 360px; }
</style>

<?php
$hbSectionId = 'cta-hero';
$hbSectionExtraStyle = 'cursor: pointer;';
$hbSectionOnclick = "window.location.href='/inventario.php'";
$hbSkipContainer = true;
$hbInnerHtml = '';
require __DIR__ . '/../includes/render-header-banner.php';
?>

<div class="container">
    <h1 class="visually-hidden">
        <?php echo htmlspecialchars(trim($seminuevosData['hero_title'] ?? '') ?: 'Autos Seminuevos en Venta en Panamá', ENT_QUOTES, 'UTF-8'); ?>
    </h1>
</div>

<!-- Content Sections -->
<div class="container py-5" id="inventario">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-primary-subtle text-primary rounded-pill fw-bold text-uppercase tracking-wider mb-2">Seminuevos</span>
        <h2 class="display-5 fw-bold text-navy font-montserrat">Vehículos en Inventario</h2>
        <p class="text-muted">Todos nuestros autos han pasado por inspección de 150 puntos.</p>
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
                        $photoUrl = !empty($vehicle['Photo']) ? $vehicle['Photo'] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600&auto=format&fit=crop';
                        if (!empty($vehicle['foto_impel'])) {
                            $photoUrl = $vehicle['foto_impel'];
                        }
                        $fullName = trim($vehicle['Make'] . ' ' . $vehicle['Model']);
                        $priceVal = (float)$vehicle['Price'];
                        $carType = !empty($vehicle['CarType']) ? $vehicle['CarType'] : 'Seminuevo';
                        ?>
                        <div class="inventory-carousel-item">
                            <div class="card vehicle-card border-0 shadow-sm rounded-4 w-100 h-100 d-flex flex-column justify-content-between overflow-hidden position-relative">
                                <span class="category-badge position-absolute bg-white px-3 py-1 text-navy rounded-pill fw-bold shadow-sm top-3 start-3 text-uppercase z-index-2" style="font-size: 0.72rem; color: #081026; z-index: 10;">
                                    <?php echo esc($carType); ?>
                                </span>
                                
                                <a href="/detalle.php?placa=<?php echo urlencode($vehicle['LicensePlate']); ?>" class="vehicle-img-container bg-light-gray text-center overflow-hidden d-block" style="height: 190px; display: flex; align-items: center; justify-content: center; position: relative;">
                                    <img src="<?php echo esc($photoUrl); ?>" alt="<?php echo esc($fullName); ?>" class="w-100 h-100" style="object-fit: cover; transition: transform 0.3s ease;">
                                </a>
                                
                                <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                                    <div>
                                        <a href="/detalle.php?placa=<?php echo urlencode($vehicle['LicensePlate']); ?>" class="text-decoration-none">
                                            <h5 class="fw-bold text-navy card-title mb-2 text-uppercase font-montserrat" style="font-size: 1.05rem; min-height: 2.7rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.3;">
                                                <?php echo esc($fullName); ?>
                                            </h5>
                                        </a>
                                        
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span class="badge-grey-pill"><?php echo number_format($vehicle['Km']); ?> Km</span>
                                            <span class="badge-grey-pill"><?php echo esc($vehicle['Year']); ?></span>
                                            <span class="badge-grey-pill text-uppercase"><?php echo esc($vehicle['Transmission']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-end justify-content-between mt-auto pt-2">
                                        <div class="price-container">
                                            <div class="card-price-blue font-poppins">$<?php echo number_format($priceVal, 0); ?></div>
                                            <div class="price-subtext">Precio sin impuesto</div>
                                        </div>
                                        <a href="/detalle.php?placa=<?php echo urlencode($vehicle['LicensePlate']); ?>" class="btn btn-theme rounded-pill px-3 py-2 text-white text-sm fw-semibold text-decoration-none" style="font-size: 0.82rem; font-family: 'Poppins', sans-serif;">Ver detalles</a>
                                    </div>
                                </div>
                            </div>
                        </div>
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
            <span class="badge px-3 py-2 bg-danger-subtle text-danger rounded-pill fw-bold text-uppercase tracking-wider mb-2">Garantía y Calidad</span>
            <h2 class="display-5 fw-bold text-navy font-montserrat">Anatomía de tu Seminuevo</h2>
            <p class="text-muted max-w-600 mx-auto">Pasa el cursor por los puntos interactivos del vehículo para descubrir por qué comprar en Automarket es tu mejor opción.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="anatomy-container">
                    <div class="position-relative overflow-hidden w-100" style="min-height: 250px;">
                        <!-- Vehicle Blueprint Image -->
                        <img src="<?php echo esc($anatomyImage); ?>" alt="Anatomía del Vehículo" class="anatomy-img mx-auto rounded-3">
                        
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
        <h2 class="fw-bold text-navy display-6 font-montserrat">Opiniones de Nuestros Clientes</h2>
        <p class="text-muted">Conoce la experiencia de quienes compraron su auto seminuevo con nosotros.</p>
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
$_upsUnitSocialLinks = $seminuevosData['social_links'] ?? [];
require __DIR__ . '/../includes/unit-payment-social.php';
?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
