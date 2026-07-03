<?php
/**
 * Textos públicos Seminuevos — lectura CMS con fallback (AM-CMS-5B2).
 */

/**
 * @param array<string, mixed> $data
 */
function seminuevos_public_copy(array $data, string $key, string $fallback): string
{
    $val = trim((string) ($data[$key] ?? ''));

    return $val !== '' ? $val : $fallback;
}

/**
 * Defaults ES alineados al copy actual en producción (no inventar textos nuevos).
 *
 * @return array<string, string>
 */
function seminuevos_public_copy_defaults(): array
{
    return [
        'hero_title'              => 'Autos Seminuevos en Venta en Panamá',
        'hero_subtitle'           => 'Todos nuestros autos han pasado por inspección de 150 puntos.',
        'inventory_eyebrow'       => 'Seminuevos',
        'anatomy_eyebrow'         => 'Garantía y Calidad',
        'anatomy_title'           => 'Anatomía de tu Seminuevo',
        'anatomy_subtitle'        => 'Pasa el cursor por los puntos interactivos del vehículo para descubrir por qué comprar en Automarket es tu mejor opción.',
        'anatomy_image_alt'       => 'Anatomía del Vehículo',
        'inventory_page_title'    => '',
        'inventory_page_subtitle' => '',
    ];
}

/**
 * @param array<string, mixed> $data
 */
function seminuevos_inventory_page_title(array $data): string
{
    $cms = trim((string) ($data['inventory_page_title'] ?? ''));
    if ($cms !== '') {
        return $cms;
    }

    return function_exists('t') ? t('inventory.title') : 'Inventario Seminuevos';
}

/**
 * @param array<string, mixed> $data
 */
function seminuevos_inventory_page_subtitle(array $data): string
{
    $cms = trim((string) ($data['inventory_page_subtitle'] ?? ''));
    if ($cms !== '') {
        return $cms;
    }

    return function_exists('t') ? t('inventory.subtitle') : 'Encuentra tu próximo auto de calidad garantizada en Automarket';
}
