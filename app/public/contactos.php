<?php
/**
 * Automarket - Contactos (Dynamic Contacts Page)
 */
$activeUnit = $_GET['unit'] ?? 'rentacar';
if (!in_array($activeUnit, ['rentacar', 'seminuevos', 'leasing', 'renting', 'taller', 'sostenibilidad'])) {
    $activeUnit = 'rentacar';
}

require_once __DIR__ . '/../includes/header.php';

$contactImageUrl = $contentService->get('homepage.contact_image_url', '/assets/img/sucursales-rac.webp');
$tallerContact = $contentService->get('taller.contact', []);

$unitHomeLinks = [
    'rentacar'       => ['url' => '/rent-a-car.php',     'label' => 'Rent A Car'],
    'seminuevos'     => ['url' => '/venta-autos.php',    'label' => 'Venta de Autos'],
    'leasing'        => ['url' => '/leasing.php',        'label' => 'Leasing'],
    'renting'        => ['url' => '/renting.php',        'label' => 'Renting'],
    'taller'         => ['url' => '/taller.php',         'label' => 'Taller'],
    'sostenibilidad' => ['url' => '/sostenibilidad.php', 'label' => 'Sostenibilidad']
];
$currentUnitHome = $unitHomeLinks[$activeUnit] ?? ['url' => '/rent-a-car.php', 'label' => 'Rent A Car'];

// Load Seminuevos sucursales
$siteData       = $contentService->getAll();
$semiSucursales = $siteData['seminuevos']['sucursales'] ?? [];
usort($semiSucursales, function($a, $b) {
    return intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99);
});
$activeSucursales = array_values(array_filter($semiSucursales, function ($s) {
    if (($s['active'] ?? true) === false) {
        return false;
    }
    $name = mb_strtolower(trim($s['name'] ?? ''), 'UTF-8');
    // No ofrecer Penonomé en el formulario de contacto Seminuevos
    if (preg_match('/penonom/u', $name)) {
        return false;
    }
    return true;
}));
?>

<?php if ($activeUnit === 'seminuevos'): ?>
<!-- ============================================================
     SEMINUEVOS CONTACT PAGE
     ============================================================ -->

