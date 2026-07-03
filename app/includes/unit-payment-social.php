<?php
/**
 * Sección de Medios de Pago y Redes Sociales por unidad de negocio.
 *
 * Uso:
 *   // Opcional: pasar redes propias de la unidad (sobreescribe el fallback global)
 *   $_upsUnitSocialLinks = $unitData['social_links'] ?? [];
 *   $_upsShowPayments = true; // opcional; false oculta Visa/Mastercard
 *   $_upsUnitContact = $unitData['footer_contact'] ?? []; // phone_display, whatsapp_number, email, schedule
 *   require __DIR__ . '/../includes/unit-payment-social.php';
 */

// Mapa icon/label por nombre de red social
$_upsNetMeta = [
    'facebook'  => ['icon' => 'bi-facebook',  'label' => 'Facebook'],
    'instagram' => ['icon' => 'bi-instagram', 'label' => 'Instagram'],
    'linkedin'  => ['icon' => 'bi-linkedin',  'label' => 'LinkedIn'],
    'tiktok'    => ['icon' => 'bi-tiktok',    'label' => 'TikTok'],
    'youtube'   => ['icon' => 'bi-youtube',   'label' => 'YouTube'],
];

// Redes por unidad (si la página las pasó)
$_upsFromUnit = [];
if (!empty($_upsUnitSocialLinks) && is_array($_upsUnitSocialLinks)) {
    if (!class_exists('FooterService')) {
        require_once __DIR__ . '/../services/FooterService.php';
    }
    foreach ($_upsUnitSocialLinks as $_upsNet => $_upsUrl) {
        $_upsUrl = trim((string)$_upsUrl);
        if ($_upsUrl === '' || !isset($_upsNetMeta[$_upsNet])) {
            continue;
        }
        if (!FooterService::isSocialUrlMatchingPlatform((string) $_upsNet, $_upsUrl)) {
            continue;
        }
        $_upsFromUnit[] = [
            'url'   => $_upsUrl,
            'icon'  => $_upsNetMeta[$_upsNet]['icon'],
            'label' => $_upsNetMeta[$_upsNet]['label'],
        ];
    }
}

if (!empty($_upsFromUnit)) {
    $_upsSocial = $_upsFromUnit;
} else {
    // Fallback: redes globales del footer
    if (!class_exists('FooterService')) {
        require_once __DIR__ . '/../services/FooterService.php';
    }
    $_upsSvc    = new FooterService();
    $_upsFooter = $_upsSvc->getFooter();
    $_upsSocial = FooterService::filterRenderableSocial($_upsFooter['social'] ?? []);
    unset($_upsSvc, $_upsFooter);
}

// Medios de pago: iconos de assets (siempre disponibles)
$_upsPayments = [
    ['src' => '/assets/img/visa.png',       'alt' => 'Visa'],
    ['src' => '/assets/img/mastercard.png', 'alt' => 'Mastercard'],
];

$_upsHasSocial   = !empty($_upsSocial);
$_upsShowPayments = ($_upsShowPayments ?? true) !== false;
$_upsHasPayments = $_upsShowPayments;
$_upsUnitContact = is_array($_upsUnitContact ?? null) ? $_upsUnitContact : [];
$_upsContactPhone = trim((string) ($_upsUnitContact['phone_display'] ?? ''));
$_upsContactWhatsapp = preg_replace('/\D/', '', (string) ($_upsUnitContact['whatsapp_number'] ?? ''));
$_upsContactEmail = trim((string) ($_upsUnitContact['email'] ?? ''));
$_upsContactSchedule = trim((string) ($_upsUnitContact['schedule'] ?? ''));
$_upsHasContact = $_upsContactPhone !== '' || $_upsContactWhatsapp !== '' || $_upsContactEmail !== '' || $_upsContactSchedule !== '';

unset($_upsUnitSocialLinks, $_upsFromUnit, $_upsNetMeta, $_upsNet, $_upsUrl);

if (!$_upsHasPayments && !$_upsHasSocial && !$_upsHasContact) {
    return;
}
?>
<section class="border-top py-4 bg-white">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-center justify-content-md-between">

            <!-- Medios de pago -->
            <?php if ($_upsHasPayments): ?>
            <div class="col-auto d-flex align-items-center gap-3 flex-wrap">
                <span class="text-muted small fw-semibold text-uppercase tracking-wider">Medios de pago</span>
                <?php foreach ($_upsPayments as $_upsP): ?>
                <img src="<?php echo esc($_upsP['src']); ?>"
                     alt="<?php echo esc($_upsP['alt']); ?>"
                     width="44" height="28"
                     loading="lazy"
                     class="rounded-1 border"
                     style="object-fit: contain; background:#fff; padding:2px;">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($_upsHasContact): ?>
            <div class="col-auto d-flex align-items-center gap-3 flex-wrap small">
                <span class="text-muted fw-semibold text-uppercase tracking-wider">Contacto</span>
                <?php if ($_upsContactPhone !== ''): ?>
                    <span class="text-navy"><i class="bi bi-telephone me-1"></i><?php echo esc($_upsContactPhone); ?></span>
                <?php endif; ?>
                <?php if ($_upsContactWhatsapp !== ''): ?>
                    <a href="https://wa.me/<?php echo esc($_upsContactWhatsapp); ?>" class="text-navy text-decoration-none" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-whatsapp me-1"></i>WhatsApp
                    </a>
                <?php endif; ?>
                <?php if ($_upsContactEmail !== ''): ?>
                    <a href="mailto:<?php echo esc($_upsContactEmail); ?>" class="text-navy text-decoration-none">
                        <i class="bi bi-envelope me-1"></i><?php echo esc($_upsContactEmail); ?>
                    </a>
                <?php endif; ?>
                <?php if ($_upsContactSchedule !== ''): ?>
                    <span class="text-navy"><i class="bi bi-clock me-1"></i><?php echo esc($_upsContactSchedule); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Redes sociales -->
            <?php if ($_upsHasSocial): ?>
            <div class="col-auto d-flex align-items-center gap-3 flex-wrap">
                <span class="text-muted small fw-semibold text-uppercase tracking-wider">Síguenos</span>
                <?php foreach ($_upsSocial as $_upsSn): ?>
                <a href="<?php echo esc($_upsSn['url']); ?>"
                   class="text-navy fs-5 opacity-75"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="<?php echo esc($_upsSn['label'] ?? ''); ?>">
                    <i class="bi <?php echo esc($_upsSn['icon'] ?? 'bi-link-45deg'); ?>"></i>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>
