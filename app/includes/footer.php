<?php
/**
 * Global Footer Template
 */
if (!isset($globalSettings)) {
    if (!class_exists('ContentService')) {
        require_once __DIR__ . '/../services/ContentService.php';
    }
    $globalSettings = (new ContentService())->get('global');
}
if (!class_exists('FooterService')) {
    require_once __DIR__ . '/../services/FooterService.php';
}
$footerService = new FooterService();
$footerData = $footerService->getFooter();
$fg = $footerData['general'];
$trackingCodes = $globalSettings['tracking_codes'] ?? [];

$resourceLinks = [
    ['label' => t('footer.about_us'), 'url' => '/pagina-institucional.php?p=sobre-nosotros'],
    ['label' => t('footer.terms'), 'url' => '/pagina-institucional.php?p=terminos'],
    ['label' => t('footer.faq'), 'url' => '/pagina-institucional.php?p=faq'],
    ['label' => t('footer.branches'), 'url' => '/sucursales-grupo.php'],
    ['label' => t('footer.sustainability'), 'url' => '/sostenibilidad.php'],
    ['label' => t('footer.auctions'), 'url' => '/pagina-institucional.php?p=subastas'],
    ['label' => t('footer.blog'), 'url' => '/blog-grupo.php'],
];

$alsoKnow = array_filter($footerData['also_know'], fn($l) => !empty($l['active']));
usort($alsoKnow, fn($a, $b) => intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99));

$socialNetworks = array_filter($footerData['social'], fn($s) => !empty($s['active']));
usort($socialNetworks, fn($a, $b) => intval($a['sort_order'] ?? 99) - intval($b['sort_order'] ?? 99));

$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isPublicSite = strpos($reqPath, '/admin') !== 0;
$captchaSiteKey = ($isPublicSite && defined('RECAPTCHA_SITE_KEY'))
    ? trim((string) RECAPTCHA_SITE_KEY)
    : '';
