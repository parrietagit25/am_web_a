<?php
/**
 * Automarket - Pago Seguro (Secure Payment)
 */
$activeUnit = 'rentacar';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- 1. Breadcrumb and Title Section -->
<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="/rent-a-car.php" class="text-danger text-decoration-none fw-semibold">Rent A Car</a></li>
                <li class="breadcrumb-item active" aria-current="page">Paga tu Reserva</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;">Paga tu Reserva</h1>
        <p class="text-muted font-poppins mt-2 mb-0">Realice el pago de su reserva de forma rápida, cómoda y segura. Aceptamos las principales tarjetas de crédito.</p>
    </div>
</section>

<!-- 2. Payment Page Layout -->
<section class="container py-5 mb-5">
    <div class="row g-5">
        <!-- Left Side: Secure Payment Form -->
        <div class="col-lg-7 col-12">
            <div class="p-4 p-md-5 rounded-4 shadow-sm" style="background-color: #ededed; border: 1px solid #e0e0e0;">
                <h3 class="fw-bold font-montserrat text-navy mb-4" style="font-size: 1.4rem;">
                    <i class="bi bi-shield-lock-fill text-danger me-2"></i>Pago Seguro con Tarjeta de Crédito
                </h3>
                
                <form id="paymentForm" onsubmit="handlePaymentSubmit(event)" novalidate>
                    <div class="row g-3">
                        <!-- Reservation Number -->
                        <div class="col-md-6 col-12">
                            <label for="reserva_id" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Número de Reserva <span class="text-danger">*</span></label>
                            <input type="text" id="reserva_id" name="reserva_id" class="form-control form-control-premium bg-white border-0 py-3" placeholder="Ej: AM-98765" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 col-12">
                            <label for="email" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control form-control-premium bg-white border-0 py-3" placeholder="nombre@correo.com" required>
                        </div>
                        
                        <!-- Amount to Pay -->
                        <div class="col-md-6 col-12">
                            <label for="monto" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Monto a Pagar (USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 text-navy fw-bold font-poppins">$</span>
                                <input type="number" id="monto" name="monto" min="1.00" step="0.01" class="form-control form-control-premium bg-white border-0 py-3 ps-1" placeholder="0.00" required>
                            </div>
                        </div>

                        <!-- Cardholder Name -->
                        <div class="col-md-6 col-12">
                            <label for="nombre_tarjeta" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Nombre en la Tarjeta <span class="text-danger">*</span></label>
                            <input type="text" id="nombre_tarjeta" name="nombre_tarjeta" class="form-control form-control-premium bg-white border-0 py-3" placeholder="Como aparece en la tarjeta" required>
                        </div>

                        <!-- Credit Card Number -->
                        <div class="col-12">
                            <label for="numero_tarjeta" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Número de Tarjeta <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" id="numero_tarjeta" name="numero_tarjeta" maxlength="19" class="form-control form-control-premium bg-white border-0 py-3 pe-5" placeholder="0000 0000 0000 0000" required>
                                <span class="position-absolute top-50 translate-middle-y end-0 me-3 text-muted">
                                    <i class="bi bi-credit-card-2-front fs-5" id="cardIcon"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Expiry & CVV -->
                        <div class="col-md-6 col-12">
                            <label for="expiracion" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Expiración (MM/AA) <span class="text-danger">*</span></label>
                            <input type="text" id="expiracion" name="expiracion" maxlength="5" class="form-control form-control-premium bg-white border-0 py-3" placeholder="MM/AA" required>
                        </div>

                        <div class="col-md-6 col-12">
                            <label for="cvv" class="form-label text-navy font-poppins fw-semibold size-xs mb-1">Código de Seguridad (CVV) <span class="text-danger">*</span></label>
                            <input type="password" id="cvv" name="cvv" maxlength="4" class="form-control form-control-premium bg-white border-0 py-3" placeholder="123" required>
                        </div>

                        <!-- Info Note -->
                        <div class="col-12 mt-3">
                            <div class="d-flex align-items-start gap-2 text-muted" style="font-size: 0.85rem;">
                                <i class="bi bi-info-circle-fill text-danger flex-shrink-0 mt-0.5"></i>
                                <span class="font-poppins">El titular de la tarjeta de crédito debe ser obligatoriamente el titular del contrato de alquiler al momento de retirar el vehículo.</span>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="col-12 text-center mt-4">
                            <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                            <button type="submit" id="payBtn" class="btn btn-theme w-100 py-3 fw-bold text-uppercase rounded-3 font-montserrat shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #c51f17; border-color: #c51f17;">
                                <i class="bi bi-lock-fill"></i> Realizar Pago Seguro
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Security Badges -->
                <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-center align-items-center gap-4 text-muted">
                    <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                        <i class="bi bi-shield-fill-check text-success fs-5"></i>
                        <span class="font-poppins fw-semibold">Conexión SSL de 256 bits</span>
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <span class="font-poppins fw-semibold">Pagos 100% Seguros</span>
                    </div>
                </div>

                <!-- Success Banner -->
                <div id="successBanner" class="mt-4 p-4 rounded-3 d-none flex-column align-items-center text-center text-white" style="background-color: #2b2b2b;">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-check-lg text-white fs-3"></i>
                    </div>
                    <h4 class="fw-bold font-montserrat mb-2">¡Pago Procesado con Éxito!</h4>
                    <p class="small font-poppins opacity-75 mb-0" id="successMessage">
                        Su transacción ha sido completada de manera segura. Se ha enviado un comprobante de pago a su correo electrónico.
                    </p>
                </div>

                <!-- Error Banner -->
                <div id="errorBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white bg-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="errorMessage" class="fw-semibold font-poppins">Ocurrió un error al procesar el pago.</span>
                </div>
            </div>
        </div>
        
        <!-- Right Side: Sidebar Policies and Notes -->
        <div class="col-lg-5 col-12">
            <div class="ps-lg-4 sticky-widget">
                <div class="p-4 rounded-4 shadow-sm text-navy bg-white border" style="font-family: 'Poppins', sans-serif;">
                    
                    <!-- Accepted Cards -->
                    <div class="mb-4 text-center text-lg-start">
                        <h5 class="fw-bold font-montserrat text-uppercase mb-3" style="font-size: 1.0rem; letter-spacing: 0.5px;">Aceptamos pagos con:</h5>
                        <div class="d-flex gap-3 justify-content-center justify-content-lg-start align-items-center fs-2 text-muted">
                            <i class="bi bi-credit-card-fill text-navy" title="Visa / Mastercard"></i>
                            <span class="badge bg-light text-dark font-montserrat" style="font-size: 0.8rem; border: 1px solid #ddd;">VISA</span>
                            <span class="badge bg-light text-dark font-montserrat" style="font-size: 0.8rem; border: 1px solid #ddd;">MASTERCARD</span>
                            <span class="badge bg-light text-dark font-montserrat" style="font-size: 0.8rem; border: 1px solid #ddd;">AMEX</span>
                        </div>
                    </div>

                    <!-- Deposit Block -->
                    <div class="mb-4">
                        <h5 class="fw-bold font-montserrat text-uppercase border-bottom pb-2 mb-3" style="font-size: 1.0rem; letter-spacing: 0.5px; color: #c51f17;">
                            <i class="bi bi-cash-stack me-2"></i>Depósito de Seguridad
                        </h5>
                        <p class="small text-muted mb-0" style="line-height: 1.6;">
                            Al retirar el vehículo, al conductor principal se le bloqueará un depósito de seguridad en su tarjeta de crédito dependiendo de la categoría del auto.
                        </p>
                        <p class="small text-muted mt-2 mb-0" style="line-height: 1.6;">
                            <strong>No se aceptarán tarjetas de débito ni dinero en efectivo para el depósito.</strong> El personal de la oficina confirmará la cantidad exacta en el counter.
                        </p>
                    </div>

                    <!-- Important Guidelines -->
                    <div>
                        <h5 class="fw-bold font-montserrat text-uppercase border-bottom pb-2 mb-3" style="font-size: 1.0rem; letter-spacing: 0.5px; color: #c51f17;">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>Importante:
                        </h5>
                        <ul class="small text-muted ps-3 mb-0" style="line-height: 1.6;">
                            <li class="mb-2">No se aceptan tarjetas virtuales ni tarjetas prepagadas que no tengan relieve físico.</li>
                            <li class="mb-2">Deberá presentar físicamente la misma tarjeta de crédito utilizada para realizar este pago al momento de retirar el auto.</li>
                            <li class="mb-0">Para alquileres con pagos a través de Yappy aplican restricciones y políticas adicionales de la sucursal.</li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Page Styling and Interactive Behavior -->
<style>
    .form-control-premium:focus {
        border-color: #c51f17 !important;
        box-shadow: 0 0 0 0.25rem rgba(197, 31, 23, 0.15) !important;
    }
    @media (min-width: 992px) {
        .sticky-widget {
            position: sticky;
            top: 100px;
            z-index: 10;
        }
    }
</style>

<script>
// Masking logic for credit card and expiration inputs
document.getElementById('numero_tarjeta').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) {
            formatted += ' ';
        }
        formatted += value[i];
    }
    e.target.value = formatted;
    
    // Change card icon based on brand
    const cardIcon = document.getElementById('cardIcon');
    if (value.startsWith('4')) {
        cardIcon.className = 'bi bi-credit-card-2-front text-primary fs-5';
    } else if (value.startsWith('5') || value.startsWith('2')) {
        cardIcon.className = 'bi bi-credit-card-2-front text-warning fs-5';
    } else if (value.startsWith('3')) {
        cardIcon.className = 'bi bi-credit-card-2-front text-info fs-5';
    } else {
        cardIcon.className = 'bi bi-credit-card-2-front text-muted fs-5';
    }
});

