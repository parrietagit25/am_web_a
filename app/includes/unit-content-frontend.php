<?php
/**
 * Helpers de lectura de contenido unificado para el frontend.
 */
require_once __DIR__ . '/../services/UnitContentService.php';

/** @param array<string, mixed> $item */
function unit_content_to_card(array $item): array
{
    return [
        'id' => $item['id'] ?? 0,
        'date' => $item['date'] ?? '',
        'title' => $item['title'] ?? '',
        'desc' => $item['excerpt'] ?? '',
        'link_text' => $item['link_text'] ?? 'Ver Más',
        'thumbnail' => $item['thumbnail'] ?? '',
        'banner' => $item['banner'] ?? ($item['thumbnail'] ?? ''),
        'subheading' => $item['subheading'] ?? '',
        'description' => $item['description'] ?? '',
        'content' => $item['content'] ?? '',
        'show_on_home' => $item['show_on_home'] ?? true,
        'source_type' => $item['source_type'] ?? 'news',
        'detail_url' => $item['detail_url'] ?? '',
    ];
}

function unit_content_get_items(ContentService $contentService, string $unitKey, string $type): array
{
    $siteData = $contentService->getAll();
    UnitContentService::ensureMigrated($siteData, $unitKey);

    $items = [];
    foreach (UnitContentService::getItems($siteData, $unitKey, $type) as $item) {
        if (empty($item['published']) || !UnitContentService::isWithinSchedule($item)) {
            continue;
        }
        $item['detail_url'] = UnitContentService::detailUrl($unitKey, $type, intval($item['id'] ?? 0));
        $items[] = unit_content_to_card($item);
    }

    return $items;
}

function unit_content_find_article(ContentService $contentService, string $unitKey, string $type, int $id): ?array
{
    $siteData = $contentService->getAll();
    UnitContentService::ensureMigrated($siteData, $unitKey);
    $item = UnitContentService::findItem($siteData, $unitKey, $type, $id);
    if (!$item || empty($item['published']) || !UnitContentService::isWithinSchedule($item)) {
        if ($unitKey === 'rentacar' && $type === 'news' && $id > 0) {
            foreach (UnitContentService::getLegacyNoticias($siteData, $unitKey) as $legacy) {
                if (intval($legacy['id'] ?? 0) === $id) {
                    return unit_content_to_card(UnitContentService::legacyNoticiaToNews($legacy));
                }
            }
        }

        return null;
    }

    return unit_content_to_card($item);
}

function unit_content_get_spotlight(ContentService $contentService, string $unitKey): array
{
    $siteData = $contentService->getAll();
    UnitContentService::ensureMigrated($siteData, $unitKey);
    $resolved = UnitContentService::getResolvedHomeSpotlight($siteData, $unitKey);
    $cards = [];
    foreach ($resolved as $item) {
        $cards[] = unit_content_to_card($item);
    }

    return $cards;
}

function unit_content_get_latest_home(ContentService $contentService, string $unitKey, int $limit = 4): array
{
    $siteData = $contentService->getAll();
    UnitContentService::ensureMigrated($siteData, $unitKey);
    $settings = UnitContentService::getContentNode($siteData, $unitKey)['settings'] ?? [];
    if (empty($settings['latest_show_on_home'])) {
        return [];
    }

    $limit = max(1, min(12, intval($settings['latest_home_limit'] ?? $limit)));
    $items = [];
    foreach (UnitContentService::getItems($siteData, $unitKey, 'latest') as $item) {
        if (empty($item['published']) || !UnitContentService::isWithinSchedule($item)) {
            continue;
        }
        if (!empty($item['show_on_home']) || !isset($item['show_on_home'])) {
            $item['detail_url'] = UnitContentService::detailUrl($unitKey, 'latest', intval($item['id'] ?? 0));
            $items[] = unit_content_to_card($item);
        }
        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function unit_content_home_block_enabled(ContentService $contentService, string $unitKey): bool
{
    $siteData = $contentService->getAll();
    UnitContentService::ensureMigrated($siteData, $unitKey);
    $settings = UnitContentService::getContentNode($siteData, $unitKey)['settings'] ?? [];

    return !empty($settings['home_block_enabled']);
}

function unit_content_rotation_interval(ContentService $contentService, string $unitKey): int
{
    $siteData = $contentService->getAll();
    $settings = UnitContentService::getContentNode($siteData, $unitKey)['settings'] ?? [];

    return max(3000, intval($settings['home_rotation_interval_ms'] ?? 6000));
}

function unit_content_home_display_mode(ContentService $contentService, string $unitKey): string
{
    $siteData = $contentService->getAll();
    $settings = UnitContentService::getContentNode($siteData, $unitKey)['settings'] ?? [];

    return ($settings['home_display_mode'] ?? 'rotation') === 'single' ? 'single' : 'rotation';
}
