<?php
/**
 * Automarket - Términos y Condiciones
 */
$activeUnit = 'rentacar';
$seoOverride = [
    'title'       => 'Términos y condiciones de alquiler | Automarket',
    'description' => 'Términos y condiciones del servicio de alquiler de Automarket Rent a Car en Panamá.',
];
require_once __DIR__ . '/../includes/header.php';

$terminosHtml = $contentService->get('homepage.terminos_condiciones', '<h3>Términos y Condiciones</h3><p>Contenido en mantenimiento.</p>');
?>

<!-- 1. Breadcrumb and Title Section -->
<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Términos y Condiciones</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Términos y Condiciones</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Información importante sobre las condiciones y coberturas de alquiler en Automarket Rent a Car.</p>
    </div>
</section>

<!-- 2. Content Section -->
<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-12">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                <div class="terms-content font-poppins text-navy fs-6 lh-lg">
                    <?php echo $terminosHtml; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom Styles for Terms Content -->
<style>
    .terms-content .subtitulo2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: var(--navy);
        font-size: 1.45rem;
        margin-top: 2rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 8px;
    }
    .terms-content .subtitulo3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        color: var(--theme-primary);
        font-size: 1.15rem;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .terms-content p {
        margin-bottom: 1.25rem;
        color: #4a5568;
        text-align: justify;
    }
    .terms-content ul {
        margin-bottom: 1.5rem;
        padding-left: 20px;
    }
    .terms-content li {
        margin-bottom: 0.5rem;
        color: #4a5568;
    }
    .terms-content .lista-puntos-rojos li {
        list-style-type: none;
        position: relative;
        padding-left: 20px;
    }
    .terms-content .lista-puntos-rojos li::before {
        content: "•";
        color: var(--theme-primary);
        font-weight: bold;
        font-size: 1.25rem;
        position: absolute;
        left: 0;
        top: -2px;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
