<?php
/**
 * Automarket - Blog / Noticias (Rent A Car Blog)
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';

$noticias = $contentService->get('homepage.noticias', []);
?>

<!-- 1. Breadcrumb and Title Section -->
<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Blog</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Blog de Noticias</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Mantente al día con las últimas novedades, eventos de interés y paquetes especiales de Automarket.</p>
    </div>
</section>

<!-- 2. Blog Post Grid -->
<section class="container py-5 mb-5">
    <?php if (empty($noticias)): ?>
        <div class="text-center py-5">
            <i class="bi bi-newspaper text-muted display-1 opacity-25"></i>
            <h4 class="mt-3 text-muted font-montserrat">No hay noticias registradas por el momento.</h4>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($noticias as $article): ?>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white blog-card transition-all">
                        <!-- Thumbnail Image -->
                        <div class="position-relative overflow-hidden" style="height: 220px; background-color: #081026;">
                            <?php if (!empty($article['thumbnail'])): ?>
                                <img src="<?php echo esc($article['thumbnail']); ?>" alt="<?php echo esc($article['title']); ?>" class="w-100 h-100 object-fit-cover blog-img">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-white-50">
                                    <i class="bi bi-image fs-1 opacity-25"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Date Badge overlay -->
                            <span class="position-absolute bottom-0 start-0 m-3 badge bg-dark bg-opacity-75 text-white font-poppins px-3 py-2 rounded-pill" style="font-size: 0.75rem;">
                                <i class="bi bi-calendar-event me-1"></i> <?php echo esc($article['date']); ?>
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="card-title fw-bold text-navy font-montserrat mb-3 blog-title" style="line-height: 1.4;">
                                <?php echo esc($article['title']); ?>
                            </h5>
                            <p class="card-text text-muted font-poppins size-xs mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
                                <?php echo esc($article['desc']); ?>
                            </p>
                            <div class="mt-auto">
                                <a href="/noticia.php?id=<?php echo intval($article['id']); ?>" class="btn btn-outline-theme rounded-pill w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1 font-montserrat">
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

<!-- CSS Styling Overrides for Blog Page -->
<style>
    .blog-card {
        border: 1px solid var(--border-color) !important;
        transition: transform 0.3s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.3s ease;
    }
    .blog-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(8,16,38,0.08) !important;
        border-color: var(--theme-primary) !important;
    }
    .blog-img {
        transition: transform 0.4s ease;
    }
    .blog-card:hover .blog-img {
        transform: scale(1.06);
    }
    .blog-title {
        transition: color 0.3s ease;
    }
    .blog-card:hover .blog-title {
        color: var(--theme-primary) !important;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
