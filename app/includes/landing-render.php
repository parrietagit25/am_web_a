<?php
/**
 * Render de landing pages independientes (HTML libre, sin layout del sitio).
 */
require_once __DIR__ . '/article-content.php';

function normalizeLandingRawContent(?string $raw): string {
    return normalizeArticleRawContent($raw);
}

function isLandingHtmlContent(?string $raw): bool {
    return isArticleHtmlContent($raw);
}

function isLandingFullDocument(?string $raw): bool {
    $trimmed = normalizeLandingRawContent($raw ?? '');
    if ($trimmed === '') {
        return false;
    }
    return (bool) preg_match('/^\s*<!DOCTYPE\b|^\s*<html\b/i', $trimmed);
}

/** Permite style/link; quita script, eventos inline y javascript: URLs. */
function sanitizeLandingHtml(string $html): string {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
    $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
    $html = preg_replace('/<embed\b[^>]*\/?>/is', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*("([^"]*)"|\'([^\']*)\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $html);
    return $html;
}

function extractLandingEmbeddedStyles(string $html): array {
    $styles = '';
    $clean = preg_replace_callback('/<style\b[^>]*>.*?<\/style>/is', function (array $m) use (&$styles): string {
        $styles .= $m[0];
        return '';
    }, $html);
    return [trim($clean), $styles];
}

/** Envuelve h1/h3/p sueltos en secciones con clases de landing-base.css. */
function enhanceLandingHtmlStructure(string $html): string {
    if ($html === '' || stripos($html, 'landing-hero') !== false || stripos($html, 'landing-features') !== false) {
        return $html;
    }
    if (!preg_match('/<h1\b/i', $html) || !preg_match('/<h3\b/i', $html)) {
        return $html;
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $wrapped = '<div id="landing-root">' . $html . '</div>';
    $doc->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $root = $doc->getElementById('landing-root');
    if (!$root) {
        return $html;
    }

    $hero = $doc->createElement('section');
    $hero->setAttribute('class', 'landing-hero');

    $features = $doc->createElement('section');
    $features->setAttribute('class', 'landing-features');

    $nodes = [];
    foreach ($root->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) === '') {
            continue;
        }
        $nodes[] = $child;
    }

    $i = 0;
    $count = count($nodes);
    while ($i < $count) {
        $node = $nodes[$i];
        $name = strtolower($node->nodeName ?? '');

        if ($name === 'h1') {
            $hero->appendChild($node->cloneNode(true));
            if ($i + 1 < $count && strtolower($nodes[$i + 1]->nodeName ?? '') === 'p') {
                $hero->appendChild($nodes[$i + 1]->cloneNode(true));
                $i += 2;
            } else {
                $i++;
            }
            continue;
        }

        if ($name === 'h3') {
            $card = $doc->createElement('div');
            $card->setAttribute('class', 'landing-feature');
            $card->appendChild($node->cloneNode(true));
            if ($i + 1 < $count && strtolower($nodes[$i + 1]->nodeName ?? '') === 'p') {
                $card->appendChild($nodes[$i + 1]->cloneNode(true));
                $i += 2;
            } else {
                $i++;
            }
            $features->appendChild($card);
            continue;
        }

        $i++;
    }

    if (!$hero->hasChildNodes() && !$features->hasChildNodes()) {
        return $html;
    }

    while ($root->firstChild) {
        $root->removeChild($root->firstChild);
    }
    if ($hero->hasChildNodes()) {
        $root->appendChild($hero);
    }
    if ($features->hasChildNodes()) {
        $root->appendChild($features);
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out !== '' ? $out : $html;
}

function renderLandingBodyContent(?string $raw): string {
    if ($raw === null || trim($raw) === '') {
        return '';
    }
    $raw = normalizeLandingRawContent($raw);
    if ($raw === '') {
        return '';
    }
    if (isLandingHtmlContent($raw)) {
        $html = fixArticleRelativeAssetUrls(sanitizeLandingHtml($raw));
        return enhanceLandingHtmlStructure($html);
    }
    return renderRacArticleContent($raw);
}

function landing_public_path(string $slug): string {
    $slug = trim($slug, '/');
    return '/l/' . rawurlencode($slug);
}

function landing_public_url(string $slug): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . landing_public_path($slug);
}
