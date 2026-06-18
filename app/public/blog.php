<?php
/**
 * Automarket - Blog (Rent A Car)
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/unit-content-frontend.php';

$articulos = unit_content_get_items($contentService, 'rentacar', 'blog');
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Blog</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Blog</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Artículos, guías y contenido permanente sobre movilidad y viajes.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <?php if (empty($articulos)): ?>
        <div class="text-center py-5">
            <i class="bi bi-journal-text text-muted display-1 opacity-25"></i>
            <h4 class="mt-3 text-muted font-montserrat">Aún no hay artículos de blog publicados.</h4>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($articulos as $article): ?>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white blog-card transition-all">
                        <div class="position-relative overflow-hidden" style="height: 220px; background-color: #081026;">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img src="<?php echo esc($article['thumbnail']); ?>" alt="<?php echo esc($article['title']); ?>" class="w-100 h-100 object-fit-cover blog-img">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-white-50">
                                    <i class="bi bi-image fs-1 opacity-25"></i>
                                </div>
                            <?php endif; ?>
                            <span class="position-absolute bottom-0 start-0 m-3 badge bg-dark bg-opacity-75 text-white font-poppins px-3 py-2 rounded-pill" style="font-size: 0.75rem;">
                                <i class="bi bi-calendar-event me-1"></i> <?php echo esc($article['date']); ?>
                            </span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="card-title fw-bold text-navy font-montserrat mb-3 blog-title" style="line-height: 1.4;">
                                <?php echo esc($article['title']); ?>
                            </h5>
                            <p class="card-text text-muted font-poppins size-xs mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
                                <?php echo esc($article['desc']); ?>
                            </p>
                            <div class="mt-auto">
                                <a href="<?php echo esc($article['detail_url']); ?>" class="btn btn-outline-theme rounded-pill w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 font-montserrat">
                                    <?php echo esc($article['link_text'] ?? 'Ver Más'); ?> <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
    .blog-card { border: 1px solid var(--border-color) !important; transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease; }
    .blog-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.08) !important; }
    .blog-card:hover .blog-img { transform: scale(1.05); }
    .blog-img { transition: transform 0.5s ease; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
