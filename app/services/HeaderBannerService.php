<?php
/**
 * Cabecera estática o slider (admin + sitio público).
 */
class HeaderBannerService
{
    public const MODE_STATIC = 'static';
    public const MODE_SLIDER = 'slider';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'mode' => self::MODE_STATIC,
            'image_url' => '',
            'alt' => '',
            'title' => '',
            'subtitle' => '',
            'link_text' => '',
            'link_url' => '',
            'slider' => [
                'interval_ms' => 5000,
                'transition' => 'fade',
                'slides' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function normalize(array $raw, string $legacyImageUrl = ''): array
    {
        $config = self::defaults();
        $config['enabled'] = !array_key_exists('enabled', $raw)
            || filter_var($raw['enabled'], FILTER_VALIDATE_BOOLEAN);
        $mode = (string) ($raw['mode'] ?? self::MODE_STATIC);
        $config['mode'] = $mode === self::MODE_SLIDER ? self::MODE_SLIDER : self::MODE_STATIC;

        $imageUrl = self::sanitizeImageUrl($raw['image_url'] ?? '');
        if ($imageUrl === '') {
            $imageUrl = self::sanitizeImageUrl($legacyImageUrl);
        }
        $config['image_url'] = $imageUrl;
        $config['alt'] = trim((string) ($raw['alt'] ?? ''));
        $config['title'] = trim((string) ($raw['title'] ?? ''));
        $config['subtitle'] = trim((string) ($raw['subtitle'] ?? ''));
        $config['link_url'] = self::sanitizeLinkUrl($raw['link_url'] ?? '');
        $config['link_text'] = $config['link_url'] !== ''
            ? trim((string) ($raw['link_text'] ?? ''))
            : '';

        $slider = is_array($raw['slider'] ?? null) ? $raw['slider'] : [];
        $config['slider']['interval_ms'] = max(1000, min(30000, (int) ($slider['interval_ms'] ?? 5000)));
        $transition = (string) ($slider['transition'] ?? 'fade');
        $config['slider']['transition'] = in_array($transition, ['fade', 'slide'], true) ? $transition : 'fade';

        $slides = [];
        foreach ($slider['slides'] ?? [] as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $url = self::sanitizeImageUrl($slide['image_url'] ?? '');
            if ($url === '') {
                continue;
            }
            $slideLinkUrl = self::sanitizeLinkUrl($slide['link_url'] ?? '');
            $slides[] = [
                'enabled' => !array_key_exists('enabled', $slide)
                    || filter_var($slide['enabled'], FILTER_VALIDATE_BOOLEAN),
                'image_url' => $url,
                'alt' => trim((string) ($slide['alt'] ?? '')),
                'title' => trim((string) ($slide['title'] ?? '')),
                'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
                'link_text' => $slideLinkUrl !== ''
                    ? trim((string) ($slide['link_text'] ?? ''))
                    : '',
                'link_url' => $slideLinkUrl,
            ];
        }
        $config['slider']['slides'] = $slides;

        if ($config['mode'] === self::MODE_SLIDER && empty($config['slider']['slides']) && $config['image_url'] !== '') {
            $config['slider']['slides'] = [[
                'enabled' => true,
                'image_url' => $config['image_url'],
                'alt' => $config['alt'],
                'title' => '',
                'subtitle' => '',
                'link_text' => '',
                'link_url' => '',
            ]];
        }

        return $config;
    }

    public static function sanitizeLinkUrl(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '' || preg_match('/[\x00-\x1F\x7F<>"\']/', $url)) {
            return '';
        }

        if (str_starts_with($url, '#')) {
            return preg_match('/^#[^\s<>"\']+$/u', $url) ? $url : '';
        }

        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//') && !str_contains($url, '\\') ? $url : '';
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : '';
    }

