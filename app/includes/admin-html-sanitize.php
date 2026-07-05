<?php
/**
 * Sanitización mínima de HTML guardado desde admin (Summernote / textarea).
 * Preserva etiquetas estructurales; elimina scripts y atributos on*.
 */
declare(strict_types=1);

require_once __DIR__ . '/renting-posts.php';

function sanitizeAdminHtmlContent(string $html): string
{
    $html = normalizeRentingRawContent($html);
    return sanitizeRentingHtml($html);
}
