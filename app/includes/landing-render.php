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

function renderLandingBodyContent(?string $raw): string {
    if ($raw === null || trim($raw) === '') {
        return '';
    }
    $raw = normalizeLandingRawContent($raw);
    if ($raw === '') {
        return '';
    }
    if (isLandingHtmlContent($raw)) {
        return fixArticleRelativeAssetUrls(sanitizeLandingHtml($raw));
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
