<?php
/**
 * Automarket - Seminuevos Sucursales
 */
$activeUnit = 'seminuevos';
require_once __DIR__ . '/../includes/header.php';

$siteData = $contentService->getAll();
$semiData = $siteData['seminuevos'] ?? [];
$semiPage = $semiData['sucursales_page'] ?? [];
$semiPageTitle = trim((string) ($semiPage['title'] ?? '')) ?: 'Sucursales';
$semiPageSubtitle = trim((string) ($semiPage['subtitle'] ?? '')) ?: 'Encuentra la sucursal de seminuevos más cercana y cómo llegar.';
$semiSectionEyebrow = trim((string) ($semiPage['section_eyebrow'] ?? '')) ?: 'Nuestras Ubicaciones';
$semiSectionTitle = trim((string) ($semiPage['section_title'] ?? '')) ?: 'Sucursales';
$semiSectionHighlight = trim((string) ($semiPage['section_title_highlight'] ?? '')) ?: 'Automarket';
$semiSectionSubtitleTpl = trim((string) ($semiPage['section_subtitle'] ?? '')) ?: 'Visítanos en cualquiera de nuestras {count} sucursales a nivel nacional';
require_once __DIR__ . '/../includes/location-public-helper.php';
require_once __DIR__ . '/../includes/location-accordion-map.php';
am_location_map_reset();

$semiSucursales = $semiData['sucursales'] ?? [];
$activeSucursales = am_list_sucursales_for_unit($contentService, 'seminuevos', $semiSucursales);

$_schemaLocationList = $activeSucursales;
require_once __DIR__ . '/../includes/schema-location-itemlist.php';
?>

