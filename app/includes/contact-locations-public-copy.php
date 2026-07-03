<?php
/**
 * Textos públicos Contactos / Sucursales — lectura CMS con fallback (AM-CMS-5B4).
 */

/**
 * @param array<string, mixed> $data
 */
function contact_locations_public_copy(array $data, string $key, string $fallback): string
{
    $val = trim((string) ($data[$key] ?? ''));

    return $val !== '' ? $val : $fallback;
}

/**
 * @return array{title: string, subtitle: string, cta_title: string, cta_text: string, cta_button: string}
 */
function rac_sucursales_page_defaults(): array
{
    return [
        'title'      => 'Nuestras Sucursales',
        'subtitle'   => 'Encuentra las sucursales de Automarket Rent a Car en Panamá: ubicaciones convenientes para facilitar tu experiencia.',
        'cta_title'  => '¡Alquila tu vehículo ahora!',
        'cta_text'   => '¡Aprovecha nuestras ofertas por tiempo limitado y vive experiencias increíbles!',
        'cta_button' => 'Reserva Ya',
    ];
}

/**
 * @param array<string, mixed> $homepage
 *
 * @return array{title: string, subtitle: string, cta_title: string, cta_text: string, cta_button: string}
 */
function rac_sucursales_page_copy(array $homepage): array
{
    $defaults = rac_sucursales_page_defaults();
    $page = is_array($homepage['sucursales_page'] ?? null) ? $homepage['sucursales_page'] : [];

    return [
        'title'      => contact_locations_public_copy($page, 'title', $defaults['title']),
        'subtitle'   => contact_locations_public_copy($page, 'subtitle', $defaults['subtitle']),
        'cta_title'  => contact_locations_public_copy($page, 'cta_title', $defaults['cta_title']),
        'cta_text'   => contact_locations_public_copy($page, 'cta_text', $defaults['cta_text']),
        'cta_button' => contact_locations_public_copy($page, 'cta_button', $defaults['cta_button']),
    ];
}

/**
 * @return array{title: string, intro: string}
 */
function semi_contact_page_defaults(): array
{
    return [
        'title' => 'Contacto',
        'intro' => 'Gracias por escribirnos. Por favor llena el formulario y pronto te responderemos.',
    ];
}

/**
 * @param array<string, mixed> $seminuevos
 *
 * @return array{title: string, intro: string, cta_title: string, cta_text: string, cta_button: string}
 */
function semi_contact_page_copy(array $seminuevos): array
{
    $defaults = semi_contact_page_defaults();
    $page = is_array($seminuevos['contact_page'] ?? null) ? $seminuevos['contact_page'] : [];

    return [
        'title'      => contact_locations_public_copy($page, 'title', $defaults['title']),
        'intro'      => contact_locations_public_copy($page, 'intro', $defaults['intro']),
        'cta_title'  => contact_locations_public_copy($page, 'cta_title', '¡Compra tu seminuevo!'),
        'cta_text'   => contact_locations_public_copy($page, 'cta_text', 'Un seminuevo es la mejor forma de estrenar sin pagar de más'),
        'cta_button' => contact_locations_public_copy($page, 'cta_button', 'Cotiza tu Vehículo'),
    ];
}

/**
 * @return array{title: string, subtitle: string, cta_title: string, cta_text: string, cta_button: string}
 */
function renting_sucursales_page_defaults(): array
{
    return [
        'title'      => 'Nuestras Sucursales',
        'subtitle'   => 'Encuentra las sucursales de Automarket Renting en Panamá.',
        'cta_title'  => 'Cotiza tu plan de Renting',
        'cta_text'   => 'Tu auto nuevo, una cuota mensual con todo incluido. Cobertura en todo el país.',
        'cta_button' => 'Cotizar plan',
    ];
}

/**
 * @param array<string, mixed> $renting
 *
 * @return array{title: string, subtitle: string, cta_title: string, cta_text: string, cta_button: string}
 */
function renting_sucursales_page_copy(array $renting): array
{
    $defaults = renting_sucursales_page_defaults();
    $root = [
        'title'      => $renting['sucursales_title'] ?? '',
        'subtitle'   => $renting['sucursales_subtitle'] ?? '',
        'cta_title'  => $renting['sucursales_cta_title'] ?? '',
        'cta_text'   => $renting['sucursales_cta_text'] ?? '',
        'cta_button' => $renting['sucursales_cta_button'] ?? '',
    ];

    return [
        'title'      => contact_locations_public_copy($root, 'title', $defaults['title']),
        'subtitle'   => contact_locations_public_copy($root, 'subtitle', $defaults['subtitle']),
        'cta_title'  => contact_locations_public_copy($root, 'cta_title', $defaults['cta_title']),
        'cta_text'   => contact_locations_public_copy($root, 'cta_text', $defaults['cta_text']),
        'cta_button' => contact_locations_public_copy($root, 'cta_button', $defaults['cta_button']),
    ];
}

/**
 * @return array{title: string, subtitle: string}
 */
function sucursales_grupo_page_defaults(): array
{
    return [
        'title'    => 'Nuestras sucursales',
        'subtitle' => 'Ubicaciones de Rent A Car, Venta de Autos, Leasing, Renting y Taller a nivel nacional.',
    ];
}

/**
 * @param array<string, mixed> $global
 *
 * @return array{title: string, subtitle: string}
 */
function sucursales_grupo_page_copy(array $global): array
{
    $defaults = sucursales_grupo_page_defaults();
    $page = is_array($global['sucursales_grupo_page'] ?? null) ? $global['sucursales_grupo_page'] : [];

    return [
        'title'    => contact_locations_public_copy($page, 'title', $defaults['title']),
        'subtitle' => contact_locations_public_copy($page, 'subtitle', $defaults['subtitle']),
    ];
}

/**
 * Cabecera de contactos en contactos.php (rama genérica).
 *
 * @param array<string, mixed> $siteData
 *
 * @return array{title: string, intro: string}
 */
function contact_page_header_copy(string $unit, array $siteData, string $fallbackTitle, string $fallbackIntro): array
{
    if ($unit === 'leasing') {
        $contact = is_array($siteData['leasing']['contact'] ?? null) ? $siteData['leasing']['contact'] : [];

        return [
            'title' => contact_locations_public_copy($contact, 'page_title', $fallbackTitle),
            'intro' => contact_locations_public_copy($contact, 'intro_text', $fallbackIntro),
        ];
    }

    if ($unit === 'renting') {
        $contact = is_array($siteData['renting']['contact'] ?? null) ? $siteData['renting']['contact'] : [];

        return [
            'title' => contact_locations_public_copy($contact, 'page_title', $fallbackTitle),
            'intro' => contact_locations_public_copy($contact, 'intro_text', $fallbackIntro),
        ];
    }

    $page = is_array(($siteData['homepage']['contact_page'] ?? null)) ? $siteData['homepage']['contact_page'] : [];

    return [
        'title' => contact_locations_public_copy($page, 'title', $fallbackTitle),
        'intro' => contact_locations_public_copy($page, 'intro', $fallbackIntro),
    ];
}
