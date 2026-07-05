<?php
/**
 * Automarket - Sostenibilidad Homepage (CMS: global.sostenibilidad_page + fallback defaults)
 */
$activeUnit = 'rentacar';
$skipUnitBusinessSchema = true;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/sostenibilidad-public-copy.php';

$contentService = new ContentService();
$globalData = $contentService->get('global', []);
if (!is_array($globalData)) {
    $globalData = [];
}
$sostPage = sostenibilidad_page_copy($globalData);

$seoOverride = [
    'title'       => (string) ($sostPage['seo_title'] ?? 'Sostenibilidad | Automarket'),
    'description' => (string) ($sostPage['meta_description'] ?? ''),
    'robots'      => ($sostPage['active'] ?? true) !== false ? 'index,follow' : 'noindex,nofollow',
];

require_once __DIR__ . '/../includes/header.php';

$heroImageUrl = trim((string) ($sostPage['hero_image_url'] ?? ''));
if ($heroImageUrl === '') {
    $heroImageUrl = sostenibilidad_page_defaults()['hero_image_url'];
}
$heroTitleLines = preg_split('/\r\n|\r|\n/', (string) ($sostPage['hero_title'] ?? '')) ?: [];
$heroTitleLines = array_values(array_filter(array_map('trim', $heroTitleLines), static fn ($line) => $line !== ''));
if ($heroTitleLines === []) {
    $heroTitleLines = preg_split('/\r\n|\r|\n/', sostenibilidad_page_defaults()['hero_title']) ?: ['Impulsando una', 'movilidad limpia'];
}
$impactBlocks = is_array($sostPage['impact_blocks'] ?? null) ? $sostPage['impact_blocks'] : sostenibilidad_page_defaults()['impact_blocks'];
$contactBullets = is_array($sostPage['contact_bullets'] ?? null) ? $sostPage['contact_bullets'] : sostenibilidad_page_defaults()['contact_bullets'];
$bodyHtml = trim((string) ($sostPage['body_html'] ?? ''));
?>

<!-- Hero Section -->
<section class="hero-wrapper text-center text-lg-start d-flex align-items-center" style="background: url('<?php echo esc($heroImageUrl); ?>') no-repeat center center; background-size: cover;" id="cta-hero">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white" style="text-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                <h1 class="display-3 fw-bold mb-3 font-montserrat leading-tight">
                    <?php
                    $heroLineCount = count($heroTitleLines);
                    foreach ($heroTitleLines as $hi => $heroLine):
                        if ($hi > 0): ?><br><?php endif;
                        echo esc($heroLine);
                    endforeach;
                    ?>
                </h1>
                <p class="fs-4 mb-4 opacity-90 font-poppins">
                    <?php echo esc($sostPage['hero_subtitle'] ?? ''); ?>
                </p>
                <a href="#programa" class="btn btn-theme btn-lg px-5 py-3 rounded-pill fw-bold text-uppercase shadow-lg fs-5">
                    <?php echo esc($sostPage['hero_cta_label'] ?? 'Conocer Programas'); ?> <i class="bi bi-chevron-down ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Impact Metrics -->
<section class="container py-5" id="programa">
    <div class="text-center mb-5">
        <span class="badge px-3 py-2 bg-success-subtle text-success rounded-pill fw-bold text-uppercase tracking-wider mb-2"><?php echo esc($sostPage['section_badge'] ?? ''); ?></span>
        <h2 class="display-5 fw-bold text-navy font-montserrat"><?php echo esc($sostPage['section_title'] ?? ''); ?></h2>
        <p class="text-muted"><?php echo esc($sostPage['section_subtitle'] ?? ''); ?></p>
    </div>

    <div class="row g-4 text-center">
        <?php foreach ($impactBlocks as $block): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <i class="bi <?php echo esc(trim((string) ($block['icon'] ?? 'bi-leaf-fill'))); ?> text-success fs-1 mb-3"></i>
                <h4 class="fw-bold text-navy"><?php echo esc($block['title'] ?? ''); ?></h4>
                <p class="text-muted font-poppins text-sm mb-0"><?php echo esc($block['text'] ?? ''); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($bodyHtml !== ''): ?>
<section class="container py-4">
    <div class="sostenibilidad-body-html font-poppins">
        <?php echo $bodyHtml; ?>
    </div>
</section>
<?php endif; ?>

<!-- Green contact form -->
<section class="bg-white py-5 border-top border-bottom" id="impacto">
    <div class="container">
        <div class="row align-items-center justify-content-between g-5">
            <div class="col-lg-5">
                <h2 class="fw-bold text-navy display-6 font-montserrat"><?php echo esc($sostPage['contact_title'] ?? ''); ?></h2>
                <p class="text-muted mb-4 font-poppins">
                    <?php echo esc($sostPage['contact_intro'] ?? ''); ?>
                </p>
                <?php foreach ($contactBullets as $bi => $bullet): ?>
                <div class="d-flex align-items-center gap-2<?php echo $bi < count($contactBullets) - 1 ? ' mb-3' : ''; ?>">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <span class="fw-semibold"><?php echo esc($bullet); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow p-4 rounded-4 bg-light">
                    <h4 class="fw-bold text-navy mb-4 font-montserrat"><?php echo esc($sostPage['form_title'] ?? ''); ?></h4>
                    <form id="ecoForm" onsubmit="submitEcoLead(event)">
                        <div class="mb-3">
                            <label for="ecoName" class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" id="ecoName" class="form-control form-control-premium" placeholder="Ingresa tu nombre" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="ecoEmail" class="form-label fw-semibold">Correo Electrónico</label>
                                <input type="email" id="ecoEmail" class="form-control form-control-premium" placeholder="nombre@correo.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ecoPhone" class="form-label fw-semibold">Teléfono</label>
                                <input type="tel" id="ecoPhone" class="form-control form-control-premium" placeholder="Ej: 6600-0000" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="ecoMsg" class="form-label fw-semibold">¿Cómo te gustaría colaborar?</label>
                            <textarea id="ecoMsg" class="form-control form-control-premium" rows="3" placeholder="Quiero cotizar flota eléctrica / participar de reforestaciones..." required></textarea>
                        </div>
                        <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                        <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white shadow-sm">
                            <i class="bi bi-leaf-fill me-2"></i>ENVIAR MENSAJE VERDE
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function submitEcoLead(e) {
    e.preventDefault();
    const payload = {
        name: document.getElementById('ecoName').value,
        phone: document.getElementById('ecoPhone').value,
        email: document.getElementById('ecoEmail').value,
        message: "Interés Sostenibilidad. Mensaje: " + document.getElementById('ecoMsg').value,
        unit: "Sostenibilidad"
    };

    fetch('/api/contacto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        alert("¡Gracias por tu interés ecológico! Un coordinador del programa se contactará contigo. ID de registro: ECO-" + Math.floor(Math.random() * 10000));
        document.getElementById('ecoForm').reset();
    })
    .catch(err => {
        console.error(err);
        alert("Ocurrió un error al enviar el formulario.");
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
