<?php
/**
 * Automarket - Requisitos de Alquiler
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';

$requisitosHtml = $contentService->get('homepage.requisitos_alquiler', '<h3>Requisitos de Alquiler</h3><p>Contenido en mantenimiento.</p>');
?>

<!-- 1. Breadcrumb and Title Section -->
<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Requisitos de Alquiler</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Requisitos de Alquiler</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Requisitos básicos y políticas para alquilar un vehículo en la República de Panamá.</p>
    </div>
</section>

<!-- 2. Content Section -->
<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-12">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                <div class="reqs-content font-poppins text-navy fs-6 lh-lg">
                    <?php echo $requisitosHtml; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom Styles for Requirements Content -->
<style>
    .reqs-content .subtitulo2 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        color: var(--navy);
        font-size: 1.45rem;
        margin-top: 2rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 8px;
    }
    .reqs-content .subtitulo3 {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        color: var(--theme-primary);
        font-size: 1.15rem;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .reqs-content p {
        margin-bottom: 1.25rem;
        color: #4a5568;
        text-align: justify;
    }
    .reqs-content ul {
        margin-bottom: 1.5rem;
        padding-left: 20px;
    }
    .reqs-content li {
        margin-bottom: 0.75rem;
        color: #4a5568;
        position: relative;
        list-style-type: none;
        padding-left: 20px;
    }
    .reqs-content li::before {
        content: "✓";
        color: var(--theme-primary);
        font-weight: bold;
        position: absolute;
        left: 0;
        top: 0;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
