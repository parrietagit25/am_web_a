<?php
/**
 * Listado Noticias — estilo Engadget (titulares, contraste, feed editorial).
 *
 * @var list<array<string, mixed>> $items
 */
?>
<section class="uc-list-body uc-list-body--engadget pb-5">
    <div class="container">
        <?php if (empty($items)): ?>
            <div class="uc-empty uc-empty--engadget text-center py-5">
                <i class="bi bi-lightning-charge display-3"></i>
                <h4 class="mt-3">Sin noticias por ahora</h4>
                <p class="text-muted mb-0">Vuelve pronto para ver las últimas novedades.</p>
            </div>
        <?php else: ?>
            <?php $featured = array_shift($items); ?>
            <article class="uc-engadget-featured mb-5">
                <a href="<?php echo esc($featured['detail_url']); ?>" class="uc-engadget-featured__link text-decoration-none">
                    <div class="row g-0 align-items-stretch">
                        <div class="col-lg-7">
                            <div class="uc-engadget-featured__media">
                                <?php if (!empty($featured['thumbnail'])): ?>
                                    <img src="<?php echo esc($featured['thumbnail']); ?>" alt="<?php echo esc($featured['title']); ?>">
                                <?php else: ?>
                                    <div class="uc-engadget-featured__placeholder"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="uc-engadget-featured__body">
                                <span class="uc-engadget-badge">Destacado</span>
                                <?php if (!empty($featured['date'])): ?>
                                    <time class="uc-engadget-date"><?php echo esc($featured['date']); ?></time>
                                <?php endif; ?>
                                <h2 class="uc-engadget-featured__title"><?php echo esc($featured['title']); ?></h2>
                                <p class="uc-engadget-featured__excerpt"><?php echo esc($featured['desc']); ?></p>
                                <span class="uc-engadget-readmore"><?php echo esc($featured['link_text'] ?? 'Leer noticia'); ?> <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </article>

            <?php if (!empty($items)): ?>
            <div class="uc-engadget-feed">
                <?php foreach ($items as $article): ?>
                <article class="uc-engadget-feed__item">
                    <a href="<?php echo esc($article['detail_url']); ?>" class="uc-engadget-feed__link">
                        <div class="uc-engadget-feed__thumb">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img src="<?php echo esc($article['thumbnail']); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="uc-engadget-feed__content">
                            <?php if (!empty($article['date'])): ?>
                                <time class="uc-engadget-date"><?php echo esc($article['date']); ?></time>
                            <?php endif; ?>
                            <h3 class="uc-engadget-feed__title"><?php echo esc($article['title']); ?></h3>
                            <p class="uc-engadget-feed__excerpt"><?php echo esc($article['desc']); ?></p>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
