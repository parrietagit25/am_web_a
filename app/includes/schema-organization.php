<?php
/**
 * Schema.org Organization global — entidad Automarket (AM-AIO-6A).
 *
 * Requiere: $contentService, $siteGlobal (desde header.php).
 */
require_once __DIR__ . '/schema-organization-helper.php';

if (!isset($contentService) || !is_object($contentService)) {
    return;
}

$_orgGlobal = is_array($siteGlobal ?? null) ? $siteGlobal : [];
$_orgSchema = am_schema_organization_build($_orgGlobal, $contentService);
am_schema_emit_json_ld($_orgSchema);

unset($_orgGlobal, $_orgSchema);
