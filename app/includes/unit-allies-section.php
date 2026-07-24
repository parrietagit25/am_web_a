<?php
/**
 * Sección pública reutilizable — Aliados / marcas.
 *
 * Variables:
 * @var list<array<string, mixed>> $alliesItems
 * @var string $alliesTitle
 * @var string $alliesSubtitle
 * @var string $alliesText
 * @var string $alliesLayout  marquee|grid
 * @var string $alliesSectionId
 * @var string $alliesTitleClass
 */
declare(strict_types=1);

$alliesItems = is_array($alliesItems ?? null) ? $alliesItems : [];
if ($alliesItems === []) {
    return;
}

$alliesTitle = trim((string) ($alliesTitle ?? ''));
$alliesSubtitle = trim((string) ($alliesSubtitle ?? ''));
$alliesText = trim((string) ($alliesText ?? ''));
$alliesLayout = (($alliesLayout ?? 'marquee') === 'grid') ? 'grid' : 'marquee';
$alliesSectionId = trim((string) ($alliesSectionId ?? 'aliados-marcas'));
$alliesTitleClass = trim((string) ($alliesTitleClass ?? 'fw-bold text-navy font-montserrat text-uppercase mb-3'));
$renderList = $alliesLayout === 'marquee' ? array_merge($alliesItems, $alliesItems) : $alliesItems;
?>
<style>
.allies-marquee { overflow: hidden; width: 100%; }
.allies-marquee-track {
    display: flex; align-items: center; gap: 2.5rem; width: max-content;
    animation: alliesMarqueeScroll 35s linear infinite;
}
.allies-marquee-track:hover { animation-play-state: paused; }
@keyframes alliesMarqueeScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.allies-logo {
    max-height: 56px; max-width: 160px; width: auto; height: auto;
    object-fit: contain; filter: grayscale(10%); opacity: 0.92;
}
.allies-logo:hover { opacity: 1; filter: none; }
.allies-grid-logo { max-height: 64px; max-width: 180px; }
@media (prefers-reduced-motion: reduce) {
    .allies-marquee-track { animation: none; flex-wrap: wrap; width: 100%; justify-content: center; }
}
</style>
<section class="py-5 bg-white" id="<?php echo esc($alliesSectionId); ?>">
    <div class="container text-center">
        <?php if ($alliesTitle !== ''): ?>
        <h2 class="<?php echo esc($alliesTitleClass); ?>"><?php echo esc($alliesTitle); ?></h2>
        <?php endif; ?>
        <?php if ($alliesSubtitle !== ''): ?>
        <p class="text-muted font-poppins mb-3"><?php echo esc($alliesSubtitle); ?></p>
        <?php endif; ?>
        <?php if ($alliesText !== ''): ?>
        <p class="text-muted mx-auto mb-4" style="max-width:980px;"><?php echo esc($alliesText); ?></p>
        <?php endif; ?>

        <?php if ($alliesLayout === 'grid'): ?>
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-4">
            <?php foreach ($renderList as $ally): ?>
                <?php if (($ally['url'] ?? '') !== ''): ?>
                <a href="<?php echo esc($ally['url']); ?>"<?php echo !empty($ally['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="<?php echo esc('Visitar ' . ($ally['name'] ?? '')); ?>">
                <?php endif; ?>
                <img src="<?php echo esc($ally['image_url'] ?? ''); ?>" alt="<?php echo esc($ally['alt'] ?? ($ally['name'] ?? '')); ?>" class="allies-logo allies-grid-logo" loading="lazy" title="<?php echo esc($ally['name'] ?? ''); ?>">
                <?php if (($ally['url'] ?? '') !== ''): ?></a><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="allies-marquee">
            <div class="allies-marquee-track">
                <?php foreach ($renderList as $ally): ?>
                    <?php if (($ally['url'] ?? '') !== ''): ?>
                    <a href="<?php echo esc($ally['url']); ?>"<?php echo !empty($ally['is_external']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="<?php echo esc('Visitar ' . ($ally['name'] ?? '')); ?>">
                    <?php endif; ?>
                    <img src="<?php echo esc($ally['image_url'] ?? ''); ?>" alt="<?php echo esc($ally['alt'] ?? ($ally['name'] ?? '')); ?>" class="allies-logo" loading="lazy" title="<?php echo esc($ally['name'] ?? ''); ?>">
                    <?php if (($ally['url'] ?? '') !== ''): ?></a><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
