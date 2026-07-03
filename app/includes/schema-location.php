<?php
/**
 * Schema.org LocalBusiness JSON-LD para ficha de sucursal (AM-SEO-3C-F).
 *
 * Variables de entrada:
 *   $_schemaLocation      (array)  — registro locations[] desde LocationService
 *   $_schemaLocationSlug  (string) — slug de la URL
 *   $_schemaActiveUnits   (array)  — unidades activas (rentacar, seminuevos, …)
 */
$_schemaLocation = is_array($_schemaLocation ?? null) ? $_schemaLocation : [];
$_schemaLocationSlug = trim((string) ($_schemaLocationSlug ?? ''));
$_schemaActiveUnits = is_array($_schemaActiveUnits ?? null) ? $_schemaActiveUnits : [];

$_slName = trim((string) ($_schemaLocation['name'] ?? ''));
if ($_slName === '' || $_schemaLocationSlug === '') {
    unset($_schemaLocation, $_schemaLocationSlug, $_schemaActiveUnits, $_slName);
    return;
}

if (!function_exists('am_location_canonical_url')) {
    require_once __DIR__ . '/location-public-helper.php';
}

$_slPageUrl = am_location_canonical_url($_schemaLocationSlug);
if ($_slPageUrl === '') {
    unset($_schemaLocation, $_schemaLocationSlug, $_schemaActiveUnits, $_slName);
    return;
}
$_slEntityId = $_slPageUrl . '#location';

$_slSiteUrl = am_schema_canonical_base(isset($contentService) && is_object($contentService) ? $contentService : null);

if (!function_exists('am_schema_organization_id')) {
    require_once __DIR__ . '/schema-organization-helper.php';
}

$_slSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'LocalBusiness',
    '@id'      => $_slEntityId,
    'name'     => $_slName,
    'url'      => $_slPageUrl,
    'parentOrganization' => [
        '@id' => am_schema_organization_id($_slSiteUrl),
    ],
];

$_slPhones = is_array($_schemaLocation['phones'] ?? null) ? $_schemaLocation['phones'] : [];
foreach ($_slPhones as $_slPhoneRaw) {
    $_slPhoneRaw = trim((string) $_slPhoneRaw);
    if ($_slPhoneRaw === '') {
        continue;
    }
    $_slDigits = preg_replace('/\D/', '', $_slPhoneRaw);
    $_slSchema['telephone'] = $_slDigits !== '' ? '+' . $_slDigits : $_slPhoneRaw;
    break;
}

$_slEmail = trim((string) ($_schemaLocation['email'] ?? ''));
if ($_slEmail !== '') {
    $_slSchema['email'] = $_slEmail;
}

$_slStreet = trim((string) ($_schemaLocation['address'] ?? ''));
$_slCity = trim((string) ($_schemaLocation['city'] ?? ''));
$_slCountry = trim((string) ($_schemaLocation['country'] ?? ''));
if ($_slStreet !== '' || $_slCity !== '') {
    $_slPostal = [
        '@type' => 'PostalAddress',
    ];
    if ($_slStreet !== '') {
        $_slPostal['streetAddress'] = $_slStreet;
    }
    if ($_slCity !== '') {
        $_slPostal['addressLocality'] = $_slCity;
    }
    if ($_slCountry !== '') {
        $_slPostal['addressCountry'] = $_slCountry;
    }
    $_slSchema['address'] = $_slPostal;
}

$_slLat = trim((string) ($_schemaLocation['lat'] ?? ''));
$_slLng = trim((string) ($_schemaLocation['lng'] ?? ''));
if ($_slLat !== '' && $_slLng !== '' && is_numeric($_slLat) && is_numeric($_slLng)) {
    $_slSchema['geo'] = [
        '@type'     => 'GeoCoordinates',
        'latitude'  => (float) $_slLat,
        'longitude' => (float) $_slLng,
    ];
}

$_slImage = trim((string) ($_schemaLocation['image_url'] ?? ''));
if ($_slImage !== '') {
    if (!preg_match('#^https?://#i', $_slImage)) {
        $_slImage = $_slSiteUrl . '/' . ltrim($_slImage, '/');
    }
    $_slSchema['image'] = $_slImage;
}

