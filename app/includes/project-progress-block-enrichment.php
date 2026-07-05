<?php
/**
 * Enriquecimiento de bloques del tablero de avances para modales ejecutivos (AM-DASH-1B).
 * Completa campos faltantes sin inventar rutas inexistentes.
 */
declare(strict_types=1);

/** @return list<string> */
function ppb_string_list(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    foreach ($value as $item) {
        $s = trim((string) $item);
        if ($s !== '') {
            $out[] = $s;
        }
    }

    return $out;
}

/** @return list<array{label: string, url: string}> */
function ppb_link_list(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    foreach ($value as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = trim((string) ($item['label'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));
        if ($label === '' || $url === '') {
            continue;
        }
        $out[] = ['label' => $label, 'url' => $url];
    }

    return $out;
}

function ppb_category(array $bloque): string
{
    $area = strtolower((string) ($bloque['area'] ?? ''));
    $codigo = strtoupper((string) ($bloque['codigo'] ?? ''));

    if (str_contains($area, 'rent a car') || str_starts_with($codigo, 'AM-RAC')) {
        return 'integracion';
    }
    if (str_contains($area, 'seo técnico') || str_starts_with($codigo, 'AM-SEO') || str_starts_with($codigo, 'MICROFIX') || str_starts_with($codigo, 'AM-INV')) {
        return 'seo';
    }
    if (str_contains($area, 'infraestructura') || str_starts_with($codigo, 'DB-') || str_starts_with($codigo, 'HOTFIX') || str_contains($codigo, 'DASH')) {
        return 'infra';
    }
    if (str_contains($area, 'cms')) {
        return 'cms';
    }
    if (str_contains($area, 'contenido') || str_contains($area, 'aeo') || str_contains($area, 'geo') || str_starts_with($codigo, 'AM-CONT') || str_starts_with($codigo, 'AM-AIO') || str_starts_with($codigo, 'AM-NEG')) {
        return 'contenido';
    }
    if (str_contains($area, 'ux')) {
        return 'ux';
    }

    return 'general';
}

/** @return list<string> */
function ppb_visibility_badges(array $bloque, string $category): array
{
    $badges = [];
    $type = (string) ($bloque['visibility_type'] ?? '');

    if ($type === 'visible_admin_publico_tecnico') {
        $badges = ['Visible en web', 'Administrable', 'Integración'];
    } elseif ($type === 'visible_admin_tecnico_no_publico') {
        $badges = ['Administrable', 'Técnico interno'];
    } elseif ($category === 'seo') {
        $badges = ['SEO técnico', 'Visible en web'];
    } elseif ($category === 'infra') {
        $badges = ['Infraestructura', 'Técnico interno'];
    } elseif ($category === 'cms') {
        $badges = ['Administrable', 'Visible en web'];
    } elseif ($category === 'contenido') {
        $badges = ['Visible en web', 'Administrable'];
    } elseif ($category === 'integracion') {
        $badges = ['Integración', 'Administrable'];
    } elseif ($category === 'ux') {
        $badges = ['Visible en web', 'UX / conversión'];
    } else {
        $badges = ['Seguimiento de entregable'];
    }

    return $badges;
}

/** @return array<string, mixed> */
function ppb_codigo_overrides(): array
{
    return [
        'AM-SEO-3D1' => [
            'public_locations' => [
                ['label' => 'Sitemap público', 'url' => '/sitemap.php'],
            ],
            'que_se_hizo' => 'Sitemap dinámico, hreflang y schemas base por unidad de negocio.',
            'technical_locations' => [
                'Metadatos y canonical en páginas públicas',
                'Schemas JSON-LD por unidad',
            ],
        ],
        'MICROFIX-SITEMAP' => [
            'public_locations' => [['label' => 'Sitemap público', 'url' => '/sitemap.php']],
            'que_se_hizo' => 'Sitemap accesible para crawlers sin PHPSESSID ni Cache-Control no-store.',
        ],
        'AM-SEO-3D2-A' => [
            'public_locations' => [
                ['label' => 'Sitemap — fichas vehículo', 'url' => '/sitemap.php'],
                ['label' => 'Inventario seminuevos', 'url' => '/inventario.php'],
            ],
            'que_se_hizo' => 'Inclusión de fichas de vehículo en sitemap con URLs amigables /autos/.',
        ],
        'AM-SEO-3C' => [
            'public_locations' => [
                ['label' => 'Sucursales públicas', 'url' => '/sucursales-grupo.php'],
                ['label' => 'Detalle sucursal (ejemplo)', 'url' => '/sucursal/tumba-muerto'],
            ],
            'que_se_hizo' => 'Maestro de sucursales con LocalBusiness e ItemList en schema.',
        ],
        'AM-SEO-3G-A' => [
            'public_locations' => [['label' => 'URL amigable sucursal', 'url' => '/sucursal/tumba-muerto']],
            'que_se_hizo' => 'Rutas /sucursal/{slug} con dual-serve nginx.',
        ],
        'AM-SEO-3G-B' => [
            'public_locations' => [
                ['label' => 'Sitemap sucursales', 'url' => '/sitemap.php'],
                ['label' => 'Detalle sucursal', 'url' => '/sucursal/tumba-muerto'],
            ],
            'que_se_hizo' => 'Canonical friendly, sitemap y schema en sucursales.',
        ],
        'AM-SEO-4A' => [
            'public_locations' => [
                ['label' => 'Detalle vehículo (canonical)', 'url' => '/autos/toyota-hilux-2025/eo5144'],
                ['label' => 'Sostenibilidad', 'url' => '/sostenibilidad.php'],
            ],
            'que_se_hizo' => 'Fallback canonical www, titles únicos, H1 en detalle, FAQPage condicional, sostenibilidad sin AutoRental.',
        ],
        'AM-INV-4B' => [
            'public_locations' => [
                ['label' => 'URL amigable vehículo', 'url' => '/autos/toyota-hilux-2025/eo5144'],
                ['label' => 'Legacy detalle (compatible)', 'url' => '/detalle.php?placa=EO5144'],
            ],
            'que_se_hizo' => 'Rutas /autos/{slug}/{placa} vía nginx; legacy /detalle.php?placa= compatible; sitemap y canonical alineados.',
        ],
        'AM-DASH-0A' => [
            'admin_locations' => [['label' => 'Tablero test (interno)', 'url' => '/avance-automarket.php']],
            'public_web_text' => 'Tablero interno de seguimiento; accesible solo en entorno test/localhost, no indexado.',
        ],
        'HOTFIX-TEL-DASH' => [
            'admin_locations' => [['label' => 'Generales → Telemetría visitantes', 'url' => '/admin/']],
            'admin_note' => 'Panel administrativo → Generales → Telemetría visitantes.',
            'que_se_hizo' => 'Corrección del dashboard de telemetría de visitantes en admin.',
        ],
        'HOTFIX-TEL-INGEST' => [
            'que_se_hizo' => 'Corrección del ingest de telemetría y recepción de eventos de visitantes.',
            'public_web_text' => 'Soporte interno de analítica; no modifica pantallas públicas visibles al usuario final.',
        ],
        'DB-INV-SYNC' => [
            'que_se_hizo' => 'Sincronización de inventario seminuevos con MariaDB y pipeline operativo.',
            'public_locations' => [
                ['label' => 'Inventario público', 'url' => '/inventario.php'],
                ['label' => 'Detalle vehículo', 'url' => '/inventario.php'],
            ],
        ],
        'AM-CMS-3B1' => [
            'admin_note' => 'Panel administrativo → Rent A Car → secciones CMS (Principal, contenidos, textos).',
            'public_locations' => [['label' => 'Rent A Car', 'url' => '/rent-a-car.php']],
        ],
        'AM-CMS-5B1' => [
            'admin_note' => 'Panel administrativo → Rent A Car → Principal (Hero) y textos visibles.',
            'public_locations' => [
                ['label' => 'Rent A Car', 'url' => '/rent-a-car.php'],
                ['label' => 'Flota', 'url' => '/flota.php'],
            ],
        ],
        'AM-CMS-5B2' => [
            'admin_note' => 'Panel administrativo → Venta de Autos → Principal (Banner y Anatomía).',
            'public_locations' => [
                ['label' => 'Venta de autos', 'url' => '/venta-autos.php'],
                ['label' => 'Inventario', 'url' => '/inventario.php'],
            ],
        ],
        'AM-CMS-5B3' => [
            'admin_note' => 'Panel administrativo → Leasing / Renting / Taller → textos visibles.',
            'public_locations' => [
                ['label' => 'Leasing', 'url' => '/leasing.php'],
                ['label' => 'Renting', 'url' => '/renting.php'],
                ['label' => 'Taller', 'url' => '/taller.php'],
            ],
        ],
        'AM-CMS-5B4' => [
            'admin_note' => 'Panel administrativo → contactos y sucursales por unidad.',
            'public_locations' => [
                ['label' => 'Contactos RAC', 'url' => '/contactos.php?unit=rentacar'],
                ['label' => 'Sucursales RAC', 'url' => '/sucursales.php'],
            ],
        ],
        'AM-CMS-5A-B3' => [
            'admin_note' => 'Panel administrativo → Generales → Pie de página → pestaña Columnas.',
            'public_locations' => [['label' => 'Footer en cualquier página pública', 'url' => '/rent-a-car.php']],
        ],
        'AM-CONT-4C-A' => [
            'admin_note' => 'Panel administrativo → contenidos por unidad (blog, noticias, novedades).',
            'public_locations' => [
                ['label' => 'Blog', 'url' => '/blog.php'],
                ['label' => 'Noticias', 'url' => '/noticias.php'],
            ],
        ],
        'AM-CONT-4C-B3' => [
            'public_locations' => [
                ['label' => 'URLs limpias blog', 'url' => '/blog.php'],
                ['label' => 'Sitemap', 'url' => '/sitemap.php'],
            ],
            'que_se_hizo' => 'Migración a URLs amigables /blog/{unit}/{type}/{slug} con redirects 301 desde legacy.',
        ],
        'AM-CONT-6D' => [
            'public_locations' => [
                ['label' => 'FAQ institucional', 'url' => '/pagina-institucional.php?p=faq'],
            ],
            'admin_note' => 'Panel administrativo → páginas institucionales y FAQ por unidad en CMS.',
        ],
        'AM-NEG-7A' => [
            'public_locations' => [['label' => 'Trabaja con nosotros', 'url' => '/trabaja-con-nosotros.php']],
            'admin_note' => 'Contenido editable vía CMS global e institucional según configuración vigente.',
        ],
        'AM-NEG-7B' => [
            'public_locations' => [['label' => 'Trabaja con nosotros — CTA Konzerta', 'url' => '/trabaja-con-nosotros.php']],
        ],
        'AM-BUGS-4D-A' => [
            'public_locations' => [
                ['label' => 'Venta de autos — sucursales', 'url' => '/venta-autos.php'],
                ['label' => 'Seminuevos sucursales', 'url' => '/seminuevos-sucursales.php'],
            ],
            'que_se_hizo' => 'Copyright sin año duplicado, redes coherentes, menú Seminuevos con sucursales, sección sucursales en venta-autos.',
        ],
        'AM-AIO-6A' => [
            'public_locations' => [['label' => 'Cualquier página — schema Organization', 'url' => '/rent-a-car.php']],
            'que_se_hizo' => 'Organization global JSON-LD; sameAs desde redes footer; parentOrganization en unidades y sucursales.',
            'technical_locations' => ['Ver código fuente HTML → script type application/ld+json Organization'],
        ],
        'AM-DASH-1A' => [
            'public_web_text' => 'Este entregable se visualiza dentro del administrativo en Generales → Dashboard de avances.',
            'que_se_hizo' => 'Tablero de avances en admin con tarjetas, filtros y modales detallados por bloque.',
        ],
        'AM-RAC-PAY-POWERTRANZ' => [
            'public_locations_note' => 'No está visible al público por seguridad. No está conectado aún a reservas reales ni al checkout público.',
            'que_se_hizo' => 'Cliente Powertranz aislado; alive OK; init HPP OK (ISO SP4); RedirectData y SpiToken presentes; tabla rac_powertranz_payments; endpoints alive/init/return/status; modo diagnóstico activo; completePayment automático bloqueado.',
        ],
    ];
}

function ppb_category_public_text(string $category): string
{
    return match ($category) {
        'seo' => 'Este cambio se valida en la estructura pública del sitio: /sitemap.php, código fuente HTML de páginas públicas, metadatos, canonical, hreflang y schema según corresponda.',
        'infra' => 'Este cambio no tiene pantalla pública dedicada; corresponde a infraestructura interna, endpoints, cron, seguridad, despliegue o soporte técnico del sitio.',
        'cms' => 'Este cambio se administra desde el panel administrativo en la sección CMS de la unidad correspondiente y se refleja en las páginas públicas asociadas.',
        'integracion' => 'Este cambio corresponde a una integración técnica. Se valida mediante endpoints, logs sanitizados, base de datos o pantalla de prueba interna.',
        'contenido' => 'Este cambio se refleja en páginas públicas de contenido o se administra desde el CMS según la unidad correspondiente.',
        'ux' => 'Este cambio es visible en la experiencia pública del sitio: navegación, footer, menús, CTAs o páginas de conversión según el bloque.',
        default => 'El entregable forma parte del sitio web Automarket y se valida en producción según el tipo de cambio implementado.',
    };
}

function ppb_category_admin_text(string $category): string
{
    return match ($category) {
        'seo' => 'No requiere administración diaria por Mercadeo; forma parte del SEO técnico centralizado del sitio. Ajustes globales en Admin → Generales → SEO cuando aplica.',
        'infra' => 'Operación y validación desde herramientas internas, admin (Telemetría, Dashboard de avances) o despliegue según el bloque.',
        'cms' => 'Panel administrativo → sección CMS de la unidad de negocio correspondiente (Rent A Car, Venta de Autos, Leasing, Renting, Taller).',
        'integracion' => 'Panel administrativo → Rent A Car → pantallas de integración o prueba técnica asociadas.',
        'contenido' => 'Panel administrativo → CMS de la unidad o páginas institucionales según el tipo de contenido.',
        'ux' => 'Panel administrativo → Generales (Pie de página, configuración global) o CMS de unidad según el cambio.',
        default => 'Panel administrativo → sección correspondiente según el área del entregable.',
    };
}

function ppb_category_validation_items(string $category, array $bloque): array
{
    $items = ppb_string_list($bloque['technical_locations'] ?? null);
    if (!empty($items)) {
        return $items;
    }

    return match ($category) {
        'seo' => [
            'Validación en sitemap público y respuesta HTTP de URLs indexables',
            'Revisión de canonical, title, H1 y schema en código fuente HTML',
        ],
        'infra' => [
            'Validación operativa post-despliegue (smoke tests, logs, HEAD de commit)',
            'Control interno sin impacto directo en pantallas de usuario',
        ],
        'cms' => [
            'Contenido editable en admin y render visible en frontend público',
            'Fallbacks conservadores si el CMS no tiene valor cargado',
        ],
        'integracion' => [
            'Pruebas en endpoints o pantallas internas de integración',
            'Registros sanitizados en base de datos cuando aplica',
        ],
        'contenido' => [
            'Publicación desde CMS y visibilidad en listados/detalle público',
            'Schema y metadatos asociados al tipo de contenido',
        ],
        'ux' => [
            'Validación visual en páginas clave del sitio público',
            'Comportamiento responsive y consistencia de navegación',
        ],
        default => [
            'Validación funcional en entorno test/producción según criterios del bloque',
        ],
    };
}

/** @param array<string, mixed> $bloque @return array<string, mixed> */
function ppb_enrich_block(array $bloque): array
{
    $codigo = strtoupper((string) ($bloque['codigo'] ?? ''));
    $category = ppb_category($bloque);
    $overrides = ppb_codigo_overrides();

    if (isset($overrides[$codigo]) && is_array($overrides[$codigo])) {
        foreach ($overrides[$codigo] as $key => $value) {
            if (!isset($bloque[$key]) || (is_array($value) && empty($bloque[$key]))) {
                $bloque[$key] = $value;
            } elseif (is_string($value) && trim((string) ($bloque[$key] ?? '')) === '') {
                $bloque[$key] = $value;
            }
        }
    }

    if (trim((string) ($bloque['modal_summary'] ?? '')) === '' && trim((string) ($bloque['descripcion'] ?? '')) !== '') {
        $bloque['modal_summary'] = (string) $bloque['descripcion'];
    }

    if (trim((string) ($bloque['que_se_hizo'] ?? '')) === '' && trim((string) ($bloque['descripcion'] ?? '')) !== '') {
        $bloque['que_se_hizo'] = (string) $bloque['descripcion'];
    }

    $evidenceItems = ppb_string_list($bloque['evidence_items'] ?? null);
    if (empty($evidenceItems) && !empty($bloque['evidencia'])) {
        $raw = (string) $bloque['evidencia'];
        $parts = preg_split('/\s*[;|]\s*|\.\s+(?=[A-ZÁÉÍÓÚ0-9])/', $raw) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && strlen($part) > 8) {
                $evidenceItems[] = rtrim($part, '.');
            }
        }
        if (empty($evidenceItems)) {
            $evidenceItems = [$raw];
        }
        $bloque['evidence_items'] = $evidenceItems;
    }

    if (empty(ppb_link_list($bloque['public_locations'] ?? null))) {
        if ($category === 'seo' && empty($bloque['public_web_text'] ?? '')) {
            $bloque['public_locations'] = [
                ['label' => 'Sitemap público', 'url' => '/sitemap.php'],
            ];
        }
    }

    if (trim((string) ($bloque['public_web_text'] ?? '')) === ''
        && trim((string) ($bloque['public_locations_note'] ?? '')) === ''
        && empty(ppb_link_list($bloque['public_locations'] ?? null))) {
        $bloque['public_web_text'] = ppb_category_public_text($category);
    }

    if (trim((string) ($bloque['admin_note'] ?? '')) === '' && empty(ppb_link_list($bloque['admin_locations'] ?? null))) {
        $bloque['admin_note'] = ppb_category_admin_text($category);
    }

    $bloque['validation_items'] = ppb_category_validation_items($category, $bloque);
    $bloque['visibility_badges'] = ppb_visibility_badges($bloque, $category);
    $bloque['block_category'] = $category;
    $bloque['avance_registrado'] = (int) ($bloque['porcentaje_estimado'] ?? 0);

    return $bloque;
}

/** @param list<array<string, mixed>> $bloques @return list<array<string, mixed>> */
function ppb_enrich_all(array $bloques): array
{
    $out = [];
    foreach ($bloques as $bloque) {
        if (!is_array($bloque)) {
            continue;
        }
        $out[] = ppb_enrich_block($bloque);
    }

    return $out;
}
