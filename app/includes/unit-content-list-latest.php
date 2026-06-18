<?php
/**
 * Listado Contenido más reciente — estilo Expedia Travel Blog (fotografía, overlays, viajes).
 *
 * @var list<array<string, mixed>> $items
 */
?>
<section class="uc-list-body uc-list-body--expedia pb-5">
    <div class="container">
        <?php if (empty($items)): ?>
            <div class="uc-empty uc-empty--expedia text-center py-5">
                <i class="bi bi-compass display-3"></i>
                <h4 class="mt-3">Nada reciente por ahora</h4>
                <p class="mb-0 opacity-75">Promociones, eventos e información destacada aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($items as $index => $article): ?>
                <div class="<?php echo $index === 0 ? 'col-12' : 'col-md-6'; ?>">
                    <article class="uc-expedia-tile<?php echo $index === 0 ? ' uc-expedia-tile--hero' : ''; ?>">
                        <a href="<?php echo esc($article['detail_url']); ?>" class="uc-expedia-tile__link">
                            <div class="uc-expedia-tile__media">
                                <?php if (!empty($article['thumbnail'])): ?>
                                    <img src="<?php echo esc($article['thumbnail']); ?>" alt="<?php echo esc($article['title']); ?>">
                                <?php else: ?>
                                    <div class="uc-expedia-tile__placeholder"></div>
                                <?php endif; ?>
                                <div class="uc-expedia-tile__overlay"></div>
                            </div>
                            <div class="uc-expedia-tile__content">
                                <?php if (!empty($article['date'])): ?>
                                    <time class="uc-expedia-date"><?php echo esc($article['date']); ?></time>
                                <?php endif; ?>
                                <h2 class="uc-expedia-tile__title"><?php echo esc($article['title']); ?></h2>
                                <p class="uc-expedia-tile__excerpt"><?php echo esc($article['desc']); ?></p>
                                <span class="uc-expedia-cta"><?php echo esc($article['link_text'] ?? 'Descubrir'); ?> <i class="bi bi-arrow-up-right"></i></span>
                            </div>
                        </a>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
