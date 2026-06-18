<?php
/**
 * Sucursales globales (Generales) — importación y deduplicación.
 */
class GlobalSucursalesService
{
    /**
     * @param array<string, mixed> $siteData
     * @return array{imported: int, merged: int, total: int}
     */
    public static function importAll(array &$siteData): array
    {
        if (!isset($siteData['global']) || !is_array($siteData['global'])) {
            $siteData['global'] = [];
        }

        $byKey = [];
        $imported = 0;
        $merged = 0;

        foreach ($siteData['global']['sucursales'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $norm = self::normalizeRow($row);
            if ($norm['name'] === '') {
                continue;
            }
            $byKey[self::nameKey($norm['name'])] = $norm;
        }

        foreach (self::collectSourceRows($siteData) as $raw) {
            $norm = self::normalizeRow($raw);
            if ($norm['name'] === '') {
                continue;
            }
            $key = self::nameKey($norm['name']);
            if (isset($byKey[$key])) {
                $before = $byKey[$key];
                $byKey[$key] = self::mergeRows($byKey[$key], $norm);
                if ($byKey[$key] !== $before) {
                    $merged++;
                }
                continue;
            }
            $byKey[$key] = $norm;
            $imported++;
        }

        $list = [];
        $nextId = 1;
        foreach ($byKey as $row) {
            $row['id'] = $nextId++;
            $list[] = $row;
        }

        $siteData['global']['sucursales'] = $list;

        return [
            'imported' => $imported,
            'merged' => $merged,
            'total' => count($list),
        ];
    }

    /** @param array<string, mixed> $siteData */
    public static function hasSourceData(array $siteData): bool
    {
        return !empty(self::collectSourceRows($siteData));
    }

    /** @param array{imported: int, merged: int, total: int} $stats */
    public static function formatImportMessage(array $stats): string
    {
        $parts = [];
        if (($stats['imported'] ?? 0) > 0) {
            $parts[] = (int) $stats['imported'] . ' nueva(s)';
        }
        if (($stats['merged'] ?? 0) > 0) {
            $parts[] = (int) $stats['merged'] . ' actualizada(s) con datos faltantes';
        }
        $detail = !empty($parts) ? implode(', ', $parts) : 'sin cambios';
        return 'Importación de sucursales completada: ' . $detail . '. Total en el módulo: ' . (int) ($stats['total'] ?? 0) . '.';
    }

    /**
     * @param array<string, mixed> $siteData
     * @return list<array<string, mixed>>
     */
    private static function collectSourceRows(array $siteData): array
    {
        $out = [];
        $lists = [
            $siteData['homepage']['sucursales'] ?? [],
            $siteData['seminuevos']['sucursales'] ?? [],
            $siteData['leasing']['sucursales'] ?? [],
            $siteData['taller']['sucursales'] ?? [],
            $siteData['renting']['sucursales'] ?? [],
            $siteData['footer']['sucursales'] ?? [],
        ];

        foreach ($lists as $list) {
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $item) {
                if (is_array($item)) {
                    $out[] = $item;
                }
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id?: int, name: string, image_url: string, lat: string, lng: string}
     */
    private static function normalizeRow(array $row): array
    {
        $name = trim((string) ($row['name'] ?? ''));
        $imageUrl = trim((string) ($row['image_url'] ?? $row['photo_url'] ?? $row['image'] ?? ''));
        $lat = trim((string) ($row['lat'] ?? $row['latitude'] ?? ''));
        $lng = trim((string) ($row['lng'] ?? $row['longitude'] ?? ''));

        if ($lat === '' && $lng === '' && !empty($row['map_url'])) {
            [$mapLat, $mapLng] = self::parseCoordsFromMapUrl((string) $row['map_url']);
            if ($lat === '' && $mapLat !== '') {
                $lat = $mapLat;
            }
            if ($lng === '' && $mapLng !== '') {
                $lng = $mapLng;
            }
        }

        $normalized = [
            'name' => $name,
            'image_url' => $imageUrl,
            'lat' => $lat,
            'lng' => $lng,
        ];

        if (isset($row['id'])) {
            $normalized['id'] = (int) $row['id'];
        }

        return $normalized;
    }

    /** @return array{0: string, 1: string} */
    private static function parseCoordsFromMapUrl(string $mapUrl): array
    {
        if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $mapUrl, $m)) {
            return [$m[1], $m[2]];
        }
        if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $mapUrl, $m)) {
            return [$m[1], $m[2]];
        }

        return ['', ''];
    }

    /**
     * @param array{name: string, image_url: string, lat: string, lng: string} $base
     * @param array{name: string, image_url: string, lat: string, lng: string} $incoming
     * @return array{name: string, image_url: string, lat: string, lng: string}
     */
    private static function mergeRows(array $base, array $incoming): array
    {
        if ($base['image_url'] === '' && $incoming['image_url'] !== '') {
            $base['image_url'] = $incoming['image_url'];
        }
        if ($base['lat'] === '' && $incoming['lat'] !== '') {
            $base['lat'] = $incoming['lat'];
        }
        if ($base['lng'] === '' && $incoming['lng'] !== '') {
            $base['lng'] = $incoming['lng'];
        }

        return $base;
    }

    private static function nameKey(string $name): string
    {
        $key = mb_strtolower(trim($name));
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);
            if ($ascii !== false) {
                $key = $ascii;
            }
        }

        return preg_replace('/[^a-z0-9]+/', '', $key) ?? '';
    }
}
