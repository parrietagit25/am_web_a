<?php
/**
 * Automarket - Sucursales Renting
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/location-public-helper.php';

$sucursalesRaw = $contentService->get('renting.sucursales', []);
$sucursales = am_list_sucursales_for_unit($contentService, 'renting', $sucursalesRaw);

$hasMap = false;
foreach ($sucursales as $s) {
    if (trim((string)($s['lat'] ?? '')) !== '' && trim((string)($s['lng'] ?? '')) !== '') {
        $hasMap = true;
        break;
    }
}

$sideImage = $contentService->get('renting.contact.contact_image_url', '');
if (empty($sideImage)) {
    $sideImage = '/assets/img/sucursales-rac.webp';
}
?>

<?php if ($hasMap): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/renting.php" class="text-danger text-decoration-none fw-semibold">Renting</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sucursales</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Nuestras Sucursales</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Encuentra las sucursales de Automarket Renting en Panamá.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row g-4">
        <div class="col-lg-7 col-12">
            <?php if (empty($sucursales)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-geo-alt text-muted display-1 opacity-25"></i>
                    <h4 class="mt-3 text-muted">No hay sucursales registradas en este momento.</h4>
                </div>
            <?php else: ?>
                <div class="accordion sucursales-accordion d-flex flex-column gap-3" id="rentingSucursalesAccordion">
                    <?php foreach ($sucursales as $index => $suc):
                        $sucId      = intval($suc['id']);
                        $isFirst    = ($index === 0);
                        $collapseId = 'renting_sucursal_collapse_' . $sucId;
                        $sucLat     = trim((string)($suc['lat'] ?? ''));
                        $sucLng     = trim((string)($suc['lng'] ?? ''));
                        $hasCoords  = $sucLat !== '' && $sucLng !== '';
                        $sucMapUrl  = trim((string)($suc['map_url'] ?? ''));
                    ?>
                        <div class="accordion-item border rounded-3 overflow-hidden shadow-sm" style="background-color: #ffffff;">
                            <h2 class="accordion-header mb-0">
                                <button class="accordion-button sucursal-header-btn <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
                                    <i class="bi bi-geo-alt-fill me-2 fs-5"></i>
                                    <span><?php echo esc($suc['name']); ?></span>
                                </button>
                            </h2>

                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" data-bs-parent="#rentingSucursalesAccordion">
                                <div class="accordion-body p-4 bg-white border-top">
                                    <div class="row g-4 align-items-stretch">
                                        <div class="col-md-6 col-12 d-flex flex-column justify-content-between">
                                            <div class="sucursal-info-list d-flex flex-column gap-3">
                                                <?php if (!empty($suc['location'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-theme font-montserrat d-block mb-1">
                                                            <i class="bi bi-geo-alt-fill me-2"></i>Ubicado en:
                                                        </span>
                                                        <span class="info-value text-navy font-poppins fs-6 fw-semibold ps-4">
                                                            <?php echo esc($suc['location']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['address'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-theme font-montserrat d-block mb-1">
                                                            <i class="bi bi-map-fill me-2"></i>Dirección:
                                                        </span>
                                                        <span class="info-value text-muted font-poppins ps-4">
                                                            <?php echo esc($suc['address']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['schedule'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-theme font-montserrat d-block mb-1">
                                                            <i class="bi bi-clock-fill me-2"></i>Horario:
                                                        </span>
                                                        <span class="info-value text-muted font-poppins ps-4" style="white-space: pre-line;">
                                                            <?php echo esc($suc['schedule']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['phone'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-theme font-montserrat d-block mb-1">
                                                            <i class="bi bi-telephone-fill me-2"></i>Teléfono:
                                                        </span>
                                                        <span class="info-value ps-4">
                                                            <a href="tel:<?php echo preg_replace('/\D/', '', $suc['phone']); ?>" class="text-navy text-decoration-none fw-bold hover-theme">
                                                                <?php echo esc($suc['phone']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['whatsapp'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-theme font-montserrat d-block mb-1">
                                                            <i class="bi bi-whatsapp me-2"></i>WhatsApp:
                                                        </span>
                                                        <span class="info-value ps-4">
                                                            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $suc['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="text-navy text-decoration-none fw-bold hover-theme">
                                                                <?php echo esc($suc['whatsapp']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($suc['email'])): ?>
                                                    <div class="info-item-block">
                                                        <span class="info-label text-theme font-montserrat d-block mb-1">
                                                            <i class="bi bi-envelope-fill me-2"></i>Email:
                                                        </span>
                                                        <span class="info-value ps-4">
                                                            <a href="mailto:<?php echo esc($suc['email']); ?>" class="text-navy text-decoration-none hover-theme">
                                                                <?php echo esc($suc['email']); ?>
                                                            </a>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mt-4 pt-3 border-top">
                                                <?php if ($hasCoords): ?>
                                                    <a href="https://maps.google.com?saddr=Current+Location&daddr=<?php echo esc($sucLat); ?>,<?php echo esc($sucLng); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-theme rounded-pill px-4 py-2 text-uppercase font-montserrat fw-semibold btn-sm shadow-sm text-white">
                                                        <i class="bi bi-arrow-up-right-circle me-1"></i>¿Cómo llegar? (Google Maps)
                                                    </a>
                                                <?php elseif (!empty($sucMapUrl)): ?>
                                                    <a href="<?php echo esc($sucMapUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-theme rounded-pill px-4 py-2 text-uppercase font-montserrat fw-semibold btn-sm shadow-sm text-white">
                                                        <i class="bi bi-arrow-up-right-circle me-1"></i>Ver en Google Maps
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-12 position-relative d-flex">
                                            <?php if ($hasCoords): ?>
                                                <div id="renting_map_<?php echo $sucId; ?>" class="rounded-3 shadow-sm border w-100 flex-grow-1" style="min-height: 280px; background-color: #f1f3f7; z-index: 1;"></div>
                                            <?php else: ?>
                                                <div class="rounded-3 border w-100 d-flex align-items-center justify-content-center bg-light" style="min-height: 180px;">
                                                    <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Sin coordenadas registradas</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($hasCoords):
                            $mapLat = floatval($sucLat ?: 8.986518);
                            $mapLng = floatval($sucLng ?: -79.528439);
                        ?>
                        <script>
                        (function() {
                            let map_<?php echo $sucId; ?> = null;
                            let marker_<?php echo $sucId; ?> = null;
                            const collapseEl_<?php echo $sucId; ?> = document.getElementById('<?php echo $collapseId; ?>');
                            collapseEl_<?php echo $sucId; ?>.addEventListener('shown.bs.collapse', function() {
                                if (!map_<?php echo $sucId; ?>) {
                                    map_<?php echo $sucId; ?> = L.map('renting_map_<?php echo $sucId; ?>').setView([<?php echo $mapLat; ?>, <?php echo $mapLng; ?>], 16);
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    }).addTo(map_<?php echo $sucId; ?>);
                                    let customIcon = L.icon({
                                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                                        iconSize: [25, 41], iconAnchor: [12, 41],
                                        popupAnchor: [1, -34], shadowSize: [41, 41]
                                    });
                                    marker_<?php echo $sucId; ?> = L.marker([<?php echo $mapLat; ?>, <?php echo $mapLng; ?>], { icon: customIcon })
                                        .addTo(map_<?php echo $sucId; ?>)
                                        .bindPopup('<span class="fw-bold"><?php echo addslashes(esc($suc['name'])); ?></span>');
                                } else {
                                    map_<?php echo $sucId; ?>.invalidateSize();
                                }
                                marker_<?php echo $sucId; ?>.openPopup();
                            });
                        })();
                        </script>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5 col-12">
            <div class="sticky-widget">
                <div class="card border-0 overflow-hidden shadow-sm" style="border-radius: 20px; border: 1px solid #e3e6f0 !important;">
                    <div class="p-5 text-center text-white" style="background-color: var(--theme-primary);">
                        <h3 class="fw-bold mb-3 font-montserrat text-uppercase" style="font-size: 1.45rem; letter-spacing: -0.3px; line-height: 1.3;">Cotiza tu plan de Renting</h3>
                        <p class="small mb-4 font-poppins opacity-90" style="line-height: 1.6; font-size: 0.92rem;">
                            Tu auto nuevo, una cuota mensual con todo incluido. Cobertura en todo el país.
                        </p>
                        <a href="/renting.php" class="btn btn-light px-5 py-3 fw-bold text-uppercase rounded-pill shadow-sm" style="color: var(--theme-primary); font-family: 'Montserrat', sans-serif; font-size: 0.95rem; letter-spacing: 0.5px;">
                            Cotizar plan
                        </a>
                    </div>
                    <div class="position-relative">
                        <img src="<?php echo esc($sideImage); ?>" alt="Renting Automarket" class="w-100" style="object-fit: cover; display: block; max-height: 380px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .text-theme { color: var(--theme-primary) !important; }
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
        color: var(--theme-primary) !important;
        border-left: 5px solid var(--theme-primary) !important;
        padding-left: 19px;
    }
    .info-label {
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .hover-theme:hover {
        color: var(--theme-primary) !important;
        text-decoration: underline !important;
    }
    .sucursales-accordion .accordion-item {
        border-radius: 12px !important;
        border: 1px solid #e3e6f0 !important;
    }
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
