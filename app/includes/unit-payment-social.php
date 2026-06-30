<?php
/**
 * Sección de Medios de Pago y Redes Sociales por unidad de negocio.
 *
 * Incluir antes del footer en cada página de unidad.
 * No requiere parámetros externos: lee desde FooterService (redes) y usa
 * los assets globales de medios de pago (visa.png / mastercard.png).
 *
 * Uso:
 *   require __DIR__ . '/../includes/unit-payment-social.php';
 */

if (!class_exists('FooterService')) {
    require_once __DIR__ . '/../services/FooterService.php';
}

$_upsSvc   = new FooterService();
$_upsFooter = $_upsSvc->getFooter();

// Redes sociales activas con URL real (no #)
$_upsSocial = array_values(array_filter(
    $_upsFooter['social'] ?? [],
    fn($s) => !empty($s['active']) && !empty($s['url']) && $s['url'] !== '#'
));
usort($_upsSocial, fn($a, $b) => intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99));

// Medios de pago: iconos de assets (siempre disponibles)
$_upsPayments = [
    ['src' => '/assets/img/visa.png',       'alt' => 'Visa'],
    ['src' => '/assets/img/mastercard.png', 'alt' => 'Mastercard'],
];

// Solo renderizar si al menos un bloque tiene contenido
$_upsHasSocial   = !empty($_upsSocial);
$_upsHasPayments = true; // siempre existen los archivos
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
