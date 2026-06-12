<?php
/**
 * Global Header Template
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/SeoService.php';

$contentService = new ContentService();
$siteGlobal = $contentService->get('global');
$trackingCodes = $siteGlobal['tracking_codes'] ?? [];
$businessUnits = $siteGlobal['business_units'] ?? require __DIR__ . '/../config/business-units.php';

/**
 * Submenú: oculta Requisitos de alquiler y deja Términos y condiciones al final.
 */
function filter_submenu_items(array $items): array {
    $visible = [];
    $terminos = null;
    foreach ($items as $sub) {
        if (!is_array($sub)) {
            continue;
        }
        $link = (string) ($sub['link'] ?? '');
        $path = strtolower(parse_url($link, PHP_URL_PATH) ?: $link);
        if (str_contains($path, 'requisitos-alquiler')) {
            continue;
        }
        if (str_contains($path, 'terminos-condiciones')) {
            $terminos = $sub;
            continue;
        }
        $visible[] = $sub;
    }
    if ($terminos !== null) {
        $visible[] = $terminos;
    }
    return $visible;
}

// Determine the active business unit
if (!isset($activeUnit) || !array_key_exists($activeUnit, $businessUnits)) {
    $activeUnit = 'rentacar';
}
$currentUnit = $businessUnits[$activeUnit];
$seoService = new SeoService($contentService);
$seo = $seoService->resolveForRequest(
    (string)($currentUnit['heroTitle'] ?? 'Automarket'),
    (string)($currentUnit['heroSubtitle'] ?? 'Innovando la movilidad en Panamá.')
);
if (isset($seoOverride) && is_array($seoOverride)) {
    foreach (['title', 'description', 'keywords', 'robots', 'canonical', 'og_title', 'og_description', 'og_image'] as $seoKey) {
        if (!empty($seoOverride[$seoKey])) {
            $seo[$seoKey] = trim((string)$seoOverride[$seoKey]);
        }
    }
}

// Parse hex color to RGB for transparency usage in styling
list($r, $g, $b) = sscanf($currentUnit['color'], "#%02x%02x%02x");
$themeRgb = "$r, $g, $b";
?>
<!DOCTYPE html>
<html lang="<?php echo esc(current_lang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($seo['title']); ?></title>
    <meta name="description" content="<?php echo esc($seo['description']); ?>">
    <?php if (!empty($seo['keywords'])): ?>
    <meta name="keywords" content="<?php echo esc($seo['keywords']); ?>">
    <?php endif; ?>
    <meta name="robots" content="<?php echo esc($seo['robots']); ?>">
    <?php if (!empty($seo['canonical'])): ?>
    <link rel="canonical" href="<?php echo esc($seo['canonical']); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo esc($seo['site_name']); ?>">
    <meta property="og:title" content="<?php echo esc($seo['og_title']); ?>">
    <meta property="og:description" content="<?php echo esc($seo['og_description']); ?>">
    <?php if (!empty($seo['canonical'])): ?>
    <meta property="og:url" content="<?php echo esc($seo['canonical']); ?>">
    <?php endif; ?>
    <?php if (!empty($seo['og_image'])): ?>
    <meta property="og:image" content="<?php echo esc($seo['og_image']); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?php echo esc($seo['og_image']); ?>">
    <?php else: ?>
    <meta name="twitter:card" content="summary">
    <?php endif; ?>
    <meta name="twitter:title" content="<?php echo esc($seo['og_title']); ?>">
    <meta name="twitter:description" content="<?php echo esc($seo['og_description']); ?>">
    <meta name="am-business-unit" content="<?php echo esc($activeUnit ?? ''); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom Style Sheet -->
    <link href="/assets/css/styles.css" rel="stylesheet">
    
    <!-- Dynamic Theme Styling Injection -->
    <style>
        :root {
            --theme-primary: <?php echo esc($currentUnit['color']); ?>;
            --theme-primary-rgb: <?php echo $themeRgb; ?>;
        }
    </style>
    <?php if (!empty(trim((string)($trackingCodes['head_html'] ?? '')))): ?>
    <?php echo $trackingCodes['head_html']; ?>
    <?php endif; ?>
