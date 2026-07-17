<?php
/**
 * Modelo normalizado de “Sobre Nosotros” por unidad.
 */
declare(strict_types=1);

require_once __DIR__ . '/UnitContentService.php';
require_once __DIR__ . '/HeaderBannerService.php';

class UnitAboutService
{
    /** @return array<string, mixed> */
    public static function normalize(array $node): array
    {
        return [
            'published' => !empty($node['published']),
            'title' => self::plain((string) ($node['title'] ?? '')),
            'subtitle' => self::plain((string) ($node['subtitle'] ?? '')),
            'main_image_url' => trim((string) ($node['main_image_url'] ?? '')),
            'main_image_alt' => self::plain((string) ($node['main_image_alt'] ?? '')),
            'body_html' => trim((string) ($node['body_html'] ?? '')),
            'cta_text' => self::plain((string) ($node['cta_text'] ?? '')),
            'cta_url' => self::sanitizeCtaUrl((string) ($node['cta_url'] ?? '')),
            'source' => (string) ($node['source'] ?? 'about_page'),
            'extra' => is_array($node['extra'] ?? null) ? $node['extra'] : [],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function resolve(array $siteData, string $unitKey): ?array
    {
        if (!UnitContentService::isSupportedUnit($unitKey, $siteData)) {
            return null;
        }

        if ($unitKey === 'renting') {
            $legacy = $siteData['renting']['sobre_nosotros'] ?? null;
            if (is_array($legacy) && self::hasRentingLegacyContent($legacy)) {
                if (array_key_exists('published', $legacy) && empty($legacy['published'])) {
                    return null;
                }
                $body = trim((string) ($legacy['intro_html'] ?? ''));
                if ($body === '' && is_array($legacy['paragraphs'] ?? null)) {
                    $body = implode('', array_map(
                        static fn ($p): string => '<p>' . htmlspecialchars((string) $p, ENT_QUOTES, 'UTF-8') . '</p>',
                        $legacy['paragraphs']
                    ));
                }
                return self::normalize([
                    'published' => true,
                    'title' => $legacy['page_title'] ?? $legacy['heading'] ?? '',
                    'subtitle' => $legacy['subtitle'] ?? $legacy['heading'] ?? '',
                    'main_image_url' => $legacy['main_image_url'] ?? '',
                    'main_image_alt' => $legacy['main_image_alt'] ?? '',
                    'body_html' => $body,
                    'cta_text' => $legacy['cta_text'] ?? '',
                    'cta_url' => $legacy['cta_url'] ?? '',
                    'source' => 'legacy_renting',
                    'extra' => ['gallery' => $legacy['gallery'] ?? []],
                ]);
            }
        }

        if ($unitKey === 'taller') {
            $legacy = $siteData['taller']['sobre_nosotros'] ?? null;
            if (is_array($legacy) && self::hasTallerLegacyContent($legacy)) {
                if (array_key_exists('published', $legacy) && empty($legacy['published'])) {
                    return null;
                }
                return self::normalize([
                    'published' => true,
                    'title' => $legacy['page_title'] ?? $legacy['section_title'] ?? '',
                    'subtitle' => $legacy['subtitle'] ?? $legacy['right_title'] ?? '',
                    'main_image_url' => $legacy['main_image_url'] ?? '',
                    'main_image_alt' => $legacy['main_image_alt'] ?? '',
                    'body_html' => $legacy['right_content'] ?? '',
                    'cta_text' => $legacy['cta_text'] ?? '',
                    'cta_url' => $legacy['cta_url'] ?? '',
                    'source' => 'legacy_taller',
                    'extra' => [
                        'bottom_title' => $legacy['bottom_title'] ?? '',
                        'stats' => $legacy['stats'] ?? [],
                    ],
                ]);
            }
        }

        $node = self::aboutNode($siteData, $unitKey);
        if (!is_array($node)) {
            return null;
        }
        $normalized = self::normalize($node);
        if (!$normalized['published'] || !self::hasRenderableContent($normalized)) {
            return null;
        }

        return $normalized;
    }

    /** @return array<string, mixed>|null */
    public static function aboutNode(array $siteData, string $unitKey): ?array
    {
        if (UnitContentService::isCustomUnit($unitKey)) {
            $node = $siteData['global']['business_units'][$unitKey]['about_page'] ?? null;
        } else {
            $dataKey = UnitContentService::unitDataKey($unitKey);
            $node = $siteData[$dataKey]['about_page'] ?? null;
        }

        return is_array($node) ? $node : null;
    }

    public static function publicUrl(string $unitKey): string
    {
        return '/sobre-nosotros.php?unit=' . rawurlencode($unitKey);
    }

    public static function sanitizeCtaUrl(string $url): string
    {
        return HeaderBannerService::sanitizeLinkUrl($url);
    }

    public static function sanitizeBodyHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        if (!class_exists('DOMDocument')) {
            throw new RuntimeException('DOMDocument es requerido para sanear el contenido.');
        }

        $allowedTags = ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'h2', 'h3', 'a', 'blockquote'];
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div id="am-about-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            throw new InvalidArgumentException('El contenido HTML no es válido.');
        }

        $root = $doc->getElementById('am-about-root');
        if (!$root instanceof DOMElement) {
            throw new InvalidArgumentException('El contenido HTML no es válido.');
        }
        $elements = [];
        foreach ($root->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }
        foreach ($elements as $element) {
            $tag = strtolower($element->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                throw new InvalidArgumentException('El contenido incluye una etiqueta HTML no permitida.');
            }
            $attributes = [];
            foreach ($element->attributes as $attribute) {
                $attributes[] = $attribute->name;
            }
            foreach ($attributes as $attributeName) {
                if ($tag !== 'a' || !in_array(strtolower($attributeName), ['href', 'target', 'rel'], true)) {
                    throw new InvalidArgumentException('El contenido incluye un atributo HTML no permitido.');
                }
            }
            if ($tag === 'a') {
                $href = self::sanitizeCtaUrl($element->getAttribute('href'));
                if ($href === '') {
                    throw new InvalidArgumentException('El contenido incluye un enlace no permitido.');
                }
                $element->setAttribute('href', $href);
                if ($element->getAttribute('target') === '_blank') {
                    $element->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $element->removeAttribute('target');
                    $element->removeAttribute('rel');
                }
            }
        }

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $doc->saveHTML($child);
        }

        return trim($clean);
    }

    private static function plain(string $value): string
    {
        return trim(strip_tags($value));
    }

    private static function hasRenderableContent(array $page): bool
    {
        return $page['title'] !== ''
            || $page['subtitle'] !== ''
            || $page['main_image_url'] !== ''
            || $page['body_html'] !== '';
    }

    private static function hasRentingLegacyContent(array $node): bool
    {
        return trim((string) ($node['intro_html'] ?? $node['page_title'] ?? $node['heading'] ?? '')) !== ''
            || !empty($node['paragraphs'])
            || !empty($node['gallery']);
    }

    private static function hasTallerLegacyContent(array $node): bool
    {
        return trim((string) ($node['right_content'] ?? $node['page_title'] ?? $node['section_title'] ?? '')) !== ''
            || trim((string) ($node['main_image_url'] ?? '')) !== ''
            || !empty($node['stats']);
    }
}
