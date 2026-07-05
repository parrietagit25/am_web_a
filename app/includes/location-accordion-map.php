<?php
/**
 * Helper PHP para registrar mapas Leaflet en acordeones de sucursales.
 */

declare(strict_types=1);

if (!function_exists('am_location_map_reset')) {
    function am_location_map_reset(): void
    {
        $GLOBALS['_am_location_map_configs'] = [];
    }
}

if (!function_exists('am_location_map_has_coords')) {
    /** @param mixed $lat @param mixed $lng */
    function am_location_map_has_coords($lat, $lng): bool
    {
        return trim((string) $lat) !== '' && trim((string) $lng) !== '';
    }
}

if (!function_exists('am_location_map_register')) {
    /**
     * @param array{
     *   mapId: string,
     *   collapseId: string,
     *   lat?: mixed,
     *   lng?: mixed,
     *   title?: string,
     *   subtitle?: string,
     *   autoInit?: bool
     * } $config
     */
    function am_location_map_register(array $config): void
    {
        if (!isset($GLOBALS['_am_location_map_configs'])) {
            am_location_map_reset();
        }

        $latRaw = $config['lat'] ?? null;
        $lngRaw = $config['lng'] ?? null;
        $hasCoords = am_location_map_has_coords($latRaw, $lngRaw);

        $GLOBALS['_am_location_map_configs'][] = [
            'mapId' => (string) ($config['mapId'] ?? ''),
            'collapseId' => (string) ($config['collapseId'] ?? ''),
            'lat' => $hasCoords ? (float) $latRaw : null,
            'lng' => $hasCoords ? (float) $lngRaw : null,
            'title' => (string) ($config['title'] ?? ''),
            'subtitle' => (string) ($config['subtitle'] ?? ''),
            'autoInit' => !empty($config['autoInit']),
        ];
    }
}

if (!function_exists('am_location_map_render_assets')) {
    function am_location_map_render_assets(): void
    {
        static $rendered = false;
        if ($rendered) {
            return;
        }

        $configs = $GLOBALS['_am_location_map_configs'] ?? [];
        $needsLeaflet = false;
        foreach ($configs as $cfg) {
            if ($cfg['lat'] !== null && $cfg['lng'] !== null) {
                $needsLeaflet = true;
                break;
            }
        }
        if (!$needsLeaflet) {
            return;
        }

        $rendered = true;
        echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>' . "\n";
        echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>' . "\n";
    }
}

if (!function_exists('am_location_map_render_container')) {
    /**
     * @param mixed $lat @param mixed $lng
     */
    function am_location_map_render_container(
        string $mapId,
        $lat,
        $lng,
        string $mapUrl = '',
        string $extraClass = 'rounded-3 shadow-sm border w-100 flex-grow-1',
        int $minHeight = 280
    ): void {
        if (am_location_map_has_coords($lat, $lng)) {
            printf(
                '<div id="%s" class="%s" style="min-height:%dpx;background-color:#f1f3f7;z-index:1;"></div>',
                htmlspecialchars($mapId, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8'),
                $minHeight
            );
            return;
        }

        $placeholderClass = trim($extraClass . ' d-flex align-items-center justify-content-center bg-light');
        printf(
            '<div class="%s" style="min-height:%dpx;">',
            htmlspecialchars($placeholderClass, ENT_QUOTES, 'UTF-8'),
            max(180, $minHeight - 80)
        );
        if ($mapUrl !== '') {
            printf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm"><i class="bi bi-geo-alt me-1"></i>Ver en Google Maps</a>',
                htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8')
            );
        } else {
            echo '<span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Mapa no disponible para esta ubicación</span>';
        }
        echo '</div>';
    }
}

if (!function_exists('am_location_map_render_boot')) {
    function am_location_map_render_boot(): void
    {
        static $booted = false;
        if ($booted) {
            return;
        }

        $configs = $GLOBALS['_am_location_map_configs'] ?? [];
        if ($configs === []) {
            return;
        }

        $booted = true;
        $json = json_encode(
            $configs,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        );
        if ($json === false) {
            return;
        }

        echo '<script src="/assets/js/location-accordion-map.js"></script>' . "\n";
        echo '<script>window.AMLocationAccordionMap && window.AMLocationAccordionMap.init(' . $json . ');</script>' . "\n";
    }
}
