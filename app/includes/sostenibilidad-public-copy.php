<?php
/**
 * Defaults y merge CMS para /sostenibilidad.php (global.sostenibilidad_page).
 * Fallback: si no hay datos en JSON, la página pública sigue usando el markup hardcodeado actual.
 */

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function sostenibilidad_page_defaults(): array
{
    return [
        'active'            => true,
        'seo_title'         => 'Sostenibilidad | Automarket',
        'meta_description'  => 'Programas de sostenibilidad, compensación de carbono y movilidad responsable de Automarket en Panamá.',
        'hero_title'        => "Impulsando una\nmovilidad limpia",
        'hero_subtitle'     => 'Conoce nuestros programas de compensación de huella de carbono, reciclaje y nuestra flota eléctrica.',
        'hero_image_url'    => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?q=80&w=1200&auto=format&fit=crop',
        'hero_cta_label'    => 'Conocer Programas',
        'section_badge'     => 'Compromiso Automarket',
        'section_title'     => 'Nuestros Ejes de Impacto',
        'section_subtitle'  => 'Trabajamos bajo metas claras de sostenibilidad ambiental y responsabilidad corporativa.',
        'body_html'         => '',
        'impact_blocks'     => [
            [
                'icon'  => 'bi-tree-fill',
                'title' => 'Reforestación y CO2',
                'text'  => 'Compensamos las emisiones de CO2 de nuestra flota mediante programas de siembra anual en cuencas hidrográficas de Panamá.',
            ],
            [
                'icon'  => 'bi-ev-front-fill',
                'title' => 'Movilidad Eléctrica',
                'text'  => 'Incrementamos un 15% anual la oferta de autos eléctricos e híbridos enchufables en nuestras divisiones de Rent A Car y Renting.',
            ],
            [
                'icon'  => 'bi-recycle',
                'title' => 'Talleres Ecológicos',
                'text'  => 'Reciclamos el 100% de los aceites usados, baterías gastadas y neumáticos desechados en nuestra red de talleres autorizados.',
            ],
        ],
        'contact_title'     => 'Únete a la Movilidad Verde',
        'contact_intro'     => '¿Quieres incorporar prácticas de movilidad verde en las flotas de tu empresa o registrarte en nuestros voluntariados ecológicos? Escríbenos y entérate de cómo colaborar.',
        'contact_bullets'   => [
            'Alquileres libres de emisiones de carbono',
            'Alianzas con fundaciones ambientales locales',
        ],
        'form_title'        => 'Registro de Interés Ecológico',
    ];
}

/**
 * @param array<string, mixed> $global
 *
 * @return array<string, mixed>
 */
function sostenibilidad_page_copy(array $global): array
{
    $defaults = sostenibilidad_page_defaults();
    $stored = is_array($global['sostenibilidad_page'] ?? null) ? $global['sostenibilidad_page'] : [];

    $merged = $defaults;
    foreach (['active', 'seo_title', 'meta_description', 'hero_title', 'hero_subtitle', 'hero_image_url', 'hero_cta_label', 'section_badge', 'section_title', 'section_subtitle', 'body_html', 'contact_title', 'contact_intro', 'form_title'] as $key) {
        if (array_key_exists($key, $stored) && trim((string) $stored[$key]) !== '') {
            $merged[$key] = $stored[$key];
        } elseif (array_key_exists($key, $stored)) {
            $merged[$key] = $stored[$key];
        }
    }

    if (array_key_exists('active', $stored)) {
        $merged['active'] = ($stored['active'] ?? true) !== false;
    }

    if (isset($stored['contact_bullets']) && is_array($stored['contact_bullets']) && $stored['contact_bullets'] !== []) {
        $merged['contact_bullets'] = array_values(array_filter(array_map('strval', $stored['contact_bullets']), static fn ($v) => trim($v) !== ''));
    }

    if (isset($stored['impact_blocks']) && is_array($stored['impact_blocks']) && $stored['impact_blocks'] !== []) {
        $blocks = [];
        foreach ($stored['impact_blocks'] as $block) {
            if (!is_array($block)) {
                continue;
            }
            $blocks[] = [
                'icon'  => trim((string) ($block['icon'] ?? 'bi-leaf-fill')),
                'title' => trim((string) ($block['title'] ?? '')),
                'text'  => trim((string) ($block['text'] ?? '')),
            ];
        }
        if ($blocks !== []) {
            $merged['impact_blocks'] = $blocks;
        }
    }

    return $merged;
}

/**
 * Indica si existe contenido persistido en JSON (no solo defaults en memoria).
 *
 * @param array<string, mixed> $global
 */
function sostenibilidad_has_stored_cms(array $global): bool
{
    $stored = $global['sostenibilidad_page'] ?? null;

    return is_array($stored) && $stored !== [];
}