?>
    <!-- Footer Section -->
    <footer class="footer bg-navy text-white pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row g-4 justify-content-between">
                
                <!-- Brand Column -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo esc($fg['logo_url'] ?? '/assets/img/logo.png'); ?>" alt="Automarket Logo" height="36" style="filter: brightness(0) invert(1); opacity: 0.95;">
                    </div>
                    <p class="text-secondary-light mb-4">
                        <?php echo esc($fg['tagline'] ?? t('footer.tagline')); ?>
                    </p>
                    <div class="contact-details text-secondary-light">
                        <p class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-white"></i> <?php echo esc($fg['address'] ?? ''); ?></p>
                        <p class="mb-2"><i class="bi bi-telephone-fill me-2 text-white"></i> <a href="tel:<?php echo preg_replace('/\D/', '', $fg['phone_display'] ?? ''); ?>" class="text-white text-decoration-none"><?php echo esc($fg['phone_display'] ?? ''); ?></a></p>
                        <p class="mb-2"><i class="bi bi-envelope-fill me-2 text-white"></i> <a href="mailto:<?php echo esc($fg['email'] ?? ''); ?>" class="text-white text-decoration-none"><?php echo esc($fg['email'] ?? ''); ?></a></p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-white"><?php echo esc($fg['resources_title'] ?? t('footer.resources')); ?></h5>
                    <ul class="list-unstyled footer-links">
                        <?php foreach ($resourceLinks as $rl): ?>
                        <li class="mb-2"><a href="<?php echo esc($rl['url']); ?>" class="text-white text-decoration-none opacity-75"><?php echo esc($rl['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Business Units Links -->
                <div class="col-lg-3 col-md-6 col-6">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-white"><?php echo esc($fg['also_know_title'] ?? t('footer.also_know')); ?></h5>
                    <ul class="list-unstyled footer-links">
                        <?php foreach ($alsoKnow as $link): ?>
                        <li class="mb-2"><a href="<?php echo esc($link['url'] ?? '#'); ?>" class="text-white text-decoration-none opacity-75"><?php echo esc($link['label'] ?? ''); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Payment Methods & Socials -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-white"><?php echo esc($fg['follow_title'] ?? t('footer.follow_us')); ?></h5>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php foreach ($socialNetworks as $sn): ?>
                        <a href="<?php echo esc($sn['url'] ?? '#'); ?>" class="text-white fs-4 opacity-75 hover-opacity-100" target="_blank" rel="noopener noreferrer" title="<?php echo esc($sn['label'] ?? ''); ?>">
                            <i class="bi <?php echo esc($sn['icon'] ?? 'bi-link-45deg'); ?>"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <h5 class="fw-bold mb-3 text-white"><?php echo esc($fg['payment_title'] ?? t('footer.payment_methods')); ?></h5>
                    <div class="payment-badges d-flex flex-wrap gap-2 align-items-center">
                        <img src="/assets/img/visa.png" alt="Visa" class="footer-payment-icon" width="44" height="28" loading="lazy">
                        <img src="/assets/img/mastercard.png" alt="Mastercard" class="footer-payment-icon" width="44" height="28" loading="lazy">
                    </div>
                </div>

            </div>

            <hr class="my-4 border-secondary">

            <!-- Copyright and Legal -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <span class="text-secondary-light small">&copy; <?php echo date('Y'); ?> <?php echo esc($fg['copyright'] ?? t('footer.copyright')); ?></span>
                <span class="text-secondary-light small d-flex gap-3">
                    <a href="<?php echo esc($fg['privacy_url'] ?? '#privacidad'); ?>" class="text-secondary-light text-decoration-none"><?php echo esc(t('footer.privacy')); ?></a>
                    <a href="<?php echo esc($fg['cookies_url'] ?? '#cookies'); ?>" class="text-secondary-light text-decoration-none"><?php echo esc(t('footer.cookies')); ?></a>
                </span>
                <?php if ($captchaSiteKey !== ''): ?>
                <p class="text-secondary-light small mb-0 mt-2" style="opacity:0.65;">
                    Este sitio está protegido por reCAPTCHA y se aplican la
                    <a href="https://policies.google.com/privacy" class="text-secondary-light" target="_blank" rel="noopener noreferrer">Política de Privacidad</a>
                    y los
                    <a href="https://policies.google.com/terms" class="text-secondary-light" target="_blank" rel="noopener noreferrer">Términos del Servicio</a>
                    de Google.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <?php
    if ($isPublicSite) {
        require __DIR__ . '/chatbot-widget.php';
    }
    ?>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $globalSettings['whatsapp_number'] ?? '5072792700'); ?>" 
       class="whatsapp-float d-flex align-items-center justify-content-center shadow-lg" 
       target="_blank" 
       rel="noopener noreferrer" 
       title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
        <span class="whatsapp-badge"><?php echo esc(t('whatsapp.help', $globalSettings['whatsapp_label'] ?? '')); ?></span>
    </a>
    <?php if (!empty(trim((string)($trackingCodes['body_end_html'] ?? '')))): ?>
    <?php echo $trackingCodes['body_end_html']; ?>
    <?php endif; ?>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($isPublicSite): ?>
    <script>window.AM_RECAPTCHA = { siteKey: <?php echo json_encode($captchaSiteKey, JSON_UNESCAPED_UNICODE); ?> };</script>
    <?php if ($captchaSiteKey !== ''): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script src="/assets/js/captcha.js?v=2"></script>
    <?php endif; ?>
    
    <!-- Custom Web Controller JavaScript -->
    <?php if (!empty($activeUnit) && $activeUnit === 'rentacar'): ?>
    <script src="/assets/js/rac-search.js?v=1"></script>
    <?php endif; ?>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/dropdown-fix.js?v=3"></script>
    <?php if ($isPublicSite): ?>
    <script>
    window.AM_TELEMETRY = {
        unit: <?php echo json_encode($activeUnit ?? '', JSON_UNESCAPED_UNICODE); ?>,
        context: <?php echo json_encode($telemetryContext ?? null, JSON_UNESCAPED_UNICODE); ?>
    };
    </script>
    <script src="/assets/js/telemetry.js?v=2" defer></script>
    <?php endif; ?>
</body>
</html>
