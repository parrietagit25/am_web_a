<?php
/**
 * Helpers Schema.org Organization global (AM-AIO-6A).
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
