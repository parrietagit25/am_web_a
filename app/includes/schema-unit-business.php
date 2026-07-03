<?php
/**
 * Schema.org JSON-LD por unidad de negocio (sustituye el bloque global único).
 *
 * Requiere: $activeUnit, $currentUnit, $siteGlobal, $contentService, $seo (opcional).
 */
require_once __DIR__ . '/unit-footer-prepare.php';
require_once __DIR__ . '/schema-organization-helper.php';

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
$_subSiteUrl = am_schema_canonical_base($contentService ?? null);
$_subLogoUrl = am_schema_logo_url($_subSiteUrl);
$_subOrgId = am_schema_organization_id($_subSiteUrl);

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

$_subSchema = [
    '@context' => 'https://schema.org',
    '@type'    => $_subSchemaType,
    'name'     => 'Automarket ' . $_subUnitLabel,
    'url'      => $_subUnitUrl,
    'logo'     => [
        '@type' => 'ImageObject',
        'url'   => $_subLogoUrl,
    ],
    'parentOrganization' => [
        '@id' => $_subOrgId,
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

if ($_subSchemaType === 'LocalBusiness') {
    $_subSchema['description'] = trim((string) ($currentUnit['heroSubtitle'] ?? ''));
}

am_schema_emit_json_ld($_subSchema);

unset($_subUnitKey, $_subUnitMap, $_subSchemaType, $_subSiteUrl, $_subLogoUrl, $_subOrgId);
unset($_subUnitLabel, $_subUnitSlug, $_subUnitPath, $_subUnitUrl, $_subUnitData, $_subContact);
unset($_subPhoneRaw, $_subPhone, $_subEmail, $_subAddress, $_subSchema);
