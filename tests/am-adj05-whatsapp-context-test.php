<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/services/WhatsappContextService.php';

function adj05_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$units = [
    'rentacar' => ['key' => 'rentacar', 'label' => 'Rent A Car'],
    'seminuevos' => ['key' => 'seminuevos', 'label' => 'Venta de Autos'],
    'leasing' => ['key' => 'leasing', 'label' => 'Leasing'],
    'renting' => ['key' => 'renting', 'label' => 'Renting'],
    'taller' => ['key' => 'taller', 'label' => 'Taller'],
    'flotas_especiales' => ['key' => 'flotas_especiales', 'label' => 'Flotas Especiales', 'is_custom' => true],
];
$siteData = [
    'global' => [
        'whatsapp_number' => '50799999999',
        'business_units' => [
            'flotas_especiales' => [
                'label' => 'Flotas Especiales',
                'footer_contact' => [
                    'whatsapp_number' => '+507 6555-1212',
                    'whatsapp_enabled' => true,
                    'whatsapp_message' => 'Hola, deseo una flota especial.',
                ],
            ],
        ],
    ],
    'homepage' => ['contact' => [
        'whatsapp_number' => '+507 6000-1001',
        'whatsapp_enabled' => true,
        'whatsapp_message' => 'Hola, deseo alquilar un auto.',
    ]],
    'seminuevos' => ['footer_contact' => [
        'whatsapp_number' => '(507) 6000-1002',
        'whatsapp_enabled' => true,
        'whatsapp_message' => 'Hola, me interesa un seminuevo.',
    ]],
    'leasing' => ['footer_contact' => [
        'whatsapp_number' => '507-6000-1003',
        'whatsapp_message' => 'Información de Leasing',
    ]],
    'renting' => ['footer_contact' => [
        'whatsapp_number' => '50760001004',
        'whatsapp_enabled' => false,
        'whatsapp_message' => 'Información de Renting',
    ]],
    'taller' => ['footer_contact' => [
        'whatsapp_number' => '50760001005',
        'whatsapp_message' => '',
    ]],
];

$routeExpectations = [
    'rent-a-car.php' => 'rentacar',
    'flota.php' => 'rentacar',
    'resultados.php' => 'rentacar',
    'reservar.php' => 'rentacar',
    'extras.php' => 'rentacar',
    'mi-reserva.php' => 'rentacar',
    'requisitos-alquiler.php' => 'rentacar',
    'terminos-condiciones.php' => 'rentacar',
    'venta-autos.php' => 'seminuevos',
    'inventario.php' => 'seminuevos',
    'detalle.php' => 'seminuevos',
    'financiamiento.php' => 'seminuevos',
    'nuestro-equipo.php' => 'seminuevos',
    'seminuevos-sucursales.php' => 'seminuevos',
    'leasing.php' => 'leasing',
    'leasing-flota.php' => 'leasing',
    'leasing-contactos.php' => 'leasing',
    'renting.php' => 'renting',
    'renting-servicios.php' => 'renting',
    'renting-contactos.php' => 'renting',
    'taller.php' => 'taller',
    'taller-sucursales.php' => 'taller',
];
foreach ($routeExpectations as $script => $expectedUnit) {
    $context = WhatsappContextService::resolve($siteData, $units, $script);
    adj05_assert($context['unit'] === $expectedUnit, "{$script} resolves to {$expectedUnit}");
}

$rac = WhatsappContextService::resolve($siteData, $units, 'rent-a-car.php');
adj05_assert($rac['visible'] === true, 'RAC renders its contextual WhatsApp');
adj05_assert($rac['phone'] === '50760001001', 'RAC number is normalized');
adj05_assert($rac['url'] === 'https://wa.me/50760001001?text=' . rawurlencode('Hola, deseo alquilar un auto.'), 'Message is URL encoded');
adj05_assert($rac['aria_label'] === 'Contactar por WhatsApp con Rent A Car', 'Accessible name identifies unit');

$renting = WhatsappContextService::resolve($siteData, $units, 'renting.php');
adj05_assert($renting['visible'] === false, 'Explicitly disabled unit is hidden');

