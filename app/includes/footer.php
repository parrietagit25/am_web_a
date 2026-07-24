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
if (!isset($contentService) || !($contentService instanceof ContentService)) {
    $contentService = new ContentService();
}
require_once __DIR__ . '/business-units-registry.php';
require_once __DIR__ . '/../services/WhatsappContextService.php';
$whatsappSiteData = isset($siteData) && is_array($siteData)
    ? $siteData
    : $contentService->getAll();
$whatsappBusinessUnits = isset($businessUnits) && is_array($businessUnits)
    ? $businessUnits
    : am_merge_business_units(
        is_array($whatsappSiteData['global']['business_units'] ?? null)
            ? $whatsappSiteData['global']['business_units']
            : []
    );
$whatsappContext = WhatsappContextService::resolve(
    $whatsappSiteData,
    $whatsappBusinessUnits,
    (string) ($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? ''),
    [
        'u' => $_GET['u'] ?? '',
        'unit' => $_GET['unit'] ?? '',
    ]
);
if (!class_exists('FooterService')) {
    require_once __DIR__ . '/../services/FooterService.php';
}
$footerService = new FooterService();
$footerData = $footerService->getFooter();
$fg = $footerData['general'];
$trackingCodes = $globalSettings['tracking_codes'] ?? [];

require_once __DIR__ . '/../services/UnitFooterService.php';
$footerActiveUnit = strtolower(trim((string) ($activeUnit ?? 'rentacar')));
if ($footerActiveUnit === '') {
    $footerActiveUnit = 'rentacar';
}
$footerSiteData = isset($siteData) && is_array($siteData)
    ? $siteData
    : $contentService->getAll();
$unitFooterResolved = UnitFooterService::resolveForRender($footerSiteData, $footerActiveUnit, $footerData);

$activeFooterColumns = [[
    'id' => 'recursos',
    'title' => $unitFooterResolved['resources_title'],
    'active' => true,
    'links' => $unitFooterResolved['resources'],
]];
$footerLinkColCount = 1;
$footerLinkColClass = 'col-lg-2 col-md-6 col-6';

$alsoKnow = $unitFooterResolved['also_know'];
$socialNetworks = $unitFooterResolved['social'];
$fg['also_know_title'] = $unitFooterResolved['also_know_title'];
$fg['follow_title'] = $unitFooterResolved['follow_title'];
$fg['resources_title'] = $unitFooterResolved['resources_title'];

// Columna de marca + franja legal (por unidad si está configurada; si no, global).
$fg['tagline'] = (string) ($unitFooterResolved['brand_tagline'] ?? ($fg['tagline'] ?? ''));
$fg['address'] = (string) ($unitFooterResolved['brand_address'] ?? ($fg['address'] ?? ''));
$fg['phone_display'] = (string) ($unitFooterResolved['brand_phone'] ?? ($fg['phone_display'] ?? ''));
$fg['email'] = (string) ($unitFooterResolved['brand_email'] ?? ($fg['email'] ?? ''));
$fg['copyright'] = (string) ($unitFooterResolved['copyright'] ?? ($fg['copyright'] ?? 'Automarket. Todos los derechos reservados.'));
$fg['privacy_label'] = (string) ($unitFooterResolved['privacy_label'] ?? ($fg['privacy_label'] ?? t('footer.privacy')));
$fg['privacy_url'] = (string) ($unitFooterResolved['privacy_url'] ?? ($fg['privacy_url'] ?? '/pagina-institucional.php?p=privacidad'));
$fg['cookies_label'] = (string) ($unitFooterResolved['cookies_label'] ?? ($fg['cookies_label'] ?? t('footer.cookies')));
$fg['cookies_url'] = (string) ($unitFooterResolved['cookies_url'] ?? ($fg['cookies_url'] ?? '/pagina-institucional.php?p=cookies'));
$fg['recaptcha_text_before'] = (string) ($unitFooterResolved['recaptcha_text_before'] ?? ($fg['recaptcha_text_before'] ?? 'Este sitio está protegido por reCAPTCHA y se aplican la'));
$fg['recaptcha_privacy_label'] = (string) ($unitFooterResolved['recaptcha_privacy_label'] ?? ($fg['recaptcha_privacy_label'] ?? 'Política de Privacidad'));
$fg['recaptcha_privacy_url'] = (string) ($unitFooterResolved['recaptcha_privacy_url'] ?? ($fg['recaptcha_privacy_url'] ?? 'https://policies.google.com/privacy'));
$fg['recaptcha_text_middle'] = (string) ($unitFooterResolved['recaptcha_text_middle'] ?? ($fg['recaptcha_text_middle'] ?? 'y los'));
$fg['recaptcha_terms_label'] = (string) ($unitFooterResolved['recaptcha_terms_label'] ?? ($fg['recaptcha_terms_label'] ?? 'Términos del Servicio'));
$fg['recaptcha_terms_url'] = (string) ($unitFooterResolved['recaptcha_terms_url'] ?? ($fg['recaptcha_terms_url'] ?? 'https://policies.google.com/terms'));
$fg['recaptcha_text_after'] = (string) ($unitFooterResolved['recaptcha_text_after'] ?? ($fg['recaptcha_text_after'] ?? 'de Google.'));

$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isPublicSite = strpos($reqPath, '/admin') !== 0;
$captchaSiteKey = ($isPublicSite && defined('RECAPTCHA_SITE_KEY'))
    ? trim((string) RECAPTCHA_SITE_KEY)
    : '';