<style>
.sn-breadcrumb-strip { background: #f8f9fc; border-bottom: 1px solid #eaecf3; }
.sn-page-title { font-size: 2.2rem; letter-spacing: -0.5px; }
.sn-accordion-item { background: #fff; border: 1px solid #e3e6f0 !important; border-radius: 12px !important; overflow: hidden; box-shadow: 0 2px 10px rgba(11,31,107,.06); }
.sn-accordion-btn { background: #fff !important; color: #081026 !important; font-weight: 700; font-family: 'Montserrat', sans-serif; font-size: 1.1rem; padding: 20px 24px; box-shadow: none !important; border: none !important; transition: color .2s, border-left .2s; }
.sn-accordion-btn:not(.collapsed) { color: #c51f17 !important; border-left: 5px solid #c51f17 !important; padding-left: 19px; }
.sn-info-label { font-weight: 700; font-size: .82rem; text-transform: uppercase; letter-spacing: .5px; color: #c51f17; font-family: 'Montserrat', sans-serif; display: block; margin-bottom: 3px; }
.sn-info-value { color: #333; font-family: 'Poppins', sans-serif; font-size: .9rem; padding-left: 22px; }
.sn-info-value a { color: #0b1f6b; text-decoration: none; font-weight: 600; }
.sn-info-value a:hover { color: #c51f17; text-decoration: underline; }
.sn-howto-btn { display: inline-flex; align-items: center; gap: 8px; background: #c51f17; color: #fff; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: .82rem; letter-spacing: .5px; text-transform: uppercase; padding: 10px 22px; border-radius: 50px; text-decoration: none; transition: background .2s, transform .15s; }
.sn-howto-btn:hover { background: #a81812; color: #fff; transform: translateY(-1px); }
.sn-section-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; color: #0b1f6b; }
.sn-section-title span { color: #c51f17; }
/* Preview primera sucursal */
.sn-accordion-item--principal { border-left: 4px solid #c51f17 !important; }
.sn-badge-principal { display: inline-flex; align-items: center; gap: 5px; background: #c51f17; color: #fff; font-family: 'Montserrat', sans-serif; font-size: .68rem; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; padding: 3px 10px; border-radius: 50px; vertical-align: middle; margin-left: 10px; }
</style>

<section class="sn-breadcrumb-strip py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins" style="font-size:.84rem;">
                <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-danger text-decoration-none fw-semibold">Venta de Autos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sucursales</li>
            </ol>
        </nav>
        <h1 class="sn-page-title fw-bold text-navy font-montserrat mb-1"><?php echo esc($semiPageTitle); ?></h1>
        <p class="text-muted font-poppins mb-0" style="font-size:.9rem;">
            <?php echo esc($semiPageSubtitle); ?>
        </p>
    </div>
</section>

<section class="container py-5 mb-3">
    <div class="text-center mb-5">
        <span class="d-inline-block text-danger fw-bold text-uppercase font-poppins mb-2" style="font-size:.78rem;letter-spacing:2px;">
            <i class="bi bi-geo-alt-fill me-1"></i><?php echo esc($semiSectionEyebrow); ?>
        </span>
        <h2 class="sn-section-title font-montserrat"><?php echo esc($semiSectionTitle); ?> <span><?php echo esc($semiSectionHighlight); ?></span></h2>
        <p class="text-muted font-poppins mt-2" style="max-width:500px;margin:0 auto;font-size:.88rem;">
            <?php echo esc(str_replace('{count}', (string) count($activeSucursales), $semiSectionSubtitleTpl)); ?>
        </p>
    </div>

    <?php if (empty($activeSucursales)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-building-slash fs-1 mb-3 d-block"></i>
            <p>Sucursales pr&oacute;ximamente disponibles.</p>
        </div>
    <?php else: ?>
        <div class="accordion d-flex flex-column gap-3" id="snSucursalesAccordion">
            <?php foreach ($activeSucursales as $index => $suc):
                $id = intval($suc['id']);
                $isFirst = ($index === 0);
                $collapseId = 'sn_suc_' . $id;
                am_location_map_register([
                    'mapId' => 'snmap_' . $id,
                    'collapseId' => $collapseId,
                    'lat' => $suc['lat'] ?? null,
                    'lng' => $suc['lng'] ?? null,
                    'title' => (string) ($suc['name'] ?? ''),
                    'subtitle' => (string) ($suc['address'] ?? ''),
                    'autoInit' => $isFirst,
                ]);
            ?>
            <div class="accordion-item sn-accordion-item<?php echo $isFirst ? ' sn-accordion-item--principal' : ''; ?>">
                <h2 class="accordion-header mb-0">
                    <button class="accordion-button sn-accordion-btn <?php echo $isFirst ? '' : 'collapsed'; ?>"
                            type="button" data-bs-toggle="collapse"
                            data-bs-target="#<?php echo $collapseId; ?>"
                            aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo $collapseId; ?>">
                        <i class="bi bi-geo-alt-fill me-2 fs-5"></i>
                        <?php echo esc($suc['name']); ?>
                        <?php if ($isFirst): ?><span class="sn-badge-principal"><i class="bi bi-star-fill"></i>Principal</span><?php endif; ?>
                    </button>
                </h2>
                <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" data-bs-parent="#snSucursalesAccordion">
                    <div class="accordion-body p-4 bg-white border-top">
                        <?php $_locSlug = $suc['slug'] ?? ''; require __DIR__ . '/../includes/location-ficha-link.php'; ?>
                        <div class="row g-4 align-items-stretch">
                            <div class="col-md-6 col-12 d-flex flex-column justify-content-between">
                                <div class="d-flex flex-column gap-3">
                                    <?php if (!empty($suc['location'])): ?>
                                        <div>
                                            <span class="sn-info-label"><i class="bi bi-geo-alt-fill me-2"></i>Ubicado en:</span>
                                            <span class="sn-info-value fw-semibold text-navy"><?php echo esc($suc['location']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($suc['address'])): ?>
                                        <div>
                                            <span class="sn-info-label"><i class="bi bi-map-fill me-2"></i>Direcci&oacute;n:</span>
                                            <span class="sn-info-value"><?php echo esc($suc['address']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($suc['schedule'])): ?>
                                        <div>
                                            <span class="sn-info-label"><i class="bi bi-clock-fill me-2"></i>Horario:</span>
                                            <span class="sn-info-value"><?php echo esc($suc['schedule']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($suc['phone'])): ?>
                                        <div>
                                            <span class="sn-info-label"><i class="bi bi-telephone-fill me-2"></i>Tel&eacute;fono:</span>
                                            <span class="sn-info-value">
                                                <a href="tel:<?php echo preg_replace('/\D/', '', $suc['phone']); ?>"><?php echo esc($suc['phone']); ?></a>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-4 pt-3 border-top">
                                    <?php if (am_location_map_has_coords($suc['lat'] ?? '', $suc['lng'] ?? '')): ?>
                                    <a href="https://maps.google.com?saddr=Current+Location&daddr=<?php echo esc($suc['lat']); ?>,<?php echo esc($suc['lng']); ?>" target="_blank" rel="noopener" class="sn-howto-btn">
                                        <i class="bi bi-arrow-up-right-circle"></i>&iquest;C&oacute;mo llegar? (Google Maps)
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 col-12 position-relative d-flex">
                                <?php am_location_map_render_container('snmap_' . $id, $suc['lat'] ?? '', $suc['lng'] ?? '', trim((string) ($suc['map_url'] ?? ''))); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
am_location_map_render_assets();
am_location_map_render_boot();
require_once __DIR__ . '/../includes/footer.php';
?>
