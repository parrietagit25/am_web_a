<?php
/**
 * Reservation Search Form Widget (handoff RAC)
 */
require_once __DIR__ . '/../services/BranchDataService.php';
$racBranches = BranchDataService::getSucursales();
?>
<div class="booking-search-card shadow-lg p-4 rounded-4 bg-light-translucent" id="racSearchCard">
    <h3 class="fw-bold mb-4 text-navy text-center text-lg-start"><i class="bi bi-calendar-check-fill me-2 text-theme"></i><?php echo esc(t('reservation.title')); ?></h3>

    <div id="racSearchAlert" class="alert alert-warning d-none rounded-3 font-poppins text-sm" role="alert"></div>

    <form id="reservationSearchForm" class="needs-validation" novalidate>
        <div class="row g-3">
            <div class="col-lg-4 col-md-6 col-12">
                <label for="pickupLocation" class="form-label fw-semibold text-navy"><i class="bi bi-geo-alt-fill text-theme me-1"></i>Sucursal de Retiro</label>
                <select id="pickupLocation" class="form-select form-control-premium" required>
                    <option value="" disabled selected>Selecciona sucursal...</option>
                    <?php foreach ($racBranches as $branch): ?>
                        <option value="<?php echo esc($branch['code']); ?>"><?php echo esc($branch['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Por favor selecciona la sucursal de retiro.</div>
            </div>

            <div class="col-lg-4 col-md-6 col-12 d-none transition-all" id="returnLocationWrapper">
                <label for="returnLocation" class="form-label fw-semibold text-navy"><i class="bi bi-geo-fill text-theme me-1"></i>Sucursal de Devolución</label>
                <select id="returnLocation" class="form-select form-control-premium">
                    <option value="" disabled selected>Selecciona sucursal...</option>
                    <?php foreach ($racBranches as $branch): ?>
                        <option value="<?php echo esc($branch['code']); ?>"><?php echo esc($branch['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-4 col-md-6 col-12">
                <label for="pickupDate" class="form-label fw-semibold text-navy"><i class="bi bi-calendar-event text-theme me-1"></i>Fecha de Retiro</label>
                <div class="input-group">
                    <input type="date" id="pickupDate" class="form-control form-control-premium" required>
                    <select id="pickupTime" class="form-select form-control-premium max-width-120" required></select>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-12">
                <label for="returnDate" class="form-label fw-semibold text-navy"><i class="bi bi-calendar-x text-theme me-1"></i>Fecha de Devolución</label>
                <div class="input-group">
                    <input type="date" id="returnDate" class="form-control form-control-premium" required>
                    <select id="returnTime" class="form-select form-control-premium max-width-120" required></select>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-12">
                <label for="driverAge" class="form-label fw-semibold text-navy"><i class="bi bi-person-badge text-theme me-1"></i>Edad del conductor</label>
                <select id="driverAge" class="form-select form-control-premium" required>
                    <option value="25" selected>25 años o más</option>
                    <option value="23">23-24 años</option>
                </select>
                <small class="text-muted font-poppins" style="font-size: 0.75rem;">Menores de 23 años: contacte la sucursal por teléfono.</small>
            </div>

            <div class="col-lg-4 col-md-6 col-12 d-none transition-all" id="couponCodeWrapper">
                <label for="promoCode" class="form-label fw-semibold text-navy"><i class="bi bi-ticket-perforated-fill text-theme me-1"></i>Código de Cupón</label>
                <input type="text" id="promoCode" class="form-control form-control-premium" placeholder="Ej. DESCUENTO10" autocomplete="off">
            </div>
        </div>

        <div class="row mt-4 align-items-center">
            <div class="col-lg-8 col-12 d-flex flex-wrap gap-4 mb-3 mb-lg-0">
                <div class="form-check form-switch custom-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleReturnBranch">
                    <label class="form-check-label fw-semibold text-navy text-sm" for="toggleReturnBranch">Devolver en otra sucursal</label>
                </div>
                <div class="form-check form-switch custom-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleCoupon">
                    <label class="form-check-label fw-semibold text-navy text-sm" for="toggleCoupon">Tengo un cupón</label>
                </div>
            </div>
            <div class="col-lg-4 col-12 text-center text-lg-end">
                <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white fs-5 shadow transition-all">
                    <i class="bi bi-search me-2"></i>BUSCAR VEHÍCULO
                </button>
            </div>
        </div>
    </form>
</div>