document.getElementById('expiracion').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if (value.length > 2) {
        e.target.value = value.substring(0, 2) + '/' + value.substring(2, 4);
    } else {
        e.target.value = value;
    }
});

async function handlePaymentSubmit(event) {
    event.preventDefault();
    
    const form = document.getElementById('paymentForm');
    const payBtn = document.getElementById('payBtn');
    const successBanner = document.getElementById('successBanner');
    const errorBanner = document.getElementById('errorBanner');
    const errorMessage = document.getElementById('errorMessage');
    
    // Reset banner displays
    successBanner.classList.add('d-none');
    successBanner.classList.remove('d-flex');
    errorBanner.classList.add('d-none');
    
    // Get fields
    const reservaId = document.getElementById('reserva_id').value.trim();
    const email = document.getElementById('email').value.trim();
    const monto = document.getElementById('monto').value.trim();
    const nombreTarjeta = document.getElementById('nombre_tarjeta').value.trim();
    const numeroTarjeta = document.getElementById('numero_tarjeta').value.replace(/\s+/g, '');
    const expiracion = document.getElementById('expiracion').value.trim();
    const cvv = document.getElementById('cvv').value.trim();
    
    // Validation
    if (!reservaId || !email || !monto || !nombreTarjeta || !numeroTarjeta || !expiracion || !cvv) {
        errorMessage.innerText = "Por favor, llene todos los campos obligatorios.";
        errorBanner.classList.remove('d-none');
        return;
    }
    
    if (numeroTarjeta.length < 13 || numeroTarjeta.length > 16) {
        errorMessage.innerText = "Número de tarjeta de crédito inválido.";
        errorBanner.classList.remove('d-none');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorMessage.innerText = "Por favor, introduzca un correo electrónico válido.";
        errorBanner.classList.remove('d-none');
        return;
    }

    if (parseFloat(monto) <= 0 || isNaN(parseFloat(monto))) {
        errorMessage.innerText = "Por favor, introduzca un monto válido superior a $0.00.";
        errorBanner.classList.remove('d-none');
        return;
    }
    
    // Mask the credit card number for safe API payload transmitting
    const last4 = numeroTarjeta.slice(-4);
    const maskedCard = `XXXX-XXXX-XXXX-${last4}`;
    
    // Disable button & show spinner
    payBtn.disabled = true;
    payBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando Pago...';
    
    try {
        const response = await fetch('/api/pago.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                reserva_id: reservaId,
                email: email,
                monto: parseFloat(monto),
                nombre_tarjeta: nombreTarjeta,
                masked_card: maskedCard
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            // Hide the form fields to prevent resubmission
            form.classList.add('d-none');
            
            // Show success banner
            successBanner.classList.remove('d-none');
            successBanner.classList.add('d-flex');
            
            // Update message if returned from backend
            document.getElementById('successMessage').innerText = data.message || "Pago completado con éxito.";
        } else {
            errorMessage.innerText = data.message || "Error al procesar el pago.";
            errorBanner.classList.remove('d-none');
            payBtn.disabled = false;
            payBtn.innerHTML = '<i class="bi bi-lock-fill"></i> Realizar Pago Seguro';
        }
    } catch (err) {
        console.error(err);
        errorMessage.innerText = "Error de red. Inténtelo más tarde.";
        errorBanner.classList.remove('d-none');
        payBtn.disabled = false;
        payBtn.innerHTML = '<i class="bi bi-lock-fill"></i> Realizar Pago Seguro';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