</head>
<body class="theme-<?php echo esc($currentUnit['key']); ?>">
<?php if (!empty(trim((string)($trackingCodes['body_start_html'] ?? '')))): ?>
<?php echo $trackingCodes['body_start_html']; ?>
<?php endif; ?>

<div class="site-header-stack">
    <!-- 1. Top Bar (Blue/Navy Premium Theme) -->
    <div class="top-bar py-2 text-white">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3 top-bar-info">
                <span class="d-none d-md-inline text-accent-light"><i class="bi bi-tag-fill me-1"></i> <?php echo esc(t('topbar.special_prices')); ?></span>
                <span><i class="bi bi-telephone-fill me-1"></i> <?php echo esc(t('topbar.call_us')); ?> <a href="tel:<?php echo preg_replace('/\D/', '', $siteGlobal['phone_display'] ?? '5072792700'); ?>" class="text-white text-decoration-none"><?php echo esc($siteGlobal['phone_display'] ?? '(507) 279-2700'); ?></a></span>
                <span class="d-none d-lg-inline"><?php echo esc(t('topbar.toll_free')); ?> <a href="tel:<?php echo preg_replace('/\D/', '', $siteGlobal['toll_free'] ?? '18667009904'); ?>" class="text-white text-decoration-none"><?php echo esc($siteGlobal['toll_free'] ?? '1-866-700-9904'); ?></a></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php
                $curLang = current_lang();
                $curFlag = $curLang === 'en' ? 'gb' : 'es';
                $curLabel = $curLang === 'en' ? t('lang.en') : t('lang.es');
                $redirectUri = $_SERVER['REQUEST_URI'] ?? '/';
                ?>
                <div class="dropdown lang-switcher">
                    <button class="btn btn-sm lang-switcher-btn dropdown-toggle" type="button" data-am-dropdown-toggle aria-expanded="false" aria-haspopup="true">
                        <img src="https://flagcdn.com/w20/<?php echo esc($curFlag); ?>.png" alt="" class="lang-flag" width="20" height="15">
                        <span><?php echo esc($curLabel); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end lang-switcher-menu shadow">
                        <li>
                            <a class="dropdown-item lang-switcher-item <?php echo $curLang === 'es' ? 'active' : ''; ?>" href="<?php echo esc(lang_url('es')); ?>">
                                <img src="https://flagcdn.com/w20/es.png" alt="" class="lang-flag" width="20" height="15">
                                <span><?php echo esc(t('lang.es')); ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item lang-switcher-item <?php echo $curLang === 'en' ? 'active' : ''; ?>" href="<?php echo esc(lang_url('en')); ?>">
                                <img src="https://flagcdn.com/w20/gb.png" alt="" class="lang-flag" width="20" height="15">
                                <span><?php echo esc(t('lang.en')); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Menú Superior Global (Tabs de unidades de negocio) -->
    <div class="global-units-menu shadow-sm">
        <div class="container px-0 px-md-3">
            <div class="d-flex overflow-x-auto text-nowrap scrollbar-hidden">
                <?php foreach ($businessUnits as $key => $unit): ?>
                    <?php 
                        $isActive = ($activeUnit === $key);
                        $tabStyle = $isActive ? "border-bottom: 3px solid " . esc($unit['color']) . "; color: " . esc($unit['color']) . "; font-weight: 700;" : "";
                    ?>
                    <a href="/<?php echo esc($unit['slug']); ?>" 
                       class="unit-tab px-4 py-3 text-decoration-none d-inline-flex align-items-center gap-2 <?php echo $isActive ? 'active' : ''; ?>"
                       style="<?php echo $tabStyle; ?>">
                        <span class="dot" style="background-color: <?php echo esc($unit['color']); ?>"></span>
                        <?php echo esc(t_unit($key, $unit['label'])); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 3. Header Inferior Dinámico (Navbar) -->
    <header class="navbar navbar-expand-lg navbar-light bg-white sticky-top py-3 shadow-sm border-bottom">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="/rent-a-car.php">
                <div class="d-flex align-items-center logo-wrapper-div">
                    <img src="/assets/img/logo.png" alt="Automarket Logo" height="32" class="me-2">
                    <span class="d-block text-uppercase fw-semibold tracking-wider font-poppins brand-sub border-start ps-2 ms-2" style="color: var(--theme-primary); font-size: 0.75rem; letter-spacing: 0.1em; border-left: 2px solid #dee2e6 !important;">
                        <?php echo esc($currentUnit['logo_subtitle']); ?>
                    </span>
                </div>
            </a>

            <!-- Mobile Hamburger Toggle button -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#dynamicNavbar" aria-controls="dynamicNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Dynamic Secondary Menu Links -->
            <div class="collapse navbar-collapse justify-content-end" id="dynamicNavbar">
                <ul class="navbar-nav mb-2 mb-lg-0 gap-1 gap-lg-3 py-3 py-lg-0 align-items-lg-center">
                    <?php foreach ($currentUnit['menu'] as $item): 
                        $link = $item['link'];
                        if (str_starts_with($link, '#')) {
                            $currentScript = basename($_SERVER['SCRIPT_NAME']);
                            if ($currentScript !== $currentUnit['slug']) {
                                $link = '/' . $currentUnit['slug'] . $link;
                            }
                        }
                    ?>
                        <?php if (isset($item['submenu']) && is_array($item['submenu']) && !empty($item['submenu'])): ?>
                            <li class="nav-item dropdown">
                                <button class="nav-link dropdown-toggle text-dark fw-semibold px-2 py-1 rounded d-flex align-items-center gap-1 border-0 bg-transparent" type="button" id="navbarDropdown-<?php echo esc(str_replace(' ', '', $item['label'])); ?>" data-am-dropdown-toggle aria-expanded="false" aria-haspopup="true">
                                    <?php echo esc(t_menu($item['label'])); ?>
                                </button>
                                <ul class="dropdown-menu shadow border-0 py-2 rounded-3" aria-labelledby="navbarDropdown-<?php echo esc(str_replace(' ', '', $item['label'])); ?>">
                                    <?php foreach (filter_submenu_items($item['submenu']) as $sub): 
                                        $subLink = $sub['link'];
                                        if (str_starts_with($subLink, '#')) {
                                            $currentScript = basename($_SERVER['SCRIPT_NAME']);
                                            if ($currentScript !== $currentUnit['slug']) {
                                                $subLink = '/' . $currentUnit['slug'] . $subLink;
                                            }
                                        }
                                    ?>
                                        <li>
                                            <a class="dropdown-item fw-semibold text-navy py-2 px-3 font-poppins" href="<?php echo esc($subLink); ?>" style="font-size: 0.85rem;">
                                                <?php echo esc(t_submenu($sub['label'])); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link text-dark fw-semibold px-2 py-1 rounded" href="<?php echo esc($link); ?>">
                                    <?php echo esc(t_menu($item['label'])); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if (!empty(trim($currentUnit['ctaText'] ?? ''))): ?>
                    <!-- Action button for active unit -->
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <?php
                            $ctaLink = $currentUnit['ctaLink'] ?? '/' . ($currentUnit['slug'] ?? '') . '#cta-hero';
                        ?>
                        <a href="<?php echo esc($ctaLink); ?>" class="btn btn-theme px-4 py-2 rounded-pill fw-bold text-white shadow-sm transition-all text-center d-block text-uppercase">
                            <?php echo esc(t_cta($currentUnit['ctaText'])); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>
</div><!-- /.site-header-stack -->
