<?php
/**
 * Etiquetas de resaltado para inventario seminuevos (estilo marketplace).
 * Las asignaciones viven en site_data (sobreviven al sync por VIN/placa).
 */
class InventoryHighlightService
{
    /** @return array<string, array{key: string, label: string, class: string}> */
    public static function catalog(): array
    {
        return [
            'nuevo' => [
                'key' => 'nuevo',
                'label' => 'Nuevo',
                'class' => 'inv-highlight--nuevo',
            ],
            'ultimas_unidades' => [
                'key' => 'ultimas_unidades',
                'label' => 'Últimas unidades',
                'class' => 'inv-highlight--ultimas',
            ],
            'pocas_unidades' => [
                'key' => 'pocas_unidades',
                'label' => 'Pocas unidades disponibles',
                'class' => 'inv-highlight--pocas',
            ],
            'oferta' => [
                'key' => 'oferta',
                'label' => 'Oferta',
                'class' => 'inv-highlight--oferta',
            ],
            'destacado' => [
                'key' => 'destacado',
                'label' => 'Destacado',
                'class' => 'inv-highlight--destacado',
            ],
        ];
    }

    public static function normalizeKey(string $raw): string
    {
        $key = strtolower(trim($raw));
        $catalog = self::catalog();

        return isset($catalog[$key]) ? $key : '';
    }

    /** @return array<string, string> */
    public static function getAssignments(array $seminuevosNode): array
    {
        $raw = $seminuevosNode['inventory_highlights']['assignments'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $storageKey => $badgeKey) {
            if (!is_string($storageKey)) {
                continue;
            }
            $badge = self::normalizeKey((string) $badgeKey);
            if ($badge === '') {
                continue;
            }
            $normalized[$storageKey] = $badge;
        }

        return $normalized;
    }

    /** @return list<string> */
    public static function storageKeysForVehicle(array $vehicle): array
    {
        $keys = [];

        $vin = strtoupper(trim((string) ($vehicle['VIN'] ?? '')));
        if ($vin !== '') {
            $keys[] = 'vin:' . $vin;
        }

        $plate = strtoupper(trim((string) ($vehicle['LicensePlate'] ?? '')));
        if ($plate !== '') {
            $keys[] = 'plate:' . $plate;
        }

        $id = intval($vehicle['id'] ?? 0);
        if ($id > 0) {
            $keys[] = 'id:' . $id;
        }

        return $keys;
    }

    /** @param array<string, string> $assignments */
    public static function resolveBadge(array $vehicle, array $assignments): ?array
    {
        foreach (self::storageKeysForVehicle($vehicle) as $storageKey) {
            if (!isset($assignments[$storageKey])) {
                continue;
            }
            $catalog = self::catalog();
            $badgeKey = $assignments[$storageKey];

            return $catalog[$badgeKey] ?? null;
        }

        return null;
    }

    /** @param array<string, string> $assignments */
    public static function resolveBadgeKey(array $vehicle, array $assignments): string
    {
        foreach (self::storageKeysForVehicle($vehicle) as $storageKey) {
            if (isset($assignments[$storageKey])) {
                return (string) $assignments[$storageKey];
            }
        }

        return '';
    }

    public static function setAssignment(array &$siteData, array $vehicle, string $highlightKey): void
    {
        if (!isset($siteData['seminuevos']) || !is_array($siteData['seminuevos'])) {
            $siteData['seminuevos'] = [];
        }
        if (!isset($siteData['seminuevos']['inventory_highlights']) || !is_array($siteData['seminuevos']['inventory_highlights'])) {
            $siteData['seminuevos']['inventory_highlights'] = ['assignments' => []];
        }
        if (!isset($siteData['seminuevos']['inventory_highlights']['assignments']) || !is_array($siteData['seminuevos']['inventory_highlights']['assignments'])) {
            $siteData['seminuevos']['inventory_highlights']['assignments'] = [];
        }

        $assignments = &$siteData['seminuevos']['inventory_highlights']['assignments'];
        $storageKeys = self::storageKeysForVehicle($vehicle);

        foreach ($storageKeys as $storageKey) {
            unset($assignments[$storageKey]);
        }

        $badgeKey = self::normalizeKey($highlightKey);
        if ($badgeKey === '' || $storageKeys === []) {
            return;
        }

        foreach ($storageKeys as $storageKey) {
            $assignments[$storageKey] = $badgeKey;
        }
    }
}
