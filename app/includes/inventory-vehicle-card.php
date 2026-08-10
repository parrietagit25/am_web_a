<?php
/**
 * Tarjeta de vehículo unificada (inventario grid + carrusel venta-autos + AJAX).
 *
 * @var array<string, mixed> $vehicle
 * @var array<string, string> $inventoryHighlightAssignments
 * @var array<string, array{enabled: bool, text: string}> $inventoryHighlightMetadata
 * @var string|null $inventoryCardWrapper 'col' (default) | 'carousel' | 'none'
 */
require_once __DIR__ . '/../services/InventoryHighlightService.php';
if (!class_exists('VehicleSlugHelper')) {
    require_once __DIR__ . '/../services/VehicleSlugHelper.php';
}
$_cardUrl = VehicleSlugHelper::toDetalleUrl($vehicle) ?? ('/detalle.php?placa=' . urlencode($vehicle['LicensePlate'] ?? ''));
$_cardWrapper = $inventoryCardWrapper ?? 'col';
if (!in_array($_cardWrapper, ['col', 'carousel', 'none'], true)) {
    $_cardWrapper = 'col';
}

$photoUrl = !empty($vehicle['Photo']) ? $vehicle['Photo'] : 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?q=80&w=600&auto=format&fit=crop';
if (!empty($vehicle['foto_impel'])) {
    $photoUrl = $vehicle['foto_impel'];
}
$fullName = trim(($vehicle['Make'] ?? '') . ' ' . ($vehicle['Model'] ?? ''));
$priceVal = (float) ($vehicle['Price'] ?? 0);
$tipoCompra = !empty($vehicle['tipo_compra']) ? $vehicle['tipo_compra'] : 'Seminuevo';
$transmission = !empty($vehicle['Transmission']) ? $vehicle['Transmission'] : 'AUTOMATICO';
$highlightBadge = InventoryHighlightService::resolveBadge(
    $vehicle,
    $inventoryHighlightAssignments ?? [],
    $inventoryHighlightMetadata ?? []
);

$badgeBgColor = '#1f347f';
if ($tipoCompra === 'GARANTIZADOS') {
    $badgeBgColor = '#dc3545';
} elseif ($tipoCompra === 'SIN GARANTIA') {
    $badgeBgColor = '#6c757d';
}

if ($_cardWrapper === 'col') {
    echo '<div class="col-lg-4 col-md-6 col-sm-6 col-12 d-flex">';
} elseif ($_cardWrapper === 'carousel') {
    echo '<div class="inventory-carousel-item">';
}
?>
    <div class="card vehicle-card border-0 shadow-sm w-100 h-100 d-flex flex-column justify-content-between overflow-hidden position-relative">
        <span class="position-absolute px-3 py-1.5 text-white fw-bold top-3 start-3 text-uppercase inv-tipo-compra-badge" style="background-color: <?php echo esc($badgeBgColor); ?>; font-size: 0.72rem; border-radius: 4px; z-index: 10; letter-spacing: 0.05em;">
            <?php echo esc($tipoCompra); ?>
        </span>

        <a href="<?php echo esc($_cardUrl); ?>" class="vehicle-img-container overflow-hidden d-block position-relative">
            <?php
            $highlightVariant = 'card';
            require __DIR__ . '/inventory-highlight-badge.php';
            ?>
            <img src="<?php echo esc($photoUrl); ?>" alt="<?php echo esc($fullName); ?>">
        </a>

        <div class="card-body d-flex flex-column justify-content-between flex-grow-1">
            <div>
                <a href="<?php echo esc($_cardUrl); ?>" class="text-decoration-none">
                    <h5 class="fw-bold text-navy card-title mb-2 text-uppercase font-montserrat" style="font-size: 1.05rem; min-height: 2.7rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.3;">
                        <?php echo esc($fullName); ?> <?php echo esc($vehicle['Year'] ?? ''); ?>
                    </h5>
                </a>

                <div class="card-spec-line">
                    <?php echo esc($vehicle['Year'] ?? ''); ?> | <?php echo number_format((int) ($vehicle['Km'] ?? 0)); ?> <?php echo esc(t('inventory.km')); ?> | <?php echo esc($transmission); ?> | <?php echo esc($tipoCompra); ?>
                </div>
            </div>

            <div class="mt-auto">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="price-container">
                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 500; font-family: 'Poppins', sans-serif; line-height: 1;"><?php echo esc(t('common.from')); ?></div>
                        <div class="card-price-balboa">
                            B/. <?php echo number_format($priceVal, 0); ?><sup style="font-size: 0.6em; top: -0.4em; font-weight: 800;">.00</sup>
                        </div>
                    </div>
                    <a href="<?php echo esc($_cardUrl); ?>" class="card-cotizar-link text-decoration-none"><?php echo esc(t('common.quote_here')); ?></a>
                </div>
                <div class="price-subtext-muted"><?php echo esc(t('common.price_no_tax')); ?></div>
            </div>
        </div>
    </div>
<?php
if ($_cardWrapper === 'col' || $_cardWrapper === 'carousel') {
    echo '</div>';
}
unset($_cardUrl, $_cardWrapper, $inventoryCardWrapper);
?>
