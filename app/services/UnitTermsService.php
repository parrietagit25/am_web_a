<?php
/**
 * Términos y condiciones independientes por unidad de negocio.
 */
declare(strict_types=1);

require_once __DIR__ . '/UnitContentService.php';
require_once __DIR__ . '/HeaderBannerService.php';

class UnitTermsService
{
    /** @return array<string, mixed> */
    public static function normalize(array $node): array
    {
        return [
            'published' => !array_key_exists('published', $node)
                || filter_var($node['published'], FILTER_VALIDATE_BOOLEAN),
            'title' => self::plain((string) ($node['title'] ?? '')),
            'subtitle' => self::plain((string) ($node['subtitle'] ?? '')),
            'body_html' => trim((string) ($node['body_html'] ?? '')),
            'source' => (string) ($node['source'] ?? 'terms_page'),
        ];
    }

    /** @return array<string, mixed>|null */
    public static function resolve(array $siteData, string $unitKey): ?array
    {
        if (!UnitContentService::isSupportedUnit($unitKey, $siteData)) {
            return null;
        }

        $node = self::termsNode($siteData, $unitKey);
        if (is_array($node)) {
            $normalized = self::normalize($node);

            return $normalized['published'] && self::hasRenderableContent($normalized)
                ? $normalized
                : null;
        }

        if ($unitKey !== 'rentacar') {
            return null;
        }

        $legacyBody = trim((string) ($siteData['homepage']['terminos_condiciones'] ?? ''));
        if ($legacyBody === '') {
            return null;
        }

        return self::normalize([
            'published' => true,
            'title' => 'Términos y Condiciones',
            'subtitle' => 'Información importante sobre las condiciones y coberturas de alquiler en Automarket Rent a Car.',
            'body_html' => $legacyBody,
            'source' => 'legacy_rentacar',
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function termsNode(array $siteData, string $unitKey): ?array
    {
        if (!UnitContentService::isSupportedUnit($unitKey, $siteData)) {
            return null;
        }

        if (UnitContentService::isCustomUnit($unitKey)) {
            $node = $siteData['global']['business_units'][$unitKey]['terms_page'] ?? null;
        } else {
            $node = $siteData[UnitContentService::unitDataKey($unitKey)]['terms_page'] ?? null;
        }

        return is_array($node) ? $node : null;
    }

    /** @param array<string, mixed> $node */
    public static function setNode(array &$siteData, string $unitKey, array $node): bool
    {
        if (!UnitContentService::isSupportedUnit($unitKey, $siteData)) {
            return false;
        }

        if (UnitContentService::isCustomUnit($unitKey)) {
            $siteData['global']['business_units'][$unitKey]['terms_page'] = $node;
        } else {
            $dataKey = UnitContentService::unitDataKey($unitKey);
            if (!isset($siteData[$dataKey]) || !is_array($siteData[$dataKey])) {
                $siteData[$dataKey] = [];
            }
            $siteData[$dataKey]['terms_page'] = $node;
        }

        return true;
    }

    /**
     * Valida y aplica una edición sin persistirla.
     *
     * @param array<string, mixed> $input
     */
    public static function apply(array &$siteData, string $unitKey, array $input): ?string
    {
        $unitKey = strtolower(trim($unitKey));
        if (!UnitContentService::isSupportedUnit($unitKey, $siteData)) {
            return 'Unidad no válida.';
        }

        $title = trim((string) ($input['terms_title'] ?? ''));
        $subtitle = trim((string) ($input['terms_subtitle'] ?? ''));
        if (strip_tags($title) !== $title || strip_tags($subtitle) !== $subtitle) {
            return 'Los campos de texto no permiten HTML.';
        }

        try {
            $bodyHtml = self::sanitizeBodyHtml((string) ($input['terms_body_html'] ?? ''));
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $e->getMessage();
        }

        $published = !empty($input['terms_published']);
        if ($published && ($title === '' || $bodyHtml === '')) {
            return 'Título y contenido son obligatorios para publicar.';
        }

        self::setNode($siteData, $unitKey, [
            'published' => $published,
            'title' => $title,
            'subtitle' => $subtitle,
            'body_html' => $bodyHtml,
        ]);

        return null;
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

        $html = preg_replace('/<(\s*\/?\s*)h1\b/i', '<$1h2', $html) ?? $html;
        $allowedTags = [
            'article', 'section', 'header', 'div', 'p', 'br',
            'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li',
            'h2', 'h3', 'h4', 'a', 'blockquote',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ];

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div id="am-terms-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            throw new InvalidArgumentException('El contenido HTML no es válido.');
        }

        $root = $doc->getElementById('am-terms-root');
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

            $attributeNames = [];
            foreach ($element->attributes as $attribute) {
                $attributeNames[] = $attribute->name;
            }
            foreach ($attributeNames as $attributeName) {
                $attribute = strtolower($attributeName);
                $allowed = $attribute === 'class'
                    || ($tag === 'a' && in_array($attribute, ['href', 'target', 'rel'], true));
                if (!$allowed) {
                    throw new InvalidArgumentException('El contenido incluye un atributo HTML no permitido.');
                }
            }

            if ($element->hasAttribute('class')
                && preg_match('/^[a-z0-9 _-]*$/i', $element->getAttribute('class')) !== 1) {
                throw new InvalidArgumentException('El contenido incluye una clase HTML no permitida.');
            }

            if ($tag === 'a') {
                $href = HeaderBannerService::sanitizeLinkUrl($element->getAttribute('href'));
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

    public static function publicUrl(string $unitKey): string
    {
        return $unitKey === 'rentacar'
            ? '/terminos-condiciones.php'
            : '/terminos-condiciones.php?unit=' . rawurlencode($unitKey);
    }

    public static function permissionKey(string $unitKey): string
    {
        return [
            'rentacar' => 'terms',
            'seminuevos' => 'semi_home',
            'leasing' => 'leasing_home',
            'renting' => 'renting_publicaciones',
            'taller' => 'taller_home',
        ][$unitKey] ?? 'global';
    }

    private static function plain(string $value): string
    {
        return trim(strip_tags($value));
    }

    /** @param array<string, mixed> $page */
    private static function hasRenderableContent(array $page): bool
    {
        return trim((string) ($page['body_html'] ?? '')) !== '';
    }
}
