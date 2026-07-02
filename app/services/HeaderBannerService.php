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
            'mode' => self::MODE_STATIC,
            'image_url' => '',
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
        $mode = (string) ($raw['mode'] ?? self::MODE_STATIC);
        $config['mode'] = $mode === self::MODE_SLIDER ? self::MODE_SLIDER : self::MODE_STATIC;

        $imageUrl = trim((string) ($raw['image_url'] ?? ''));
        if ($imageUrl === '') {
            $imageUrl = trim($legacyImageUrl);
        }
        $config['image_url'] = $imageUrl;

        $slider = is_array($raw['slider'] ?? null) ? $raw['slider'] : [];
        $config['slider']['interval_ms'] = max(1000, min(30000, (int) ($slider['interval_ms'] ?? 5000)));
        $transition = (string) ($slider['transition'] ?? 'fade');
        $config['slider']['transition'] = in_array($transition, ['fade', 'slide'], true) ? $transition : 'fade';

        $slides = [];
        foreach ($slider['slides'] ?? [] as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $url = trim((string) ($slide['image_url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $slides[] = [
                'image_url' => $url,
                'alt' => trim((string) ($slide['alt'] ?? '')),
                'title' => trim((string) ($slide['title'] ?? '')),
                'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
            ];
        }
        $config['slider']['slides'] = $slides;

        if ($config['mode'] === self::MODE_SLIDER && empty($config['slider']['slides']) && $config['image_url'] !== '') {
            $config['slider']['slides'] = [['image_url' => $config['image_url'], 'alt' => '', 'title' => '', 'subtitle' => '']];
        }

        return $config;
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
        $config['mode'] = $mode;

        if ($mode === self::MODE_STATIC) {
            $staticUrl = trim((string) ($post[$prefix . '_static_url'] ?? $existing['image_url'] ?? ''));
            $staticFileKey = $prefix . '_static_file';
            if (isset($files[$staticFileKey]) && is_array($files[$staticFileKey]) && ($files[$staticFileKey]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $uploaded = $contentService->uploadImage($files[$staticFileKey], $uploadPrefix . 'static_');
                if ($uploaded === false || $uploaded === '') {
                    return 'No se pudo subir la imagen de cabecera.';
                }
                $staticUrl = $uploaded;
            }
            if ($staticUrl === '') {
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
            $existingSlides = $existing['slider']['slides'] ?? [];
            $fileKey = $prefix . '_slide_file';
            $fileNames = is_array($files[$fileKey]['name'] ?? null) ? $files[$fileKey]['name'] : [];
            $rowCount = max(count($urls), count($fileNames));

            $slides = [];
            for ($i = 0; $i < $rowCount; $i++) {
                $url = trim((string) ($urls[$i] ?? ''));
                if (isset($files[$fileKey]['error'][$i]) && $files[$fileKey]['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name' => $files[$fileKey]['name'][$i] ?? '',
                        'type' => $files[$fileKey]['type'][$i] ?? '',
                        'tmp_name' => $files[$fileKey]['tmp_name'][$i] ?? '',
                        'error' => $files[$fileKey]['error'][$i],
                        'size' => $files[$fileKey]['size'][$i] ?? 0,
                    ];
                    $uploaded = $contentService->uploadImage($singleFile, $uploadPrefix . 'slide_');
                    if ($uploaded === false || $uploaded === '') {
                        return 'No se pudo subir una imagen del slider.';
                    }
                    $url = $uploaded;
                }
                if ($url !== '') {
                    $slides[] = [
                        'image_url' => $url,
                        'alt' => trim((string) ($existingSlides[$i]['alt'] ?? '')),
                        'title' => trim((string) ($titles[$i] ?? $existingSlides[$i]['title'] ?? '')),
                        'subtitle' => trim((string) ($subtitles[$i] ?? $existingSlides[$i]['subtitle'] ?? '')),
                    ];
                }
            }

            if (empty($slides)) {
                return 'Agregue al menos una imagen al slider.';
            }

            $config['slider']['slides'] = $slides;
        }

        $legacyFallback = (string) ($nodeRef[$legacyImageKey] ?? '');
        $config = self::normalize($config, $legacyFallback);
        $nodeRef['header_banner'] = $config;
        $nodeRef[$legacyImageKey] = self::primaryImageUrl($config, $legacyFallback);

        return null;
    }
}
