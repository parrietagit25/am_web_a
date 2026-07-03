<?php
/**
 * Partial: Schema.org Car (Vehicle) + Offer JSON-LD
 *
 * Variables de entrada (definir ANTES de incluir este archivo):
 *   $_svVehicle  (array)  — fila de Automarket_Invs_web tal como la devuelve la BD.
 *
 * Reglas:
 *  - Requiere al menos Make y Model con valor → si no, return sin imprimir.
 *  - Omite campos vacíos, nulos o numéricos cero (no emite claves sin datos).
 *  - mileageFromOdometer solo se emite si Km > 0.
 *  - offers solo se emite si Price > 0.
 *  - availability: si no existe columna Status o Status != 'Sold' → InStock.
 *  - image: foto_impel tiene prioridad; luego Photo; si ambas vacías no se emite.
 *  - url: construida desde REQUEST_URI + dominio canónico.
 *  - No construye JSON manualmente: usa json_encode().
 *  - Variables internas con prefijo $_sv*.
 *  - unset() al finalizar.
 *
 * Uso:
 *   $_svVehicle = $vehicle;
 *   require __DIR__ . '/../includes/schema-vehicle.php';
 */

$_svVehicle = $_svVehicle ?? [];

// ── Guardia mínima ────────────────────────────────────────────────────────────
$_svMake  = trim((string)($_svVehicle['Make']  ?? ''));
$_svModel = trim((string)($_svVehicle['Model'] ?? ''));

if ($_svMake === '' || $_svModel === '') {
    unset($_svVehicle, $_svMake, $_svModel);
    return;
}

// ── Campos opcionales ─────────────────────────────────────────────────────────
$_svYear         = trim((string)($_svVehicle['Year']         ?? ''));
$_svTrim         = trim((string)($_svVehicle['Trim']         ?? ''));
$_svTransmission = trim((string)($_svVehicle['Transmission'] ?? ''));
$_svFuel         = trim((string)($_svVehicle['Fuel']         ?? ''));
$_svColor        = trim((string)($_svVehicle['Color']        ?? ''));
$_svKm           = (float)($_svVehicle['Km'] ?? 0);
$_svPrice        = (float)($_svVehicle['Price'] ?? 0);
$_svStatus       = trim((string)($_svVehicle['Status']       ?? ''));
$_svPlate        = trim((string)($_svVehicle['LicensePlate'] ?? ''));

// Nombre legible del vehículo
$_svNameParts = array_filter([$_svMake, $_svModel, $_svYear, $_svTrim]);
$_svName      = implode(' ', $_svNameParts);

// Imagen: foto_impel > Photo > sin imagen
$_svImage = '';
if (!empty($_svVehicle['foto_impel'])) {
    $_svImage = trim((string)$_svVehicle['foto_impel']);
} elseif (!empty($_svVehicle['Photo'])) {
    $_svImage = trim((string)$_svVehicle['Photo']);
}

// URL canónica de la ficha (amigable si está disponible; legacy si no)
if (!empty($_svFriendlyUrl)) {
    $_svUrl = 'https://www.automarket.com.pa' . $_svFriendlyUrl;
} else {
    $_svUrl = 'https://www.automarket.com.pa' . ($_SERVER['REQUEST_URI'] ?? '/detalle.php');
}

// Disponibilidad
$_svAvailability = (strtolower($_svStatus) === 'sold')
    ? 'https://schema.org/OutOfStock'
    : 'https://schema.org/InStock';

// ── Construir el schema ───────────────────────────────────────────────────────
$_svSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'Car',
    'name'     => $_svName,
    'brand'    => [
        '@type' => 'Brand',
        'name'  => $_svMake,
    ],
    'model'    => $_svModel,
    'url'      => $_svUrl,
];

// Campos opcionales: solo se agregan si tienen valor
if ($_svYear !== '') {
    $_svSchema['vehicleModelDate'] = $_svYear;
}
if ($_svTrim !== '') {
    $_svSchema['vehicleConfiguration'] = $_svTrim;
}
if ($_svTransmission !== '') {
    $_svSchema['vehicleTransmission'] = $_svTransmission;
}
if ($_svFuel !== '') {
    $_svSchema['fuelType'] = $_svFuel;
}
if ($_svColor !== '') {
    $_svSchema['color'] = $_svColor;
}
if ($_svImage !== '') {
    $_svSchema['image'] = $_svImage;
}
if ($_svKm > 0) {
    $_svSchema['mileageFromOdometer'] = [
        '@type'    => 'QuantitativeValue',
        'value'    => (int)$_svKm,
        'unitCode' => 'KMT',          // kilómetros (código UN/CEFACT)
    ];
}
if ($_svPlate !== '') {
    $_svSchema['vehicleIdentificationNumber'] = $_svPlate;
}

// Offer: solo si hay precio
if ($_svPrice > 0) {
    $_svSchema['offers'] = [
        '@type'        => 'Offer',
        'price'        => $_svPrice,
        'priceCurrency'=> 'USD',
        'availability' => $_svAvailability,
        'url'          => $_svUrl,
    ];
}

// ── Emitir JSON-LD ────────────────────────────────────────────────────────────
$_svJson = json_encode($_svSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo '<script type="application/ld+json">' . "\n" . $_svJson . "\n" . '</script>' . "\n";

// ── Limpiar scope ─────────────────────────────────────────────────────────────
unset(
    $_svVehicle, $_svMake, $_svModel, $_svYear, $_svTrim,
    $_svTransmission, $_svFuel, $_svColor, $_svKm, $_svPrice,
    $_svStatus, $_svPlate, $_svNameParts, $_svName, $_svImage,
    $_svUrl, $_svAvailability, $_svSchema, $_svJson, $_svFriendlyUrl
);
