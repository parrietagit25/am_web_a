<?php
/**
 * Cabecera configurable para páginas de listado de contenido.
 *
 * @var string $ucActiveType  news | blog | latest
 */
require_once __DIR__ . '/../services/HeaderBannerService.php';

$ucActiveType = $ucActiveType ?? 'news';
$ucHeader = unit_content_get_page_header($contentService, $unitKey, $ucActiveType);
$ucAlign = $ucHeader['align'] ?? 'left';
if (!in_array($ucAlign, ['left', 'center', 'right'], true)) {
    $ucAlign = 'left';
}
$ucPageTitle = trim((string) ($ucHeader['title'] ?? ''));
if ($ucPageTitle === '') {
    if (!class_exists('UnitContentService')) {
        require_once __DIR__ . '/../services/UnitContentService.php';
    }
    $ucPageTitle = UnitContentService::TYPE_LABELS[$ucActiveType] ?? 'Novedades';
}
$ucPageSubtitle = $ucHeader['subtitle'] ?? '';
$ucPageKicker = $ucHeader['kicker'] ?? '';
$ucHeaderEnabled = !isset($ucHeader['enabled']) || (bool) $ucHeader['enabled'];
$ucBannerUrl = $ucHeaderEnabled ? trim((string) ($ucHeader['banner'] ?? '')) : '';
$ucBannerAlt = trim((string) ($ucHeader['alt'] ?? ''));
$ucButtonUrl = $ucHeaderEnabled
    ? HeaderBannerService::sanitizeLinkUrl($ucHeader['button_url'] ?? '')
    : '';
$ucButtonText = $ucButtonUrl !== '' ? trim((string) ($ucHeader['button_text'] ?? '')) : '';
if ($ucButtonUrl !== '' && $ucButtonText === '') {
    $ucButtonText = 'Conocer más sobre ' . $ucPageTitle;
}
?>

<section class="uc-page-hero uc-page-hero--align-<?php echo esc($ucAlign); ?><?php echo $ucBannerUrl !== '' ? ' uc-page-hero--has-image' : ''; ?>">
    <?php if ($ucBannerUrl !== ''): ?>
        <img class="uc-page-hero__bg-img" src="<?php echo esc($ucBannerUrl); ?>" alt="<?php echo esc($ucBannerAlt !== '' ? $ucBannerAlt : $ucPageTitle); ?>">
    <?php endif; ?>
    <div class="uc-page-hero__overlay"></div>
    <div class="container py-4 py-lg-5 position-relative">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb uc-page-hero__breadcrumb mb-3">
                <li class="breadcrumb-item"><a href="<?php echo esc($unitHome); ?>"><?php echo esc($unitLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($ucPageTitle); ?></li>
            </ol>
        </nav>
        <div class="uc-page-hero__content">
            <?php if ($ucPageKicker !== ''): ?>
                <span class="uc-page-hero__kicker"><?php echo esc($ucPageKicker); ?></span>
            <?php endif; ?>
            <h1 class="uc-page-hero__title mb-2"><?php echo esc($ucPageTitle); ?></h1>
            <?php if ($ucPageSubtitle !== ''): ?>
                <p class="uc-page-hero__subtitle mb-0"><?php echo esc($ucPageSubtitle); ?></p>
            <?php endif; ?>
            <?php if ($ucButtonUrl !== '' && $ucButtonText !== ''): ?>
                <a href="<?php echo esc($ucButtonUrl); ?>" class="btn btn-theme mt-3"><?php echo esc($ucButtonText); ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