    public static function sanitizeImageUrl(mixed $value): string
    {
        $url = self::sanitizeLinkUrl($value);

        return str_starts_with($url, '#') ? '' : $url;
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public static function normalizeFromNode(array $node, string $legacyImageKey = 'image_url'): array
    {
        $legacy = trim((string) ($node[$legacyImageKey] ?? $node['image_url'] ?? ''));
        $banner = $node['header_banner'] ?? null;

        if (!is_array($banner) || $banner === []) {
            return self::normalize(['mode' => self::MODE_STATIC, 'image_url' => $legacy], $legacy);
        }

        return self::normalize($banner, $legacy);
    }

    /**
     * @param array<string, mixed> $siteData
     * @param list<string> $path
     */
    public static function readAtPath(array $siteData, array $path, string $legacyImageKey = 'image_url'): array
    {
        $node = self::getNode($siteData, $path);

        return self::normalizeFromNode(is_array($node) ? $node : [], $legacyImageKey);
    }

    /**
     * @param array<string, mixed> $siteData
     * @param list<string> $path
     * @return array<string, mixed>
     */
    public static function &nodeRef(array &$siteData, array $path): array
    {
        $ref = &$siteData;
        foreach ($path as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        return $ref;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param list<string> $path
     * @return array<string, mixed>
     */
    public static function getNode(array $siteData, array $path): array
    {
        $node = $siteData;
        foreach ($path as $segment) {
            if (!is_array($node) || !isset($node[$segment])) {
                return [];
            }
            $node = $node[$segment];
        }

        return is_array($node) ? $node : [];
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function primaryImageUrl(array $config, string $fallback = ''): string
    {
        if (($config['mode'] ?? self::MODE_STATIC) === self::MODE_SLIDER) {
            $first = $config['slider']['slides'][0]['image_url'] ?? '';
            if ($first !== '') {
                return $first;
            }
        }

        $static = trim((string) ($config['image_url'] ?? ''));

        return $static !== '' ? $static : $fallback;
    }

    /**
     * @param array<string, mixed> $siteData
     * @param list<string> $path
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     */
    public static function applyPostAtPath(
        array &$siteData,
        array $path,
        string $prefix,
        array $post,
        array $files,
        ContentService $contentService,
        string $uploadPrefix = 'hb_',
        string $legacyImageKey = 'image_url'
    ): ?string {
        $nodeRef = &self::nodeRef($siteData, $path);
        $existing = self::normalizeFromNode($nodeRef, $legacyImageKey);
        $mode = (string) ($post[$prefix . '_mode'] ?? self::MODE_STATIC);
        $mode = $mode === self::MODE_SLIDER ? self::MODE_SLIDER : self::MODE_STATIC;

        $config = self::defaults();
        $config['enabled'] = filter_var(
            $post[$prefix . '_enabled'] ?? $existing['enabled'],
            FILTER_VALIDATE_BOOLEAN
        );
        $config['mode'] = $mode;
        $config['alt'] = trim((string) ($post[$prefix . '_alt'] ?? $existing['alt']));
        $config['title'] = trim((string) ($post[$prefix . '_title'] ?? $existing['title']));
        $config['subtitle'] = trim((string) ($post[$prefix . '_subtitle'] ?? $existing['subtitle']));
        $rawLinkUrl = trim((string) ($post[$prefix . '_link_url'] ?? $existing['link_url']));
        $config['link_url'] = self::sanitizeLinkUrl($rawLinkUrl);
        if ($rawLinkUrl !== '' && $config['link_url'] === '') {
            return 'El enlace de la cabecera no es válido. Use una ruta interna, un ancla o una URL HTTPS.';
        }
        $config['link_text'] = $config['link_url'] !== ''
            ? trim((string) ($post[$prefix . '_link_text'] ?? $existing['link_text']))
            : '';

        if ($mode === self::MODE_STATIC) {
            $removeStatic = filter_var($post[$prefix . '_remove_static'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $staticUrl = $removeStatic
                ? ''
                : trim((string) ($post[$prefix . '_static_url'] ?? $existing['image_url'] ?? ''));
            if ($staticUrl !== '') {
                $sanitizedStaticUrl = self::sanitizeImageUrl($staticUrl);
                if ($sanitizedStaticUrl === '') {
                    return 'La ruta de la imagen de cabecera no es válida.';
                }
                $staticUrl = $sanitizedStaticUrl;
            }
            $staticFileKey = $prefix . '_static_file';
            $staticFileError = (int) ($files[$staticFileKey]['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($staticFileError !== UPLOAD_ERR_NO_FILE && $staticFileError !== UPLOAD_ERR_OK) {
                return 'La imagen de cabecera no pudo subirse. Verifique formato y tamaño máximo.';
            }
            if (isset($files[$staticFileKey]) && is_array($files[$staticFileKey]) && $staticFileError === UPLOAD_ERR_OK) {
                $uploaded = $contentService->uploadImage($files[$staticFileKey], $uploadPrefix . 'static_', true);
                if ($uploaded === false || $uploaded === '') {
                    return 'No se pudo subir la imagen de cabecera. Use JPG, PNG, GIF o WEBP de hasta 12 MB.';
                }
                $staticUrl = $uploaded;
            }
            if ($config['enabled'] && $staticUrl === '') {
                return 'Debe subir una imagen de cabecera o conservar la actual.';
            }
            $config['image_url'] = $staticUrl;
            $config['slider'] = $existing['slider'];
        } else {
            $config['image_url'] = $existing['image_url'];
            $config['slider']['interval_ms'] = max(1000, min(30000, (int) ($post[$prefix . '_interval_ms'] ?? 5000)));
            $transition = (string) ($post[$prefix . '_transition'] ?? 'fade');
            $config['slider']['transition'] = in_array($transition, ['fade', 'slide'], true) ? $transition : 'fade';

            $urls = $post[$prefix . '_slide_url'] ?? [];
            if (!is_array($urls)) {
                $urls = [];
            }
            $titles = $post[$prefix . '_slide_title'] ?? [];
            $subtitles = $post[$prefix . '_slide_subtitle'] ?? [];
            if (!is_array($titles)) {
                $titles = [];
            }
            if (!is_array($subtitles)) {
                $subtitles = [];
            }
            $enabledRows = $post[$prefix . '_slide_enabled'] ?? [];
            $alts = $post[$prefix . '_slide_alt'] ?? [];
            $linkTexts = $post[$prefix . '_slide_link_text'] ?? [];
            $linkUrls = $post[$prefix . '_slide_link_url'] ?? [];
            $enabledRows = is_array($enabledRows) ? $enabledRows : [];
            $alts = is_array($alts) ? $alts : [];
            $linkTexts = is_array($linkTexts) ? $linkTexts : [];
            $linkUrls = is_array($linkUrls) ? $linkUrls : [];
            $existingSlides = $existing['slider']['slides'] ?? [];
            $fileKey = $prefix . '_slide_file';
            $fileNames = is_array($files[$fileKey]['name'] ?? null) ? $files[$fileKey]['name'] : [];
            $rowCount = max(
                count($urls),
                count($fileNames),
                count($enabledRows),
                count($alts),
                count($titles),
                count($subtitles),
                count($linkTexts),
                count($linkUrls)
            );

            $slides = [];
            for ($i = 0; $i < $rowCount; $i++) {
                $url = trim((string) ($urls[$i] ?? ''));
                if ($url !== '') {
                    $sanitizedSlideUrl = self::sanitizeImageUrl($url);
                    if ($sanitizedSlideUrl === '') {
                        return 'Una de las rutas de imagen del slider no es válida.';
                    }
                    $url = $sanitizedSlideUrl;
                }
                $slideFileError = (int) ($files[$fileKey]['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($slideFileError !== UPLOAD_ERR_NO_FILE && $slideFileError !== UPLOAD_ERR_OK) {
                    return 'Una imagen del slider no pudo subirse. Verifique formato y tamaño máximo.';
                }
                if ($slideFileError === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name' => $files[$fileKey]['name'][$i] ?? '',
                        'type' => $files[$fileKey]['type'][$i] ?? '',
                        'tmp_name' => $files[$fileKey]['tmp_name'][$i] ?? '',
                        'error' => $files[$fileKey]['error'][$i],
                        'size' => $files[$fileKey]['size'][$i] ?? 0,
                    ];
                    $uploaded = $contentService->uploadImage($singleFile, $uploadPrefix . 'slide_', true);
                    if ($uploaded === false || $uploaded === '') {
                        return 'No se pudo subir una imagen del slider. Use JPG, PNG, GIF o WEBP de hasta 12 MB.';
                    }
                    $url = $uploaded;
                }
                if ($url !== '') {
                    $rawSlideLinkUrl = trim((string) ($linkUrls[$i] ?? $existingSlides[$i]['link_url'] ?? ''));
                    $slideLinkUrl = self::sanitizeLinkUrl($rawSlideLinkUrl);
                    if ($rawSlideLinkUrl !== '' && $slideLinkUrl === '') {
                        return 'Uno de los enlaces del slider no es válido. Use una ruta interna, un ancla o una URL HTTPS.';
                    }
                    $slides[] = [
                        'enabled' => filter_var(
                            $enabledRows[$i] ?? $existingSlides[$i]['enabled'] ?? true,
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'image_url' => $url,
                        'alt' => trim((string) ($alts[$i] ?? $existingSlides[$i]['alt'] ?? '')),
                        'title' => trim((string) ($titles[$i] ?? $existingSlides[$i]['title'] ?? '')),
                        'subtitle' => trim((string) ($subtitles[$i] ?? $existingSlides[$i]['subtitle'] ?? '')),
                        'link_text' => $slideLinkUrl !== ''
                            ? trim((string) ($linkTexts[$i] ?? $existingSlides[$i]['link_text'] ?? ''))
                            : '',
                        'link_url' => $slideLinkUrl,
                    ];
                }
            }

            if ($config['enabled'] && empty($slides)) {
                return 'Agregue al menos una imagen al slider.';
            }

            $config['slider']['slides'] = $slides;
        }

        $config = self::normalize($config);
        $nodeRef['header_banner'] = $config;
        $nodeRef[$legacyImageKey] = self::primaryImageUrl($config, '');

        return null;
    }
}
