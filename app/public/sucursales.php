<?php
/**
 * Automarket - Nuestras Sucursales (Rent A Car Locations)
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/location-public-helper.php';

$sucursalesRaw = $contentService->get('homepage.sucursales', []);
$sucursales = am_list_sucursales_for_unit($contentService, 'rentacar', $sucursalesRaw);

$_schemaLocationList = $sucursales;
require_once __DIR__ . '/../includes/schema-location-itemlist.php';
?>

<!-- Leaflet.js Interactive Map Assets -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<!-- 1. Breadcrumb and Title Section -->
<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sucursales</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Nuestras Sucursales</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Encuentra las sucursales de Automarket Rent a Car en Panamá: ubicaciones convenientes para facilitar tu experiencia.</p>
    </div>
</section>

<!-- 2. Accordion and Promo Widget Container -->
<section class="container py-5 mb-5">
    <div class="row g-4">
        <!-- Left: Accordion list of locations -->
        <div class="col-lg-7 col-12">
            <?php if (empty($sucursales)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-geo-alt text-muted display-1 opacity-25"></i>
                    <h4 class="mt-3 text-muted">No hay sucursales registradas en este momento.</h4>
                </div>
            <?php else: ?>
                <div class="accordion sucursales-accordion d-flex flex-column gap-3" id="sucursalesAccordion">
                    
                    <?php foreach ($sucursales as $index => $suc): 
                        $id = intval($suc['id']);
                        $isFirst = ($index === 0);
                        $collapseId = "sucursal_collapse_" . $id;
                    ?>
                        <div class="accordion-item border rounded-3 overflow-hidden shadow-sm" style="background-color: #ffffff;">
                            <!-- Header Trigger button -->
                            <h2 class="accordion-header mb-0">
                                <button class="accordion-button sucursal-header-btn <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
                                    <i class="bi bi-geo-alt-fill me-2 fs-5"></i>
                                    <span><?php echo esc($suc['name']); ?></span>
                                </button>
                            </h2>
                            
                            <!-- Collapse body -->
                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" data-bs-parent="#sucursalesAccordion">
                                <div class="accordion-body p-4 bg-white border-top">
                                    <?php $_locSlug = $suc['slug'] ?? ''; require __DIR__ . '/../includes/location-ficha-link.php'; ?>
                                    <div class="row g-4 align-items-stretch">
                                        <!-- Column 1: Details -->
                                        <div class="col-md-6 col-12 d-flex flex-column justify-content-between">
                                            <div class="sucursal-info-list d-flex flex-column gap-3">
                                                
                                                <?php if (!empty($suc['location'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-danger font-montserrat d-block mb-1">
                                                            <i class="bi bi-geo-alt-fill me-2"></i>Ubicado en:
                                                        </span>
                                                        <span class="info-value text-navy font-poppins fs-6 fw-semibold ps-4">
                                                            <?php echo esc($suc['location']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['address'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-danger font-montserrat d-block mb-1">
                                                            <i class="bi bi-map-fill me-2"></i>Dirección:
                                                        </span>
                                                        <span class="info-value text-muted font-poppins ps-4">
                                                            <?php echo esc($suc['address']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['schedule'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-danger font-montserrat d-block mb-1">
                                                            <i class="bi bi-clock-fill me-2"></i>Horario:
                                                        </span>
                                                        <span class="info-value text-muted font-poppins ps-4">
                                                            <?php echo esc($suc['schedule']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['phone'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-danger font-montserrat d-block mb-1">
                                                            <i class="bi bi-telephone-fill me-2"></i>Teléfono:
                                                        </span>
                                                        <span class="info-value ps-4">
                                                            <a href="tel:<?php echo preg_replace('/\D/', '', $suc['phone']); ?>" class="text-navy text-decoration-none fw-bold hover-red">
                                                                <?php echo esc($suc['phone']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                            </div>

                                            <div class="mt-4 pt-3 border-top">
                                                <a href="https://maps.google.com?saddr=Current+Location&daddr=<?php echo esc($suc['lat']); ?>,<?php echo esc($suc['lng']); ?>" target="_blank" class="btn btn-danger rounded-pill px-4 py-2 text-uppercase font-montserrat fw-semibold btn-sm shadow-sm">
                                                    <i class="bi bi-arrow-up-right-circle me-1"></i>¿Cómo llegar? (Google Maps)
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Column 2: Leaflet Interactive Map -->
                                        <div class="col-md-6 col-12 position-relative d-flex">
                                            <div id="map_<?php echo $id; ?>" class="rounded-3 shadow-sm border w-100 flex-grow-1" style="min-height: 280px; background-color: #f1f3f7; z-index: 1;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leaflet map initialization hook script -->
                        <?php 
                        $lat = floatval($suc['lat'] ?: 8.986518);
                        $lng = floatval($suc['lng'] ?: -79.528439);
                        ?>
                        <script>
                        (function() {
                            let map_<?php echo $id; ?> = null;
                            let marker_<?php echo $id; ?> = null;
                            let collapseElement = document.getElementById('<?php echo $collapseId; ?>');

                            collapseElement.addEventListener('shown.bs.collapse', function() {
                                if (!map_<?php echo $id; ?>) {
                                    // Initialize map coordinate point
                                    map_<?php echo $id; ?> = L.map("map_<?php echo $id; ?>").setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 16);
                                    
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    }).addTo(map_<?php echo $id; ?>);

                                    // Set standard marker icon
                                    let customIcon = L.icon({
                                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                                        iconSize: [25, 41],
                                        iconAnchor: [12, 41],
                                        popupAnchor: [1, -34],
                                        shadowSize: [41, 41]
                                    });

                                    marker_<?php echo $id; ?> = L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>], { icon: customIcon })
                                        .addTo(map_<?php echo $id; ?>)
                                        .bindPopup('<span class="fw-bold text-navy"><?php echo esc($suc['name']); ?></span><br><small class="text-muted"><?php echo esc($suc['location']); ?></small>');
                                } else {
                                    // Reset bounds if already loaded
                                    map_<?php echo $id; ?>.invalidateSize();
                                }
                                marker_<?php echo $id; ?>.openPopup();
                            });
                        })();
                        </script>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Promo widget card -->
        <div class="col-lg-5 col-12">
            <div class="sticky-widget">
                <div class="card border-0 overflow-hidden shadow-sm" style="border-radius: 20px; border: 1px solid #e3e6f0 !important;">
                    <!-- Red Header Section -->
                    <div class="p-5 text-center text-white" style="background-color: #c51f17;">
                        <h3 class="fw-bold mb-3 font-montserrat text-uppercase" style="font-size: 1.45rem; letter-spacing: -0.3px; line-height: 1.3;">¡Alquila tu vehículo ahora!</h3>
                        <p class="small mb-4 font-poppins opacity-90" style="line-height: 1.6; font-size: 0.92rem;">
                            ¡Aprovecha nuestras ofertas por tiempo limitado y vive experiencias increíbles!
                        </p>
                        <a href="/rent-a-car.php#cta-hero" class="btn btn-light px-5 py-3 fw-bold text-uppercase rounded-pill shadow-sm" style="color: #c51f17; font-family: 'Montserrat', sans-serif; font-size: 0.95rem; letter-spacing: 0.5px;">
                            Reserva Ya
                        </a>
                    </div>
                    
                    <!-- Image Section -->
                    <div class="position-relative">
                        <img src="/assets/img/sucursales-rac.webp" alt="Te acompañamos a tu destino" class="w-100" style="object-fit: cover; display: block; max-height: 500px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom styling overrides for sucursal page structure -->
<style>
    .sucursal-header-btn {
        background-color: #ffffff !important;
        color: #081026 !important;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
        font-size: 1.15rem;
        padding: 20px 24px;
        box-shadow: none !important;
        border: none !important;
        transition: all 0.3s ease;
    }
    .sucursal-header-btn:not(.collapsed) {
        color: #c51f17 !important;
        border-left: 5px solid #c51f17 !important;
        padding-left: 19px;
    }
    .info-label {
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .hover-red:hover {
        color: #c51f17 !important;
        text-decoration: underline !important;
    }
    .sucursales-accordion .accordion-item {
        border-radius: 12px !important;
        border: 1px solid #e3e6f0 !important;
    }
    /* Fixed popup bounds on leaflet maps */
    .leaflet-popup-content-wrapper {
        border-radius: 8px !important;
        font-family: 'Poppins', sans-serif;
    }
    @media (min-width: 992px) {
        .sticky-widget {
            position: sticky;
            top: 100px;
            z-index: 10;
        }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