$taller = WhatsappContextService::resolve($siteData, $units, 'taller.php');
adj05_assert($taller['message'] === 'Hola, deseo información sobre Taller.', 'Empty message uses neutral unit fallback');

$custom = WhatsappContextService::resolve($siteData, $units, 'unidad.php', ['u' => 'flotas_especiales']);
adj05_assert($custom['visible'] === true && $custom['phone'] === '50765551212', 'Custom unit uses isolated contact');
$missingCustom = WhatsappContextService::resolve($siteData, $units, 'unidad.php', ['u' => 'sin_contacto']);
adj05_assert($missingCustom['visible'] === false, 'Unknown custom unit is hidden');

$editorial = WhatsappContextService::resolve($siteData, $units, 'noticia.php', ['unit' => 'seminuevos', 'slug' => 'demo']);
adj05_assert($editorial['visible'] === true && $editorial['unit'] === 'seminuevos', 'Editorial unit metadata is respected');
$legacyEditorial = WhatsappContextService::resolve($siteData, $units, 'noticia.php', ['id' => '12']);
adj05_assert($legacyEditorial['visible'] === false, 'Legacy article without verifiable unit is general');

foreach ([
    'sostenibilidad.php',
    'blog-grupo.php',
    'sucursales-grupo.php',
    'sucursal.php',
    'pagina-institucional.php',
    'trabaja-con-nosotros.php',
    'contacto.php',
] as $generalScript) {
    $context = WhatsappContextService::resolve($siteData, $units, $generalScript);
    adj05_assert($context['visible'] === false, "{$generalScript} does not render WhatsApp");
}

$contactBeforeSelection = WhatsappContextService::resolve($siteData, $units, 'contactos.php');
adj05_assert($contactBeforeSelection['visible'] === false, 'Shared contact page hides before choosing a unit');
$contactSelected = WhatsappContextService::resolve($siteData, $units, 'contactos.php', ['unit' => 'taller']);
adj05_assert($contactSelected['visible'] === true && $contactSelected['unit'] === 'taller', 'Validated contact unit is accepted');
$invalidUnit = WhatsappContextService::resolve($siteData, $units, 'blog.php', ['unit' => '<script>']);
adj05_assert($invalidUnit['visible'] === false, 'Invalid unit parameter is hidden');
$arrayUnit = WhatsappContextService::resolve($siteData, $units, 'blog.php', ['unit' => ['seminuevos']]);
adj05_assert($arrayUnit['visible'] === false, 'Array unit parameter is rejected without coercion');

$corruptData = $siteData;
$corruptData['homepage']['contact']['whatsapp_message'] = '<script>' . str_repeat('x', 220);
$corrupt = WhatsappContextService::resolve($corruptData, $units, 'rent-a-car.php');
adj05_assert($corrupt['visible'] === true, 'Unsafe stored message cannot break the public footer');
adj05_assert($corrupt['message'] === 'Hola, deseo información sobre Rent A Car.', 'Unsafe stored message falls back safely');

foreach (['50760001001', '+507 6000-1001', '(507) 6000-1001', '507-6000-1001'] as $phone) {
    adj05_assert(WhatsappContextService::normalizePhone($phone) === '50760001001', 'Presentation characters normalize safely');
}
foreach (['', '12345', '507ABC1001', '<b>50760001001</b>', 'javascript:50760001001', 'https://wa.me/50760001001', '50760001001 ext 2'] as $phone) {
    adj05_assert(WhatsappContextService::normalizePhone($phone) === '', 'Invalid phone is rejected');
}

adj05_assert(WhatsappContextService::normalizeMessage("¡Hola! ¿Información?\nGracias") === "¡Hola! ¿Información?\nGracias", 'Spanish text and newline are accepted');
foreach (['<b>Hola</b>', '<script>alert(1)</script>', str_repeat('x', 201)] as $message) {
    $rejected = false;
    try {
        WhatsappContextService::normalizeMessage($message);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    adj05_assert($rejected, 'Unsafe or oversized message is rejected');
}

echo "AM-ADJ-05 WhatsApp context tests: OK\n";
