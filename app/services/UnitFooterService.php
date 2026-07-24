<?php
/**
 * Pie de página por unidad de negocio.
 * Persistido en global.business_units.{unit}.unit_footer.
 * Si la unidad no tiene pie configurado, el sitio sigue usando el footer global.
 */
declare(strict_types=1);

require_once __DIR__ . '/FooterService.php';
require_once __DIR__ . '/UnitContentService.php';
require_once __DIR__ . '/GenericPageService.php';
require_once __DIR__ . '/../includes/business-units-registry.php';

class UnitFooterService
{
    public const CONTENT_KINDS = ['news', 'blog', 'latest'];

    /** @return array<string, string> tab slug => permission */
    public static function tabPermissionMap(): array
    {
        return [
            'rentacar-footer' => 'hero',
            'seminuevos-footer' => 'semi_home',
            'leasing-footer' => 'leasing_home',
            'renting-footer' => 'renting_home',
            'taller-footer' => 'taller_home',
        ];
    }

    public static function tabSlug(string $unitKey): string
    {
        $unitKey = strtolower(trim($unitKey));
        if (UnitContentService::isCustomUnit($unitKey)) {
            return 'unit-' . $unitKey . '-footer';
        }

        return $unitKey . '-footer';
    }

    public static function permissionKey(string $unitKey): string
    {
        $unitKey = strtolower(trim($unitKey));
        $map = self::tabPermissionMap();
        $tab = self::tabSlug($unitKey);
        if (isset($map[$tab])) {
            return $map[$tab];
        }

        return UnitContentService::isCustomUnit($unitKey) ? 'global' : 'footer';
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>
     */
    public static function raw(array $siteData, string $unitKey): array
    {
        $unitKey = strtolower(trim($unitKey));
        $units = $siteData['global']['business_units'] ?? [];
        if (!is_array($units) || !isset($units[$unitKey]) || !is_array($units[$unitKey])) {
            return [];
        }
        $raw = $units[$unitKey]['unit_footer'] ?? null;

        return is_array($raw) ? $raw : [];
    }

    public static function isConfigured(array $siteData, string $unitKey): bool
    {
        $raw = self::raw($siteData, $unitKey);

        return !empty($raw['configured']);
    }

    /**
     * Datos para el editor admin (si no hay guardado, siembra desde el footer global + redes unitarias).
     *
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>
     */
    public static function forAdmin(array $siteData, string $unitKey): array
    {
        $raw = self::raw($siteData, $unitKey);
        if (!empty($raw['configured'])) {
            $config = self::normalize($raw, $siteData, $unitKey);
            $seed = self::seedFromGlobal($siteData, $unitKey);
            foreach (self::brandLegalKeys() as $key) {
                if (!array_key_exists($key, $raw)) {
                    $config[$key] = $seed[$key];
                }
            }
            foreach (['privacy', 'cookies'] as $legalKey) {
                $slugKey = $legalKey . '_page_slug';
                $urlKey = $legalKey . '_url';
                if (trim((string) ($config[$slugKey] ?? '')) === '') {
                    $inferred = self::pageSlugFromUrl((string) ($config[$urlKey] ?? ''));
                    if ($inferred !== '') {
                        $config[$slugKey] = $inferred;
                    }
                }
            }

            return $config;
        }

        return self::normalize(self::seedFromGlobal($siteData, $unitKey), $siteData, $unitKey);
    }

    /**
     * Resolución pública: unitario si está configurado; si no, global.
     *
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $globalFooter FooterService::getFooter()
     * @return array<string, mixed>
     */
    public static function resolveForRender(array $siteData, string $unitKey, array $globalFooter): array
    {
        $unitKey = strtolower(trim($unitKey));
        if ($unitKey === '' || !self::isConfigured($siteData, $unitKey)) {
            return self::fromGlobalFooter($globalFooter);
        }

        $raw = self::raw($siteData, $unitKey);
        $config = self::normalize($raw, $siteData, $unitKey);
        $globalGeneral = is_array($globalFooter['general'] ?? null) ? $globalFooter['general'] : [];
        $brandLegal = self::resolveBrandLegal($config, $raw, $globalGeneral, $siteData, $unitKey);

        return array_merge([
            'source' => 'unit',
            'resources_title' => (string) $config['resources_title'],
            'resources' => self::filterActiveLinks($config['resources']),
            'also_know_title' => (string) $config['also_know_title'],
            'also_know' => self::filterActiveLinks($config['also_know']),
            'follow_title' => (string) $config['follow_title'],
            'social' => FooterService::filterRenderableSocial($config['social']),
        ], $brandLegal);
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, mixed> $post
     */
    public static function apply(array &$siteData, string $unitKey, array $post): ?string
    {
        $unitKey = strtolower(trim($unitKey));
        if ($unitKey === '' || !preg_match('/^[a-z0-9_]+$/', $unitKey)) {
            return 'Unidad de negocio no válida.';
        }
        if (!UnitContentService::isSupportedUnit($unitKey, $siteData)
            && !isset(($siteData['global']['business_units'] ?? [])[$unitKey])) {
            return 'Unidad de negocio no válida.';
        }

        if (!isset($siteData['global']) || !is_array($siteData['global'])) {
            $siteData['global'] = [];
        }
        if (!isset($siteData['global']['business_units']) || !is_array($siteData['global']['business_units'])) {
            $siteData['global']['business_units'] = [];
        }
        if (!isset($siteData['global']['business_units'][$unitKey])
            || !is_array($siteData['global']['business_units'][$unitKey])) {
            $siteData['global']['business_units'][$unitKey] = ['key' => $unitKey];
        }

        $resourcesTitle = trim((string) ($post['uf_resources_title'] ?? 'Recursos'));
        if ($resourcesTitle === '') {
            $resourcesTitle = 'Recursos';
        }
        $alsoKnowTitle = trim((string) ($post['uf_also_know_title'] ?? 'Conoce también'));
        if ($alsoKnowTitle === '') {
            $alsoKnowTitle = 'Conoce también';
        }
        $followTitle = trim((string) ($post['uf_follow_title'] ?? 'Síguenos'));
        if ($followTitle === '') {
            $followTitle = 'Síguenos';
        }

        $brandLegal = self::brandLegalFromPost($post);

        try {
            $resources = self::parseResourcesFromPost($post, $siteData, $unitKey);
            $alsoKnow = self::parseAlsoKnowFromPost($post);
            $social = self::parseSocialFromPost($post);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $siteData['global']['business_units'][$unitKey]['unit_footer'] = self::normalize(array_merge([
            'configured' => true,
            'resources_title' => $resourcesTitle,
            'resources' => $resources,
            'also_know_title' => $alsoKnowTitle,
            'also_know' => $alsoKnow,
            'follow_title' => $followTitle,
            'social' => $social,
        ], $brandLegal), $siteData, $unitKey);

        // Mantener social_links legacy sincronizado (campos simples de la home).
        $legacyMap = [];
        foreach ($social as $entry) {
            if (empty($entry['active'])) {
                continue;
            }
            $platform = FooterService::detectSocialPlatformFromUrl((string) ($entry['url'] ?? ''))
                ?? FooterService::detectSocialPlatformFromLabel((string) ($entry['label'] ?? ''));
            if ($platform !== null) {
                $legacyMap[$platform] = (string) ($entry['url'] ?? '');
            }
        }
        self::syncLegacySocialLinks($siteData, $unitKey, $legacyMap);

        return null;
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>
     */
    public static function normalize(array $raw, array $siteData, string $unitKey): array
    {
        $resources = [];
        foreach (array_values(is_array($raw['resources'] ?? null) ? $raw['resources'] : []) as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized = self::normalizeResourceLink($item, $i, $siteData, $unitKey);
            if ($normalized !== null) {
                $resources[] = $normalized;
            }
        }

        $alsoKnow = [];
        foreach (array_values(is_array($raw['also_know'] ?? null) ? $raw['also_know'] : []) as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim(strip_tags((string) ($item['label'] ?? '')));
            $url = FooterService::sanitizeFooterUrl((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $alsoKnow[] = [
                'id' => trim((string) ($item['id'] ?? '')) !== '' ? (string) $item['id'] : ('ak_' . ($i + 1)),
                'label' => mb_substr($label, 0, 100, 'UTF-8'),
                'url' => $url,
                'active' => !array_key_exists('active', $item) || filter_var($item['active'], FILTER_VALIDATE_BOOLEAN),
                'sort_order' => (int) ($item['sort_order'] ?? $i),
            ];
        }
        usort($alsoKnow, static fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $social = [];
        foreach (array_values(is_array($raw['social'] ?? null) ? $raw['social'] : []) as $i => $item) {
            if (!is_array($item)) {
                continue;
            }
            $entry = FooterService::normalizeSocialEntry([
                'id' => $item['id'] ?? ('s_' . ($i + 1)),
                'label' => $item['label'] ?? '',
                'icon' => $item['icon'] ?? '',
                'url' => $item['url'] ?? '',
                'active' => !array_key_exists('active', $item) || filter_var($item['active'], FILTER_VALIDATE_BOOLEAN),
                'sort_order' => (int) ($item['sort_order'] ?? $i),
            ]);
            if (trim((string) ($entry['label'] ?? '')) === '' && trim((string) ($entry['url'] ?? '')) === '') {
                continue;
            }
            $social[] = $entry;
        }
        usort($social, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        $brandLegal = self::hydrateLegalUrlsFromPages(
            self::normalizeBrandLegal($raw),
            $siteData,
            $unitKey
        );

        return array_merge([
            'configured' => !empty($raw['configured']),
            'resources_title' => trim((string) ($raw['resources_title'] ?? 'Recursos')) ?: 'Recursos',
            'resources' => $resources,
            'also_know_title' => trim((string) ($raw['also_know_title'] ?? 'Conoce también')) ?: 'Conoce también',
            'also_know' => $alsoKnow,
            'follow_title' => trim((string) ($raw['follow_title'] ?? 'Síguenos')) ?: 'Síguenos',
            'social' => $social,
        ], $brandLegal);
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>
     */
    private static function seedFromGlobal(array $siteData, string $unitKey): array
    {
        $footerService = new FooterService();
        $global = $footerService->getFooter();
        $resources = [];
        foreach ($global['columns'] ?? [] as $column) {
            if (!is_array($column) || ($column['id'] ?? '') !== 'recursos') {
                continue;
            }
            foreach (array_values($column['links'] ?? []) as $i => $link) {
                if (!is_array($link)) {
                    continue;
                }
                $resources[] = [
                    'id' => (string) ($link['id'] ?? ('res_' . ($i + 1))),
                    'label' => (string) ($link['label'] ?? ''),
                    'url' => (string) ($link['url'] ?? ''),
                    'link_kind' => 'custom',
                    'page_slug' => '',
                    'active' => !isset($link['active']) || (bool) $link['active'],
                    'sort_order' => (int) ($link['sort_order'] ?? $i),
                ];
            }
        }

        // Atajos de contenido de la unidad al final (si no existen).
        foreach ([
            'latest' => 'Novedades',
            'blog' => 'Blog',
            'news' => 'Noticias',
        ] as $kind => $label) {
            $url = self::contentUrl($unitKey, $kind);
            $exists = false;
            foreach ($resources as $link) {
                if (self::urlsMatch((string) ($link['url'] ?? ''), $url)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $resources[] = [
                    'id' => 'content_' . $kind,
                    'label' => $label,
                    'url' => $url,
                    'link_kind' => $kind,
                    'page_slug' => '',
                    'active' => false,
                    'sort_order' => count($resources) + 1,
                ];
            }
        }

        $alsoKnow = [];
        foreach (array_values($global['also_know'] ?? []) as $i => $link) {
            if (!is_array($link)) {
                continue;
            }
            $alsoKnow[] = [
                'id' => (string) ($link['id'] ?? ('ak_' . ($i + 1))),
                'label' => (string) ($link['label'] ?? ''),
                'url' => (string) ($link['url'] ?? ''),
                'active' => !isset($link['active']) || (bool) $link['active'],
                'sort_order' => (int) ($link['sort_order'] ?? $i),
            ];
        }

        $social = self::seedSocial($siteData, $unitKey, $global['social'] ?? []);
        $general = is_array($global['general'] ?? null) ? $global['general'] : [];

        return array_merge([
            'configured' => false,
            'resources_title' => (string) (($general['resources_title'] ?? '') ?: 'Recursos'),
            'resources' => $resources,
            'also_know_title' => (string) (($general['also_know_title'] ?? '') ?: 'Conoce también'),
            'also_know' => $alsoKnow,
            'follow_title' => (string) (($general['follow_title'] ?? '') ?: 'Síguenos'),
            'social' => $social,
        ], self::brandLegalDefaultsFromGeneral($general));
    }

    /**
     * @param array<string, mixed> $siteData
     * @param list<array<string, mixed>> $globalSocial
     * @return list<array<string, mixed>>
     */
    private static function seedSocial(array $siteData, string $unitKey, array $globalSocial): array
    {
        $legacy = self::legacySocialMap($siteData, $unitKey);
        $hasLegacy = false;
        foreach ($legacy as $url) {
            if (trim((string) $url) !== '') {
                $hasLegacy = true;
                break;
            }
        }

        if ($hasLegacy) {
            $catalog = FooterService::socialPlatformCatalog();
            $social = [];
            $order = 1;
            foreach ($catalog as $platform => $meta) {
                $url = trim((string) ($legacy[$platform] ?? ''));
                $social[] = [
                    'id' => 's_' . $platform,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'url' => $url !== '' ? $url : '#',
                    'active' => $url !== '',
                    'sort_order' => $order++,
                ];
            }

            return $social;
        }

        return is_array($globalSocial) ? $globalSocial : [];
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array<string, string>
     */
    private static function legacySocialMap(array $siteData, string $unitKey): array
    {
        $bucket = match ($unitKey) {
            'rentacar' => $siteData['homepage'] ?? [],
            'seminuevos', 'leasing', 'renting', 'taller' => $siteData[$unitKey] ?? [],
            default => $siteData['global']['business_units'][$unitKey] ?? [],
        };
        if (!is_array($bucket)) {
            return [];
        }
        $links = $bucket['social_links'] ?? [];

        return is_array($links) ? $links : [];
    }

    /**
     * @param array<string, mixed> $siteData
     * @param array<string, string> $legacyMap
     */
    private static function syncLegacySocialLinks(array &$siteData, string $unitKey, array $legacyMap): void
    {
        $platforms = array_keys(FooterService::socialPlatformCatalog());
        $normalized = [];
        foreach ($platforms as $platform) {
            $normalized[$platform] = trim((string) ($legacyMap[$platform] ?? ''));
        }

        if ($unitKey === 'rentacar') {
            if (!isset($siteData['homepage']) || !is_array($siteData['homepage'])) {
                $siteData['homepage'] = [];
            }
            $siteData['homepage']['social_links'] = $normalized;

            return;
        }
        if (in_array($unitKey, ['seminuevos', 'leasing', 'renting', 'taller'], true)) {
            if (!isset($siteData[$unitKey]) || !is_array($siteData[$unitKey])) {
                $siteData[$unitKey] = [];
            }
            $siteData[$unitKey]['social_links'] = $normalized;

            return;
        }
        if (!isset($siteData['global']['business_units'][$unitKey]) || !is_array($siteData['global']['business_units'][$unitKey])) {
            $siteData['global']['business_units'][$unitKey] = ['key' => $unitKey];
        }
        $siteData['global']['business_units'][$unitKey]['social_links'] = $normalized;
    }

    /**
     * @param array<string, mixed> $globalFooter
     * @return array<string, mixed>
     */
    private static function fromGlobalFooter(array $globalFooter): array
    {
        $resources = [];
        foreach ($globalFooter['columns'] ?? [] as $column) {
            if (!is_array($column) || ($column['id'] ?? '') !== 'recursos') {
                continue;
            }
            $resources = FooterService::filterRenderableColumnLinks($column['links'] ?? []);
        }
        $alsoKnow = array_values(array_filter(
            is_array($globalFooter['also_know'] ?? null) ? $globalFooter['also_know'] : [],
            static fn ($l) => is_array($l) && !empty($l['active'])
        ));
        usort($alsoKnow, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));
        $general = is_array($globalFooter['general'] ?? null) ? $globalFooter['general'] : [];

        return array_merge([
            'source' => 'global',
            'resources_title' => (string) (($general['resources_title'] ?? '') ?: 'Recursos'),
            'resources' => $resources,
            'also_know_title' => (string) (($general['also_know_title'] ?? '') ?: 'Conoce también'),
            'also_know' => $alsoKnow,
            'follow_title' => (string) (($general['follow_title'] ?? '') ?: 'Síguenos'),
            'social' => FooterService::filterRenderableSocial($globalFooter['social'] ?? []),
        ], self::brandLegalDefaultsFromGeneral($general));
    }

    /**
     * @return list<string>
     */
    public static function brandLegalKeys(): array
    {
        return [
            'brand_tagline',
            'brand_address',
            'brand_phone',
            'brand_email',
            'copyright',
            'privacy_label',
            'privacy_page_slug',
            'privacy_url',
            'cookies_label',
            'cookies_page_slug',
            'cookies_url',
            'recaptcha_text_before',
            'recaptcha_privacy_label',
            'recaptcha_privacy_url',
            'recaptcha_text_middle',
            'recaptcha_terms_label',
            'recaptcha_terms_url',
            'recaptcha_text_after',
        ];
    }

    /**
     * @param array<string, mixed> $general
     * @return array<string, string>
     */
    public static function brandLegalDefaultsFromGeneral(array $general): array
    {
        $privacyUrl = FooterService::sanitizeFooterUrl((string) ($general['privacy_url'] ?? '/pagina-institucional.php?p=privacidad'))
            ?: '/pagina-institucional.php?p=privacidad';
        $cookiesUrl = FooterService::sanitizeFooterUrl((string) ($general['cookies_url'] ?? '/pagina-institucional.php?p=cookies'))
            ?: '/pagina-institucional.php?p=cookies';

        return [
            'brand_tagline' => trim((string) ($general['tagline'] ?? '')),
            'brand_address' => trim((string) ($general['address'] ?? '')),
            'brand_phone' => trim((string) ($general['phone_display'] ?? '')),
            'brand_email' => trim((string) ($general['email'] ?? '')),
            'copyright' => FooterService::normalizeCopyrightText((string) ($general['copyright'] ?? 'Automarket. Todos los derechos reservados.')),
            'privacy_label' => 'Política de Privacidad',
            'privacy_page_slug' => self::pageSlugFromUrl($privacyUrl),
            'privacy_url' => $privacyUrl,
            'cookies_label' => 'Cookies',
            'cookies_page_slug' => self::pageSlugFromUrl($cookiesUrl),
            'cookies_url' => $cookiesUrl,
            'recaptcha_text_before' => 'Este sitio está protegido por reCAPTCHA y se aplican la',
            'recaptcha_privacy_label' => 'Política de Privacidad',
            'recaptcha_privacy_url' => 'https://policies.google.com/privacy',
            'recaptcha_text_middle' => 'y los',
            'recaptcha_terms_label' => 'Términos del Servicio',
            'recaptcha_terms_url' => 'https://policies.google.com/terms',
            'recaptcha_text_after' => 'de Google.',
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, string>
     */
    private static function normalizeBrandLegal(array $raw): array
    {
        $defaults = [
            'brand_tagline' => '',
            'brand_address' => '',
            'brand_phone' => '',
            'brand_email' => '',
            'copyright' => '',
            'privacy_label' => 'Política de Privacidad',
            'privacy_page_slug' => '',
            'privacy_url' => '',
            'cookies_label' => 'Cookies',
            'cookies_page_slug' => '',
            'cookies_url' => '',
            'recaptcha_text_before' => 'Este sitio está protegido por reCAPTCHA y se aplican la',
            'recaptcha_privacy_label' => 'Política de Privacidad',
            'recaptcha_privacy_url' => 'https://policies.google.com/privacy',
            'recaptcha_text_middle' => 'y los',
            'recaptcha_terms_label' => 'Términos del Servicio',
            'recaptcha_terms_url' => 'https://policies.google.com/terms',
            'recaptcha_text_after' => 'de Google.',
        ];

        $out = [];
        foreach ($defaults as $key => $fallback) {
            if (!array_key_exists($key, $raw)) {
                $out[$key] = $fallback;
                continue;
            }
            $value = trim(strip_tags((string) ($raw[$key] ?? '')));
            if (str_ends_with($key, '_page_slug')) {
                $out[$key] = self::sanitizePageSlug($value);
                continue;
            }
            if (str_ends_with($key, '_url')) {
                $sanitized = FooterService::sanitizeFooterUrl($value);
                $out[$key] = $sanitized !== '' ? $sanitized : $fallback;
                continue;
            }
            if ($key === 'copyright') {
                $out[$key] = $value !== ''
                    ? FooterService::normalizeCopyrightText($value)
                    : $fallback;
                continue;
            }
            if ($key === 'brand_email' && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $out[$key] = $fallback;
                continue;
            }
            $out[$key] = $value !== '' ? mb_substr($value, 0, 500, 'UTF-8') : $fallback;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    private static function brandLegalFromPost(array $post): array
    {
        return self::normalizeBrandLegal([
            'brand_tagline' => $post['uf_brand_tagline'] ?? '',
            'brand_address' => $post['uf_brand_address'] ?? '',
            'brand_phone' => $post['uf_brand_phone'] ?? '',
            'brand_email' => $post['uf_brand_email'] ?? '',
            'copyright' => $post['uf_copyright'] ?? '',
            'privacy_label' => $post['uf_privacy_label'] ?? 'Política de Privacidad',
            'privacy_page_slug' => $post['uf_privacy_page'] ?? '',
            'privacy_url' => $post['uf_privacy_url'] ?? '',
            'cookies_label' => $post['uf_cookies_label'] ?? 'Cookies',
            'cookies_page_slug' => $post['uf_cookies_page'] ?? '',
            'cookies_url' => $post['uf_cookies_url'] ?? '',
            'recaptcha_text_before' => $post['uf_recaptcha_text_before'] ?? '',
            'recaptcha_privacy_label' => $post['uf_recaptcha_privacy_label'] ?? '',
            'recaptcha_privacy_url' => $post['uf_recaptcha_privacy_url'] ?? '',
            'recaptcha_text_middle' => $post['uf_recaptcha_text_middle'] ?? '',
            'recaptcha_terms_label' => $post['uf_recaptcha_terms_label'] ?? '',
            'recaptcha_terms_url' => $post['uf_recaptcha_terms_url'] ?? '',
            'recaptcha_text_after' => $post['uf_recaptcha_text_after'] ?? '',
        ]);
    }

    /**
     * Completa marca/legal desde el global cuando el pie unitario aún no tiene esas claves.
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $globalGeneral
     * @param array<string, mixed> $siteData
     * @return array<string, string>
     */
    private static function resolveBrandLegal(
        array $config,
        array $raw,
        array $globalGeneral,
        array $siteData = [],
        string $unitKey = ''
    ): array {
        $defaults = self::brandLegalDefaultsFromGeneral($globalGeneral);
        $out = [];
        foreach (self::brandLegalKeys() as $key) {
            if (!array_key_exists($key, $raw)) {
                $out[$key] = $defaults[$key];
                continue;
            }
            $value = trim((string) ($config[$key] ?? ''));
            $out[$key] = $value !== '' ? $value : $defaults[$key];
        }

        return self::hydrateLegalUrlsFromPages($out, $siteData, $unitKey);
    }

    /**
     * Resuelve privacy_url / cookies_url desde páginas del maestro.
     *
     * @param array<string, string> $brandLegal
     * @param array<string, mixed> $siteData
     * @return array<string, string>
     */
    private static function hydrateLegalUrlsFromPages(array $brandLegal, array $siteData, string $unitKey): array
    {
        foreach (['privacy', 'cookies'] as $legalKey) {
            $slugKey = $legalKey . '_page_slug';
            $urlKey = $legalKey . '_url';
            $slug = self::sanitizePageSlug((string) ($brandLegal[$slugKey] ?? ''));
            $brandLegal[$slugKey] = $slug;
            if ($slug === '') {
                continue;
            }
            if (GenericPageService::findBySlug($siteData, $slug) === null) {
                continue;
            }
            $url = GenericPageService::publicPath($slug);
            if ($unitKey !== '' && $unitKey !== 'rentacar') {
                $url .= '?unit=' . rawurlencode($unitKey);
            }
            $brandLegal[$urlKey] = $url;
        }

        return $brandLegal;
    }

    private static function sanitizePageSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return '';
        }

        return mb_substr($slug, 0, 80, 'UTF-8');
    }

    public static function pageSlugFromUrl(string $url): string
    {
        $path = parse_url(trim($url), PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }
        if (preg_match('#^/p/([a-z0-9]+(?:-[a-z0-9]+)*)$#i', $path, $m)) {
            return strtolower($m[1]);
        }

        return '';
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $siteData
     * @return array<string, mixed>|null
     */
    private static function normalizeResourceLink(array $item, int $index, array $siteData, string $unitKey): ?array
    {
        $label = trim(strip_tags((string) ($item['label'] ?? '')));
        $kind = strtolower(trim((string) ($item['link_kind'] ?? 'custom')));
        if (!in_array($kind, ['custom', 'page', 'news', 'blog', 'latest'], true)) {
            $kind = 'custom';
        }
        $pageSlug = strtolower(trim((string) ($item['page_slug'] ?? '')));
        $url = trim((string) ($item['url'] ?? ''));

        if ($kind === 'page') {
            if ($pageSlug === '' || GenericPageService::findBySlug($siteData, $pageSlug) === null) {
                // Conserva URL si el slug dejó de existir.
                $kind = 'custom';
            } else {
                $url = GenericPageService::publicPath($pageSlug);
                if ($unitKey !== '' && $unitKey !== 'rentacar') {
                    $url .= '?unit=' . rawurlencode($unitKey);
                }
            }
        } elseif (in_array($kind, self::CONTENT_KINDS, true)) {
            $url = self::contentUrl($unitKey, $kind);
            if ($label === '') {
                $label = ['news' => 'Noticias', 'blog' => 'Blog', 'latest' => 'Novedades'][$kind];
            }
        }

        $url = FooterService::sanitizeFooterUrl($url);
        if ($label === '' || $url === '') {
            return null;
        }

        return [
            'id' => trim((string) ($item['id'] ?? '')) !== '' ? (string) $item['id'] : ('res_' . ($index + 1)),
            'label' => mb_substr($label, 0, 100, 'UTF-8'),
            'url' => $url,
            'link_kind' => $kind,
            'page_slug' => $kind === 'page' ? $pageSlug : '',
            'active' => !array_key_exists('active', $item) || filter_var($item['active'], FILTER_VALIDATE_BOOLEAN),
            'sort_order' => (int) ($item['sort_order'] ?? $index),
        ];
    }

    public static function contentUrl(string $unitKey, string $kind): string
    {
        $path = match ($kind) {
            'news' => '/noticias.php',
            'blog' => '/blog.php',
            default => '/contenido-reciente.php',
        };
        if ($unitKey !== '' && $unitKey !== 'rentacar') {
            return $path . '?unit=' . rawurlencode($unitKey);
        }

        return $path;
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $siteData
     * @return list<array<string, mixed>>
     */
    private static function parseResourcesFromPost(array $post, array $siteData, string $unitKey): array
    {
        $labels = $post['uf_res_label'] ?? [];
        $kinds = $post['uf_res_kind'] ?? [];
        $urls = $post['uf_res_url'] ?? [];
        $pages = $post['uf_res_page'] ?? [];
        $actives = $post['uf_res_active'] ?? [];
        if (!is_array($labels)) {
            $labels = [];
        }
        $count = count($labels);
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $normalized = self::normalizeResourceLink([
                'id' => 'res_' . ($i + 1),
                'label' => $labels[$i] ?? '',
                'link_kind' => is_array($kinds) ? ($kinds[$i] ?? 'custom') : 'custom',
                'url' => is_array($urls) ? ($urls[$i] ?? '') : '',
                'page_slug' => is_array($pages) ? ($pages[$i] ?? '') : '',
                'active' => is_array($actives) ? (($actives[$i] ?? '0') === '1') : false,
                'sort_order' => $i,
            ], $i, $siteData, $unitKey);
            if ($normalized !== null) {
                $items[] = $normalized;
            }
        }
        if (count($items) > 40) {
            throw new InvalidArgumentException('Recursos admite máximo 40 enlaces.');
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array<string, mixed>>
     */
    private static function parseAlsoKnowFromPost(array $post): array
    {
        $labels = $post['uf_ak_label'] ?? [];
        $urls = $post['uf_ak_url'] ?? [];
        $actives = $post['uf_ak_active'] ?? [];
        if (!is_array($labels)) {
            $labels = [];
        }
        $items = [];
        foreach ($labels as $i => $label) {
            $label = trim(strip_tags((string) $label));
            $url = FooterService::sanitizeFooterUrl((string) (is_array($urls) ? ($urls[$i] ?? '') : ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $items[] = [
                'id' => 'ak_' . ($i + 1),
                'label' => mb_substr($label, 0, 100, 'UTF-8'),
                'url' => $url,
                'active' => is_array($actives) ? (($actives[$i] ?? '0') === '1') : false,
                'sort_order' => count($items),
            ];
        }
        if (count($items) > 20) {
            throw new InvalidArgumentException('Conoce también admite máximo 20 enlaces.');
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array<string, mixed>>
     */
    private static function parseSocialFromPost(array $post): array
    {
        $labels = $post['uf_social_label'] ?? [];
        $icons = $post['uf_social_icon'] ?? [];
        $urls = $post['uf_social_url'] ?? [];
        $actives = $post['uf_social_active'] ?? [];
        if (!is_array($labels)) {
            $labels = [];
        }
        $items = [];
        foreach ($labels as $i => $label) {
            $entry = FooterService::normalizeSocialEntry([
                'id' => 's_' . ($i + 1),
                'label' => $label,
                'icon' => is_array($icons) ? ($icons[$i] ?? '') : '',
                'url' => is_array($urls) ? ($urls[$i] ?? '') : '',
                'active' => is_array($actives) ? (($actives[$i] ?? '0') === '1') : false,
                'sort_order' => count($items) + 1,
            ]);
            if (trim((string) ($entry['label'] ?? '')) === '' && (trim((string) ($entry['url'] ?? '')) === '' || ($entry['url'] ?? '') === '#')) {
                continue;
            }
            $items[] = $entry;
        }
        if (count($items) > 12) {
            throw new InvalidArgumentException('Síguenos admite máximo 12 redes sociales.');
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $links
     * @return list<array<string, mixed>>
     */
    private static function filterActiveLinks(array $links): array
    {
        $out = [];
        foreach ($links as $link) {
            if (!is_array($link) || empty($link['active'])) {
                continue;
            }
            $out[] = $link;
        }
        usort($out, static fn ($a, $b) => intval($a['sort_order'] ?? 99) <=> intval($b['sort_order'] ?? 99));

        return $out;
    }

    private static function urlsMatch(string $a, string $b): bool
    {
        $na = rtrim(strtolower(trim($a)), '/');
        $nb = rtrim(strtolower(trim($b)), '/');

        return $na !== '' && $na === $nb;
    }
}
