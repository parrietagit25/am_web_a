<?php
/**
 * Helpers Schema.org Organization global (AM-AIO-6A / AM-AIO-6B).
 *
 * sameAs: solo redes del footer pasando FooterService::filterRenderableSocial()
 * (activas, URL http(s), plataforma coherente con label/icon; sin # ni cruces TikTok/otra red).
 * Perfiles GBP/Wikidata/TikTok oficial: pendientes hasta URL en seo.global.verified_same_as_urls[].
 */

/**
 * URL canónica del sitio (sin barra final).
 */
function am_schema_canonical_base(?ContentService $contentService = null): string
{
    $base = 'https://www.automarket.com.pa';
    if ($contentService instanceof ContentService) {
        $seoGlobal = $contentService->get('seo.global', []);
        $fromCms = rtrim(trim((string) ($seoGlobal['canonical_base_url'] ?? '')), '/');
        if ($fromCms !== '' && str_starts_with($fromCms, 'http')) {
            $base = $fromCms;
        }
    }

    return $base;
}

function am_schema_organization_id(string $siteUrl): string
{
    return rtrim($siteUrl, '/') . '/#organization';
}

function am_schema_logo_url(string $siteUrl): string
{
    return rtrim($siteUrl, '/') . '/assets/img/logo.png';
}

/**
 * Slots de perfiles oficiales pendientes de URL verificada por Mercadeo/negocio (AM-AIO-6B).
 * No se emiten en sameAs hasta cargarse en seo.global.verified_same_as_urls (manual/CMS futuro).
 *
 * @return list<array{key: string, label: string, nota: string}>
 */
function am_schema_pending_official_profile_slots(): array
{
    return [
        [
            'key'   => 'google_business_profile',
            'label' => 'Google Business Profile',
            'nota'  => 'URL pública del perfil (maps.app.goo.gl, g.page o business.google). No inventar.',
        ],
        [
            'key'   => 'wikidata',
            'label' => 'Wikidata',
            'nota'  => 'Entidad Q… en wikidata.org, solo si existe y negocio la confirma.',
        ],
        [
            'key'   => 'tiktok_official',
            'label' => 'TikTok oficial',
            'nota'  => 'tiktok.com/@… validado; excluir si label TikTok apunta a otra plataforma.',
        ],
    ];
}

/**
 * URLs adicionales ya verificadas por negocio (opcional en site_data, sin admin en 6B).
 * Clave: seo.global.verified_same_as_urls — lista de strings https. Vacía por defecto.
 *
 * @return list<string>
 */
function am_schema_extra_verified_same_as(?ContentService $contentService = null): array
{
    if (!$contentService instanceof ContentService) {
        return [];
    }

    $raw = $contentService->get('seo.global.verified_same_as_urls', []);
    if (!is_array($raw)) {
        return [];
    }

    $urls = [];
    foreach ($raw as $url) {
        $url = trim((string) $url);
        if ($url !== '' && str_starts_with($url, 'http')) {
            $urls[] = $url;
        }
    }

    return array_values(array_unique($urls));
}

/**
 * URLs sameAs verificables desde redes del footer (sin placeholders ni URLs inválidas).
 *
 * @param array<string, mixed> $siteGlobal
 *
 * @return list<string>
 */
function am_schema_collect_same_as(array $siteGlobal, ?ContentService $contentService = null): array
{
    if (!class_exists('FooterService')) {
        require_once __DIR__ . '/../services/FooterService.php';
    }

    $socialRaw = $siteGlobal['footer']['social'] ?? [];
    if ($socialRaw === [] && $contentService instanceof ContentService) {
        try {
            $footerService = new FooterService($contentService);
            $socialRaw = $footerService->getFooter()['social'] ?? [];
        } catch (\Throwable $e) {
            $socialRaw = [];
        }
    }

    $social = FooterService::filterRenderableSocial(is_array($socialRaw) ? $socialRaw : []);
    $sameAs = [];
    foreach ($social as $entry) {
        $url = trim((string) ($entry['url'] ?? ''));
        if ($url !== '' && str_starts_with($url, 'http')) {
            $sameAs[] = $url;
        }
    }

    foreach (am_schema_extra_verified_same_as($contentService) as $extraUrl) {
        $sameAs[] = $extraUrl;
    }

    return array_values(array_unique($sameAs));
}

/**
 * Bloque Organization global para JSON-LD.
 *
 * @param array<string, mixed> $siteGlobal
 *
 * @return array<string, mixed>
 */
function am_schema_organization_build(array $siteGlobal, ?ContentService $contentService = null): array
{
    $siteUrl = am_schema_canonical_base($contentService);
    $orgId = am_schema_organization_id($siteUrl);

    $seoGlobal = $contentService instanceof ContentService
        ? $contentService->get('seo.global', [])
        : [];
    $orgName = trim((string) ($seoGlobal['site_name'] ?? 'Automarket Panamá'));
    if ($orgName === '') {
        $orgName = 'Automarket Panamá';
    }

    $phoneRaw = trim((string) ($siteGlobal['phone_display'] ?? ''));
    $phone = $phoneRaw !== '' ? '+' . preg_replace('/\D/', '', $phoneRaw) : '';
    $email = trim((string) ($siteGlobal['email'] ?? ''));
    $address = trim((string) ($siteGlobal['address'] ?? ''));

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        '@id'      => $orgId,
        'name'     => $orgName,
        'url'      => rtrim($siteUrl, '/') . '/',
        'logo'     => [
            '@type' => 'ImageObject',
            'url'   => am_schema_logo_url($siteUrl),
        ],
    ];

    if ($phone !== '' && $phone !== '+') {
        $schema['telephone'] = $phone;
    }
    if ($email !== '') {
        $schema['email'] = $email;
    }
    if ($address !== '') {
        $schema['address'] = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $address,
            'addressLocality' => 'Ciudad de Panamá',
            'addressCountry'  => 'PA',
        ];
    }

    $sameAs = am_schema_collect_same_as($siteGlobal, $contentService);
    if ($sameAs !== []) {
        $schema['sameAs'] = $sameAs;
    }

    return $schema;
}

/**
 * Emite JSON-LD de Organization si el payload es válido.
 *
 * @param array<string, mixed> $schema
 */
function am_schema_emit_json_ld(array $schema): void
{
    $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        return;
    }
    echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
}
