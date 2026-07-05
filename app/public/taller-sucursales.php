<?php
/**
 * Automarket - Sucursales Taller
 */
$activeUnit = 'taller';
require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/location-public-helper.php';
require_once __DIR__ . '/../includes/location-accordion-map.php';
am_location_map_reset();

$sucursalesRaw = $contentService->get('taller.sucursales', []);
$sucursales = am_list_sucursales_for_unit($contentService, 'taller', $sucursalesRaw);

$_schemaLocationList = $sucursales;
require_once __DIR__ . '/../includes/schema-location-itemlist.php';

$title = $contentService->get('taller.sucursales_title', 'Nuestras Sucursales');
$subtitle = $contentService->get('taller.sucursales_subtitle', 'Encuentra nuestros talleres y centros de atención.');
$sideImage = $contentService->get('taller.sucursales_image_url', '');
if (empty($sideImage)) {
    $sideImage = '/assets/img/sucursales-rac.webp';
}
?>

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
                        am_location_map_register([
                            'mapId' => 'taller_map_' . $id,
                            'collapseId' => $collapseId,
                            'lat' => $suc['lat'] ?? null,
                            'lng' => $suc['lng'] ?? null,
                            'title' => (string) ($suc['name'] ?? ''),
                            'subtitle' => (string) ($suc['address'] ?? ''),
                            'autoInit' => $isFirst,
                        ]);
                    ?>
                        <div class="accordion-item border rounded-3 overflow-hidden shadow-sm bg-white">
                            <h2 class="accordion-header mb-0">
                                <button class="accordion-button sucursal-header-btn <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
                                    <span><?php echo esc($suc['name']); ?></span>
                                </button>
                            </h2>
                            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" data-bs-parent="#tallerSucursalesAccordion">
                                <div class="accordion-body p-4 bg-white border-top">
                                    <?php $_locSlug = $suc['slug'] ?? ''; require __DIR__ . '/../includes/location-ficha-link.php'; ?>
                                    <div class="row g-4 align-items-stretch">
                                        <div class="col-md-6 col-12">
                                            <div class="sucursal-info-list d-flex flex-column gap-2">
                                                <?php if (!empty($suc['address'])): ?><div><strong>Dirección:</strong><div class="text-muted"><?php echo esc($suc['address']); ?></div></div><?php endif; ?>
                                                <?php if (!empty($suc['schedule'])): ?><div><strong>Horario:</strong><div class="text-muted"><?php echo esc($suc['schedule']); ?></div></div><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12 d-flex">
                                            <?php am_location_map_render_container('taller_map_' . $id, $suc['lat'] ?? '', $suc['lng'] ?? '', trim((string) ($suc['map_url'] ?? '')), 'rounded-3 shadow-sm border w-100 flex-grow-1', 230); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

<?php
am_location_map_render_assets();
am_location_map_render_boot();
require_once __DIR__ . '/../includes/footer.php';
?>
