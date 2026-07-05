<?php
/**
 * Sucursales consolidadas — Grupo Automarket (agrupadas por unidad de negocio).
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';

$contentService = new ContentService();
require_once __DIR__ . '/../includes/location-public-helper.php';
require_once __DIR__ . '/../includes/contact-locations-public-copy.php';

$grupoGroups = am_sucursales_grouped_by_unit($contentService);
$globalData = $contentService->get('global', []);
$grupoPage = sucursales_grupo_page_copy(is_array($globalData) ? $globalData : []);

$grupoFlatList = [];
foreach ($grupoGroups as $grupoGroup) {
    foreach ($grupoGroup['items'] as $grupoItem) {
        $grupoFlatList[] = $grupoItem;
    }
}

require_once __DIR__ . '/../includes/header.php';

$_schemaLocationList = $grupoFlatList;
require_once __DIR__ . '/../includes/schema-location-itemlist.php';
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Automarket</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sucursales</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0"><?php echo esc($grupoPage['title']); ?></h1>
        <p class="text-muted font-poppins mt-2 mb-0"><?php echo esc($grupoPage['subtitle']); ?></p>
    </div>
</section>

<section class="container py-5 mb-5">
    <?php if ($grupoGroups === []): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-geo-alt display-1 opacity-25"></i>
            <p class="mt-3">Información de sucursales próximamente.</p>
        </div>
    <?php else: ?>
        <?php foreach ($grupoGroups as $grupoGroup): ?>
        <div class="mb-5">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-2 mb-4">
                <h2 class="h4 fw-bold text-navy font-montserrat mb-0">
                    <?php echo esc($grupoGroup['label']); ?>
                </h2>
                <a href="<?php echo esc($grupoGroup['page_url']); ?>" class="small text-danger text-decoration-none fw-semibold">
                    Ver sucursales de <?php echo esc($grupoGroup['label']); ?> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="row g-4">
                <?php foreach ($grupoGroup['items'] as $suc): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <?php
                            $_locSlug = trim((string) ($suc['slug'] ?? ''));
                            $_locName = esc($suc['name'] ?? '');
                            ?>
                            <h5 class="fw-bold text-navy mb-3">
                                <?php if ($_locSlug !== ''): ?>
                                    <a href="<?php echo esc(am_location_detail_path($_locSlug)); ?>" class="text-navy text-decoration-none"><?php echo $_locName; ?></a>
                                <?php else: ?>
                                    <?php echo $_locName; ?>
                                <?php endif; ?>
                            </h5>
                            <?php unset($_locSlug, $_locName); ?>
                            <?php if (!empty($suc['location'])): ?>
                                <p class="small mb-1"><i class="bi bi-geo-alt text-danger me-1"></i><?php echo esc($suc['location']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($suc['address'])): ?>
                                <p class="small text-muted mb-1"><?php echo esc($suc['address']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($suc['schedule'])): ?>
                                <p class="small mb-1"><i class="bi bi-clock me-1"></i><?php echo esc($suc['schedule']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($suc['phone'])): ?>
                                <p class="small mb-0">
                                    <a href="tel:<?php echo preg_replace('/\D/', '', $suc['phone']); ?>" class="text-decoration-none fw-semibold"><?php echo esc($suc['phone']); ?></a>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($suc['lat']) && !empty($suc['lng'])): ?>
                                <a href="https://maps.google.com?q=<?php echo urlencode($suc['lat'] . ',' . $suc['lng']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-theme mt-3">Ver en mapa</a>
                            <?php elseif (!empty($suc['map_url'])): ?>
                                <a href="<?php echo esc($suc['map_url']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-theme mt-3">Ver en mapa</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
