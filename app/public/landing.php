<?php
/**
 * Landing page pública — documento independiente (sin menú ni pie del sitio).
 * URL: /l/{slug}  o  /landing.php?slug={slug}
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../includes/landing-render.php';

$contentService = new ContentService();
$siteGlobal = $contentService->get('global');
$trackingCodes = $siteGlobal['tracking_codes'] ?? [];

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' && !empty($_SERVER['PATH_INFO'])) {
    $slug = trim($_SERVER['PATH_INFO'], '/');
}

$landings = $contentService->get('landings', []);
if (!is_array($landings)) {
    $landings = [];
}

$landing = null;
foreach ($landings as $item) {
    if (!is_array($item) || ($item['slug'] ?? '') !== $slug) {
        continue;
    }
    $active = $item['active'] ?? false;
    if ($active === true || $active === 1 || $active === '1') {
        $landing = $item;
        break;
    }
}

function landing_output_tracking(?string $html, string $position): void {
    $html = trim($html ?? '');
    if ($html !== '') {
        echo $html;
    }
}

function landing_render_404(): void {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Página no encontrada</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f6f9; color: #1a2744; }
        .box { text-align: center; padding: 2rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Página no encontrada</h1>
        <p>La landing no existe o está inactiva.</p>
    </div>
</body>
</html>
    <?php
    exit;
}

if (!$landing) {
    landing_render_404();
}

$seo = is_array($landing['seo'] ?? null) ? $landing['seo'] : [];
$pageTitle = trim($seo['title'] ?? '') ?: trim($landing['title'] ?? 'Landing');
$description = trim($seo['description'] ?? '') ?: trim($landing['excerpt'] ?? '');
$keywords = trim($seo['keywords'] ?? '');
$robots = trim($seo['robots'] ?? 'index,follow');
$canonical = trim($seo['canonical_url'] ?? '');
if ($canonical === '') {
    $canonical = landing_public_url($landing['slug'] ?? '');
}
$ogTitle = trim($seo['og_title'] ?? '') ?: $pageTitle;
$ogDescription = trim($seo['og_description'] ?? '') ?: $description;
$ogImage = trim($seo['og_image'] ?? '') ?: trim($landing['image_url'] ?? '');

$rawContent = $landing['content_html'] ?? '';
$bodyHtml = renderLandingBodyContent($rawContent);

if (isLandingFullDocument($rawContent)) {
    $doc = sanitizeLandingHtml(normalizeLandingRawContent($rawContent));
    $headHtml = trim($trackingCodes['head_html'] ?? '');
    if ($headHtml !== '' && stripos($doc, '</head>') !== false) {
        $doc = preg_replace('/<\/head>/i', $headHtml . "\n</head>", $doc, 1);
    }
    $bodyStart = trim($trackingCodes['body_start_html'] ?? '');
    if ($bodyStart !== '' && preg_match('/<body[^>]*>/i', $doc, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        $doc = substr($doc, 0, $pos) . $bodyStart . substr($doc, $pos);
    }
    $bodyEnd = trim($trackingCodes['body_end_html'] ?? '');
    if ($bodyEnd !== '' && stripos($doc, '</body>') !== false) {
        $doc = preg_replace('/<\/body>/i', $bodyEnd . "\n</body>", $doc, 1);
    }
    echo $doc;
    exit;
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?></title>
    <?php if ($description !== ''): ?>
    <meta name="description" content="<?php echo esc($description); ?>">
    <?php endif; ?>
    <?php if ($keywords !== ''): ?>
    <meta name="keywords" content="<?php echo esc($keywords); ?>">
    <?php endif; ?>
    <meta name="robots" content="<?php echo esc($robots); ?>">
    <link rel="canonical" href="<?php echo esc($canonical); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo esc($ogTitle); ?>">
    <?php if ($ogDescription !== ''): ?>
    <meta property="og:description" content="<?php echo esc($ogDescription); ?>">
    <?php endif; ?>
    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?php echo esc($ogImage); ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?php echo esc($canonical); ?>">
    <?php landing_output_tracking($trackingCodes['head_html'] ?? '', 'head'); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1a1a1a; line-height: 1.6; }
        img { max-width: 100%; height: auto; }
        a { color: inherit; }
    </style>
</head>
<body>
<?php landing_output_tracking($trackingCodes['body_start_html'] ?? '', 'body_start'); ?>
<?php echo $bodyHtml; ?>
<?php landing_output_tracking($trackingCodes['body_end_html'] ?? '', 'body_end'); ?>
</body>
</html>
