<?php
/**
 * Blog / noticias agregadas de todas las unidades de negocio.
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/FooterService.php';

$contentService = new ContentService();
$footerService = new FooterService($contentService);
$posts = $footerService->collectAllBlogPosts();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Automarket</a></li>
                <li class="breadcrumb-item active" aria-current="page">Blog y noticias</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0">Blog y noticias</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Novedades, eventos y publicaciones de todas nuestras unidades de negocio.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <?php if (empty($posts)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-newspaper display-1 opacity-25"></i>
            <p class="mt-3">No hay publicaciones por el momento.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $article): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div style="height:200px;background:#081026;">
                        <?php if (!empty($article['thumbnail'])): ?>
                            <img src="<?php echo esc($article['thumbnail']); ?>" alt="" class="w-100 h-100 object-fit-cover">
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <span class="badge bg-danger-subtle text-danger align-self-start mb-2"><?php echo esc($article['unit_label']); ?></span>
                        <?php if (!empty($article['date'])): ?>
                            <small class="text-muted mb-2"><i class="bi bi-calendar-event me-1"></i><?php echo esc($article['date']); ?></small>
                        <?php endif; ?>
                        <h5 class="fw-bold text-navy font-montserrat"><?php echo esc($article['title']); ?></h5>
                        <p class="text-muted small flex-grow-1"><?php echo esc($article['excerpt']); ?></p>
                        <a href="<?php echo esc($article['url']); ?>" class="btn btn-outline-theme rounded-pill w-100"><?php echo esc($article['link_text']); ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
