<?php
/**
 * Reservation Search Form Widget
 */
?>
<div class="booking-search-card shadow-lg p-4 rounded-4 bg-light-translucent">
    <h3 class="fw-bold mb-4 text-navy text-center text-lg-start"><i class="bi bi-calendar-check-fill me-2 text-theme"></i><?php echo esc(t('reservation.title')); ?></h3>
    
    <form id="reservationSearchForm" class="needs-validation" novalidate>
        <div class="row g-3">
            
            <!-- Retiro Location -->
            <div class="col-lg-4 col-md-6 col-12">
                <label for="pickupLocation" class="form-label fw-semibold text-navy"><i class="bi bi-geo-alt-fill text-theme me-1"></i>Sucursal de Retiro</label>
                <select id="pickupLocation" class="form-select form-control-premium" required>
                    <option value="" disabled selected>Selecciona sucursal...</option>
                    <option value="PTY">Aeropuerto Int. Tocumen (PTY)</option>
                    <option value="VES">Vía España (Ciudad de Panamá)</option>
                    <option value="DAV">David (Chiriquí)</option>
                    <option value="COR">Coronado (Playas)</option>
                    <option value="COL">Colón Free Zone</option>
                </select>
                <div class="invalid-feedback">Por favor selecciona la sucursal de retiro.</div>
            </div>

            <!-- Conditional Devolución Location -->
            <div class="col-lg-4 col-md-6 col-12 d-none transition-all" id="returnLocationWrapper">
                <label for="returnLocation" class="form-label fw-semibold text-navy"><i class="bi bi-geo-fill text-theme me-1"></i>Sucursal de Devolución</label>
                <select id="returnLocation" class="form-select form-control-premium">
                    <option value="" disabled selected>Selecciona sucursal...</option>
                    <option value="PTY">Aeropuerto Int. Tocumen (PTY)</option>
                    <option value="VES">Vía España (Ciudad de Panamá)</option>
                    <option value="DAV">David (Chiriquí)</option>
                    <option value="COR">Coronado (Playas)</option>
                    <option value="COL">Colón Free Zone</option>
                </select>
            </div>

            <!-- Date / Time Pick Up -->
            <div class="col-lg-4 col-md-6 col-12">
                <label for="pickupDate" class="form-label fw-semibold text-navy"><i class="bi bi-calendar-event text-theme me-1"></i>Fecha de Retiro</label>
                <div class="input-group">
                    <input type="date" id="pickupDate" class="form-control form-control-premium" required min="<?php echo date('Y-m-d'); ?>">
                    <select id="pickupTime" class="form-select form-control-premium max-width-120" required>
                        <?php 
                        for ($h = 6; $h <= 22; $h++) {
                            $timeStr = sprintf("%02d:00", $h);
                            $selected = ($h === 10) ? 'selected' : '';
                            echo "<option value=\"$timeStr\" $selected>$timeStr</option>";
                            $timeStrHalf = sprintf("%02d:30", $h);
                            echo "<option value=\"$timeStrHalf\">$timeStrHalf</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Date / Time Drop Off -->
            <div class="col-lg-4 col-md-6 col-12">
                <label for="returnDate" class="form-label fw-semibold text-navy"><i class="bi bi-calendar-x text-theme me-1"></i>Fecha de Devolución</label>
                <div class="input-group">
                    <input type="date" id="returnDate" class="form-control form-control-premium" required min="<?php echo date('Y-m-d'); ?>">
                    <select id="returnTime" class="form-select form-control-premium max-width-120" required>
                        <?php 
                        for ($h = 6; $h <= 22; $h++) {
                            $timeStr = sprintf("%02d:00", $h);
                            $selected = ($h === 10) ? 'selected' : '';
                            echo "<option value=\"$timeStr\" $selected>$timeStr</option>";
                            $timeStrHalf = sprintf("%02d:30", $h);
                            echo "<option value=\"$timeStrHalf\">$timeStrHalf</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Conditional Coupon Input -->
            <div class="col-lg-4 col-md-6 col-12 d-none transition-all" id="couponCodeWrapper">
                <label for="promoCode" class="form-label fw-semibold text-navy"><i class="bi bi-ticket-perforated-fill text-theme me-1"></i>Código de Cupón</label>
                <input type="text" id="promoCode" class="form-control form-control-premium" placeholder="Ej. DESCUENTO10">
            </div>

        </div>

        <!-- Toggles Section (Checkboxes) -->
        <div class="row mt-4 align-items-center">
            <div class="col-lg-8 col-12 d-flex flex-wrap gap-4 mb-3 mb-lg-0">
                
                <!-- Toggle: return branch -->
                <div class="form-check form-switch custom-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleReturnBranch">
                    <label class="form-check-label fw-semibold text-navy text-sm" for="toggleReturnBranch">Devolver en otra sucursal</label>
                </div>

                <!-- Toggle: use coupon -->
                <div class="form-check form-switch custom-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleCoupon">
                    <label class="form-check-label fw-semibold text-navy text-sm" for="toggleCoupon">Tengo un cupón</label>
                </div>

                <!-- Checkbox: age limit -->
                <div class="form-check custom-checkbox">
                    <input class="form-check-input" type="checkbox" id="ageCheck" checked required>
                    <label class="form-check-label fw-semibold text-navy text-sm" for="ageCheck">Soy mayor de 25 años</label>
                    <div class="invalid-feedback text-danger-dark font-poppins size-xs mt-1">Es necesario tener 25+ años para rentar un vehículo.</div>
                </div>

            </div>
            
            <!-- Submit Button -->
            <div class="col-lg-4 col-12 text-center text-lg-end">
                <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white fs-5 shadow transition-all">
                    <i class="bi bi-search me-2"></i>BUSCAR VEHÍCULO
                </button>
            </div>
        </div>
    </form>
</div>
