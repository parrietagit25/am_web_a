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
$hbSkipContainer = (bool) ($hbSkipContainer ?? false);
$hbBackgroundOverlay = trim($hbBackgroundOverlay ?? '');
$hbConfiguredOverlay = HeaderBannerService::overlayCss($hbConfig);
$hbBgPrefix = $hbConfiguredOverlay !== ''
    ? $hbConfiguredOverlay . ' '
    : ($hbBackgroundOverlay !== '' ? $hbBackgroundOverlay . ' ' : '');
$hbCarouselId = ($hbSectionId !== '' ? $hbSectionId : 'hb') . '-carousel';
$hbEnabled = (bool) ($hbConfig['enabled'] ?? true);
$hbDefaultLinkUrl = HeaderBannerService::sanitizeLinkUrl($hbDefaultLinkUrl ?? '');
$hbDefaultLinkText = trim((string) ($hbDefaultLinkText ?? ''));

$slides = array_values(array_filter(
    $hbConfig['slider']['slides'] ?? [],
    static fn (array $slide): bool => !isset($slide['enabled']) || (bool) $slide['enabled']
));
$sliderModeSelected = $hbEnabled
    && ($hbConfig['mode'] ?? HeaderBannerService::MODE_STATIC) === HeaderBannerService::MODE_SLIDER;
$isSlider = $sliderModeSelected && !empty($slides);
$interval = (int) ($hbConfig['slider']['interval_ms'] ?? 5000);
$transition = ($hbConfig['slider']['transition'] ?? 'fade') === 'slide' ? '' : ' carousel-fade';
$fallbackImage = 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?q=80&w=1200&auto=format&fit=crop';
$staticImage = HeaderBannerService::primaryImageUrl($hbConfig, $fallbackImage);
$staticTitle = trim((string) ($hbConfig['title'] ?? ''));
$staticSubtitle = trim((string) ($hbConfig['subtitle'] ?? ''));
$staticAlt = trim((string) ($hbConfig['alt'] ?? ''));
$staticLinkUrl = HeaderBannerService::sanitizeLinkUrl($hbConfig['link_url'] ?? '');
if ($staticLinkUrl === '') {
    $staticLinkUrl = $hbDefaultLinkUrl;
}
$staticLinkText = trim((string) ($hbConfig['link_text'] ?? ''));
if ($staticLinkText === '') {
    $staticLinkText = $hbDefaultLinkText;
}
if ($staticLinkText === '' && $staticLinkUrl !== '') {
    $staticLinkText = $staticTitle !== '' ? 'Conocer más sobre ' . $staticTitle : 'Ver más';
}
$staticHasCaption = $staticTitle !== '' || $staticSubtitle !== '' || ($staticLinkUrl !== '' && $staticLinkText !== '');
$hbHasLegacyContent = trim(strip_tags($hbInnerHtml)) !== '';
$renderStaticCaption = $staticHasCaption && !$hbHasLegacyContent;
?>
<?php if (!$hbEnabled): ?>
    <?php if (trim(strip_tags($hbInnerHtml)) !== ''): ?>
<section class="<?php echo esc($hbSectionClass); ?> hb-banner-disabled"<?php echo $hbSectionId !== '' ? ' id="' . esc($hbSectionId) . '"' : ''; ?> style="background-color: #081026;<?php echo $hbSectionExtraStyle !== '' ? ' ' . esc($hbSectionExtraStyle) : ''; ?>">
    <div class="container py-5">
        <?php echo $hbInnerHtml; ?>
    </div>
</section>
    <?php endif; ?>
<?php elseif ($sliderModeSelected && empty($slides)): ?>
    <?php if (trim(strip_tags($hbInnerHtml)) !== ''): ?>
<section class="<?php echo esc($hbSectionClass); ?> hb-banner-empty-slider"<?php echo $hbSectionId !== '' ? ' id="' . esc($hbSectionId) . '"' : ''; ?> style="background-color: #081026;<?php echo $hbSectionExtraStyle !== '' ? ' ' . esc($hbSectionExtraStyle) : ''; ?>">
    <div class="container py-5">
        <?php echo $hbInnerHtml; ?>
    </div>
</section>
    <?php endif; ?>
