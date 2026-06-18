<?php
/**
 * Automarket - Noticias (Rent A Car)
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/unit-content-frontend.php';

$noticias = unit_content_get_items($contentService, 'rentacar', 'news');
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Noticias</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Noticias</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Comunicados, novedades y actualidad de Automarket Rent A Car.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <?php if (empty($noticias)): ?>
        <div class="text-center py-5">
            <i class="bi bi-newspaper text-muted display-1 opacity-25"></i>
            <h4 class="mt-3 text-muted font-montserrat">No hay noticias publicadas por el momento.</h4>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($noticias as $article): ?>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white blog-card">
                        <div class="position-relative overflow-hidden" style="height: 220px; background-color: #081026;">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img src="<?php echo esc($article['thumbnail']); ?>" alt="<?php echo esc($article['title']); ?>" class="w-100 h-100 object-fit-cover">
                            <?php endif; ?>
                            <span class="position-absolute bottom-0 start-0 m-3 badge bg-dark bg-opacity-75 text-white font-poppins px-3 py-2 rounded-pill" style="font-size: 0.75rem;">
                                <i class="bi bi-calendar-event me-1"></i> <?php echo esc($article['date']); ?>
                            </span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="fw-bold text-navy font-montserrat mb-3"><?php echo esc($article['title']); ?></h5>
                            <p class="text-muted flex-grow-1"><?php echo esc($article['desc']); ?></p>
                            <a href="<?php echo esc($article['detail_url']); ?>" class="btn btn-outline-theme rounded-pill w-100 py-2 fw-semibold">
                                <?php echo esc($article['link_text'] ?? 'Ver Más'); ?> <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
