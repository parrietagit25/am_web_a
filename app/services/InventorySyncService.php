<?php
/**
 * Sincronización de inventario seminuevos (temp → web).
 * Compatible con el flujo Python / GoDaddy (api_web.php + api_web_pasar_data.php).
 */
class InventorySyncService
{
    private const TEMP_TABLE = 'Automarket_Invs_web_temp';
    private const MAIN_TABLE = 'Automarket_Invs_web';

    /** Columnas compartidas entre temp y web (sin id ni Fechaa_log). */
    private const SYNC_COLUMNS = [
        'Transmission', 'Color', 'Make', 'Km', 'Code', 'LicensePlate', 'Model',
        'Chasis', 'Unit', 'Engine', 'Fuel', 'Price', 'PriceTax', 'Doors', 'CarType',
        'CC', 'LocationCode', 'LocationName', 'Interior', 'Headline', 'Description',
        'Photo', 'Status', 'Marked', 'Promo', 'PromoPrice', 'PromoPriceTax', 'LoadDate',
        'Prefijo', 'Year', 'VIN', 'trg_updatefechaWeb', 'update_stat', 'stat_master',
        'prioridad', 'Internacional', 'tipo_compra', 'foto_impel',
    ];

    public static function ingestBatch(array $items): array
    {
        InventorySyncSchema::ensure();
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $processed = 0;
        $errors = 0;
        $vins = [];

        $pdo->beginTransaction();
        try {
            foreach ($items as $raw) {
                if (!is_array($raw)) {
                    continue;
                }

                $data = self::normalizeVehicle($raw);
                $vin = $data['VIN'];
                if ($vin === '') {
                    continue;
                }

                $vins[] = $vin;
                $exists = (int) ($db->selectOne(
                    'SELECT COUNT(*) AS c FROM ' . self::TEMP_TABLE . ' WHERE VIN = :vin',
                    [':vin' => $vin]
                )['c'] ?? 0);

                if ($exists > 0) {
                    $sets = [];
                    $params = [':vin_where' => $vin];
                    foreach (self::SYNC_COLUMNS as $col) {
                        if ($col === 'VIN') {
                            continue;
                        }
                        $sets[] = "$col = :$col";
                        $params[":$col"] = $data[$col];
                    }
                    $sets[] = 'stat_master = 1';
                    $sets[] = 'prioridad = 0';
                    $sets[] = "foto_impel = ''";
                    $sql = 'UPDATE ' . self::TEMP_TABLE . ' SET ' . implode(', ', $sets) . ' WHERE VIN = :vin_where';
                    try {
                        $db->execute($sql, $params);
                        $processed++;
                    } catch (Throwable $e) {
                        $errors++;
                        am_log('Inventory sync UPDATE VIN=' . $vin . ': ' . $e->getMessage(), 'ERROR');
                    }
                } else {
                    $cols = self::SYNC_COLUMNS;
                    $placeholders = array_map(fn ($c) => ':' . $c, $cols);
                    $params = [];
                    foreach ($cols as $col) {
                        $params[':' . $col] = $data[$col];
                    }
                    $sql = 'INSERT INTO ' . self::TEMP_TABLE . ' (' . implode(', ', $cols) . ')
                            VALUES (' . implode(', ', $placeholders) . ')';
                    try {
                        $db->execute($sql, $params);
                        $processed++;
                    } catch (Throwable $e) {
                        $errors++;
                        am_log('Inventory sync INSERT VIN=' . $vin . ': ' . $e->getMessage(), 'ERROR');
                    }
                }
            }

            if (count($vins) > 0) {
                $placeholders = [];
                $params = [];
                foreach ($vins as $i => $vin) {
                    $key = ':vin' . $i;
                    $placeholders[] = $key;
                    $params[$key] = $vin;
                }
                $sql = 'UPDATE ' . self::TEMP_TABLE . ' SET stat_master = 2
                        WHERE VIN NOT IN (' . implode(', ', $placeholders) . ')';
                $db->execute($sql, $params);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        am_log(sprintf(
            'Inventory sync ingest: %d procesados, %d errores, %d VINs en lote',
            $processed,
            $errors,
            count($vins)
        ));

        return [
            'ok' => true,
            'processed' => $processed,
            'errors' => $errors,
            'vins_in_batch' => count($vins),
            'temp_count' => self::tempCount(),
        ];
    }

    public static function tempCount(): int
    {
        InventorySyncSchema::ensure();
        $db = Database::getInstance();
        return (int) ($db->selectOne('SELECT COUNT(*) AS c FROM ' . self::TEMP_TABLE)['c'] ?? 0);
    }

    public static function mainCount(): int
    {
        $db = Database::getInstance();
        return (int) ($db->selectOne(
            "SELECT COUNT(*) AS c FROM " . self::MAIN_TABLE . " WHERE Status = 'DISPONIBLE'"
        )['c'] ?? 0);
    }

    public static function pasarData(): array
    {
        InventorySyncSchema::ensure();
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $minVehicles = defined('INVENTORY_SYNC_MIN_VEHICLES') ? (int) INVENTORY_SYNC_MIN_VEHICLES : 50;
        $totalAutos = (int) ($db->selectOne('SELECT COUNT(*) AS c FROM ' . self::TEMP_TABLE)['c'] ?? 0);

        if ($totalAutos < $minVehicles) {
            $msg = "ALERTA: hay menos de {$minVehicles} vehículos en temp ({$totalAutos}).";
            am_log($msg, 'WARNING');
            self::appendErrorFile($msg);
            return [
                'ok' => false,
                'message' => $msg,
                'total_temp' => $totalAutos,
                'min_required' => $minVehicles,
            ];
        }

        $pdo->beginTransaction();
        try {
            $inserted = self::insertNewFromTemp($db);
            $updated = self::updateExistingFromTemp($db);
            $deleted = self::deleteRemovedFromMain($db);
            $db->execute('DELETE FROM ' . self::TEMP_TABLE);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            am_log('Inventory sync pasar error: ' . $e->getMessage(), 'ERROR');
            throw $e;
        }

        $highlightStats = ['relinked' => 0, 'restored' => 0, 'vin_preserved' => 0, 'saved' => false];
        try {
            require_once __DIR__ . '/InventoryHighlightService.php';
            $highlightStats = InventoryHighlightService::reconcileAfterInventorySync();
        } catch (Throwable $e) {
            am_log('Inventory highlights reconcile error: ' . $e->getMessage(), 'ERROR');
        }

        $msg = "Pase completado: {$inserted} insertados, {$updated} actualizados, {$deleted} eliminados.";
        if ($highlightStats['saved']) {
            $msg .= sprintf(
                ' Etiquetas: %d re-enlazadas, %d restauradas.',
                $highlightStats['relinked'],
                $highlightStats['restored']
            );
        }
        am_log($msg);

        return [
            'ok' => true,
            'message' => $msg,
            'inserted' => $inserted,
            'updated' => $updated,
            'deleted' => $deleted,
            'total_temp' => $totalAutos,
            'highlights' => $highlightStats,
        ];
    }

    private static function insertNewFromTemp(Database $db): int
    {
        $newRows = $db->select(
            'SELECT * FROM ' . self::TEMP_TABLE . ' t
             WHERE t.VIN IS NOT NULL AND t.VIN != \'\'
             AND t.VIN NOT IN (SELECT VIN FROM ' . self::MAIN_TABLE . ' WHERE VIN IS NOT NULL)'
        );

        if (count($newRows) === 0) {
            return 0;
        }

        $maxId = (int) ($db->selectOne('SELECT MAX(id) AS m FROM ' . self::MAIN_TABLE)['m'] ?? 0);
        $inserted = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($newRows as $row) {
            $maxId++;
            $cols = array_merge(['id', 'date_update'], self::SYNC_COLUMNS);
            $placeholders = array_map(fn ($c) => ':' . $c, $cols);
            $params = [':id' => $maxId, ':date_update' => $now];
            foreach (self::SYNC_COLUMNS as $col) {
                $params[':' . $col] = $row[$col] ?? null;
            }
            $sql = 'INSERT INTO ' . self::MAIN_TABLE . ' (' . implode(', ', $cols) . ')
                    VALUES (' . implode(', ', $placeholders) . ')';
            $db->execute($sql, $params);
            $inserted++;
        }

        return $inserted;
    }

    private static function updateExistingFromTemp(Database $db): int
    {
        $existing = $db->select(
            'SELECT t.VIN FROM ' . self::TEMP_TABLE . ' t
             INNER JOIN ' . self::MAIN_TABLE . ' m ON m.VIN = t.VIN'
        );

        if (count($existing) === 0) {
            return 0;
        }

        $sets = [];
        foreach (self::SYNC_COLUMNS as $col) {
            if ($col === 'VIN') {
                continue;
            }
            $sets[] = "m.$col = t.$col";
        }
        $sets[] = 'm.date_update = :now';

        $sql = 'UPDATE ' . self::MAIN_TABLE . ' AS m
                INNER JOIN ' . self::TEMP_TABLE . ' AS t ON m.VIN = t.VIN
                SET ' . implode(', ', $sets);

        if ($db->getDriverName() === 'sqlite') {
            $setsSqlite = [];
            foreach (self::SYNC_COLUMNS as $col) {
                if ($col === 'VIN') {
                    continue;
                }
                $setsSqlite[] = "$col = (SELECT t.$col FROM " . self::TEMP_TABLE . " t WHERE t.VIN = " . self::MAIN_TABLE . ".VIN)";
            }
            $setsSqlite[] = 'date_update = :now';
            $sql = 'UPDATE ' . self::MAIN_TABLE . ' SET ' . implode(', ', $setsSqlite) . '
                    WHERE VIN IN (SELECT VIN FROM ' . self::TEMP_TABLE . ')';
        }

        return $db->execute($sql, [':now' => date('Y-m-d H:i:s')]);
    }

    private static function deleteRemovedFromMain(Database $db): int
    {
        $sql = 'DELETE FROM ' . self::MAIN_TABLE . '
                WHERE VIN IS NOT NULL AND VIN != \'\'
                AND VIN NOT IN (SELECT VIN FROM ' . self::TEMP_TABLE . ' WHERE VIN IS NOT NULL AND VIN != \'\')';
        return $db->execute($sql);
    }

    private static function normalizeVehicle(array $raw): array
    {
        $data = [];
        foreach (self::SYNC_COLUMNS as $col) {
            $data[$col] = $raw[$col] ?? null;
        }

        $data['VIN'] = trim((string) ($data['VIN'] ?? ''));
        $data['Status'] = strtoupper(trim((string) ($data['Status'] ?? 'DISPONIBLE')));
        if ($data['Status'] === '') {
            $data['Status'] = 'DISPONIBLE';
        }

        $data['Marked'] = !empty($raw['Marked']) ? 1 : 0;
        $data['Promo'] = !empty($raw['Promo']) ? 1 : 0;
        $data['stat_master'] = 1;
        $data['prioridad'] = 0;
        $data['foto_impel'] = '';
        $data['Internacional'] = ($data['Internacional'] === null || $data['Internacional'] === '')
            ? ''
            : (string) $data['Internacional'];

        foreach (['Price', 'PriceTax', 'PromoPrice', 'PromoPriceTax'] as $numCol) {
            if ($data[$numCol] !== null && $data[$numCol] !== '') {
                $data[$numCol] = (float) $data[$numCol];
            }
        }

        foreach (['Km', 'Doors', 'CC', 'LocationCode', 'Prefijo', 'Year', 'update_stat'] as $intCol) {
            if ($data[$intCol] !== null && $data[$intCol] !== '') {
                $data[$intCol] = (int) $data[$intCol];
            }
        }

        return $data;
    }

    private static function appendErrorFile(string $message): void
    {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        file_put_contents($logDir . '/inventory-sync-errors.txt', $line, FILE_APPEND);
    }
}
