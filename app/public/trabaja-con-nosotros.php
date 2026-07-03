<?php
/**
 * Automarket — Trabaja con Nosotros / Vacantes (módulo público base AM-NEG-7A).
 * Sin integración Konzerta ni almacenamiento de postulaciones en esta fase.
 */
$activeUnit = 'rentacar';
$skipUnitBusinessSchema = true;
$seoOverride = [
    'title'       => 'Trabaja con Nosotros | Automarket',
    'description' => 'Oportunidades laborales en Automarket Panamá. Conoce nuestra cultura y envíanos tu información mientras publicamos vacantes.',
    'robots'      => 'index,follow',
];
require_once __DIR__ . '/../includes/header.php';

$global = $contentService->get('global', []);
$careersCms = $contentService->get('global.careers_page', []);
if (!is_array($careersCms)) {
    $careersCms = [];
}

$intro = trim((string) ($careersCms['intro'] ?? ''));
if ($intro === '') {
    $intro = 'En Automarket creemos en el talento que impulsa la movilidad en Panamá. '
        . 'Somos un grupo con más de dos décadas en el sector automotriz y buscamos personas '
        . 'comprometidas, con actitud de servicio y ganas de crecer junto a nosotros.';
}

$emptyState = trim((string) ($careersCms['empty_state'] ?? ''));
if ($emptyState === '') {
    $emptyState = 'Actualmente no hay vacantes publicadas en el sitio. '
        . 'Puedes enviarnos tu información para futuras oportunidades.';
}

$contactEmail = trim((string) ($global['email'] ?? ''));
if ($contactEmail === '') {
    $contactEmail = 'info@automarket.com.pa';
}

$futureNote = 'La integración con plataformas externas de reclutamiento (por ejemplo Konzerta) '
    . 'se evaluará en un bloque posterior, una vez definida la estrategia con Mercadeo y RR. HH.';
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Automarket</a></li>
                <li class="breadcrumb-item active" aria-current="page">Trabaja con Nosotros</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Trabaja con Nosotros</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Oportunidades laborales y cultura corporativa en Automarket Panamá.</p>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-12">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white mb-4">
                <p class="font-poppins text-navy fs-6 lh-lg mb-0"><?php echo esc($intro); ?></p>
            </div>

            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white text-center">
                <i class="bi bi-briefcase-fill text-danger display-5 mb-3" aria-hidden="true"></i>
                <h2 class="h4 fw-bold text-navy font-montserrat mb-3">Vacantes</h2>
                <p class="text-muted font-poppins mb-4"><?php echo esc($emptyState); ?></p>
                <p class="font-poppins text-navy mb-4">
                    Si deseas dejarnos tu información de contacto, escríbenos a
                    <a href="mailto:<?php echo esc($contactEmail); ?>" class="text-danger fw-semibold text-decoration-none"><?php echo esc($contactEmail); ?></a>.
                </p>
                <p class="small text-muted font-poppins mb-0"><?php echo esc($futureNote); ?></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
