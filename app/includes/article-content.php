<?php
/**
 * Render de contenido de artículos (blog RAC): HTML seguro o texto con formato simple.
 */

function sanitizeArticleHtml(string $html): string {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
    $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*\/?>/is', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*("([^"]*)"|\'([^\']*)\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $html);
    return $html;
}

function isArticleHtmlContent(?string $raw): bool {
    $trimmed = trim($raw ?? '');
    if ($trimmed === '') {
        return false;
    }
    if (strpos($trimmed, '&lt;') !== false || strpos($trimmed, '&gt;') !== false) {
        $trimmed = html_entity_decode($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return (bool) preg_match('/<\s*[a-z][a-z0-9]*\b/i', $trimmed);
}

function normalizeArticleRawContent(?string $raw): string {
    $trimmed = trim($raw ?? '');
    if ($trimmed === '') {
        return '';
    }
    if (strpos($trimmed, '&lt;') !== false || strpos($trimmed, '&gt;') !== false) {
        $decoded = html_entity_decode($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (isArticleHtmlContent($decoded)) {
            return $decoded;
        }
    }
    return $trimmed;
}

/**
 * Convierte rutas relativas de imágenes en uploads (ej. mod_FERIA.webp).
 */
function fixArticleRelativeAssetUrls(string $html): string {
    return preg_replace_callback(
        '/\s(src|href)=(["\'])(?!https?:\/\/|\/|#|mailto:|tel:|data:)([^"\']+)\2/i',
        static function (array $m): string {
            $path = $m[3];
            if (strpos($path, '/') === false) {
                $path = '/assets/img/uploads/' . ltrim($path, '/');
            } elseif ($path[0] !== '/') {
                $path = '/' . $path;
            }
            return ' ' . $m[1] . '="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '"';
        },
        $html
    );
}

function renderRacArticleContent(?string $raw): string {
    if ($raw === null || trim($raw) === '') {
        return '';
    }

    $raw = normalizeArticleRawContent($raw);

    if (isArticleHtmlContent($raw)) {
        return fixArticleRelativeAssetUrls(sanitizeArticleHtml($raw));
    }

    $formatInline = static function (string $text): string {
        $text = esc($text);
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        return $text;
    };

    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $html = '';
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^[-*•]\s+(.+)$/u', $trimmed, $matches)) {
            if (!$inList) {
                $html .= '<ul class="article-checklist mb-4">';
                $inList = true;
            }
            $html .= '<li class="mb-2">' . $formatInline($matches[1]) . '</li>';
            continue;
        }

        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }

        $html .= '<p class="mb-3">' . $formatInline($trimmed) . '</p>';
    }

    if ($inList) {
        $html .= '</ul>';
    }

    return $html;
}
