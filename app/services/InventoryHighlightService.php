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
            'promo' => [
                'key' => 'promo',
                'label' => 'Promo',
                'class' => 'inv-highlight--promo',
            ],
            'featured' => [
                'key' => 'featured',
                'label' => 'Destacado',
                'class' => 'inv-highlight--featured',
            ],
            'recommended' => [
                'key' => 'recommended',
                'label' => 'Recomendado',
                'class' => 'inv-highlight--recommended',
            ],
            'popular' => [
                'key' => 'popular',
                'label' => 'Más buscado',
                'class' => 'inv-highlight--popular',
            ],
            'custom' => [
                'key' => 'custom',
                'label' => 'Personalizado',
                'class' => 'inv-highlight--custom',
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

    /** @return array<string, array{enabled: bool, text: string}> */
    public static function getMetadata(array $seminuevosNode): array
    {
        $raw = $seminuevosNode['inventory_highlights']['meta'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $metadata = [];
        foreach ($raw as $storageKey => $values) {
            if (!is_string($storageKey) || !is_array($values)) {
                continue;
            }
            try {
                $metadata[$storageKey] = [
                    'enabled' => !array_key_exists('enabled', $values) || !empty($values['enabled']),
                    'text' => self::normalizeVisualText((string) ($values['text'] ?? '')),
                ];
            } catch (InvalidArgumentException $e) {
                continue;
            }
        }

        return $metadata;
    }

    public static function normalizeVisualText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($length > 60) {
            throw new InvalidArgumentException('El texto de la etiqueta no puede superar 60 caracteres.');
        }
        if (preg_match('/[\r\n]/u', $text)
            || strip_tags($text) !== $text
            || preg_match('/javascript\s*:|(?:^|\s)on[a-z]+\s*=/iu', $text)) {
            throw new InvalidArgumentException('El texto de la etiqueta contiene contenido no permitido.');
        }

        return $text;
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
    public static function resolveBadge(array $vehicle, array $assignments, array $metadata = []): ?array
    {
        foreach (self::storageKeysForVehicle($vehicle) as $storageKey) {
            if (!isset($assignments[$storageKey])) {
                continue;
            }
            $catalog = self::catalog();
            $badgeKey = $assignments[$storageKey];
            $badge = $catalog[$badgeKey] ?? null;
            if ($badge === null) {
                return null;
            }
            $meta = $metadata[$storageKey] ?? ['enabled' => true, 'text' => ''];
            if (empty($meta['enabled'])) {
                return null;
            }
            $text = trim((string) ($meta['text'] ?? ''));
            if ($text !== '') {
                $badge['label'] = $text;
            }

            return $badge;
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

    /**
     * @param array<string, array{enabled: bool, text: string}> $metadata
     * @return array{enabled: bool, text: string}
     */
    public static function resolveMetadata(array $vehicle, array $metadata): array
    {
        foreach (self::storageKeysForVehicle($vehicle) as $storageKey) {
            if (isset($metadata[$storageKey])) {
                return $metadata[$storageKey];
            }
        }

        return ['enabled' => true, 'text' => ''];
    }

    /**
     * @param array{enabled?: mixed, text?: mixed}|null $visualMetadata
     */
    public static function setAssignment(
        array &$siteData,
        array $vehicle,
        string $highlightKey,
        ?array $visualMetadata = null
    ): void
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
        if (!isset($siteData['seminuevos']['inventory_highlights']['meta']) || !is_array($siteData['seminuevos']['inventory_highlights']['meta'])) {
            $siteData['seminuevos']['inventory_highlights']['meta'] = [];
        }

        $assignments = &$siteData['seminuevos']['inventory_highlights']['assignments'];
        $metadata = &$siteData['seminuevos']['inventory_highlights']['meta'];
        $storageKeys = self::storageKeysForVehicle($vehicle);
        $existingMetadata = ['enabled' => true, 'text' => ''];
        foreach ($storageKeys as $storageKey) {
            if (isset($metadata[$storageKey]) && is_array($metadata[$storageKey])) {
                $existingMetadata = [
                    'enabled' => !array_key_exists('enabled', $metadata[$storageKey]) || !empty($metadata[$storageKey]['enabled']),
                    'text' => self::normalizeVisualText((string) ($metadata[$storageKey]['text'] ?? '')),
                ];
                break;
            }
        }

        foreach ($storageKeys as $storageKey) {
            unset($assignments[$storageKey]);
            unset($metadata[$storageKey]);
        }

        $badgeKey = self::normalizeKey($highlightKey);
        if ($badgeKey === '' || $storageKeys === []) {
            return;
        }

        foreach ($storageKeys as $storageKey) {
            $assignments[$storageKey] = $badgeKey;
        }

        $normalizedMetadata = $visualMetadata === null
            ? $existingMetadata
            : [
                'enabled' => !array_key_exists('enabled', $visualMetadata) || !empty($visualMetadata['enabled']),
                'text' => self::normalizeVisualText((string) ($visualMetadata['text'] ?? '')),
            ];
        if (!$normalizedMetadata['enabled'] || $normalizedMetadata['text'] !== '') {
            foreach ($storageKeys as $storageKey) {
                $metadata[$storageKey] = $normalizedMetadata;
            }
        }
    }

    /**
     * Tras el pase de inventario: re-enlaza etiquetas al VIN/placa/id actuales
     * y conserva anclas por VIN aunque el vehículo salga temporalmente del inventario.
     *
     * @return array{relinked: int, restored: int, vin_preserved: int, saved: bool}
     */
    public static function reconcileAfterInventorySync(): array
    {
        require_once __DIR__ . '/ContentService.php';
        require_once __DIR__ . '/Database.php';

        $contentService = new ContentService();
        $siteData = $contentService->getAll();
        $db = Database::getInstance();

        $assignments = self::getAssignments($siteData['seminuevos'] ?? []);
        $vehicles = $db->select(
            'SELECT id, VIN, LicensePlate FROM Automarket_Invs_web'
        );

        $vinAnchors = self::buildVinAnchors($assignments, $vehicles);
        $idsInDb = [];
        $platesInDb = [];
        $vinsInDb = [];

        foreach ($vehicles as $vehicle) {
            $idsInDb[intval($vehicle['id'] ?? 0)] = true;
            $vin = strtoupper(trim((string) ($vehicle['VIN'] ?? '')));
            $plate = strtoupper(trim((string) ($vehicle['LicensePlate'] ?? '')));
            if ($vin !== '') {
                $vinsInDb[$vin] = true;
            }
            if ($plate !== '') {
                $platesInDb[$plate] = true;
            }
        }

        $relinked = 0;
        $restored = 0;

        foreach ($vehicles as $vehicle) {
            $vin = strtoupper(trim((string) ($vehicle['VIN'] ?? '')));
            $currentBadge = self::resolveBadgeKey($vehicle, $assignments);

            if ($currentBadge === '' && $vin !== '' && isset($vinAnchors[$vin])) {
                self::setAssignment($siteData, $vehicle, $vinAnchors[$vin]);
                $assignments = self::getAssignments($siteData['seminuevos'] ?? []);
                $restored++;
                continue;
            }

            if ($currentBadge !== '') {
                self::setAssignment($siteData, $vehicle, $currentBadge);
                $assignments = self::getAssignments($siteData['seminuevos'] ?? []);
                if ($vin !== '') {
                    $vinAnchors[$vin] = $currentBadge;
                }
                $relinked++;
            }
        }

        if (!isset($siteData['seminuevos']['inventory_highlights']['assignments'])
            || !is_array($siteData['seminuevos']['inventory_highlights']['assignments'])) {
            $siteData['seminuevos']['inventory_highlights']['assignments'] = [];
        }

        $assignments = &$siteData['seminuevos']['inventory_highlights']['assignments'];

        foreach ($vinAnchors as $vin => $badge) {
            $assignments['vin:' . $vin] = $badge;
        }

        foreach (array_keys($assignments) as $storageKey) {
            if (str_starts_with($storageKey, 'id:')) {
                $id = intval(substr($storageKey, 3));
                if ($id <= 0 || !isset($idsInDb[$id])) {
                    unset($assignments[$storageKey]);
                }
                continue;
            }

            if (str_starts_with($storageKey, 'plate:')) {
                $plate = substr($storageKey, 6);
                if ($plate === '' || !isset($platesInDb[$plate])) {
                    unset($assignments[$storageKey]);
                }
            }
        }

        if (isset($siteData['seminuevos']['inventory_highlights']['meta'])
            && is_array($siteData['seminuevos']['inventory_highlights']['meta'])) {
            foreach (array_keys($siteData['seminuevos']['inventory_highlights']['meta']) as $storageKey) {
                if (!isset($assignments[$storageKey])) {
                    unset($siteData['seminuevos']['inventory_highlights']['meta'][$storageKey]);
                }
            }
        }

        $vinPreserved = count($vinAnchors);
        $saved = $contentService->saveAll($siteData);

        if ($relinked > 0 || $restored > 0) {
            am_log(sprintf(
                'Inventory highlights reconcile: %d re-enlazadas, %d restauradas, %d anclas VIN',
                $relinked,
                $restored,
                $vinPreserved
            ));
        }

        return [
            'relinked' => $relinked,
            'restored' => $restored,
            'vin_preserved' => $vinPreserved,
            'saved' => $saved,
        ];
    }

    /**
     * @param array<string, string> $assignments
     * @param list<array<string, mixed>> $vehicles
     * @return array<string, string> VIN => badge key
     */
    private static function buildVinAnchors(array $assignments, array $vehicles): array
    {
        $anchors = [];

        foreach ($assignments as $storageKey => $badge) {
            if (str_starts_with($storageKey, 'vin:')) {
                $vin = substr($storageKey, 4);
                if ($vin !== '') {
                    $anchors[$vin] = $badge;
                }
            }
        }

        $byId = [];
        $byPlate = [];
        foreach ($vehicles as $vehicle) {
            $byId[intval($vehicle['id'] ?? 0)] = $vehicle;
            $plate = strtoupper(trim((string) ($vehicle['LicensePlate'] ?? '')));
            if ($plate !== '') {
                $byPlate[$plate] = $vehicle;
            }
        }

        foreach ($assignments as $storageKey => $badge) {
            if (str_starts_with($storageKey, 'id:')) {
                $id = intval(substr($storageKey, 3));
                if ($id > 0 && isset($byId[$id])) {
                    $vin = strtoupper(trim((string) ($byId[$id]['VIN'] ?? '')));
                    if ($vin !== '') {
                        $anchors[$vin] = $badge;
                    }
                }
                continue;
            }

            if (str_starts_with($storageKey, 'plate:')) {
                $plate = substr($storageKey, 6);
                if ($plate !== '' && isset($byPlate[$plate])) {
                    $vin = strtoupper(trim((string) ($byPlate[$plate]['VIN'] ?? '')));
                    if ($vin !== '') {
                        $anchors[$vin] = $badge;
                    }
                }
            }
        }

        return $anchors;
    }
}
