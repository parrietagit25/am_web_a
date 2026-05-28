<?php
/**
 * Publicaciones de Renting (datos + render de contenido)
 */

function sanitizeRentingHtml($html) {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*("([^"]*)"|\'([^\']*)\'|[^\s>]+)/i', '', $html);
    return $html;
}

function isRentingHtmlContent($raw) {
    $trimmed = trim($raw ?? '');
    if ($trimmed === '') {
        return false;
    }
    if (strpos($trimmed, '&lt;') !== false || strpos($trimmed, '&gt;') !== false) {
        $trimmed = html_entity_decode($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return (bool) preg_match('/<\s*[a-z][a-z0-9]*\b/i', $trimmed);
}

function normalizeRentingRawContent($raw) {
    $trimmed = trim($raw ?? '');
    if ($trimmed === '' || !isRentingHtmlContent($trimmed)) {
        return $trimmed;
    }
    if (strpos($trimmed, '&lt;') !== false || strpos($trimmed, '&gt;') !== false) {
        return html_entity_decode($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return $trimmed;
}

function renderRentingArticleContent($raw) {
    if ($raw === null || $raw === '') {
        return '';
    }

    $raw = normalizeRentingRawContent($raw);

    if (isRentingHtmlContent($raw)) {
        return sanitizeRentingHtml($raw);
    }

    $formatInline = function ($text) {
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
                $html .= '<ul class="renting-checklist list-unstyled mb-4">';
                $inList = true;
            }
            $html .= '<li class="d-flex align-items-start gap-2 mb-2">';
            $html .= '<i class="bi bi-check-square-fill renting-check-icon flex-shrink-0"></i>';
            $html .= '<span>' . $formatInline($matches[1]) . '</span>';
            $html .= '</li>';
            continue;
        }

        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }

        $html .= '<p class="renting-article-paragraph">' . $formatInline($trimmed) . '</p>';
    }

    if ($inList) {
        $html .= '</ul>';
    }

    return $html;
}

function fixRentingServiciosImagePaths($html) {
    $replacements = [
        '/assets//assets/img/placa.PNG' => '/assets/img/placa.PNG',
        '/assets//assets/img/mantenimiento.PNG' => '/assets/img/mantenimiento.PNG',
        'assets/img/revisado-placa.jpg' => '/assets/img/placa.PNG',
        '/assets/img/revisado-placa.jpg' => '/assets/img/placa.PNG',
        'assets/img/mantenimiento-automarket.jpg' => '/assets/img/mantenimiento.PNG',
        '/assets/img/mantenimiento-automarket.jpg' => '/assets/img/mantenimiento.PNG',
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $html);
}

function getRentingSectionIntroRaw($section) {
    if (!empty($section['intro_html'])) {
        return normalizeRentingRawContent($section['intro_html']);
    }
    $paragraphs = $section['paragraphs'] ?? [];
    if (empty($paragraphs)) {
        return '';
    }
    $combined = implode("\n", $paragraphs);
    if (isRentingHtmlContent($combined) || isRentingHtmlContent($paragraphs[0] ?? '')) {
        return normalizeRentingRawContent($combined);
    }
    return '';
}

function getRentingServiciosIntroRaw($servicios) {
    $html = getRentingSectionIntroRaw($servicios);
    return $html !== '' ? fixRentingServiciosImagePaths($html) : '';
}

function getRentingSectionParagraphsText($section) {
    $introRaw = getRentingSectionIntroRaw($section);
    if ($introRaw !== '') {
        return $introRaw;
    }
    return implode("\n\n", $section['paragraphs'] ?? []);
}

function renderRentingServiciosField($raw) {
    if ($raw === null || trim($raw) === '') {
        return '';
    }
    if (isRentingHtmlContent($raw)) {
        return renderRentingArticleContent($raw);
    }
    return '<p class="renting-servicios-paragraph">' . nl2br(esc($raw)) . '</p>';
}

function getRentingPosts($contentService) {
    $rentingData = $contentService->get('renting', []);
    return $rentingData['posts'] ?? [];
}

function findRentingPostById($contentService, $postId) {
    foreach (getRentingPosts($contentService) as $post) {
        if (intval($post['id']) === intval($postId)) {
            return $post;
        }
    }
    return null;
}
