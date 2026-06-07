<?php
/**
 * Automarket - Rent A Car Checkout Page
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Stepper Navigation Progress Bar -->
<section class="container mt-4 pt-2">
    <div class="row">
        <div class="col-12">
            <div class="stepper-container" id="checkoutStepper">
                <div class="stepper-line"></div>
                <div class="stepper-line-active" id="checkoutStepperProgress" style="width: 70%;"></div>
                
                <div class="step-item completed" data-step="1">
                    <div class="step-badge" style="cursor: pointer;" onclick="window.location.href='/rent-a-car.php'"><i class="bi bi-check-lg"></i></div>
                    <span class="step-title">1. Fecha y Lugar</span>
                </div>
                <div class="step-item completed" data-step="2">
                    <div class="step-badge" style="cursor: pointer;" onclick="window.location.href='/resultados.php'"><i class="bi bi-check-lg"></i></div>
                    <span class="step-title">2. Vehículo</span>
                </div>
                <div class="step-item active" data-step="3" id="stepperStep3">
                    <div class="step-badge">3</div>
                    <span class="step-title">3. Adicionales y Datos</span>
                </div>
                <div class="step-item" data-step="4" id="stepperStep4">
                    <div class="step-badge">4</div>
                    <span class="step-title">4. Confirmación</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Error / fallback state if no vehicle selected -->
<section class="container mt-5 mb-5" id="checkoutMainSection">
    <div id="noVehicleWarning" class="card border-0 shadow-sm p-5 text-center rounded-4 d-none">
        <i class="bi bi-cart-x text-muted opacity-50" style="font-size: 4rem;"></i>
        <h4 class="fw-bold text-navy mt-3">No has seleccionado ningún vehículo</h4>
        <p class="text-muted mb-4 font-poppins">Por favor, realiza una búsqueda y selecciona el vehículo de tu preferencia para continuar.</p>
        <a href="/rent-a-car.php" class="btn btn-theme px-4 py-2 rounded-pill fw-bold text-white shadow-sm">
            Volver al Inicio
        </a>
    </div>

    <!-- Success confirmation state -->
    <div id="successPanel" class="card border-0 shadow p-5 text-center rounded-4 bg-white d-none my-4">
        <div class="text-success mb-3">
            <i class="bi bi-check-circle-fill" style="font-size: 5rem;"></i>
        </div>
        <h2 class="fw-bold text-navy font-montserrat">¡Reserva Registrada con Éxito!</h2>
        <p class="text-muted font-poppins fs-5 max-width-600 mx-auto mt-2 mb-2">
            Tu solicitud fue registrada correctamente.
        </p>
        <p id="successReservationCode" class="fw-bold text-navy fs-4 font-montserrat mb-4"></p>
        <p class="text-muted font-poppins mb-4">Un asesor de Automarket se pondrá en contacto contigo para confirmar los detalles.</p>
        <div class="border-top pt-4">
            <a href="/rent-a-car.php" class="btn btn-theme px-5 py-3 rounded-pill fw-bold text-white shadow-sm text-uppercase">
                Nueva Búsqueda
            </a>
        </div>
    </div>

    <!-- Main Checkout split layout -->
    <div class="row g-4" id="checkoutGrid">
        
        <!-- Left Column: Checkout Form -->
        <div class="col-lg-7 col-12">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h3 class="fw-bold text-navy mb-3 font-montserrat"><i class="bi bi-shield-check text-danger me-2"></i>Protección y extras</h3>
                <div id="coverageOptions" class="mb-4 d-flex flex-column gap-2"></div>

                <h3 class="fw-bold text-navy mb-4 font-montserrat mt-2"><i class="bi bi-person-fill text-danger me-2"></i>Tus Datos Personales</h3>
                
                <form id="checkoutBookingForm" class="needs-validation" novalidate onsubmit="submitCheckoutBooking(event)">
                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-6 col-12">
                            <label for="checkoutName" class="form-label fw-semibold text-navy">Nombre Completo</label>
                            <input type="text" id="checkoutName" class="form-control form-control-premium" placeholder="Ingresa tu nombre y apellido" required>
                            <div class="invalid-feedback">Por favor ingresa tu nombre.</div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6 col-12">
                            <label for="checkoutPhone" class="form-label fw-semibold text-navy">Número Telefónico</label>
                            <input type="tel" id="checkoutPhone" class="form-control form-control-premium" placeholder="Ej: 6655-4433" required>
                            <div class="invalid-feedback">Por favor ingresa tu número telefónico.</div>
                        </div>

                        <!-- Email -->
                        <div class="col-12">
                            <label for="checkoutEmail" class="form-label fw-semibold text-navy">Correo Electrónico</label>
                            <input type="email" id="checkoutEmail" class="form-control form-control-premium" placeholder="nombre@correo.com" required>
                            <div class="invalid-feedback">Por favor ingresa un correo electrónico válido.</div>
                        </div>

                        <!-- Comments -->
                        <div class="col-12">
                            <label for="checkoutComments" class="form-label fw-semibold text-navy">Comentarios Adicionales (Opcional)</label>
                            <textarea id="checkoutComments" class="form-control form-control-premium" rows="4" placeholder="Ej: Necesito silla de bebé, conductor adicional, entrega en oficina específica..."></textarea>
                        </div>

                        <!-- Checkbox policy -->
                        <div class="col-12 mt-3">
                            <div class="form-check custom-checkbox">
                                <input class="form-check-input" type="checkbox" id="checkoutPolicyCheck" required checked>
                                <label class="form-check-label text-muted text-sm font-poppins" for="checkoutPolicyCheck">
                                    Acepto las políticas de alquiler, términos de privacidad y condiciones de Automarket Rent A Car.
                                </label>
                            </div>
                        </div>

                        <!-- Submit Booking Action -->
                        <div class="col-12 mt-4">
                            <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                            <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white fs-5 shadow">
                                <i class="bi bi-shield-lock-fill me-2"></i>CONFIRMAR RESERVA
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Booking Overview -->
        <div class="col-lg-5 col-12">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white position-sticky" style="top: 100px;">
                <h4 class="fw-bold text-navy mb-4 font-montserrat"><i class="bi bi-cart-check-fill text-danger me-2"></i>Resumen de Alquiler</h4>
                
                <!-- Vehicle Preview Info -->
                <div class="d-flex align-items-center gap-3 border-bottom pb-4 mb-4">
                    <div id="checkoutVehicleImage" class="bg-light-gray rounded p-2 text-center d-flex align-items-center justify-content-center" style="width: 100px; height: 75px;">
                        <!-- Injected via JS -->
                    </div>
                    <div>
                        <span id="checkoutVehicleCategory" class="badge bg-danger-subtle text-danger text-uppercase mb-1" style="font-size: 0.7rem;">-</span>
                        <h5 id="checkoutVehicleName" class="fw-bold text-navy mb-0 fs-6">-</h5>
                        <small id="checkoutVehicleTrans" class="text-muted d-block font-poppins" style="font-size: 0.8rem;">-</small>
                    </div>
                </div>

                <!-- Query Details -->
                <div class="border-bottom pb-4 mb-4">
                    <div class="d-flex flex-column gap-3 text-sm font-poppins">
                        <div>
                            <span class="text-muted d-block">Sucursal de Retiro</span>
                            <span id="checkoutPickupLoc" class="fw-semibold text-navy"><i class="bi bi-geo-alt-fill text-danger"></i> -</span>
                        </div>
                        <div>
                            <span class="text-muted d-block">Sucursal de Devolución</span>
                            <span id="checkoutReturnLoc" class="fw-semibold text-navy"><i class="bi bi-geo-fill text-danger"></i> -</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted d-block">Fecha Retiro</span>
                                <span id="checkoutPickupDate" class="fw-semibold text-navy"><i class="bi bi-calendar-event"></i> -</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Fecha Devolución</span>
                                <span id="checkoutReturnDate" class="fw-semibold text-navy"><i class="bi bi-calendar-x"></i> -</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Details Breakdown -->
                <div>
                    <h5 class="fw-bold text-navy mb-3 font-montserrat fs-6">Desglose de Tarifas</h5>
                    <div class="d-flex flex-column gap-2 text-sm font-poppins mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tarifa Base Alquiler</span>
                            <span id="checkoutBaseRate" class="fw-semibold text-navy">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Cargo por Acceso de Servicio (SAF)</span>
                            <span id="checkoutSafRate" class="fw-semibold text-navy">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between" id="checkoutCoverageRow">
                            <span class="text-muted">Protección seleccionada</span>
                            <span id="checkoutCoverageRate" class="fw-semibold text-navy">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Impuesto (ITBMS 7%)</span>
                            <span id="checkoutItbmsRate" class="fw-semibold text-navy">$0.00</span>
                        </div>
                    </div>
                    
                    <hr class="border-light-gray my-3">
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-navy fs-5 font-montserrat">Precio Total Estimado</span>
                        <div class="text-end">
                            <span id="checkoutTotalRate" class="fw-bold text-navy fs-4 font-poppins">$0.00</span>
                            <span class="text-muted text-sm d-block font-poppins" style="font-size: 0.75rem; margin-top: -3px;">USD</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<script>
function setCheckoutStepperConfirmed() {
    const progress = document.getElementById('checkoutStepperProgress');
    if (progress) progress.style.width = '100%';

    document.querySelectorAll('#checkoutStepper .step-item').forEach(el => {
        el.classList.remove('active');
        el.classList.add('completed');
        const badge = el.querySelector('.step-badge');
        if (badge) badge.innerHTML = '<i class="bi bi-check-lg"></i>';
    });

    const step4 = document.getElementById('stepperStep4');
    if (step4) {
        step4.classList.add('active');
        const badge = step4.querySelector('.step-badge');
        if (badge) badge.innerHTML = '<i class="bi bi-check-lg"></i>';
    }
}

function recalcCheckoutTotals() {
    const state = window._racCheckoutPricing;
    if (!state) return;

    const selected = document.querySelector('input[name="coverage_code"]:checked');
    let coverageAmt = 0;
    let coverageLabel = '—';
    if (selected && state.packagesByCode[selected.value]) {
        const pkg = state.packagesByCode[selected.value];
        coverageAmt = parseFloat(pkg.amountTotal ?? pkg.pricePerDay ?? 0) || 0;
        coverageLabel = pkg.name || pkg.description || selected.value;
    }

    const subtotal = state.rentalBase + state.saf + coverageAmt;
    const itbms = Math.round(subtotal * 0.07 * 100) / 100;
    const total = Math.round((subtotal + itbms) * 100) / 100;

    document.getElementById('checkoutBaseRate').innerText = '$' + state.rentalBase.toFixed(2);
    document.getElementById('checkoutSafRate').innerText = '$' + state.saf.toFixed(2);
    document.getElementById('checkoutCoverageRate').innerText = '$' + coverageAmt.toFixed(2);
    document.getElementById('checkoutItbmsRate').innerText = '$' + itbms.toFixed(2);
    document.getElementById('checkoutTotalRate').innerText = '$' + total.toFixed(2);

    window._racCheckoutPricing.currentTotal = total;
    window._racCheckoutPricing.currentCoverage = coverageAmt;
    window._racCheckoutPricing.currentItbms = itbms;

    document.querySelectorAll('#coverageOptions label').forEach(lbl => {
        lbl.classList.remove('border-danger');
        const inp = lbl.querySelector('input[name="coverage_code"]');
        if (inp && inp.checked) lbl.classList.add('border-danger');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    // Read from sessionStorage
    const selectedVehicleRaw = sessionStorage.getItem('selectedVehicle');
    const searchCriteriaRaw = sessionStorage.getItem('searchCriteria');
    
    const noVehicleWarning = document.getElementById('noVehicleWarning');
    const checkoutGrid = document.getElementById('checkoutGrid');
    
    if (!selectedVehicleRaw || !searchCriteriaRaw) {
        if (noVehicleWarning) noVehicleWarning.classList.remove('d-none');
        if (checkoutGrid) checkoutGrid.classList.add('d-none');
        return;
    }
    
    const vehicle = JSON.parse(selectedVehicleRaw);
    const criteria = JSON.parse(searchCriteriaRaw);
    
    // 1. Populate Vehicle details
    const checkoutVehicleName = document.getElementById('checkoutVehicleName');
    const checkoutVehicleCategory = document.getElementById('checkoutVehicleCategory');
    const checkoutVehicleTrans = document.getElementById('checkoutVehicleTrans');
    const checkoutVehicleImage = document.getElementById('checkoutVehicleImage');
    
    if (checkoutVehicleName) checkoutVehicleName.innerText = vehicle.name || 'Vehículo Seleccionado';
    if (checkoutVehicleCategory) checkoutVehicleCategory.innerText = vehicle.category || 'General';
    if (checkoutVehicleTrans) {
        const pax = vehicle.passengers || 5;
        const gear = vehicle.transmission || 'Automática';
        const ac = vehicle.ac ? 'Con A/C' : 'Sin A/C';
        checkoutVehicleTrans.innerText = `${gear} | ${pax} Pasajeros | ${ac}`;
    }
    
    if (checkoutVehicleImage) {
        const hostUrl = "https://automarket-rentacar-fme3z.ondigitalocean.app";
        let imgSrc = '';
        if (vehicle.image) {
            imgSrc = vehicle.image.startsWith('http') ? vehicle.image : (hostUrl + vehicle.image);
            checkoutVehicleImage.innerHTML = `<img src="${imgSrc}" class="img-fluid" alt="${vehicle.name}" style="max-height: 55px; object-fit: contain;">`;
        } else {
            checkoutVehicleImage.innerHTML = `<i class="bi bi-car-front text-muted opacity-50" style="font-size: 2.2rem;"></i>`;
        }
    }
    
    // 2. Populate Query details
    const checkoutPickupLoc = document.getElementById('checkoutPickupLoc');
    const checkoutReturnLoc = document.getElementById('checkoutReturnLoc');
    const checkoutPickupDate = document.getElementById('checkoutPickupDate');
    const checkoutReturnDate = document.getElementById('checkoutReturnDate');
    
    if (checkoutPickupLoc) checkoutPickupLoc.innerHTML = `<i class="bi bi-geo-alt-fill text-danger me-1"></i> Sucursal: <strong>${criteria.locationCode}</strong>`;
    if (checkoutReturnLoc) checkoutReturnLoc.innerHTML = `<i class="bi bi-geo-fill text-danger me-1"></i> Sucursal: <strong>${criteria.returnLocationCode || criteria.locationCode}</strong>`;
    if (checkoutPickupDate) checkoutPickupDate.innerHTML = `<i class="bi bi-calendar-event me-1"></i> ${criteria.pickupDate} <small class="text-muted">${criteria.pickupTime}</small>`;
    if (checkoutReturnDate) checkoutReturnDate.innerHTML = `<i class="bi bi-calendar-x me-1"></i> ${criteria.returnDate} <small class="text-muted">${criteria.returnTime}</small>`;
    
    const pricing = vehicle.pricing || {};
    const rentalBase = parseFloat(pricing.rateBase ?? vehicle.priceTotal ?? vehicle.priceWeb ?? 0) || 0;
    const saf = parseFloat(pricing.saf ?? 0) || 0;

    const coverageWrap = document.getElementById('coverageOptions');
    const packages = pricing.coveragePackages || vehicle.availableCoverages || [];
    const packagesByCode = {};
    packages.forEach((pkg, i) => {
        const code = pkg.code || pkg.coverageType || ('cov_' + i);
        packagesByCode[code] = pkg;
    });

    window._racCheckoutPricing = {
        rentalBase,
        saf,
        packagesByCode
    };

    const defaultPkg = packages.find(p => p.isDefault) || packages[0];
    const defaultCode = defaultPkg ? (defaultPkg.code || defaultPkg.coverageType) : 'BASIC';

    if (coverageWrap && packages.length) {
        packages.forEach((pkg, i) => {
            const code = pkg.code || pkg.coverageType || ('cov_' + i);
            const id = 'cov_' + code.replace(/[^a-zA-Z0-9_-]/g, '');
            const isDefault = pkg.isDefault || code === defaultCode;
            const checked = isDefault ? 'checked' : '';
            const name = (pkg.name || pkg.description || code).replace(/</g, '&lt;');
            const amt = parseFloat(pkg.amountTotal || pkg.pricePerDay || 0).toFixed(2);
            const ded = parseFloat(pkg.deductible || 0).toFixed(0);
            coverageWrap.insertAdjacentHTML('beforeend', `
                <label class="border rounded-3 p-3 d-flex gap-3 align-items-start cursor-pointer ${checked ? 'border-danger' : ''}">
                    <input type="radio" name="coverage_code" class="form-check-input mt-1 rac-coverage-radio" value="${code}" id="${id}" data-amount="${amt}" ${checked}>
                    <div>
                        <span class="fw-bold text-navy d-block">${name}</span>
                        <small class="text-muted">$${amt} total · Deducible $${ded}</small>
                    </div>
                </label>`);
        });

        coverageWrap.querySelectorAll('.rac-coverage-radio').forEach(radio => {
            radio.addEventListener('change', recalcCheckoutTotals);
        });
    } else if (coverageWrap) {
        coverageWrap.innerHTML = '<p class="text-muted text-sm">La protección se confirmará con su asesor al validar la reserva.</p>';
        document.getElementById('checkoutCoverageRow')?.classList.add('d-none');
    }

    recalcCheckoutTotals();
});

/**
 * Handle form submit
 */
