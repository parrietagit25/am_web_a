<?php
/**
 * Automarket - Contactos (Leasing Operativo)
 */
$activeUnit = 'leasing';
require_once __DIR__ . '/../includes/header.php';

$leasingContact = $contentService->get('leasing.contact', []);
$contactImageUrl = $leasingContact['contact_image_url'] ?? '';
$defaultImage = '/assets/img/sucursales-rac.webp';
if (empty($contactImageUrl)) {
    $contactImageUrl = $contentService->get('homepage.contact_image_url', $defaultImage);
}
?>

<style>
.leasing-contact-form-panel {
    background-color: #ededed;
    border: 1px solid #e0e0e0;
    border-radius: 16px;
}
.leasing-contact-form-panel .form-control:focus {
    border-color: var(--theme-primary);
    box-shadow: 0 0 0 3px rgba(var(--theme-primary-rgb), 0.12);
}
.leasing-contact-sidebar {
    position: sticky;
    top: 100px;
    z-index: 10;
}
.leasing-contact-sidebar-img {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(8, 16, 38, 0.08);
    line-height: 0;
}
.leasing-contact-sidebar-img img {
    width: 100%;
    display: block;
    object-fit: cover;
    min-height: 420px;
    max-height: 520px;
}
.leasing-contact-phone-link:hover {
    color: var(--theme-primary) !important;
}
@media (max-width: 991px) {
    .leasing-contact-sidebar {
        position: static;
    }
    .leasing-contact-sidebar-img img {
        min-height: 280px;
        max-height: 360px;
    }
}
</style>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item">
                    <a href="/leasing.php" class="text-danger text-decoration-none fw-semibold">Leasing Operativo</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Contactos</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.3rem; letter-spacing: -0.5px;">Contactos</h1>
        <p class="text-muted font-poppins mt-2 mb-0">
            Gracias por escribirnos. Tus comentarios son muy importantes para nosotros; completa el formulario y pronto te responderemos.
        </p>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row g-4 g-lg-5 align-items-start">
        <div class="col-lg-7 col-12">
            <div class="leasing-contact-form-panel p-4 p-md-5 shadow-sm">
                <form id="contactForm" onsubmit="handleLeasingContactSubmit(event)" novalidate>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="empresa" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Empresa <span class="text-danger">*</span></label>
                            <input type="text" id="empresa" name="empresa" class="form-control py-3 bg-white border-0" placeholder="Razón social o nombre comercial" required minlength="2">
                        </div>
                        <div class="col-12">
                            <label for="direccion" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Dirección</label>
                            <input type="text" id="direccion" name="direccion" class="form-control py-3 bg-white border-0" placeholder="Dirección de la empresa (opcional)">
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="nombre" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" id="nombre" name="nombre" class="form-control py-3 bg-white border-0" placeholder="Nombre y apellido" required minlength="3">
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="telefono" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" id="telefono" name="telefono" class="form-control py-3 bg-white border-0" placeholder="+507 6123-4567" required minlength="7">
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">E-mail <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control py-3 bg-white border-0" placeholder="correo@empresa.com" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="tipo_vehiculo" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Tipo de vehículo <span class="text-danger">*</span></label>
                            <select id="tipo_vehiculo" name="tipo_vehiculo" class="form-select py-3 bg-white border-0" required>
                                <option value="">Seleccione...</option>
                                <option value="SUV">SUV</option>
                                <option value="Sedán">Sedán</option>
                                <option value="Pickup">Pickup</option>
                                <option value="Van">Van</option>
                                <option value="Hatchback">Hatchback</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="fecha_alquiler" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Fecha tentativa de alquiler <span class="text-danger">*</span></label>
                            <input type="date" id="fecha_alquiler" name="fecha_alquiler" class="form-control py-3 bg-white border-0" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="primera_vez" name="primera_vez" value="1">
                                <label class="form-check-label font-poppins text-muted" style="font-size: 0.82rem;" for="primera_vez">
                                    ¿Primera vez con Automarket corporativo?
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" required>
                                <label class="form-check-label font-poppins text-muted" style="font-size: 0.82rem;" for="consent">
                                    Autorizo el tratamiento de mis datos personales conforme a la
                                    <a href="#privacidad" class="text-danger">Política de Privacidad</a> de Automarket.
                                </label>
                            </div>
                        </div>
                        <div class="col-12 text-center mt-2">
                            <button type="submit" id="submitBtn" class="btn btn-theme px-5 py-3 fw-bold text-uppercase rounded-3 font-montserrat shadow-sm" style="min-width: 180px;">
                                Solicitar cotización
                            </button>
                        </div>
                    </div>
                </form>

                <div id="successBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white" style="background-color: #2b2b2b;">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">
                        <i class="bi bi-check-lg text-white"></i>
                    </div>
                    <span id="successMessage" class="fw-semibold font-poppins">¡Gracias! Nos pondremos en contacto en menos de 24 horas.</span>
                </div>
                <div id="errorBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white bg-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="errorMessage" class="fw-semibold font-poppins">Ocurrió un error al enviar el mensaje.</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="leasing-contact-sidebar">
                <div class="mb-4">
                    <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">Teléfono:</h5>
                    <div class="d-flex flex-column gap-2">
                        <a href="tel:5072792700" class="leasing-contact-phone-link text-navy font-poppins fs-5 text-decoration-none fw-semibold d-flex align-items-center gap-2">
                            <i class="bi bi-telephone-fill text-muted"></i> (507) 279-2700
                        </a>
                        <a href="tel:50767470070" class="leasing-contact-phone-link text-navy font-poppins fs-5 text-decoration-none fw-semibold d-flex align-items-center gap-2">
                            <i class="bi bi-telephone-fill text-muted"></i> (507) 6747-0070
                        </a>
                    </div>
                </div>
                <div class="mb-4">
                    <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.05rem; letter-spacing: 0.5px;">WhatsApp:</h5>
                    <a href="https://api.whatsapp.com/send?phone=50767470070" target="_blank" rel="noopener" class="btn text-white fw-bold d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3 shadow-sm" style="background-color: #25d366; font-family: 'Poppins', sans-serif;">
                        <i class="bi bi-whatsapp fs-5"></i> (507) 6747-0070
                    </a>
                </div>
                <?php if (!empty($contactImageUrl)): ?>
                    <div class="leasing-contact-sidebar-img">
                        <img src="<?php echo esc($contactImageUrl); ?>" alt="Leasing Operativo - Contacto" loading="lazy">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
