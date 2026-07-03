<?php
/**
 * Automarket - Renting — Contactos
 */
$activeUnit = 'renting';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/unit-footer-prepare.php';

$renting = $contentService->get('renting', []);
$contactRaw = $renting['contact'] ?? [];
$contact = am_unit_contact_with_footer_fallback($contactRaw, $renting);
$siteData = $contentService->getAll();
$rentingContactResolved = am_unit_contact_resolved_for_display($contact, $siteData['global'] ?? []);

$pageTitle = $contactRaw['page_title'] ?? 'Contactos';
$introText = $contactRaw['intro_text'] ?? 'Gracias por escribirnos. Tus comentarios son muy importantes para nosotros; completa el formulario y pronto te responderemos.';
$contactImageUrl = $contactRaw['contact_image_url'] ?? '';
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
.renting-contact-sidebar {
    position: sticky;
    top: 100px;
    z-index: 10;
}
.renting-contact-phone-link:hover {
    color: var(--theme-primary) !important;
}
@media (max-width: 991.98px) {
    .renting-contact-side-image {
        min-height: 280px;
    }
    .renting-contact-sidebar {
        position: static;
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
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="rc_nombre" class="form-label fw-semibold text-navy small">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" id="rc_nombre" class="form-control py-3" placeholder="Nombre y apellido" required minlength="3" maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label for="rc_email" class="form-label fw-semibold text-navy small">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" id="rc_email" class="form-control py-3" placeholder="correo@ejemplo.com" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rc_phone" class="form-label fw-semibold text-navy small">Número telefónico <span class="text-danger">*</span></label>
                            <input type="tel" id="rc_phone" class="form-control py-3" placeholder="+507 6123-4567" pattern="[0-9+\-() ]+" required>
                        </div>
                        <div class="col-12">
                            <label for="rc_auto_interes" class="form-label fw-semibold text-navy small">Auto de tu interés <span class="text-danger">*</span></label>
                            <input type="text" id="rc_auto_interes" class="form-control py-3" placeholder="Ej: Toyota Hilux 2026" required maxlength="100">
                        </div>
                        <div class="col-12">
                            <label for="rc_rango_ingresos" class="form-label fw-semibold text-navy small">Rango de ingresos</label>
                            <select id="rc_rango_ingresos" class="form-select py-3">
                                <option value="">Selecciona...</option>
                                <option value="650 a 2499">B/. 650 a 2,499</option>
                                <option value="2500 a 4000">B/. 2,500 a 4,000</option>
                                <option value="más de $4000">Más de B/. 4,000</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rc_consent" value="1" required>
                                <label class="form-check-label font-poppins text-muted small" for="rc_consent">
                                    He leído y acepto la <a href="/pagina-institucional.php?p=privacidad" class="text-danger" target="_blank" rel="noopener">Política de Privacidad</a>.
                                    Autorizo el tratamiento de mis datos para ser contactado por un asesor comercial.
                                </label>
                            </div>
                        </div>
                        <div class="col-12 text-center mt-2">
                            <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                            <button type="submit" id="rc_submit" class="btn w-100 py-3 fw-bold text-white text-uppercase font-montserrat" style="background:#0b1f6b; border-radius:4px; max-width: 280px;">
                                Enviar solicitud
                            </button>
                        </div>
                    </div>
                </form>

                <div id="rc_success" class="mt-4 p-3 rounded d-none align-items-center gap-2 text-white" style="background:#2b2b2b;">
                    <span class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;"><i class="bi bi-check-lg"></i></span>
                    <span id="rc_success_msg" class="fw-semibold font-poppins">¡Gracias! Pronto te contactaremos.</span>
                </div>
                <div id="rc_error" class="mt-4 p-3 rounded d-none bg-danger text-white small"></div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="renting-contact-sidebar">
                <?php
                $_uccResolved = $rentingContactResolved;
                $_uccLinkClass = 'renting-contact-phone-link text-navy font-poppins fs-5 text-decoration-none fw-semibold d-flex align-items-center gap-2';
                require __DIR__ . '/../includes/unit-contact-cards.php';
                ?>
                <?php if (!empty($contactImageUrl)): ?>
                <div class="renting-contact-side-image mt-4">
                    <img src="<?php echo esc($contactImageUrl); ?>" alt="Contacto Automarket Renting" loading="lazy">
                </div>
                <?php endif; ?>
            </div>
        </div>
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

        const nombre = document.getElementById('rc_nombre').value.trim();
        const email = document.getElementById('rc_email').value.trim().toLowerCase();
        const telefono = document.getElementById('rc_phone').value.trim();
        const autoInteres = document.getElementById('rc_auto_interes').value.trim();
        const rangoIngresos = document.getElementById('rc_rango_ingresos').value;
        const consent = document.getElementById('rc_consent').checked;
        const okMsg = document.getElementById('rc_success_msg');

        if (!nombre || !email || !telefono || !autoInteres) {
            err.textContent = 'Complete todos los campos obligatorios.';
            err.classList.remove('d-none');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            err.textContent = 'Introduzca un correo electrónico válido.';
            err.classList.remove('d-none');
            return;
        }
        if (!consent) {
            err.textContent = 'Debe aceptar el tratamiento de sus datos personales.';
            err.classList.remove('d-none');
            return;
        }

        submit.disabled = true;
        submit.textContent = 'ENVIANDO...';
        try {
            const res = await fetch('/api/renting-lead.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    nombre: nombre,
                    email: email,
                    telefono: telefono,
                    auto_interes: autoInteres,
                    rango_ingresos: rangoIngresos,
                    consent: consent
                })
            });
            const data = await res.json();
            if (res.ok && (data.status === 'success' || data.status === 'partial')) {
                okMsg.textContent = data.message || '¡Gracias! Pronto te contactaremos.';
                ok.classList.remove('d-none');
                ok.classList.add('d-flex');
                form.reset();
                if (window.dataLayer && data.crm && data.crm.deal_id) {
                    window.dataLayer.push({
                        event: 'renting_lead_submitted',
                        deal_id: data.crm.deal_id,
                        person_source: data.crm.person_source
                    });
                }
            } else {
                err.textContent = data.message || data.details || 'Error al enviar el mensaje.';
                err.classList.remove('d-none');
            }
        } catch (x) {
            err.textContent = 'Error de conexión. Verifique su internet e intente de nuevo.';
            err.classList.remove('d-none');
        } finally {
            submit.disabled = false;
            submit.textContent = 'Enviar solicitud';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
