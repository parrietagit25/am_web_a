<?php
/**
 * Sitemap XML dinámico — sin sesión ni cookies (crawler-friendly).
 *
 * No carga config.php para evitar session_start() (PHPSESSID + Cache-Control no-store).
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

date_default_timezone_set('America/Panama');

require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/SitemapService.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$contentService = new ContentService();
echo SitemapService::renderXml($contentService);
