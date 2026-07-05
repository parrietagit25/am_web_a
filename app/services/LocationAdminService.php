<?php
/**
 * Administración del maestro locations[] — AM-SEO-3C-D1.
 *
 * Fuente de verdad para listados públicos: *.location_refs (vía LocationService::listForUnit).
 * Overrides por unidad: locations[].units[$unitKey].
 * Silos legacy no se modifican.
 */
require_once __DIR__ . '/LocationService.php';

class LocationAdminService
{
    public const UNIT_KEYS = ['rentacar', 'seminuevos', 'leasing', 'renting', 'taller'];

    /** @var array<string, string> */
    public const UNIT_LABELS = [
        'rentacar'   => 'Rent A Car',
        'seminuevos' => 'Venta de Autos',
        'leasing'    => 'Leasing Operativo',
        'renting'    => 'Renting',
        'taller'     => 'Taller',
    ];

    private const BACKUP_DIR = __DIR__ . '/../storage/backups';

    /** @var array<string, string> */
    private const UNIT_SECTION_MAP = [
        'rentacar'   => 'homepage',
        'seminuevos' => 'seminuevos',
        'leasing'    => 'leasing',
        'renting'    => 'renting',
        'taller'     => 'taller',
    ];

    public static function sectionForUnit(string $unitKey): ?string
    {
        return self::UNIT_SECTION_MAP[$unitKey] ?? null;
    }

    public static function createBackup(string $siteDataPath): ?string
    {
        if (!is_readable($siteDataPath)) {
            return null;
        }

        if (!is_dir(self::BACKUP_DIR) && !@mkdir(self::BACKUP_DIR, 0775, true) && !is_dir(self::BACKUP_DIR)) {
            return null;
        }

        $stamp = date('Ymd-His');
        $backupPath = self::BACKUP_DIR . '/site_data-before-locations-admin-' . $stamp . '.json';

        return @copy($siteDataPath, $backupPath) ? $backupPath : null;
    }

