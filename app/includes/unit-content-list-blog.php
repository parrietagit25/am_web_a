<?php
/**
 * Listado Blog — estilo HubSpot (limpio, tarjetas suaves, enfoque editorial).
 *
 * @var list<array<string, mixed>> $items
 */
?>
<section class="uc-list-body uc-list-body--hubspot pb-5">
    <div class="container">
        <?php if (empty($items)): ?>
            <div class="uc-empty uc-empty--hubspot text-center py-5">
                <i class="bi bi-journal-richtext display-3 text-muted"></i>
                <h4 class="mt-3 text-navy">Próximamente</h4>
                <p class="text-muted mb-0">Próximamente compartiremos novedades, consejos y noticias de Automarket.</p>
            </div>
        <?php else: ?>
            <div class="row g-4 g-xl-5">
                <?php foreach ($items as $article): ?>
                <div class="col-md-6 col-xl-4 d-flex">
                    <article class="uc-hubspot-card w-100">
                        <a href="<?php echo esc($article['detail_url']); ?>" class="uc-hubspot-card__link text-decoration-none d-flex flex-column h-100">
                            <div class="uc-hubspot-card__media">
                                <?php if (!empty($article['thumbnail'])): ?>
                                    <img src="<?php echo esc($article['thumbnail']); ?>" alt="<?php echo esc($article['title']); ?>">
                                <?php else: ?>
                                    <div class="uc-hubspot-card__placeholder"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <span class="uc-hubspot-chip">Blog</span>
                            </div>
                            <div class="uc-hubspot-card__body flex-grow-1 d-flex flex-column">
                                <?php if (!empty($article['date'])): ?>
                                    <time class="uc-hubspot-date"><?php echo esc($article['date']); ?></time>
                                <?php endif; ?>
                                <h2 class="uc-hubspot-card__title"><?php echo esc($article['title']); ?></h2>
                                <p class="uc-hubspot-card__excerpt flex-grow-1"><?php echo esc($article['desc']); ?></p>
                                <span class="uc-hubspot-cta">
                                    <?php echo esc($article['link_text'] ?? 'Leer artículo'); ?>
                                    <i class="bi bi-arrow-right-short"></i>
                                </span>
                            </div>
                        </a>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
