<?php
/**
 * Automarket - Sucursales Taller
 */
$activeUnit = 'taller';
require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/location-public-helper.php';

$sucursalesRaw = $contentService->get('taller.sucursales', []);
$sucursales = am_list_sucursales_for_unit($contentService, 'taller', $sucursalesRaw);

$title = $contentService->get('taller.sucursales_title', 'Nuestras Sucursales');
$subtitle = $contentService->get('taller.sucursales_subtitle', 'Encuentra nuestros talleres y centros de atención.');
$sideImage = $contentService->get('taller.sucursales_image_url', '');
if (empty($sideImage)) {
    $sideImage = '/assets/img/sucursales-rac.webp';
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/taller.php" class="text-decoration-none fw-semibold" style="color: var(--theme-primary);">Taller</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sucursales</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.3rem; letter-spacing: -0.5px;"><?php echo esc($title); ?></h1>
        <p class="text-muted font-poppins mt-2 mb-0"><?php echo esc($subtitle); ?></p>
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
                <div class="accordion sucursales-accordion d-flex flex-column gap-3" id="tallerSucursalesAccordion">
                    <?php foreach ($sucursales as $index => $suc):
                        $id = intval($suc['id']);
                        $isFirst = ($index === 0);
                        $collapseId = 'taller_sucursal_collapse_' . $id;
                    ?>
                        <div class="accordion-item border rounded-3 overflow-hidden shadow-sm bg-white">
                            <h2 class="accordion-header mb-0">
                                <button class="accordion-button sucursal-header-btn <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
                                    <span><?php echo esc($suc['name']); ?></span>
                                </button>
                            </h2>
                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" data-bs-parent="#tallerSucursalesAccordion">
                                <div class="accordion-body p-4 bg-white border-top">
                                    <div class="row g-4 align-items-stretch">
                                        <div class="col-md-6 col-12">
                                            <div class="sucursal-info-list d-flex flex-column gap-2">
                                                <?php if (!empty($suc['address'])): ?><div><strong>Dirección:</strong><div class="text-muted"><?php echo esc($suc['address']); ?></div></div><?php endif; ?>
                                                <?php if (!empty($suc['schedule'])): ?><div><strong>Horario:</strong><div class="text-muted"><?php echo esc($suc['schedule']); ?></div></div><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12 d-flex">
                                            <div id="taller_map_<?php echo $id; ?>" class="rounded-3 shadow-sm border w-100 flex-grow-1" style="min-height: 230px; background-color: #f1f3f7;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $lat = floatval($suc['lat'] ?: 8.986518);
                        $lng = floatval($suc['lng'] ?: -79.528439);
                        ?>
                        <script>
                        (function() {
                            let mapEl = null;
                            let marker = null;
                            const collapseElement = document.getElementById('<?php echo $collapseId; ?>');
                            collapseElement.addEventListener('shown.bs.collapse', function() {
                                if (!mapEl) {
                                    mapEl = L.map("taller_map_<?php echo $id; ?>").setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 16);
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    }).addTo(mapEl);
                                    marker = L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>]).addTo(mapEl);
                                } else {
                                    mapEl.invalidateSize();
                                }
                                marker.openPopup();
                            });
                        })();
                        </script>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5 col-12">
            <div class="sticky-widget">
                <div class="card border-0 overflow-hidden shadow-sm" style="border-radius: 18px; border: 1px solid #e3e6f0 !important;">
                    <div class="position-relative">
                        <img src="<?php echo esc($sideImage); ?>" alt="Sucursales Taller" class="w-100" style="object-fit: cover; display: block; max-height: 500px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.sucursal-header-btn {
    background-color: #ffffff !important;
    color: #081026 !important;
    font-weight: 700;
    font-family: 'Montserrat', sans-serif;
    font-size: 1.15rem;
    padding: 18px 22px;
    box-shadow: none !important;
    border: none !important;
}
.sucursal-header-btn:not(.collapsed) {
    color: var(--theme-primary) !important;
    border-left: 5px solid var(--theme-primary) !important;
    padding-left: 17px;
}
.sucursales-accordion .accordion-item { border-radius: 12px !important; border: 1px solid #e3e6f0 !important; }
@media (min-width: 992px) { .sticky-widget { position: sticky; top: 100px; z-index: 10; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
