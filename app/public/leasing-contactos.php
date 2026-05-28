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
                    <input type="hidden" id="contact_unit" name="unit" value="Leasing Operativo">

                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <label for="first_name" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Su Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-control py-3 bg-white border-0" placeholder="Su nombre" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="last_name" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Su Apellido <span class="text-danger">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-control py-3 bg-white border-0" placeholder="Su Apellido" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="email" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">E-mail <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control py-3 bg-white border-0" placeholder="E-mail" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="phone" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Su teléfono</label>
                            <input type="text" id="phone" name="phone" class="form-control py-3 bg-white border-0" placeholder="xxx-xxxx">
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label text-navy font-poppins fw-semibold" style="font-size: 0.83rem;">Comentarios <span class="text-danger">*</span></label>
                            <textarea id="message" name="message" class="form-control bg-white border-0 py-3" rows="5" placeholder="Comentarios" required></textarea>
                        </div>
                        <div class="col-12 text-center mt-2">
                            <button type="submit" id="submitBtn" class="btn btn-theme px-5 py-3 fw-bold text-uppercase rounded-3 font-montserrat shadow-sm" style="min-width: 180px;">
                                Enviar
                            </button>
                        </div>
                    </div>
                </form>

                <div id="successBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white" style="background-color: #2b2b2b;">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">
                        <i class="bi bi-check-lg text-white"></i>
                    </div>
                    <span class="fw-semibold font-poppins">¡Operación exitosa! Mensaje enviado correctamente.</span>
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
    var errorBanner = document.getElementById('errorBanner');
    var errorMessage = document.getElementById('errorMessage');

    successBanner.classList.add('d-none');
    successBanner.classList.remove('d-flex');
    errorBanner.classList.add('d-none');
    errorBanner.classList.remove('d-flex');

    var firstName = document.getElementById('first_name').value.trim();
    var lastName = document.getElementById('last_name').value.trim();
    var email = document.getElementById('email').value.trim();
    var phone = document.getElementById('phone').value.trim();
    var message = document.getElementById('message').value.trim();

    if (!firstName || !lastName || !email || !message) {
        errorMessage.innerText = 'Por favor, llene todos los campos obligatorios.';
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

    submitBtn.disabled = true;
    submitBtn.innerText = 'Enviando...';

    try {
        var response = await fetch('/api/contacto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                first_name: firstName,
                last_name: lastName,
                email: email,
                phone: phone,
                message: message,
                unit: 'Leasing Operativo'
            })
        });
        var data = await response.json();
        if (response.ok && data.status === 'success') {
            successBanner.classList.remove('d-none');
            successBanner.classList.add('d-flex');
            form.reset();
        } else {
            errorMessage.innerText = data.message || 'Error al enviar el mensaje.';
            errorBanner.classList.remove('d-none');
            errorBanner.classList.add('d-flex');
        }
    } catch (err) {
        console.error(err);
        errorMessage.innerText = 'Error de red. Inténtelo más tarde.';
        errorBanner.classList.remove('d-none');
        errorBanner.classList.add('d-flex');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Enviar';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