function submitCheckoutBooking(e) {
    e.preventDefault();
    
    const form = document.getElementById('checkoutBookingForm');
    
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }
    
    form.classList.add('was-validated');
    
    const name = document.getElementById('checkoutName').value;
    const phone = document.getElementById('checkoutPhone').value;
    const email = document.getElementById('checkoutEmail').value;
    const comments = document.getElementById('checkoutComments').value;
    
    const vehicle = JSON.parse(sessionStorage.getItem('selectedVehicle'));
    const criteria = JSON.parse(sessionStorage.getItem('searchCriteria'));
    
    const coverageEl = document.querySelector('input[name="coverage_code"]:checked');
    const rateType = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';

    const pricingState = window._racCheckoutPricing || {};
    const estimatedTotal = pricingState.currentTotal
        || vehicle.priceTotalEstimated
        || vehicle.priceTotal
        || 0;

    let coverageName = '';
    let coverageDeductible = null;
    if (coverageEl && pricingState.packagesByCode && pricingState.packagesByCode[coverageEl.value]) {
        const pkg = pricingState.packagesByCode[coverageEl.value];
        coverageName = pkg.name || pkg.description || coverageEl.value;
        coverageDeductible = pkg.deductible != null ? parseFloat(pkg.deductible) : null;
    }

    const payload = {
        customer_name: name,
        customer_phone: phone,
        customer_email: email,
        customer_comments: comments,
        coverage_code: coverageEl ? coverageEl.value : '',
        coverage_name: coverageName,
        coverage_amount: pricingState.currentCoverage ?? 0,
        coverage_deductible: coverageDeductible,
        price_rental_base: pricingState.rentalBase ?? null,
        price_saf: pricingState.saf ?? null,
        price_itbms: pricingState.currentItbms ?? null,
        rate_type: rateType,
        price_total_estimated: estimatedTotal,
        search: criteria,
        vehicle: vehicle
    };
    
    // Show full screen spinner
    let loader = document.createElement('div');
    loader.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white';
    loader.style.zIndex = '99999';
    loader.style.backgroundColor = 'rgba(8, 16, 38, 0.9)';
    loader.style.backdropFilter = 'blur(6px)';
    loader.innerHTML = `
        <div class="spinner-border text-danger" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Procesando...</span>
        </div>
        <h3 class="mt-4 fw-bold font-montserrat">Procesando tu Reserva</h3>
        <p class="text-secondary-light font-poppins text-sm text-center">Guardando su reserva...</p>
    `;
    document.body.appendChild(loader);
    
    fetch('/api/rac-reservation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json().then(data => ({ ok: res.ok, data })))
    .then(({ ok, data }) => {
        if (loader) loader.remove();
        if (!ok || !data.success) {
            alert(data.message || 'No se pudo registrar la reserva.');
            return;
        }
        
        document.getElementById('checkoutGrid').classList.add('d-none');
        document.getElementById('successPanel').classList.remove('d-none');
        setCheckoutStepperConfirmed();
        const codeEl = document.getElementById('successReservationCode');
        if (codeEl && data.reservation_code) {
            codeEl.textContent = 'Código de reserva: ' + data.reservation_code;
        }
        
        sessionStorage.removeItem('selectedVehicle');
        sessionStorage.removeItem('selectedRateType');
        sessionStorage.removeItem('searchResults');
        sessionStorage.removeItem('searchCriteria');
    })
    .catch(err => {
        console.error(err);
        if (loader) loader.remove();
        alert("Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.");
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