$racCaptchaBypassLocal = false;
if ($isPublicSite) {
    if (!class_exists('CaptchaService')) {
        require_once __DIR__ . '/../services/CaptchaService.php';
    }
    $racCaptchaBypassLocal = CaptchaService::isLocalCaptchaBypassAllowed();
}
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

                <!-- Link columns (dynamic, B2) -->
                <?php foreach ($activeFooterColumns as $footerColumn): ?>
                <?php
                $footerColId = (string) ($footerColumn['id'] ?? '');
                if ($footerColId === 'recursos') {
                    $footerColHeading = trim((string) ($fg['resources_title'] ?? ''));
                    if ($footerColHeading === '') {
                        $footerColHeading = trim((string) ($footerColumn['title'] ?? ''));
                    }
                    if ($footerColHeading === '') {
                        $footerColHeading = t('footer.resources');
                    }
                } else {
                    $footerColHeading = trim((string) ($footerColumn['title'] ?? ''));
                }
                if ($footerColHeading === '') {
                    continue;
                }
                ?>
                <div class="<?php echo esc($footerLinkColClass); ?>">
                    <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2 text-white"><?php echo esc($footerColHeading); ?></h5>
                    <ul class="list-unstyled footer-links">
                        <?php foreach ($footerColumn['links'] as $footerLink): ?>
                        <?php
                        $footerOpenNew = (($footerLink['open_in'] ?? 'same') === 'new');
                        ?>
                        <li class="mb-2">
                            <a href="<?php echo esc($footerLink['url'] ?? '#'); ?>" class="text-white text-decoration-none opacity-75"<?php if ($footerOpenNew): ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc($footerLink['label'] ?? ''); ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>

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
            <?php
            $footerPrivacyUrl = (string) ($fg['privacy_url'] ?? '#privacidad');
            $footerCookiesUrl = (string) ($fg['cookies_url'] ?? '#cookies');
            $footerRecaptchaPrivacyUrl = (string) ($fg['recaptcha_privacy_url'] ?? 'https://policies.google.com/privacy');
            $footerRecaptchaTermsUrl = (string) ($fg['recaptcha_terms_url'] ?? 'https://policies.google.com/terms');
            $footerExternalAttrs = static function (string $url): string {
                return preg_match('#^https?://#i', $url)
                    ? ' target="_blank" rel="noopener noreferrer"'
                    : '';
            };
            ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <span class="text-secondary-light small">&copy; <?php echo date('Y'); ?> <?php echo esc($fg['copyright'] ?? t('footer.copyright')); ?></span>
                <span class="text-secondary-light small d-flex gap-3">
                    <a href="<?php echo esc($footerPrivacyUrl); ?>" class="text-secondary-light text-decoration-none"<?php echo $footerExternalAttrs($footerPrivacyUrl); ?>><?php echo esc((string) ($fg['privacy_label'] ?? t('footer.privacy'))); ?></a>
                    <a href="<?php echo esc($footerCookiesUrl); ?>" class="text-secondary-light text-decoration-none"<?php echo $footerExternalAttrs($footerCookiesUrl); ?>><?php echo esc((string) ($fg['cookies_label'] ?? t('footer.cookies'))); ?></a>
                </span>
                <?php if ($captchaSiteKey !== ''): ?>
                <p class="text-secondary-light small mb-0 mt-2 w-100" style="opacity:0.65;">
                    <?php echo esc((string) ($fg['recaptcha_text_before'] ?? 'Este sitio está protegido por reCAPTCHA y se aplican la')); ?>
                    <a href="<?php echo esc($footerRecaptchaPrivacyUrl); ?>" class="text-secondary-light"<?php echo $footerExternalAttrs($footerRecaptchaPrivacyUrl); ?>><?php echo esc((string) ($fg['recaptcha_privacy_label'] ?? 'Política de Privacidad')); ?></a>
                    <?php echo esc(' ' . trim((string) ($fg['recaptcha_text_middle'] ?? 'y los')) . ' '); ?>
                    <a href="<?php echo esc($footerRecaptchaTermsUrl); ?>" class="text-secondary-light"<?php echo $footerExternalAttrs($footerRecaptchaTermsUrl); ?>><?php echo esc((string) ($fg['recaptcha_terms_label'] ?? 'Términos del Servicio')); ?></a>
                    <?php echo esc(' ' . trim((string) ($fg['recaptcha_text_after'] ?? 'de Google.'))); ?>
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

    <?php if ($whatsappContext['visible']): ?>
    <!-- WhatsApp contextual por unidad -->
    <a href="<?php echo esc($whatsappContext['url']); ?>"
       class="whatsapp-float d-flex align-items-center justify-content-center shadow-lg" 
       target="_blank" 
       rel="noopener noreferrer" 
       title="<?php echo esc($whatsappContext['aria_label']); ?>"
       aria-label="<?php echo esc($whatsappContext['aria_label']); ?>">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
        <span class="whatsapp-badge" aria-hidden="true">WhatsApp <?php echo esc($whatsappContext['unit_label']); ?></span>
    </a>
    <?php endif; ?>
    <?php if (!empty(trim((string)($trackingCodes['body_end_html'] ?? '')))): ?>
    <?php echo $trackingCodes['body_end_html']; ?>
    <?php endif; ?>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($isPublicSite): ?>
    <script>window.AM_RECAPTCHA = { siteKey: <?php echo json_encode($captchaSiteKey, JSON_UNESCAPED_UNICODE); ?>, racBypassLocal: <?php echo $racCaptchaBypassLocal ? 'true' : 'false'; ?> };</script>
    <?php if ($captchaSiteKey !== ''): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script src="/assets/js/captcha.js?v=3"></script>
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
