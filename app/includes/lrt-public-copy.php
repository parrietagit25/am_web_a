<?php
/**
 * Textos públicos Leasing / Renting / Taller — lectura CMS con fallback (AM-CMS-5B3).
 */

/**
 * @param array<string, mixed> $data
 */
function lrt_public_copy(array $data, string $key, string $fallback): string
{
    $val = trim((string) ($data[$key] ?? ''));

    return $val !== '' ? $val : $fallback;
}

/**
 * @return list<array{title: string, text: string}>
 */
function leasing_advantages_default_cards(): array
{
    return [
        [
            'title' => '100% Deducible',
            'text'  => 'La cuota mensual del leasing operativo es un gasto operativo totalmente deducible del Impuesto sobre la Renta.',
        ],
        [
            'title' => 'Mantenimiento Incluido',
            'text'  => 'Nos encargamos del mantenimiento preventivo, correctivo, llantas e inspecciones de tus unidades.',
        ],
        [
            'title' => 'Renovación Constante',
            'text'  => 'Mantén una flota moderna y segura, renovando tus vehículos al finalizar tu contrato de 36 o 48 meses.',
        ],
    ];
}

/**
 * @return array{eyebrow: string, title: string, subtitle: string, cards: list<array{title: string, text: string}>}
 */
function leasing_advantages_defaults(): array
{
    return [
        'eyebrow'  => 'Leasing Operativo',
        'title'    => 'Ventajas Corporativas',
        'subtitle' => 'Ahorra costos y enfoca tus recursos en el núcleo de tu negocio.',
        'cards'    => leasing_advantages_default_cards(),
    ];
}

/**
 * @param array<string, mixed> $leasing
 *
 * @return array{eyebrow: string, title: string, subtitle: string, cards: list<array{title: string, text: string}>}
 */
function leasing_advantages_copy(array $leasing): array
{
    $defaults = leasing_advantages_defaults();
    $section = is_array($leasing['advantages'] ?? null) ? $leasing['advantages'] : [];
    $cardsIn = is_array($section['cards'] ?? null) ? $section['cards'] : [];
    $cards = [];

    foreach ($defaults['cards'] as $i => $defaultCard) {
        $cardIn = is_array($cardsIn[$i] ?? null) ? $cardsIn[$i] : [];
        $cards[] = [
            'title' => lrt_public_copy($cardIn, 'title', $defaultCard['title']),
            'text'  => lrt_public_copy($cardIn, 'text', $defaultCard['text']),
        ];
    }

    return [
        'eyebrow'  => lrt_public_copy($section, 'eyebrow', $defaults['eyebrow']),
        'title'    => lrt_public_copy($section, 'title', $defaults['title']),
        'subtitle' => lrt_public_copy($section, 'subtitle', $defaults['subtitle']),
        'cards'    => $cards,
    ];
}

/**
 * @param array<string, mixed> $leasing
 */
function leasing_opinions_title(array $leasing): string
{
    return lrt_public_copy(
        $leasing,
        'opinions_title',
        'Lo que opinan nuestros clientes de nosotros...'
    );
}

/**
 * @param array<string, mixed> $leasing
 */
function leasing_hero_cta_text(array $leasing): string
{
    return lrt_public_copy($leasing, 'hero_cta_text', 'Conocer Soluciones');
}

/**
 * @param array<string, mixed> $leasing
 *
 * @return array{badge: string, slogan: string, side_text: string}
 */
function leasing_lead_side_copy(array $leasing): array
{
    return [
        'badge'     => lrt_public_copy($leasing, 'lead_badge', 'Leasing Corporativo'),
        'slogan'    => lrt_public_copy($leasing, 'lead_slogan', 'MANTÉN A TU EMPRESA SIEMPRE OPERATIVA'),
        'side_text' => lrt_public_copy(
            $leasing,
            'lead_side_text',
            'Maximiza la productividad de tu flota reduciendo tiempos muertos por reparaciones o colisiones. Nos encargamos de toda la gestión técnica y operativa.'
        ),
    ];
}

/**
 * @param array<string, mixed> $renting
 */
function renting_hero_cta_text(array $renting): string
{
    return lrt_public_copy($renting, 'hero_cta_text', 'Cotizar ahora');
}

/**
 * @param array<string, mixed> $taller
 */
function taller_hero_cta_text(array $taller): string
{
    return lrt_public_copy($taller, 'hero_cta_text', 'Ver Servicios');
}

/**
 * @return array{title: string, subtitle: string}
 */
function leasing_fleet_page_defaults(): array
{
    return [
        'title'    => 'Descubre Nuestra Flota',
        'subtitle' => 'Vehículos disponibles para leasing operativo corporativo en Panamá.',
    ];
}

/**
 * @param array<string, mixed> $leasing
 *
 * @return array{title: string, subtitle: string}
 */
function leasing_fleet_page_copy(array $leasing): array
{
    $defaults = leasing_fleet_page_defaults();

    return [
        'title'    => lrt_public_copy($leasing, 'fleet_page_title', $defaults['title']),
        'subtitle' => lrt_public_copy($leasing, 'fleet_page_subtitle', $defaults['subtitle']),
    ];
}