<style>
.sn-breadcrumb-strip { background: #f8f9fc; border-bottom: 1px solid #eaecf3; }
.sn-page-title { font-size: 2.2rem; letter-spacing: -0.5px; }

/* Form panel */
.sn-form-panel { background: #ededed; border: 1px solid #e0e0e0; border-radius: 16px; }
.sn-form-panel .form-control,
.sn-form-panel .form-select {
    border-radius: 8px; border: 1px solid #d1d5db;
    font-family: 'Poppins', sans-serif; font-size: .88rem;
    transition: border-color .2s, box-shadow .2s;
}
.sn-form-panel .form-control:focus,
.sn-form-panel .form-select:focus { border-color: #c51f17; box-shadow: 0 0 0 3px rgba(197,31,23,.12); }
.sn-submit-btn {
    background: #c51f17; border: none; color: #fff; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase; padding: 13px 0;
    border-radius: 8px; width: 100%; font-size: .9rem;
    font-family: 'Montserrat', sans-serif;
    transition: background .2s, transform .15s, box-shadow .2s;
}
.sn-submit-btn:hover { background: #a81812; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(197,31,23,.3); }

/* Right sidebar */
.sn-sidebar-sticky { position: sticky; top: 100px; }
.sn-cta-box {
    background: #0d1f5e; border-radius: 16px 16px 0 0;
    padding: 38px 30px; text-align: center; color: #fff;
}
.sn-cta-box h3 {
    font-size: 1.35rem; font-weight: 900; font-family: 'Montserrat', sans-serif;
    line-height: 1.25; margin-bottom: 12px;
}
.sn-cta-box p { font-family: 'Poppins', sans-serif; font-size: .9rem; opacity: .85; margin-bottom: 22px; }
.sn-cta-btn-outline {
    display: inline-block; border: 2px solid #fff; color: #c51f17;
    background: #fff; font-family: 'Montserrat', sans-serif; font-weight: 800;
    font-size: .82rem; letter-spacing: 1.5px; text-transform: uppercase;
    padding: 11px 30px; border-radius: 6px; text-decoration: none;
    transition: all .22s ease;
}
.sn-cta-btn-outline:hover { background: #c51f17; border-color: #c51f17; color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(197,31,23,.4); }
.sn-sidebar-img { border-radius: 0 0 16px 16px; overflow: hidden; line-height: 0; }
.sn-sidebar-img img { width: 100%; display: block; object-fit: cover; max-height: 340px; }

@media (max-width: 991px) { .sn-sidebar-sticky { position: static; } }
</style>

<!-- BREADCRUMB + TITLE -->
<section class="sn-breadcrumb-strip py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins" style="font-size:.84rem;">
                <li class="breadcrumb-item"><a href="/venta-autos.php" class="text-danger text-decoration-none fw-semibold">Venta de Autos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contacto</li>
            </ol>
        </nav>
        <h1 class="sn-page-title fw-bold text-navy font-montserrat mb-1">Contacto</h1>
        <p class="text-muted font-poppins mb-0" style="font-size:.9rem;">
            Gracias por escribirnos. Por favor llena el formulario y pronto te responderemos.
        </p>
    </div>
</section>

<!-- FORM + SIDEBAR -->
<section class="container py-5">
    <div class="row g-4">

        <!-- LEFT: Contact Form -->
        <div class="col-lg-7 col-12">
            <div class="sn-form-panel p-4 p-md-5">
                <form id="contactForm" onsubmit="handleContactSubmit(event)" novalidate>
                    <input type="hidden" id="contact_unit" name="unit" value="Seminuevos">

                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <label for="first_name" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">Su Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-control py-3 bg-white" placeholder="Su nombre" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="last_name" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">Su Apellido <span class="text-danger">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-control py-3 bg-white" placeholder="Su Apellido" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="email" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">E-mail <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control py-3 bg-white" placeholder="E-mail" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="phone" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">Su Tel&eacute;fono <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone" class="form-control py-3 bg-white" placeholder="xxx-xxxx" required>
                        </div>

                        <div class="col-12">
                            <label for="auto_interes" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">Auto de tu inter&eacute;s <span class="text-danger">*</span></label>
                            <input type="text" id="auto_interes" name="auto_interes" class="form-control py-3 bg-white" placeholder="Ej: Toyota Yaris 2022" required minlength="3">
                        </div>

                        <div class="col-md-6 col-12">
                            <label for="provincia" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">Provincia</label>
                            <select id="provincia" name="provincia" class="form-control form-select py-3 bg-white">
                                <option value="">Selecciona...</option>
                                <option>Bocas Del Toro</option>
                                <option>Coclé</option>
                                <option>Colón</option>
                                <option>Chiriquí</option>
                                <option>Darien</option>
                                <option>Herrera</option>
                                <option>Los Santos</option>
                                <option>Panamá</option>
                                <option>Veraguas</option>
                                <option>Panamá Oeste (La Chorrera)</option>
                            </select>
                        </div>

                        <?php if (!empty($activeSucursales)): ?>
                        <div class="col-md-6 col-12">
                            <label for="contact_branch" class="form-label text-navy font-poppins fw-semibold" style="font-size:.83rem;">Sucursal de inter&eacute;s</label>
                            <select id="contact_branch" name="branch" class="form-control form-select py-3 bg-white">
                                <option value="">&mdash; Seleccione una sucursal &mdash;</option>
                                <?php foreach ($activeSucursales as $suc): ?>
                                    <option value="<?php echo esc($suc['name']); ?>"><?php echo esc($suc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" required>
                                <label class="form-check-label font-poppins text-muted" style="font-size:.82rem;" for="consent">
                                    Autorizo el tratamiento de mis datos personales conforme a la
                                    <a href="/pagina-institucional.php?p=privacidad" class="text-danger" target="_blank" rel="noopener">Pol&iacute;tica de Privacidad</a> de Automarket.
                                </label>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                            <button type="submit" id="submitBtn" class="sn-submit-btn">Enviar</button>
                        </div>
                    </div>
                </form>

                <div id="successBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white" style="background:#2b2b2b;">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;">
                        <i class="bi bi-check-lg text-white"></i>
                    </div>
                    <span id="successMessage" class="fw-semibold font-poppins">&iexcl;Gracias! Un asesor te contactar&aacute; pronto.</span>
                </div>
                <div id="errorBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white bg-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="errorMessage" class="fw-semibold font-poppins">Ocurri&oacute; un error al enviar el mensaje.</span>
                </div>
            </div>
        </div>

        <!-- RIGHT: CTA box + Image stacked -->
        <div class="col-lg-5 col-12 d-none d-lg-block">
            <div class="sn-sidebar-sticky">
                <!-- Top: Dark navy CTA box -->
                <div class="sn-cta-box">
                    <h3>&iexcl;Compra tu seminuevo!</h3>
                    <p>Un seminuevo es la mejor forma de estrenar sin pagar de m&aacute;s</p>
                    <a href="/inventario.php" class="sn-cta-btn-outline">Cotiza tu Veh&iacute;culo</a>
                </div>
                <!-- Bottom: Image -->
                <div class="sn-sidebar-img">
                    <img src="/assets/img/contactos-sn.webp" alt="Automarket Seminuevos" loading="lazy">
                </div>
            </div>
        </div>

    </div>
</section>

<script>
async function handleContactSubmit(event) {
    event.preventDefault();
    var form          = document.getElementById('contactForm');
    var submitBtn     = document.getElementById('submitBtn');
    var successBanner  = document.getElementById('successBanner');
    var successMessage = document.getElementById('successMessage');
    var errorBanner    = document.getElementById('errorBanner');
    var errorMessage   = document.getElementById('errorMessage');

    successBanner.classList.add('d-none');
    successBanner.classList.remove('d-flex');
    errorBanner.classList.add('d-none');
    errorBanner.classList.remove('d-flex');

    var firstName   = document.getElementById('first_name').value.trim();
    var lastName    = document.getElementById('last_name').value.trim();
    var email       = document.getElementById('email').value.trim();
    var phone       = document.getElementById('phone').value.trim();
    var autoInteres = document.getElementById('auto_interes').value.trim();
    var provincia   = document.getElementById('provincia').value;
    var consent     = document.getElementById('consent').checked;
    var branchEl    = document.getElementById('contact_branch');
    var branch      = branchEl ? branchEl.value : '';

    if (!firstName || !lastName || !email || !phone || !autoInteres) {
        errorMessage.innerText = 'Por favor, llene todos los campos obligatorios.';
        errorBanner.classList.remove('d-none'); errorBanner.classList.add('d-flex'); return;
    }
    if (autoInteres.length < 3) {
        errorMessage.innerText = 'Indique el auto de su inter\u00e9s (m\u00ednimo 3 caracteres).';
        errorBanner.classList.remove('d-none'); errorBanner.classList.add('d-flex'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errorMessage.innerText = 'Por favor, introduzca una direcci\u00f3n de correo v\u00e1lida.';
        errorBanner.classList.remove('d-none'); errorBanner.classList.add('d-flex'); return;
    }
    if (!consent) {
        errorMessage.innerText = 'Debe aceptar el tratamiento de sus datos personales.';
        errorBanner.classList.remove('d-none'); errorBanner.classList.add('d-flex'); return;
    }
    submitBtn.disabled = true; submitBtn.innerText = 'Enviando...';
    try {
        var response = await fetch('/api/seminuevos-lead.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                first_name: firstName,
                last_name: lastName,
                email: email,
                phone: phone,
                auto_interes: autoInteres,
                provincia: provincia,
                branch: branch,
                consent: consent
            })
        });
        var data = await response.json();
        if (response.ok && (data.status === 'success' || data.status === 'partial')) {
            successMessage.innerText = data.message || '\u00a1Gracias! Un asesor te contactar\u00e1 pronto.';
            successBanner.classList.remove('d-none'); successBanner.classList.add('d-flex'); form.reset();
            if (window.dataLayer && data.crm && data.crm.deal_id) {
                window.dataLayer.push({
                    event: 'lead_seminuevos',
                    deal_id: data.crm.deal_id,
                    person_source: data.crm.person_source
                });
            }
        } else {
            errorMessage.innerText = data.message || data.crm_error || 'Error al enviar el mensaje.';
            errorBanner.classList.remove('d-none'); errorBanner.classList.add('d-flex');
        }
    } catch (err) {
        console.error(err);
        errorMessage.innerText = 'Error de red. Int\u00e9ntelo m\u00e1s tarde.';
        errorBanner.classList.remove('d-none'); errorBanner.classList.add('d-flex');
    } finally {
        submitBtn.disabled = false; submitBtn.innerText = 'Enviar';
    }
}
</script>

<?php else: ?>
<!-- ============================================================
     GENERIC CONTACT PAGE (rent-a-car, leasing, renting, etc.)
     ============================================================ -->

<!-- Breadcrumb and Title -->
<section class="py-5" style="background-color: #f8f9fc;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 font-poppins">
                <li class="breadcrumb-item"><a href="<?php echo esc($currentUnitHome['url']); ?>" class="text-danger text-decoration-none fw-semibold"><?php echo esc($currentUnitHome['label']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc(t('contact.title')); ?></li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold text-navy font-montserrat mb-0" style="font-size: 2.30rem; letter-spacing: -0.5px;"><?php echo esc(($activeUnit === 'taller' && !empty($tallerContact['title'] ?? '')) ? $tallerContact['title'] : t('contact.title')); ?></h1>
        <p class="text-muted font-poppins mt-2 mb-0">
            <?php
            echo esc(($activeUnit === 'taller' && !empty($tallerContact['intro'] ?? '')) ? $tallerContact['intro'] : t('contact.intro'));
            ?>
        </p>
    </div>
</section>

<!-- Contact Form & Info -->
<section class="container py-5 mb-5">
    <div class="row g-5">
        <div class="col-lg-7 col-12">
            <div class="p-4 p-md-5 rounded-4 shadow-sm" style="background-color: #ededed; border: 1px solid #e0e0e0;">
                <form id="contactForm" onsubmit="handleContactSubmit(event)" novalidate>
                    <input type="hidden" id="contact_unit" name="unit" value="<?php echo esc($activeUnit === 'rentacar' ? 'Rent A Car' : ucfirst($activeUnit)); ?>">
                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <label for="first_name" class="form-label text-navy font-poppins fw-semibold size-xs mb-1"><?php echo esc(t('contact.first_name')); ?></label>
                            <input type="text" id="first_name" name="first_name" class="form-control form-control-premium bg-white border-0 py-3" placeholder="<?php echo esc(t('contact.first_name')); ?>" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="last_name" class="form-label text-navy font-poppins fw-semibold size-xs mb-1"><?php echo esc(t('contact.last_name')); ?></label>
                            <input type="text" id="last_name" name="last_name" class="form-control form-control-premium bg-white border-0 py-3" placeholder="<?php echo esc(t('contact.last_name')); ?>" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="email" class="form-label text-navy font-poppins fw-semibold size-xs mb-1"><?php echo esc(t('contact.email')); ?></label>
                            <input type="email" id="email" name="email" class="form-control form-control-premium bg-white border-0 py-3" placeholder="<?php echo esc(t('contact.email')); ?>" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="phone" class="form-label text-navy font-poppins fw-semibold size-xs mb-1"><?php echo esc(t('contact.phone')); ?></label>
                            <input type="text" id="phone" name="phone" class="form-control form-control-premium bg-white border-0 py-3" placeholder="<?php echo esc(t('contact.phone')); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label text-navy font-poppins fw-semibold size-xs mb-1"><?php echo esc(t('contact.message')); ?></label>
                            <textarea id="message" name="message" class="form-control form-control-premium bg-white border-0 py-3" rows="5" placeholder="<?php echo esc(t('contact.message')); ?>" required></textarea>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                            <button type="submit" id="submitBtn" class="btn btn-theme px-5 py-3 fw-bold text-uppercase rounded-3 font-montserrat shadow-sm" style="background-color: #c51f17; border-color: #c51f17; min-width: 180px;">
                                <?php echo esc(t('common.send')); ?>
                            </button>
                        </div>
                    </div>
                </form>
                <div id="successBanner" class="mt-4 p-3 rounded-3 d-none align-items-center justify-content-between text-white" style="background-color: #2b2b2b;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                            <i class="bi bi-check-lg text-white"></i>
                        </div>
                        <span class="fw-semibold font-poppins">&iexcl;Operaci&oacute;n exitosa!</span>
                    </div>
                    <div class="small opacity-75 font-poppins d-none d-md-block" style="font-size: 0.75rem;">Mensaje enviado correctamente</div>
                </div>
                <div id="errorBanner" class="mt-4 p-3 rounded-3 d-none align-items-center gap-2 text-white bg-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="errorMessage" class="fw-semibold font-poppins">Ocurri&oacute; un error al enviar el mensaje.</span>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="ps-lg-4 sticky-widget">
                <div class="contact-info-panel py-4 px-1">
                    <div class="mb-5">
                        <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.1rem; letter-spacing: 0.5px;"><?php echo esc(t('contact.phone_label')); ?></h5>
                        <div class="d-flex flex-column gap-2">
                            <?php
                            $phone1 = ($activeUnit === 'taller') ? ($tallerContact['phone_1'] ?? '(507) 279-2700') : '(507) 279-2700';
                            $phone2 = ($activeUnit === 'taller') ? ($tallerContact['phone_2'] ?? '(507) 6747-0070') : '(507) 6747-0070';
                            ?>
                            <?php if (!empty(trim($phone1))): ?>
                                <a href="tel:<?php echo preg_replace('/\D/', '', $phone1); ?>" class="text-navy font-poppins fs-5 text-decoration-none fw-semibold hover-red d-flex align-items-center gap-2">
                                    <i class="bi bi-telephone-fill text-muted"></i> <?php echo esc($phone1); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty(trim($phone2))): ?>
                                <a href="tel:<?php echo preg_replace('/\D/', '', $phone2); ?>" class="text-navy font-poppins fs-5 text-decoration-none fw-semibold hover-red d-flex align-items-center gap-2">
                                    <i class="bi bi-telephone-fill text-muted"></i> <?php echo esc($phone2); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-5">
                        <h5 class="fw-bold font-montserrat text-navy text-uppercase mb-3" style="font-size: 1.1rem; letter-spacing: 0.5px;"><?php echo esc(t('contact.whatsapp')); ?></h5>
                        <?php
                        $waText = ($activeUnit === 'taller') ? ($tallerContact['whatsapp'] ?? '(507) 6747-0070') : '(507) 6747-0070';
                        $waDigits = preg_replace('/\D/', '', $waText);
                        ?>
                        <a href="https://api.whatsapp.com/send?phone=<?php echo esc($waDigits); ?>" target="_blank" class="btn text-white fw-bold d-inline-flex align-items-center gap-2 px-4 py-2 rounded-3 shadow-sm hover-grow" style="background-color: #25d366; font-family: 'Poppins', sans-serif;">
                            <i class="bi bi-whatsapp fs-5"></i> <?php echo esc($waText); ?>
                        </a>
                    </div>
                    <?php
                    $sideImage = $contactImageUrl;
                    if ($activeUnit === 'taller' && !empty($tallerContact['image_url'] ?? '')) {
                        $sideImage = $tallerContact['image_url'];
                    }
                    ?>
                    <?php if (!empty($sideImage)): ?>
                        <div class="mt-4 rounded-4 overflow-hidden border shadow-sm">
                            <img src="<?php echo esc($sideImage); ?>" alt="Automarket Contacto" class="img-fluid w-100" style="object-fit: cover; max-height: 350px; display: block;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .hover-red:hover { color: #c51f17 !important; }
    .hover-grow { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-grow:hover { transform: scale(1.03); box-shadow: 0 4px 10px rgba(37, 211, 102, 0.3) !important; }
    @media (min-width: 992px) { .sticky-widget { position: sticky; top: 100px; z-index: 10; } }
</style>

<script>
async function handleContactSubmit(event) {
    event.preventDefault();
    var form = document.getElementById('contactForm');
    var submitBtn = document.getElementById('submitBtn');
    var successBanner = document.getElementById('successBanner');
    var errorBanner = document.getElementById('errorBanner');
    var errorMessage = document.getElementById('errorMessage');
    successBanner.classList.add('d-none'); successBanner.classList.remove('d-flex');
    errorBanner.classList.add('d-none');
    var firstName = document.getElementById('first_name').value.trim();
    var lastName = document.getElementById('last_name').value.trim();
    var email = document.getElementById('email').value.trim();
    var phone = document.getElementById('phone').value.trim();
    var message = document.getElementById('message').value.trim();
    if (!firstName || !lastName || !email || !phone || !message) {
        errorMessage.innerText = 'Por favor, llene todos los campos obligatorios.';
        errorBanner.classList.remove('d-none'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errorMessage.innerText = 'Por favor, introduzca una direcci\u00f3n de correo v\u00e1lida.';
        errorBanner.classList.remove('d-none'); return;
    }
    submitBtn.disabled = true; submitBtn.innerText = 'Enviando...';
    try {
        var response = await fetch('/api/contacto.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ first_name: firstName, last_name: lastName, email: email, phone: phone, message: message,
                unit: document.getElementById('contact_unit').value })
        });
        var data = await response.json();
        if (response.ok && data.status === 'success') {
            successBanner.classList.remove('d-none'); successBanner.classList.add('d-flex'); form.reset();
        } else {
            errorMessage.innerText = data.message || 'Error al enviar el mensaje.';
            errorBanner.classList.remove('d-none');
        }
    } catch (err) {
        console.error(err);
        errorMessage.innerText = 'Error de red. Int\u00e9ntelo m\u00e1s tarde.';
        errorBanner.classList.remove('d-none');
    } finally {
        submitBtn.disabled = false; submitBtn.innerText = 'Enviar';
    }
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
