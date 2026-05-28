<?php
/**
 * News Article Detail Page
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';

$newsId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$noticias = $contentService->get('homepage.noticias', []);
$article = null;

// Find news by ID
foreach ($noticias as $noticia) {
    if (intval($noticia['id']) === $newsId) {
        $article = $noticia;
        break;
    }
}

// Redirect to home if not found
if (!$article) {
    echo "<script>window.location.href='/rent-a-car.php';</script>";
    exit;
}
?>

<!-- Article Header Section -->
<section class="container py-5 mt-4">
    <div class="text-center mb-4">
        <a href="/blog.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i> Volver a Noticias
        </a>
        <h1 class="display-5 fw-bold text-navy font-montserrat px-3"><?php echo esc($article['title']); ?></h1>
        <small class="text-muted font-poppins d-block mt-2"><i class="bi bi-calendar-event me-1"></i> Publicado el <?php echo esc($article['date']); ?></small>
    </div>

    <!-- Article Content Box -->
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card border-1 rounded-4 shadow-sm overflow-hidden bg-white p-3 p-md-5">
                
                <!-- Large Banner Image -->
                <?php if (!empty($article['banner'])): ?>
                    <div class="mb-5 rounded-3 overflow-hidden text-center">
                        <img src="<?php echo esc($article['banner']); ?>" alt="<?php echo esc($article['title']); ?>" class="img-fluid w-100" style="max-height: 480px; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <!-- Subheading and Body -->
                <div class="px-2 px-md-4">
                    <?php if (!empty($article['subheading'])): ?>
                        <h3 class="fw-bold text-navy mb-4 font-montserrat"><?php echo esc($article['subheading']); ?></h3>
                    <?php endif; ?>

                    <?php if (!empty($article['description'])): ?>
                        <p class="fs-5 text-muted font-poppins mb-4 lh-lg" style="text-align: justify;">
                            <?php 
                            $desc_html = esc($article['description']);
                            $desc_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $desc_html);
                            $desc_html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $desc_html);
                            echo $desc_html;
                            ?>
                        </p>
                    <?php endif; ?>

                    <!-- Main Dynamic Content (with bullet/paragraph spacing) -->
                    <?php if (!empty($article['content'])): ?>
                        <div class="article-body-content text-navy font-poppins fs-6 lh-lg" style="text-align: justify; white-space: pre-line;">
                            <?php 
                            $content_html = esc($article['content']);
                            $content_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content_html);
                            $content_html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content_html);
                            echo $content_html;
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
