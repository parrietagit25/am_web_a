<?php
/**
 * Cabecera común para páginas de listado de contenido.
 *
 * @var string $ucPageTitle
 * @var string $ucPageSubtitle
 * @var string $ucLayoutClass  engadget | hubspot | expedia
 */
$ucLayoutClass = $ucLayoutClass ?? 'hubspot';
$ucPageTitle = $ucPageTitle ?? '';
$ucPageSubtitle = $ucPageSubtitle ?? '';
?>
<link href="/assets/css/content-listings.css" rel="stylesheet">

<?php if ($ucLayoutClass === 'engadget'): ?>
<section class="uc-list-hero uc-list-hero--engadget">
    <div class="container py-4 py-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb uc-breadcrumb--light mb-3">
                <li class="breadcrumb-item"><a href="<?php echo esc($unitHome); ?>"><?php echo esc($unitLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($ucPageTitle); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
            <div>
                <span class="uc-kicker uc-kicker--engadget">Actualidad</span>
                <h1 class="uc-list-title uc-list-title--engadget mb-2"><?php echo esc($ucPageTitle); ?></h1>
                <p class="uc-list-subtitle uc-list-subtitle--engadget mb-0"><?php echo esc($ucPageSubtitle); ?></p>
            </div>
            <div class="uc-nav-pills">
                <?php require __DIR__ . '/unit-content-type-nav.php'; ?>
            </div>
        </div>
    </div>
</section>
<?php elseif ($ucLayoutClass === 'expedia'): ?>
<section class="uc-list-hero uc-list-hero--expedia">
    <div class="container py-5 py-lg-6 text-center text-white">
        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
            <ol class="breadcrumb uc-breadcrumb--light mb-0">
                <li class="breadcrumb-item"><a href="<?php echo esc($unitHome); ?>"><?php echo esc($unitLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($ucPageTitle); ?></li>
            </ol>
        </nav>
        <h1 class="uc-list-title uc-list-title--expedia mb-3"><?php echo esc($ucPageTitle); ?></h1>
        <p class="uc-list-subtitle uc-list-subtitle--expedia mx-auto mb-4"><?php echo esc($ucPageSubtitle); ?></p>
        <div class="d-flex justify-content-center">
            <?php require __DIR__ . '/unit-content-type-nav.php'; ?>
        </div>
    </div>
</section>
<?php else: ?>
<section class="uc-list-hero uc-list-hero--hubspot">
    <div class="container py-5 text-center">
        <nav aria-label="breadcrumb" class="d-flex justify-content-center mb-3">
            <ol class="breadcrumb uc-breadcrumb--hubspot mb-0">
                <li class="breadcrumb-item"><a href="<?php echo esc($unitHome); ?>"><?php echo esc($unitLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($ucPageTitle); ?></li>
            </ol>
        </nav>
        <span class="uc-kicker uc-kicker--hubspot">Recursos</span>
        <h1 class="uc-list-title uc-list-title--hubspot mb-3"><?php echo esc($ucPageTitle); ?></h1>
        <p class="uc-list-subtitle uc-list-subtitle--hubspot mx-auto mb-4"><?php echo esc($ucPageSubtitle); ?></p>
        <div class="d-flex justify-content-center">
            <?php require __DIR__ . '/unit-content-type-nav.php'; ?>
        </div>
    </div>
</section>
<?php endif; ?>