<?php elseif ($isSlider): ?>
<section class="<?php echo esc($hbSectionClass); ?> hero-banner-slider position-relative overflow-hidden p-0"<?php echo $hbSectionId !== '' ? ' id="' . esc($hbSectionId) . '"' : ''; ?><?php echo $hbSectionExtraStyle !== '' ? ' style="' . esc($hbSectionExtraStyle) . '"' : ''; ?>>
    <div id="<?php echo esc($hbCarouselId); ?>" class="carousel slide<?php echo esc($transition); ?> h-100 w-100 position-absolute top-0 start-0" data-bs-ride="carousel" data-bs-interval="<?php echo (int) $interval; ?>" data-bs-pause="hover">
        <div class="carousel-inner h-100">
            <?php foreach ($slides as $i => $slide):
                $slideTitle = trim((string) ($slide['title'] ?? ''));
                $slideSubtitle = trim((string) ($slide['subtitle'] ?? ''));
                $slideAlt = trim((string) ($slide['alt'] ?? ''));
                $slideLinkUrl = HeaderBannerService::sanitizeLinkUrl($slide['link_url'] ?? '');
                if ($slideLinkUrl === '') {
                    $slideLinkUrl = $hbDefaultLinkUrl;
                }
                $slideLinkText = trim((string) ($slide['link_text'] ?? ''));
                if ($slideLinkText === '') {
                    $slideLinkText = $hbDefaultLinkText;
                }
                if ($slideLinkText === '' && $slideLinkUrl !== '') {
                    $slideLinkText = $slideTitle !== '' ? 'Conocer más sobre ' . $slideTitle : 'Ver más';
                }
                $slideHasText = $slideTitle !== '' || $slideSubtitle !== '' || ($slideLinkUrl !== '' && $slideLinkText !== '');
            ?>
            <div class="carousel-item h-100<?php echo $i === 0 ? ' active' : ''; ?>"
                 style="background: <?php echo $hbBgPrefix; ?>url('<?php echo esc($slide['image_url']); ?>') no-repeat center center; background-size: cover; min-height: 360px;">
                <img src="<?php echo esc($slide['image_url']); ?>" alt="<?php echo esc($slideAlt !== '' ? $slideAlt : $slideTitle); ?>" class="visually-hidden">
                <?php if ($slideHasText): ?>
                <div class="carousel-caption hb-slide-caption text-start start-0 end-0 bottom-0 pb-4 px-3 px-md-5">
                    <div class="container">
                        <?php if ($slideTitle !== ''): ?>
                        <h2 class="display-6 fw-bold text-white text-shadow mb-2"><?php echo esc($slideTitle); ?></h2>
                        <?php endif; ?>
                        <?php if ($slideSubtitle !== ''): ?>
                        <p class="lead text-white text-shadow mb-0"><?php echo esc($slideSubtitle); ?></p>
                        <?php endif; ?>
                        <?php if ($slideLinkUrl !== '' && $slideLinkText !== ''): ?>
                        <a href="<?php echo esc($slideLinkUrl); ?>" class="btn btn-theme mt-3"><?php echo esc($slideLinkText); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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
<section class="<?php echo esc($hbSectionClass); ?> position-relative overflow-hidden" style="background: <?php echo $hbBgPrefix; ?>url('<?php echo esc($staticImage); ?>') no-repeat center center; background-size: cover;<?php echo $hbSectionExtraStyle !== '' ? ' ' . esc($hbSectionExtraStyle) : ''; ?>"<?php echo $hbSectionId !== '' ? ' id="' . esc($hbSectionId) . '"' : ''; ?>>
    <img src="<?php echo esc($staticImage); ?>" alt="<?php echo esc($staticAlt !== '' ? $staticAlt : $staticTitle); ?>" class="visually-hidden">
    <?php if ($hbHasLegacyContent || (!$hbSkipContainer && !$renderStaticCaption)): ?>
    <div class="container py-5">
        <?php echo $hbInnerHtml; ?>
    </div>
    <?php endif; ?>
    <?php if ($renderStaticCaption): ?>
    <div class="container position-relative pb-4" style="z-index: 2;">
        <div class="hb-static-caption text-white text-shadow">
            <?php if ($staticTitle !== ''): ?>
            <h2 class="h3 fw-bold mb-2"><?php echo esc($staticTitle); ?></h2>
            <?php endif; ?>
            <?php if ($staticSubtitle !== ''): ?>
            <p class="mb-0"><?php echo esc($staticSubtitle); ?></p>
            <?php endif; ?>
            <?php if ($staticLinkUrl !== '' && $staticLinkText !== ''): ?>
            <a href="<?php echo esc($staticLinkUrl); ?>" class="btn btn-theme mt-3"><?php echo esc($staticLinkText); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php
unset(
    $slides,
    $sliderModeSelected,
    $isSlider,
    $interval,
    $transition,
    $fallbackImage,
    $staticImage,
    $staticTitle,
    $staticSubtitle,
    $staticAlt,
    $staticLinkUrl,
    $staticLinkText,
    $staticHasCaption,
    $hbHasLegacyContent,
    $renderStaticCaption,
    $hbConfiguredOverlay
);
?>
