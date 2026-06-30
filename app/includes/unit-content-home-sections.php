<?php
/**
 * Bloques Destacados + Contenido más reciente en home de una unidad.
 *
 * @var string $ucUnitKey
 * @var ContentService $contentService
 */
require_once __DIR__ . '/unit-content-frontend.php';

$ucCarouselId = 'ucSpotlightCarousel-' . preg_replace('/[^a-z0-9_-]/i', '-', $ucUnitKey);
$ucSpotlightEnabled = unit_content_home_block_enabled($contentService, $ucUnitKey);
$ucSpotlightItems = $ucSpotlightEnabled ? unit_content_get_spotlight($contentService, $ucUnitKey) : [];
$ucLatestItems = unit_content_get_latest_home($contentService, $ucUnitKey);
$ucRotationMs = unit_content_rotation_interval($contentService, $ucUnitKey);
$ucDisplayMode = unit_content_home_display_mode($contentService, $ucUnitKey);
?>

<?php if ($ucSpotlightEnabled && !empty($ucSpotlightItems)): ?>
<section class="container py-5 mb-4 border-top" id="destacado-home-<?php echo esc($ucUnitKey); ?>">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-navy display-6 font-montserrat">Destacados</h2>
    </div>

    <?php if ($ucDisplayMode === 'rotation' && count($ucSpotlightItems) > 1): ?>
    <div id="<?php echo esc($ucCarouselId); ?>" class="carousel slide rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel" data-bs-interval="<?php echo intval($ucRotationMs); ?>">
        <div class="carousel-inner">
            <?php foreach ($ucSpotlightItems as $idx => $item): ?>
            <div class="carousel-item<?php echo $idx === 0 ? ' active' : ''; ?>">
                <div class="row g-0 bg-white">
                    <div class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger-subtle text-danger mb-2 align-self-start"><?php echo esc($item['date']); ?></span>
                        <h3 class="fw-bold text-navy mb-3"><?php echo esc($item['title']); ?></h3>
                        <p class="text-muted mb-4"><?php echo esc($item['desc']); ?></p>
                        <a href="<?php echo esc($item['detail_url']); ?>" class="btn btn-premium align-self-start"><?php echo esc($item['link_text']); ?></a>
                    </div>
                    <div class="col-lg-6" style="min-height:280px;background:#f1f3f7;">
                        <?php if (!empty($item['thumbnail'])): ?>
                        <img src="<?php echo esc($item['thumbnail']); ?>" alt="" class="w-100 h-100" style="object-fit:cover;min-height:280px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo esc($ucCarouselId); ?>" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?php echo esc($ucCarouselId); ?>" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>
    <?php else: ?>
        <?php $item = $ucSpotlightItems[0]; ?>
        <div class="row g-0 bg-white rounded-4 overflow-hidden shadow-sm">
            <div class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                <span class="badge bg-danger-subtle text-danger mb-2 align-self-start"><?php echo esc($item['date']); ?></span>
                <h3 class="fw-bold text-navy mb-3"><?php echo esc($item['title']); ?></h3>
                <p class="text-muted mb-4"><?php echo esc($item['desc']); ?></p>
                <a href="<?php echo esc($item['detail_url']); ?>" class="btn btn-premium align-self-start"><?php echo esc($item['link_text']); ?></a>
            </div>
            <div class="col-lg-6" style="min-height:280px;background:#f1f3f7;">
                <?php if (!empty($item['thumbnail'])): ?>
                <img src="<?php echo esc($item['thumbnail']); ?>" alt="" class="w-100 h-100" style="object-fit:cover;min-height:280px;">
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if (!empty($ucLatestItems)): ?>
<section class="container py-5 mb-5 border-top" id="contenido-reciente-<?php echo esc($ucUnitKey); ?>">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-navy display-6 font-montserrat">Novedades</h2>
        <p class="text-muted">Promociones, eventos e información de interés.</p>
    </div>
    <div class="row g-4">
        <?php foreach ($ucLatestItems as $item): ?>
        <div class="col-lg-4 col-md-6 col-12 d-flex mb-4">
            <div class="card border-0 shadow-sm rounded-4 w-100 p-3 d-flex flex-column justify-content-between" style="background-color: #f1f3f7;">
                <div class="position-relative rounded-3 overflow-hidden mb-3">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <img src="<?php echo esc($item['thumbnail']); ?>" alt="<?php echo esc($item['title'] ?? ''); ?>" class="w-100" style="height: 220px; object-fit: cover; border-radius: 12px;">
                    <?php endif; ?>
                </div>
                <div class="card-body p-0 d-flex flex-column justify-content-between flex-grow-1">
                    <div>
                        <h5 class="fw-bold text-navy mb-2" style="font-size: 1.15rem;"><?php echo esc($item['title'] ?? ''); ?></h5>
                        <p class="text-muted text-sm mb-3"><?php echo esc($item['desc'] ?? ''); ?></p>
                    </div>
                    <div class="text-end mt-2">
                        <a href="<?php echo esc($item['detail_url']); ?>" class="fw-bold text-decoration-none text-sm" style="color: #c51f17;">
                            <?php echo esc($item['link_text'] ?? 'Ver Más'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
