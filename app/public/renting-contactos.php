<?php
/**
 * Automarket - Renting — Contactos
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';

$renting = $contentService->get('renting', []);
$contact = $renting['contact'] ?? [];

$pageTitle = $contact['page_title'] ?? 'Contactos';
$introText = $contact['intro_text'] ?? 'Gracias por escribirnos. Tus comentarios son muy importantes para nosotros; completa el formulario y pronto te responderemos.';
$contactImageUrl = $contact['contact_image_url'] ?? '';
?>

<style>
.renting-contact-form-panel {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}
.renting-contact-form-panel .form-control {
    border-radius: 4px;
    border-color: #d1d5db;
}
.renting-contact-form-panel .form-control:focus {
    border-color: var(--theme-primary);
    box-shadow: 0 0 0 3px rgba(var(--theme-primary-rgb), 0.12);
}
.renting-contact-side-image {
    border-radius: 12px;
    overflow: hidden;
    height: 100%;
    min-height: 420px;
    box-shadow: 0 8px 24px rgba(8, 16, 38, 0.08);
}
.renting-contact-side-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
@media (max-width: 991.98px) {
    .renting-contact-side-image {
        min-height: 280px;
    }
}
</style>

<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item">
                    <a href="/renting.php" class="text-decoration-none fw-semibold" style="color: var(--theme-primary);">Renting</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc($pageTitle); ?></li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.3rem; letter-spacing: -0.5px;"><?php echo esc($pageTitle); ?></h1>
        <?php if (!empty($introText)): ?>
            <p class="text-muted font-poppins mt-2 mb-0"><?php echo esc($introText); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="container py-5 mb-5">
    <div class="row g-4 renting-quote-layout align-items-stretch">
        <div class="col-lg-7 col-12">
            <div class="renting-contact-form-panel p-4 p-md-5 shadow-sm h-100">
                <form id="rentingContactForm" novalidate>
                    <input type="hidden" name="unit" value="Renting">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="rc_first_name" class="form-label fw-semibold text-navy small">Su Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="rc_first_name" class="form-control py-3" placeholder="Su nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rc_last_name" class="form-label fw-semibold text-navy small">Su Apellido <span class="text-danger">*</span></label>
                            <input type="text" id="rc_last_name" class="form-control py-3" placeholder="Su apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rc_email" class="form-label fw-semibold text-navy small">E-mail <span class="text-danger">*</span></label>
                            <input type="email" id="rc_email" class="form-control py-3" placeholder="correo@ejemplo.com" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rc_phone" class="form-label fw-semibold text-navy small">Teléfono</label>
                            <input type="text" id="rc_phone" class="form-control py-3" placeholder="xxx-xxxx">
                        </div>
                        <div class="col-12">
                            <label for="rc_message" class="form-label fw-semibold text-navy small">Comentarios <span class="text-danger">*</span></label>
                            <textarea id="rc_message" class="form-control py-3" rows="5" placeholder="Escriba su mensaje" required></textarea>
                        </div>
                        <div class="col-12 text-center mt-2">
                            <button type="submit" id="rc_submit" class="btn w-100 py-3 fw-bold text-white text-uppercase font-montserrat" style="background:#0b1f6b; border-radius:4px; max-width: 280px;">
                                Enviar
                            </button>
                        </div>
                    </div>
                </form>

                <div id="rc_success" class="mt-4 p-3 rounded d-none align-items-center gap-2 text-white" style="background:#2b2b2b;">
                    <span class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;"><i class="bi bi-check-lg"></i></span>
                    <span class="fw-semibold font-poppins">¡Operación exitosa! Mensaje enviado correctamente.</span>
                </div>
                <div id="rc_error" class="mt-4 p-3 rounded d-none bg-danger text-white small"></div>
            </div>
        </div>

        <?php if (!empty($contactImageUrl)): ?>
        <div class="col-lg-5 col-12">
            <div class="renting-contact-side-image">
                <img src="<?php echo esc($contactImageUrl); ?>" alt="Contacto Automarket Renting" loading="lazy">
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rentingContactForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submit = document.getElementById('rc_submit');
        const ok = document.getElementById('rc_success');
        const err = document.getElementById('rc_error');
        ok.classList.add('d-none');
        ok.classList.remove('d-flex');
        err.classList.add('d-none');

        const firstName = document.getElementById('rc_first_name').value.trim();
        const lastName = document.getElementById('rc_last_name').value.trim();
        const email = document.getElementById('rc_email').value.trim();
        const phone = document.getElementById('rc_phone').value.trim();
        const message = document.getElementById('rc_message').value.trim();

        if (!firstName || !lastName || !email || !message) {
            err.textContent = 'Complete todos los campos obligatorios.';
            err.classList.remove('d-none');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            err.textContent = 'Introduzca un correo electrónico válido.';
            err.classList.remove('d-none');
            return;
        }

        submit.disabled = true;
        submit.textContent = 'ENVIANDO...';
        try {
            const res = await fetch('/api/contacto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    phone: phone,
                    message: message,
                    unit: 'Renting'
                })
            });
            const data = await res.json();
            if (res.ok && data.status === 'success') {
                ok.classList.remove('d-none');
                ok.classList.add('d-flex');
                form.reset();
            } else {
                err.textContent = data.message || 'Error al enviar el mensaje.';
                err.classList.remove('d-none');
            }
        } catch (x) {
            err.textContent = 'Error de red. Intente más tarde.';
            err.classList.remove('d-none');
        } finally {
            submit.disabled = false;
            submit.textContent = 'Enviar';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