$_slUnitAdditionalMap = [
    'rentacar'   => 'https://schema.org/AutoRental',
    'seminuevos' => 'https://schema.org/AutoDealer',
    'leasing'    => 'https://schema.org/LocalBusiness',
    'renting'    => 'https://schema.org/LocalBusiness',
    'taller'     => 'https://schema.org/AutoRepair',
];

$_slAdditional = [];
foreach ($_schemaActiveUnits as $_slUnitKey) {
    $_slUnitKey = trim((string) $_slUnitKey);
    if ($_slUnitKey === '' || !isset($_slUnitAdditionalMap[$_slUnitKey])) {
        continue;
    }
    $_slTypeUrl = $_slUnitAdditionalMap[$_slUnitKey];
    if ($_slTypeUrl === 'https://schema.org/LocalBusiness') {
        continue;
    }
    if (!in_array($_slTypeUrl, $_slAdditional, true)) {
        $_slAdditional[] = $_slTypeUrl;
    }
}
if (count($_slAdditional) === 1) {
    $_slSchema['additionalType'] = $_slAdditional[0];
} elseif ($_slAdditional !== []) {
    $_slSchema['additionalType'] = $_slAdditional;
}

$_slStructured = $_schemaLocation['hours']['structured'] ?? null;
if (is_array($_slStructured) && $_slStructured !== []) {
    $_slDayMap = [
        'monday'    => 'Monday',
        'tuesday'   => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday'  => 'Thursday',
        'friday'    => 'Friday',
        'saturday'  => 'Saturday',
        'sunday'    => 'Sunday',
    ];
    $_slOpeningHours = [];
    foreach ($_slDayMap as $_slDayKey => $_slDayLabel) {
        $_slDay = $_slStructured[$_slDayKey] ?? null;
        if (!is_array($_slDay)) {
            continue;
        }
        $_slOpens = trim((string) ($_slDay['open'] ?? ''));
        $_slCloses = trim((string) ($_slDay['close'] ?? ''));
        if ($_slOpens === '' || $_slCloses === '') {
            continue;
        }
        $_slOpeningHours[] = [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => $_slDayLabel,
            'opens'     => $_slOpens,
            'closes'    => $_slCloses,
        ];
    }
    if ($_slOpeningHours !== []) {
        $_slSchema['openingHoursSpecification'] = $_slOpeningHours;
    }
}

$_slJson = json_encode(
    $_slSchema,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
if ($_slJson === false) {
    unset($_schemaLocation, $_schemaLocationSlug, $_schemaActiveUnits, $_slName, $_slSiteUrl, $_slPageUrl, $_slEntityId);
    unset($_slSchema, $_slPhones, $_slPhoneRaw, $_slDigits, $_slEmail, $_slStreet, $_slCity, $_slCountry, $_slPostal);
    unset($_slLat, $_slLng, $_slImage, $_slUnitAdditionalMap, $_slAdditional, $_slUnitKey, $_slTypeUrl);
    unset($_slStructured, $_slDayMap, $_slDayKey, $_slDayLabel, $_slDay, $_slOpens, $_slCloses, $_slOpeningHours, $_slJson);
    return;
}

echo '<script type="application/ld+json">' . "\n" . $_slJson . "\n" . '</script>' . "\n";

unset($_schemaLocation, $_schemaLocationSlug, $_schemaActiveUnits, $_slName, $_slSiteUrl, $_slPageUrl, $_slEntityId);
unset($_slSchema, $_slPhones, $_slPhoneRaw, $_slDigits, $_slEmail, $_slStreet, $_slCity, $_slCountry, $_slPostal);
unset($_slLat, $_slLng, $_slImage, $_slUnitAdditionalMap, $_slAdditional, $_slUnitKey, $_slTypeUrl);
unset($_slStructured, $_slDayMap, $_slDayKey, $_slDayLabel, $_slDay, $_slOpens, $_slCloses, $_slOpeningHours, $_slJson);