    public static function isUnitAssociated(array $siteData, string $locationId, string $unitKey): bool
    {
        $section = self::sectionForUnit($unitKey);
        if ($section === null) {
            return false;
        }

        foreach ($siteData[$section]['location_refs'] ?? [] as $ref) {
            if (!is_array($ref)) {
                continue;
            }
            if (trim((string) ($ref['location_id'] ?? '')) === $locationId) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed>|null */
    public static function getUnitRef(array $siteData, string $locationId, string $unitKey): ?array
    {
        $section = self::sectionForUnit($unitKey);
        if ($section === null) {
            return null;
        }

        foreach ($siteData[$section]['location_refs'] ?? [] as $ref) {
            if (!is_array($ref)) {
                continue;
            }
            if (trim((string) ($ref['location_id'] ?? '')) === $locationId) {
                return $ref;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $siteData
     * @return array{ok: bool, error: string, backup: string, location_id: string}
     */
    public static function saveFromPost(array &$siteData, array $post): array
    {
        $locationId = trim((string) ($post['location_id'] ?? ''));
        if ($locationId === '') {
            return ['ok' => false, 'error' => 'Ubicación no válida.', 'backup' => '', 'location_id' => ''];
        }

        $locations = $siteData['locations'] ?? [];
        if (!is_array($locations)) {
            $locations = [];
        }

        $foundIdx = null;
        $existing = null;
        foreach ($locations as $idx => $loc) {
            if (!is_array($loc)) {
                continue;
            }
            if (trim((string) ($loc['id'] ?? '')) === $locationId) {
                $foundIdx = $idx;
                $existing = $loc;
                break;
            }
        }

        if ($existing === null || $foundIdx === null) {
            return ['ok' => false, 'error' => 'Ubicación no encontrada.', 'backup' => '', 'location_id' => $locationId];
        }

        $name = trim((string) ($post['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'error' => 'El nombre es obligatorio.', 'backup' => '', 'location_id' => $locationId];
        }

        $slug = trim((string) ($post['slug'] ?? ''));
        if ($slug === '') {
            $slug = LocationService::normalizeSlug($name);
        } else {
            $slug = LocationService::normalizeSlug($slug);
        }

        $racCode = strtoupper(trim((string) ($post['rac_code'] ?? '')));

        $locationService = new LocationService($siteData);
        if (!$locationService->isSlugUnique($slug, $locationId)) {
            return [
                'ok' => false,
                'error' => 'El slug «' . $slug . '» ya está en uso por otra ubicación.',
                'backup' => '',
                'location_id' => $locationId,
            ];
        }
        if (!$locationService->isRacCodeUnique($racCode, $locationId)) {
            return [
                'ok' => false,
                'error' => 'El código RAC «' . $racCode . '» ya está asignado a otra ubicación.',
                'backup' => '',
                'location_id' => $locationId,
            ];
        }

        $phonesRaw = trim((string) ($post['phones'] ?? ''));
        $phones = [];
        if ($phonesRaw !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $phonesRaw) ?: [] as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $phones[] = $line;
                }
            }
        }

        $mapEmbed = trim((string) ($post['map_embed_url'] ?? ''));
        $hoursDisplay = trim((string) ($post['hours_display'] ?? ''));

        $updated = $existing;
        $updated['name'] = $name;
        $updated['slug'] = $slug;
        $updated['location_label'] = trim((string) ($post['location_label'] ?? ''));
        $updated['address'] = trim((string) ($post['address'] ?? ''));
        $updated['city'] = trim((string) ($post['city'] ?? '')) ?: 'Ciudad de Panamá';
        $updated['country'] = trim((string) ($post['country'] ?? '')) ?: 'PA';
        $updated['lat'] = trim((string) ($post['lat'] ?? ''));
        $updated['lng'] = trim((string) ($post['lng'] ?? ''));
        $updated['image_url'] = trim((string) ($post['image_url'] ?? ''));
        $updated['map_url'] = $mapEmbed;
        $updated['map_embed_url'] = $mapEmbed;
        $updated['phones'] = $phones;
        $updated['whatsapp'] = trim((string) ($post['whatsapp'] ?? ''));
        $updated['email'] = trim((string) ($post['email'] ?? ''));
        $updated['rac_code'] = $racCode;
        $updated['active'] = !empty($post['active']);
        $updated['sort_order'] = (int) ($post['sort_order'] ?? ($existing['sort_order'] ?? 99));

        if (!isset($updated['hours']) || !is_array($updated['hours'])) {
            $updated['hours'] = [];
        }
        $updated['hours']['display'] = $hoursDisplay;

        if (!isset($updated['meta']) || !is_array($updated['meta'])) {
            $updated['meta'] = [];
        }
        $updated['meta']['updated_at'] = date('c');

        $units = is_array($existing['units'] ?? null) ? $existing['units'] : [];

        foreach (self::UNIT_KEYS as $unitKey) {
            $enabled = !empty($post['unit_enabled'][$unitKey]);
            $unitPost = is_array($post['unit_override'][$unitKey] ?? null)
                ? $post['unit_override'][$unitKey]
                : [];

            $refActive = !empty($unitPost['ref_active']);
            $refSort = isset($unitPost['ref_sort_order']) && $unitPost['ref_sort_order'] !== ''
                ? (int) $unitPost['ref_sort_order']
                : (int) ($updated['sort_order'] ?? 99);

            if ($enabled) {
                $override = is_array($units[$unitKey] ?? null) ? $units[$unitKey] : [];
                $override['active'] = $refActive;

                foreach (['phone', 'whatsapp', 'email'] as $field) {
                    if (!array_key_exists($field, $unitPost)) {
                        continue;
                    }
                    $val = trim((string) $unitPost[$field]);
                    if ($val === '') {
                        unset($override[$field], $override[$field . '_override']);
                    } else {
                        $override[$field] = $val;
                    }
                }

                $hoursOverride = trim((string) ($unitPost['hours_display'] ?? ''));
                if ($hoursOverride === '') {
                    unset($override['hours_display'], $override['schedule']);
                } else {
                    $override['hours_display'] = $hoursOverride;
                    $override['schedule'] = $hoursOverride;
                }

                if (isset($unitPost['ref_sort_order']) && $unitPost['ref_sort_order'] !== '') {
                    $override['sort_order'] = (int) $unitPost['ref_sort_order'];
                }

                $units[$unitKey] = $override;
            }

            self::syncUnitRef($siteData, $locationId, $unitKey, $enabled, $refActive, $refSort);
        }

        $updated['units'] = $units;
        $siteData['locations'][$foundIdx] = $updated;

        $siteDataPath = __DIR__ . '/../storage/site_data.json';
        $backupPath = self::createBackup($siteDataPath);
        if ($backupPath === null) {
            return [
                'ok' => false,
                'error' => 'No se pudo crear el backup en app/storage/backups/. No se guardaron cambios.',
                'backup' => '',
                'location_id' => $locationId,
            ];
        }

        return ['ok' => true, 'error' => '', 'backup' => $backupPath, 'location_id' => $locationId];
    }

    /**
     * Alta controlada en el maestro locations[] — solo desde Generales → Sucursales maestro.
     *
     * @param array<string, mixed> $siteData
     * @return array{ok: bool, error: string, backup: string, location_id: string}
     */
    public static function createFromPost(array &$siteData, array $post): array
    {
        $name = trim((string) ($post['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'error' => 'El nombre es obligatorio.', 'backup' => '', 'location_id' => ''];
        }

        $newId = self::generateNextLocationId($siteData);

        if (!isset($siteData['locations']) || !is_array($siteData['locations'])) {
            $siteData['locations'] = [];
        }

        $siteData['locations'][] = [
            'id'         => $newId,
            'slug'       => '',
            'name'       => $name,
            'active'     => true,
            'sort_order' => (int) ($post['sort_order'] ?? 99),
            'country'    => 'PA',
            'city'       => 'Ciudad de Panamá',
            'phones'     => [],
            'hours'      => ['display' => ''],
            'units'      => [],
            'meta'       => [
                'created_at' => date('c'),
                'sources'    => ['admin.create'],
            ],
        ];

        $post['location_id'] = $newId;

        return self::saveFromPost($siteData, $post);
    }

    /** Genera el siguiente id loc_XXX disponible en locations[]. */
    private static function generateNextLocationId(array $siteData): string
    {
        $max = 0;
        foreach ($siteData['locations'] ?? [] as $loc) {
            if (!is_array($loc)) {
                continue;
            }
            $id = trim((string) ($loc['id'] ?? ''));
            if (preg_match('/^loc_(\d+)$/', $id, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'loc_' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $siteData
     */
    private static function syncUnitRef(
        array &$siteData,
        string $locationId,
        string $unitKey,
        bool $enabled,
        bool $refActive,
        int $refSort
    ): void {
        $section = self::sectionForUnit($unitKey);
        if ($section === null) {
            return;
        }

        if (!isset($siteData[$section]) || !is_array($siteData[$section])) {
            $siteData[$section] = [];
        }

        $refs = $siteData[$section]['location_refs'] ?? [];
        if (!is_array($refs)) {
            $refs = [];
        }

        $newRefs = [];
        $found = false;

        foreach ($refs as $ref) {
            if (!is_array($ref)) {
                continue;
            }
            if (trim((string) ($ref['location_id'] ?? '')) !== $locationId) {
                $newRefs[] = $ref;
                continue;
            }

            if ($enabled) {
                $newRefs[] = [
                    'location_id' => $locationId,
                    'sort_order'    => $refSort,
                    'active'        => $refActive,
                ];
                $found = true;
            }
        }

        if ($enabled && !$found) {
            $newRefs[] = [
                'location_id' => $locationId,
                'sort_order'    => $refSort,
                'active'        => $refActive,
            ];
        }

        $siteData[$section]['location_refs'] = array_values($newRefs);
    }

    /**
     * @param array<string, mixed> $siteData
     * @return list<array<string, mixed>>
     */
    public static function sortedLocations(array $siteData): array
    {
        $locations = $siteData['locations'] ?? [];
        if (!is_array($locations)) {
            return [];
        }

        $list = array_values(array_filter($locations, function ($loc) {
            return is_array($loc) && trim((string) ($loc['id'] ?? '')) !== '';
        }));

        usort($list, function (array $a, array $b): int {
            $oa = (int) ($a['sort_order'] ?? 99);
            $ob = (int) ($b['sort_order'] ?? 99);

            return $oa !== $ob ? $oa - $ob : strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $list;
    }
}
