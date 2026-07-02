<?php
/**
 * Schema.org JSON-LD por unidad de negocio (sustituye el bloque global único).
 *
 * Requiere: $activeUnit, $currentUnit, $siteGlobal, $contentService, $seo (opcional).
 */
require_once __DIR__ . '/unit-footer-prepare.php';

if (!class_exists('FooterService')) {
    require_once __DIR__ . '/../services/FooterService.php';
}

$_subUnitKey = $activeUnit ?? 'rentacar';
$_subUnitMap = [
    'rentacar'   => 'AutoRental',
    'seminuevos' => 'AutoDealer',
    'taller'     => 'AutoRepair',
    'leasing'    => 'LocalBusiness',
    'renting'    => 'LocalBusiness',
];

if (!isset($_subUnitMap[$_subUnitKey])) {
    return;
}

$_subSchemaType = $_subUnitMap[$_subUnitKey];
$_subSeoGlobal = $contentService->get('seo.global', []);
$_subCanonicalBase = rtrim(trim((string) ($_subSeoGlobal['canonical_base_url'] ?? '')), '/');
$_subSiteUrl = $_subCanonicalBase !== '' ? $_subCanonicalBase : 'https://www.automarket.com.pa';
$_subLogoUrl = $_subSiteUrl . '/assets/img/logo.png';

$_subUnitLabel = trim((string) ($currentUnit['label'] ?? 'Automarket'));
$_subUnitSlug = trim((string) ($currentUnit['slug'] ?? 'rent-a-car.php'));
$_subUnitPath = '/' . ltrim($_subUnitSlug, '/');
$_subUnitUrl = rtrim($_subSiteUrl, '/') . $_subUnitPath;

$_subUnitData = am_unit_site_data_from_service($contentService, $_subUnitKey);
$_subContact = am_unit_footer_contact_array($_subUnitData);

$_subPhoneRaw = trim((string) ($_subContact['phone_display'] ?? ''));
if ($_subPhoneRaw === '') {
    $_subPhoneRaw = trim((string) ($siteGlobal['phone_display'] ?? ''));
}
$_subPhone = $_subPhoneRaw !== '' ? '+' . preg_replace('/\D/', '', $_subPhoneRaw) : '';

$_subEmail = trim((string) ($_subContact['email'] ?? ''));
if ($_subEmail === '') {
    $_subEmail = trim((string) ($siteGlobal['email'] ?? ''));
}

$_subAddress = trim((string) ($siteGlobal['address'] ?? ''));

$_subSocialRaw = $siteGlobal['footer']['social'] ?? [];
if (empty($_subSocialRaw) && class_exists('FooterService')) {
    try {
        $_subFs = new FooterService();
        $_subSocialRaw = $_subFs->getFooter()['social'] ?? [];
    } catch (\Throwable $e) {
        $_subSocialRaw = [];
    }
}
$_subSameAs = [];
foreach ($_subSocialRaw as $_subSn) {
    $_subSnUrl = trim((string) ($_subSn['url'] ?? ''));
    $_subSnActive = $_subSn['active'] ?? true;
    if ($_subSnActive && str_starts_with($_subSnUrl, 'http')) {
        $_subSameAs[] = $_subSnUrl;
    }
}

$_subSchema = [
    '@context' => 'https://schema.org',
    '@type'    => $_subSchemaType,
    'name'     => 'Automarket ' . $_subUnitLabel,
    'url'      => $_subUnitUrl,
    'logo'     => [
        '@type' => 'ImageObject',
        'url'   => $_subLogoUrl,
    ],
];

if ($_subPhone !== '' && $_subPhone !== '+') {
    $_subSchema['telephone'] = $_subPhone;
}
if ($_subEmail !== '') {
    $_subSchema['email'] = $_subEmail;
}
if ($_subAddress !== '') {
    $_subSchema['address'] = [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $_subAddress,
        'addressLocality' => 'Ciudad de Panamá',
        'addressCountry'  => 'PA',
    ];
}
if (!empty($_subSameAs)) {
    $_subSchema['sameAs'] = array_values($_subSameAs);
}

if ($_subSchemaType === 'LocalBusiness') {
    $_subSchema['description'] = trim((string) ($currentUnit['heroSubtitle'] ?? ''));
}

$_subJson = json_encode($_subSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo '<script type="application/ld+json">' . "\n" . $_subJson . "\n" . '</script>' . "\n";

unset($_subUnitKey, $_subUnitMap, $_subSchemaType, $_subSeoGlobal, $_subCanonicalBase, $_subSiteUrl, $_subLogoUrl);
unset($_subUnitLabel, $_subUnitSlug, $_subUnitPath, $_subUnitUrl, $_subUnitData, $_subContact);
unset($_subPhoneRaw, $_subPhone, $_subEmail, $_subAddress, $_subSocialRaw, $_subSameAs, $_subSn, $_subSnUrl, $_subSnActive);
unset($_subSchema, $_subJson, $_subFs);
