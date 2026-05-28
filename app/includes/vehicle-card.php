<?php
/**
 * Vehicle Card Component
 * Expects $vehicle array to be set.
 */
if (!isset($vehicle)) {
    return;
}

$name = esc($vehicle['name'] ?? 'Vehículo Automarket');
$category = esc($vehicle['category'] ?? 'General');
$passengers = (int)($vehicle['passengers'] ?? 5);
$ac = (bool)($vehicle['ac'] ?? true);
$transmission = esc($vehicle['transmission'] ?? 'Automática');
$price = (float)($vehicle['price'] ?? 0.00);

// Use temporary mock images or placeholder icons if image isn't loaded
$imgName = esc($vehicle['img'] ?? 'default-car.jpg');
$imgUrl = "/assets/img/" . $imgName;

// Fallback to stylized SVG or placeholder if no image exists
?>
<div class="col-lg-4 col-md-6 col-12 d-flex">
    <div class="card vehicle-card border-0 shadow-sm rounded-4 w-100 flex-column justify-content-between overflow-hidden transition-all duration-300">
        
        <!-- Category Badge -->
        <span class="category-badge position-absolute bg-white px-3 py-1 text-navy rounded-pill fw-bold shadow-sm top-3 start-3 text-uppercase z-index-2">
            <?php echo $category; ?>
        </span>

        <!-- Card Image Area -->
        <div class="card-image-wrapper bg-light-gray p-4 text-center position-relative">
            <!-- Simulated image wrapper using CSS grids if images aren't present -->
            <div class="car-illustration-placeholder py-4">
                <i class="bi bi-car-front text-theme opacity-25" style="font-size: 5rem;"></i>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body px-4 py-3">
            <h4 class="card-title fw-bold text-navy mb-3"><?php echo $name; ?></h4>
            
            <!-- Specs Grid -->
            <div class="specs-grid d-flex flex-wrap gap-3 mb-4 text-muted">
                <div class="spec-item d-flex align-items-center gap-1">
                    <i class="bi bi-people-fill text-theme-secondary"></i>
                    <span><?php echo $passengers; ?> Pasajeros</span>
                </div>
                <div class="spec-item d-flex align-items-center gap-1">
                    <i class="bi bi-snow text-theme-secondary"></i>
                    <span><?php echo $ac ? 'A/C' : 'No A/C'; ?></span>
                </div>
                <div class="spec-item d-flex align-items-center gap-1">
                    <i class="bi bi-gear-wide-connected text-theme-secondary"></i>
                    <span><?php echo $transmission; ?></span>
                </div>
            </div>
            
            <hr class="border-light-gray my-3">

            <!-- Pricing and Action -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="price-block">
                    <span class="text-muted text-sm d-block font-poppins">Precio diario</span>
                    <span class="fs-3 fw-bold text-navy font-poppins">$<?php echo number_format($price, 2); ?></span>
                    <span class="text-muted text-sm font-poppins">USD</span>
                </div>
                <a href="#booking-step-3" class="btn btn-theme px-4 py-2 rounded-pill fw-bold text-white shadow-sm transition-all" onclick="alert('Iniciando reserva para: <?php echo addslashes($name); ?>. Se conectará con el paso 3 del stepper.')">
                    Reservar <i class="bi bi-arrow-right-short ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
