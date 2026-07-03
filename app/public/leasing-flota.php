<?php
/**
 * Automarket - Flota Leasing Operativo
 */
$activeUnit = 'leasing';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/lrt-public-copy.php';

$leasingData = $contentService->get('leasing', []);
$leasingFleetPage = leasing_fleet_page_copy(is_array($leasingData) ? $leasingData : []);
$vehicles = $leasingData['vehicles'] ?? [];
?>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb font-poppins">
                <li class="breadcrumb-item"><a href="/leasing.php" class="text-danger text-decoration-none fw-semibold">Leasing Operativo</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nuestra Flota</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-2" style="font-size: 2.2rem; letter-spacing: -0.5px;"><?php echo esc($leasingFleetPage['title']); ?></h1>
        <p class="text-muted font-poppins mb-0"><?php echo esc($leasingFleetPage['subtitle']); ?></p>
    </div>
</section>

<section class="container mb-5">
    <div class="fleet-filter-bar-wrapper p-1 mb-4" style="background-color: #ffffff; border: 1px solid #e3e6f0; border-radius: 12px; box-shadow: 0 4px 10px rgba(8,16,38,0.02);">
        <div class="d-flex overflow-x-auto text-nowrap gap-1 pb-1 scrollbar-hidden" style="-webkit-overflow-scrolling: touch;">
            <button class="btn fleet-filter-btn active" data-category="all">Todos</button>
            <button class="btn fleet-filter-btn" data-category="Sedanes">Sedanes</button>
            <button class="btn fleet-filter-btn" data-category="SUV">SUV</button>
            <button class="btn fleet-filter-btn" data-category="Familiares">Familiares</button>
            <button class="btn fleet-filter-btn" data-category="Comerciales">Comerciales</button>
            <button class="btn fleet-filter-btn" data-category="Promociones">Promociones</button>
            <button class="btn fleet-filter-btn" data-category="SUV Mini">SUV compacto</button>
        </div>
    </div>

    <div class="row g-4" id="fleetGridContainer">
        <?php if (empty($vehicles)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-car-front text-muted display-1 opacity-25"></i>
                <h4 class="mt-3 text-muted">No se encontraron vehículos cargados.</h4>
            </div>
        <?php else: ?>
            <?php foreach ($vehicles as $vehicle):
                $categoryClean = esc($vehicle['category']);
            ?>
                <div class="col-lg-6 col-12 fleet-card-wrapper" data-category="<?php echo $categoryClean; ?>" style="transition: all 0.4s ease;">
                    <div class="card fleet-card h-100 border-0 p-4 shadow-sm" style="border-radius: 20px; border: 1px solid #ebedf3 !important; background-color: #ffffff;">
                        <h3 class="fw-bold text-center text-navy font-montserrat mb-4" style="font-size: 1.45rem; letter-spacing: -0.3px; color: #081026;">
                            <?php echo esc($vehicle['name']); ?>
                        </h3>

                        <div class="row align-items-center">
                            <div class="col-md-5 text-center mb-3 mb-md-0">
                                <img src="<?php echo esc($vehicle['image_url']); ?>" alt="<?php echo esc($vehicle['name']); ?>" class="img-fluid fleet-img-hover" style="max-height: 160px; object-fit: contain;">
                            </div>

                            <div class="col-md-7 border-start-md ps-md-4">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <?php if (!empty($vehicle['doors'])): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-door-closed text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium"><?php echo esc($vehicle['doors']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($vehicle['ac'] ?? false): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-snow text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium">Aire Acondicionado</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($vehicle['windows'] ?? false): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-window-sidebar text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium">Ventanas Eléctricas</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($vehicle['transmission']) && $vehicle['transmission'] !== 'Ninguno'): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-gear text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium"><?php echo esc($vehicle['transmission']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-6">
                                        <?php if (!empty($vehicle['passengers'])): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-people text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium"><?php echo esc($vehicle['passengers']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($vehicle['traction']) && $vehicle['traction'] !== 'Ninguno'): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-arrow-right-left text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium"><?php echo esc($vehicle['traction']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($vehicle['license_type']) && $vehicle['license_type'] !== 'Ninguno'): ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-person-badge text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium"><?php echo esc($vehicle['license_type']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php
                                        if (!empty($vehicle['extras'])):
                                            $extrasArr = explode(',', $vehicle['extras']);
                                            foreach ($extrasArr as $extra):
                                                $extra = trim($extra);
                                                if (empty($extra)) continue;

                                                $extraIcon = 'bi-plus-circle';
                                                $lowerExtra = strtolower($extra);
                                                if (strpos($lowerExtra, 'mp3') !== false || strpos($lowerExtra, 'music') !== false || strpos($lowerExtra, 'player') !== false || strpos($lowerExtra, 'audio') !== false) {
                                                    $extraIcon = 'bi-music-note-beamed';
                                                } elseif (strpos($lowerExtra, 'abs') !== false || strpos($lowerExtra, 'freno') !== false) {
                                                    $extraIcon = 'bi-shield-check';
                                                } elseif (strpos($lowerExtra, 'maleta') !== false || strpos($lowerExtra, 'grande') !== false || strpos($lowerExtra, 'equipaje') !== false) {
                                                    $extraIcon = 'bi-briefcase';
                                                } elseif (strpos($lowerExtra, '4x4') !== false || strpos($lowerExtra, 'cuatro ruedas') !== false || strpos($lowerExtra, '4wd') !== false || strpos($lowerExtra, 'tracción en las') !== false) {
                                                    $extraIcon = 'bi-signpost-2';
                                                } elseif (strpos($lowerExtra, 'dirección') !== false || strpos($lowerExtra, 'steering') !== false || strpos($lowerExtra, 'asistida') !== false) {
                                                    $extraIcon = 'bi-compass-fill';
                                                }
                                        ?>
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="bi <?php echo $extraIcon; ?> text-theme" style="font-size: 1.5rem;"></i>
                                                <span class="small text-navy-light fw-medium"><?php echo esc($extra); ?></span>
                                            </div>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="text-center mt-4 pb-4">
        <a href="/leasing.php#cotizar-seccion" class="btn btn-theme px-5 py-3 rounded-pill fw-bold text-white shadow text-uppercase">
            Solicitar cotización de flota
        </a>
    </div>
</section>

<style>
    .text-theme { color: var(--theme-primary) !important; }
    .fleet-filter-btn {
        border: none !important;
        background: transparent !important;
        color: var(--theme-primary) !important;
        font-weight: 700;
        font-size: 0.95rem;
        font-family: 'Montserrat', sans-serif;
        padding: 10px 22px;
        border-radius: 6px;
        transition: all 0.2s ease-in-out;
        text-transform: none;
    }
    .fleet-filter-btn:hover {
        background-color: rgba(var(--theme-primary-rgb), 0.05) !important;
    }
    .fleet-filter-btn.active {
        background-color: var(--theme-primary) !important;
        color: #ffffff !important;
    }
    .fleet-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .fleet-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(8,16,38,0.08) !important;
    }
    .fleet-img-hover {
        transition: transform 0.4s ease;
    }
    .fleet-card:hover .fleet-img-hover {
        transform: scale(1.05);
    }
    @media (min-width: 768px) {
        .border-start-md {
            border-start: 1px solid #ebedf3;
            border-left: 1px solid #ebedf3;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.fleet-filter-btn');
    const fleetCards = document.querySelectorAll('.fleet-card-wrapper');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            const selectedCategory = this.getAttribute('data-category');

            fleetCards.forEach(card => {
                const cardCategory = card.getAttribute('data-category');

                if (selectedCategory === 'all' || cardCategory === selectedCategory) {
                    card.style.opacity = '0';
                    card.classList.remove('d-none');
                    setTimeout(() => {
                        card.style.opacity = '1';
                    }, 50);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.classList.add('d-none');
                    }, 300);
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
