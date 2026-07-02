<?php
/**
 * Sección de Medios de Pago y Redes Sociales por unidad de negocio.
 *
 * Uso:
 *   // Opcional: pasar redes propias de la unidad (sobreescribe el fallback global)
 *   $\_upsUnitSocialLinks = $unitData['social_links'] ?? [];
 *   require __DIR__ . '/../includes/unit-payment-social.php';
 *   // Sin esa variable, cae al footer global.
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
    foreach ($_upsUnitSocialLinks as $_upsNet => $_upsUrl) {
        $_upsUrl = trim((string)$_upsUrl);
        if ($_upsUrl !== '' && isset($_upsNetMeta[$_upsNet])) {
            $_upsFromUnit[] = [
                'url'   => $_upsUrl,
                'icon'  => $_upsNetMeta[$_upsNet]['icon'],
                'label' => $_upsNetMeta[$_upsNet]['label'],
            ];
        }
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
    $_upsSocial = array_values(array_filter(
        $_upsFooter['social'] ?? [],
        fn($s) => !empty($s['active']) && !empty($s['url']) && $s['url'] !== '#'
    ));
    usort($_upsSocial, fn($a, $b) => intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99));
    unset($_upsSvc, $_upsFooter);
}

// Medios de pago: iconos de assets (siempre disponibles)
$_upsPayments = [
    ['src' => '/assets/img/visa.png',       'alt' => 'Visa'],
    ['src' => '/assets/img/mastercard.png', 'alt' => 'Mastercard'],
];

$_upsHasSocial   = !empty($_upsSocial);
$_upsHasPayments = true;

unset($_upsUnitSocialLinks, $_upsFromUnit, $_upsNetMeta, $_upsNet, $_upsUrl);
?>
<section class="border-top py-4 bg-white">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-center justify-content-md-between">

            <!-- Medios de pago -->
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
