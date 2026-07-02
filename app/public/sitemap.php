<?php
/**
 * Sitemap XML dinámico — páginas principales del sitio.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ContentService.php';
require_once __DIR__ . '/../services/SitemapService.php';

header('Content-Type: application/xml; charset=UTF-8');

$contentService = new ContentService();
echo SitemapService::renderXml($contentService);
