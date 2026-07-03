<?php
/**
 * Textos públicos Rent A Car — lectura CMS con fallback (AM-CMS-5B1).
 */

/**
 * @param array<string, mixed> $data
 */
function rentacar_public_copy(array $data, string $key, string $fallback): string
{
    $val = trim((string) ($data[$key] ?? ''));

    return $val !== '' ? $val : $fallback;
}

/**
 * Defaults ES alineados al copy actual en producción (no inventar textos nuevos).
 *
 * @return array<string, string>
 */
function rentacar_fleet_section_defaults(): array
{
    return [
        'eyebrow'  => 'Categorías',
        'title'    => 'Descubre Nuestra Flota de Alquiler',
        'subtitle' => 'Selecciona la categoría que mejor se adapte a tus necesidades de viaje.',
        'cta_text' => 'Ver todas las categorías',
    ];
}

/**
 * @return array<string, string>
 */
function rentacar_search_results_defaults(): array
{
    return [
        'title' => 'Vehículos Disponibles',
    ];
}

/**
 * @return array<string, string>
 */
function rentacar_opiniones_section_defaults(): array
{
    return [
        'title'    => 'Opiniones de Nuestros Clientes',
        'subtitle' => 'Conoce la experiencia de quienes viajan y confían en nosotros todos los días.',
    ];
}

/**
 * @param array<string, mixed> $homepage
 *
 * @return array<string, string>
 */
function rentacar_fleet_section_copy(array $homepage): array
{
    $defaults = rentacar_fleet_section_defaults();
    $section = is_array($homepage['fleet_section'] ?? null) ? $homepage['fleet_section'] : [];

    return [
        'eyebrow'  => rentacar_public_copy($section, 'eyebrow', $defaults['eyebrow']),
        'title'    => rentacar_public_copy($section, 'title', $defaults['title']),
        'subtitle' => rentacar_public_copy($section, 'subtitle', $defaults['subtitle']),
        'cta_text' => rentacar_public_copy($section, 'cta_text', $defaults['cta_text']),
    ];
}

/**
 * @param array<string, mixed> $homepage
 */
function rentacar_search_results_title(array $homepage): string
{
    $defaults = rentacar_search_results_defaults();
    $section = is_array($homepage['search_results'] ?? null) ? $homepage['search_results'] : [];

    return rentacar_public_copy($section, 'title', $defaults['title']);
}

/**
 * @param array<string, mixed> $homepage
 *
 * @return array<string, string>
 */
function rentacar_opiniones_section_copy(array $homepage): array
{
    $defaults = rentacar_opiniones_section_defaults();
    $section = is_array($homepage['opiniones_section'] ?? null) ? $homepage['opiniones_section'] : [];

    return [
        'title'    => rentacar_public_copy($section, 'title', $defaults['title']),
        'subtitle' => rentacar_public_copy($section, 'subtitle', $defaults['subtitle']),
    ];
}
