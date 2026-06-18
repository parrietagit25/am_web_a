<?php
/**
 * Renderiza cabecera estática o slider.
 *
 * @var array<string, mixed> $hbConfig
 * @var string $hbSectionClass
 * @var string $hbSectionId
 * @var string $hbInnerHtml  HTML del contenido sobre la imagen
 */
require_once __DIR__ . '/../services/HeaderBannerService.php';

$hbConfig = HeaderBannerService::normalize($hbConfig ?? []);
$hbSectionClass = trim($hbSectionClass ?? 'hero-wrapper text-center text-lg-start d-flex align-items-center');
$hbSectionId = trim($hbSectionId ?? '');
$hbInnerHtml = $hbInnerHtml ?? '';
$hbSectionExtraStyle = trim($hbSectionExtraStyle ?? '');
$hbSectionOnclick = trim($hbSectionOnclick ?? '');
$hbSkipContainer = (bool) ($hbSkipContainer ?? false);
$hbBackgroundOverlay = trim($hbBackgroundOverlay ?? '');
$hbBgPrefix = $hbBackgroundOverlay !== '' ? $hbBackgroundOverlay . ' ' : '';
$hbCarouselId = ($hbSectionId !== '' ? $hbSectionId : 'hb') . '-carousel';

$isSlider = ($hbConfig['mode'] ?? HeaderBannerService::MODE_STATIC) === HeaderBannerService::MODE_SLIDER
    && !empty($hbConfig['slider']['slides']);
$slides = $hbConfig['slider']['slides'] ?? [];
$interval = (int) ($hbConfig['slider']['interval_ms'] ?? 5000);
$transition = ($hbConfig['slider']['transition'] ?? 'fade') === 'slide' ? '' : ' carousel-fade';
$fallbackImage = 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?q=80&w=1200&auto=format&fit=crop';
$staticImage = HeaderBannerService::primaryImageUrl($hbConfig, $fallbackImage);
$hbOnclickAttr = $hbSectionOnclick !== '' ? ' onclick="' . esc($hbSectionOnclick) . '"' : '';
?>
<?php if ($isSlider): ?>
<section class="<?php echo esc($hbSectionClass); ?> hero-banner-slider position-relative overflow-hidden p-0"<?php echo $hbSectionId !== '' ? ' id="' . esc($hbSectionId) . '"' : ''; ?><?php echo $hbOnclickAttr; ?><?php echo $hbSectionExtraStyle !== '' ? ' style="' . esc($hbSectionExtraStyle) . '"' : ''; ?>>
    <div id="<?php echo esc($hbCarouselId); ?>" class="carousel slide<?php echo esc($transition); ?> h-100 w-100 position-absolute top-0 start-0" data-bs-ride="carousel" data-bs-interval="<?php echo (int) $interval; ?>" data-bs-pause="hover">
        <div class="carousel-inner h-100">
            <?php foreach ($slides as $i => $slide): ?>
            <div class="carousel-item h-100<?php echo $i === 0 ? ' active' : ''; ?>"
                 style="background: <?php echo $hbBgPrefix; ?>url('<?php echo esc($slide['image_url']); ?>') no-repeat center center; background-size: cover; min-height: 360px;">
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($slides) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo esc($hbCarouselId); ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?php echo esc($hbCarouselId); ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
        <?php endif; ?>
    </div>
    <?php if (!$hbSkipContainer || $hbInnerHtml !== ''): ?>
    <div class="container py-5 position-relative" style="z-index: 2; min-height: 360px;">
        <?php echo $hbInnerHtml; ?>
    </div>
    <?php endif; ?>
</section>
<?php else: ?>
<section class="<?php echo esc($hbSectionClass); ?>" style="background: <?php echo $hbBgPrefix; ?>url('<?php echo esc($staticImage); ?>') no-repeat center center; background-size: cover;<?php echo $hbSectionExtraStyle !== '' ? ' ' . esc($hbSectionExtraStyle) : ''; ?>"<?php echo $hbSectionId !== '' ? ' id="' . esc($hbSectionId) . '"' : ''; ?><?php echo $hbOnclickAttr; ?>>
    <?php if (!$hbSkipContainer || $hbInnerHtml !== ''): ?>
    <div class="container py-5">
        <?php echo $hbInnerHtml; ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