async function handleLeasingContactSubmit(event) {
    event.preventDefault();
    var form = document.getElementById('contactForm');
    var submitBtn = document.getElementById('submitBtn');
    var successBanner = document.getElementById('successBanner');
    var successMessage = document.getElementById('successMessage');
    var errorBanner = document.getElementById('errorBanner');
    var errorMessage = document.getElementById('errorMessage');

    successBanner.classList.add('d-none');
    successBanner.classList.remove('d-flex');
    errorBanner.classList.add('d-none');
    errorBanner.classList.remove('d-flex');

    var empresa = document.getElementById('empresa').value.trim();
    var direccion = document.getElementById('direccion').value.trim();
    var nombre = document.getElementById('nombre').value.trim();
    var telefono = document.getElementById('telefono').value.trim();
    var email = document.getElementById('email').value.trim();
    var tipoVehiculo = document.getElementById('tipo_vehiculo').value;
    var fechaAlquiler = document.getElementById('fecha_alquiler').value;
    var primeraVez = document.getElementById('primera_vez').checked ? 'SI' : 'NO';
    var consent = document.getElementById('consent').checked;

    if (!empresa || !nombre || !telefono || !email || !tipoVehiculo || !fechaAlquiler) {
        errorMessage.innerText = 'Por favor, complete todos los campos obligatorios.';
        errorBanner.classList.remove('d-none');
        errorBanner.classList.add('d-flex');
        return;
    }
    if (telefono.replace(/\D/g, '').length < 7) {
        errorMessage.innerText = 'El teléfono debe tener al menos 7 dígitos.';
        errorBanner.classList.remove('d-none');
        errorBanner.classList.add('d-flex');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errorMessage.innerText = 'Por favor, introduzca una dirección de correo válida.';
        errorBanner.classList.remove('d-none');
        errorBanner.classList.add('d-flex');
        return;
    }
    if (!consent) {
        errorMessage.innerText = 'Debe aceptar el tratamiento de sus datos personales.';
        errorBanner.classList.remove('d-none');
        errorBanner.classList.add('d-flex');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = 'Enviando...';

    try {
        var response = await fetch('/api/amcorp-lead.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                empresa: empresa,
                direccion: direccion,
                nombre: nombre,
                telefono: telefono,
                email: email,
                tipo_vehiculo: tipoVehiculo,
                fecha_alquiler: fechaAlquiler,
                primera_vez: primeraVez,
                consent: consent
            })
        });
        var data = await response.json();
        if (response.ok && (data.status === 'success' || data.status === 'partial')) {
            successMessage.innerText = data.message || '¡Gracias! Nos pondremos en contacto en menos de 24 horas.';
            successBanner.classList.remove('d-none');
            successBanner.classList.add('d-flex');
            form.reset();
            var crm = data.crm && data.crm.data ? data.crm.data : (data.crm || {});
            if (window.dataLayer && crm.deal_id) {
                window.dataLayer.push({
                    event: 'amcorp_lead_submitted',
                    deal_id: crm.deal_id,
                    org_source: crm.org_source
                });
            }
        } else {
            var errText = data.message || 'Error al enviar el mensaje.';
            if (data.errores && data.errores.length) {
                errText = data.errores.join(' · ');
            }
            errorMessage.innerText = errText;
            errorBanner.classList.remove('d-none');
            errorBanner.classList.add('d-flex');
        }
    } catch (err) {
        console.error(err);
        errorMessage.innerText = 'Error de conexión. Verifique su internet e intente de nuevo.';
        errorBanner.classList.remove('d-none');
        errorBanner.classList.add('d-flex');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Solicitar cotización';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
