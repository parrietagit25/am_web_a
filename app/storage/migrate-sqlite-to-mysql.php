<?php
/**
 * Migración SQLite -> MariaDB (Automarket).
 *
 * Script CLI standalone.
 *
 * Uso:
 *   php app/storage/migrate-sqlite-to-mysql.php --dry-run
 *   php app/storage/migrate-sqlite-to-mysql.php --execute --yes-i-am-sure
 *
 * --execute exige --yes-i-am-sure explícito; sin él, aborta sin tocar nada.
 * --execute corre primero el mismo preflight que --dry-run; si hay cualquier
 * error, aborta antes de escribir una sola fila.
 *
 * Destino (MariaDB) se configura por variables de entorno, NUNCA por config.php:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este script solo puede ejecutarse por CLI." . PHP_EOL;
    exit(1);
}

const SQLITE_PATH = __DIR__ . '/database.sqlite';

/** Tablas incluidas en el ETL, en el orden de migración diseñado en DB1B-2B. */
const TABLES = [
    'admin_users',
    'rac_reservations',
    'rac_alert_emails',
    'admin_audit_logs',
    'chatbot_sessions',
    'chatbot_messages',
    'telemetry_visitors',
    'telemetry_events',
    'Automarket_Invs_web',
    'Automarket_Invs_web_temp',
];

/** Tablas explícitamente excluidas del ETL, con el motivo a imprimir. */
const EXCLUDED_TABLES = [
    'site_content_store' => '0 filas, sin referencias en app/, CMS real usa site_data.json',
];

/** Columna(s) de clave primaria por tabla (para validar NULLs/duplicados). */
const PRIMARY_KEYS = [
    'admin_users' => 'id',
    'rac_reservations' => 'id',
    'rac_alert_emails' => 'id',
    'admin_audit_logs' => 'id',
    'chatbot_sessions' => 'id',
    'chatbot_messages' => 'id',
    'telemetry_visitors' => 'visitor_id',
    'telemetry_events' => 'id',
    'Automarket_Invs_web' => 'id',
    'Automarket_Invs_web_temp' => 'id',
];

/** Columnas bit/boolean a normalizar a entero 0/1 explícito al migrar. */
const BOOLISH_COLUMNS = [
    'Automarket_Invs_web' => ['Marked', 'Promo'],
];

function main(array $argv): int
{
    $args = array_slice($argv, 1);
    $hasDryRun = in_array('--dry-run', $args, true);
    $hasExecute = in_array('--execute', $args, true);
    $hasConfirm = in_array('--yes-i-am-sure', $args, true);

    if ($hasDryRun && $hasExecute) {
        echo "Error: no se puede usar --dry-run y --execute al mismo tiempo." . PHP_EOL;
        return 1;
    }
    if (!$hasDryRun && !$hasExecute) {
        echo "Error: debe indicar --dry-run o --execute." . PHP_EOL;
        echo "Uso: php " . basename(__FILE__) . " --dry-run" . PHP_EOL;
        echo "     php " . basename(__FILE__) . " --execute --yes-i-am-sure" . PHP_EOL;
        return 1;
    }

    if ($hasExecute && !$hasConfirm) {
        echo "Error: --execute requiere confirmación explícita --yes-i-am-sure." . PHP_EOL;
        echo "Uso: php " . basename(__FILE__) . " --execute --yes-i-am-sure" . PHP_EOL;
        return 1;
    }

    return $hasExecute ? runExecute() : runDryRun();
}

/**
 * Corre el preflight completo de solo lectura contra SQLite y MariaDB.
 * No escribe nada en ninguna base. Devuelve el contexto recolectado.
 */
function preflight(): array
{
    $errors = [];
    $warnings = [];
    $sqlite = null;
    $db = null;
    $sourceCounts = [];
    $destCounts = [];
    $sourceColumnsByTable = [];
    $destColumnTypesByTable = [];

    echo "Origen SQLite: " . SQLITE_PATH . PHP_EOL;

    if (!is_file(SQLITE_PATH)) {
        $errors[] = "No existe el archivo SQLite de origen: " . SQLITE_PATH;
        return compact('errors', 'warnings', 'sqlite', 'db', 'sourceCounts', 'destCounts', 'sourceColumnsByTable', 'destColumnTypesByTable');
    }

    try {
        $sqlite = new SQLite3(SQLITE_PATH, SQLITE3_OPEN_READONLY);
    } catch (Throwable $e) {
        $errors[] = "No se pudo abrir SQLite en modo solo lectura: " . $e->getMessage();
        return compact('errors', 'warnings', 'sqlite', 'db', 'sourceCounts', 'destCounts', 'sourceColumnsByTable', 'destColumnTypesByTable');
    }
    echo "Origen SQLite: OK (solo lectura)" . PHP_EOL;

    // --- Destino MariaDB (vía Database.php, credenciales por entorno) ---
    $envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $missingEnv = [];
    foreach ($envVars as $name) {
        if (getenv($name) === false || getenv($name) === '') {
            $missingEnv[] = $name;
        }
    }
    if (!empty($missingEnv)) {
        $errors[] = "Faltan variables de entorno para el destino MySQL: " . implode(', ', $missingEnv);
        return compact('errors', 'warnings', 'sqlite', 'db', 'sourceCounts', 'destCounts', 'sourceColumnsByTable', 'destColumnTypesByTable');
    }

    if (!defined('DB_REQUIRE_MYSQL')) {
        define('DB_REQUIRE_MYSQL', true);
        define('DB_HOST', getenv('DB_HOST'));
        define('DB_NAME', getenv('DB_NAME'));
        define('DB_USER', getenv('DB_USER'));
        define('DB_PASS', getenv('DB_PASS'));
    }

    require_once __DIR__ . '/../services/Database.php';

    try {
        $db = Database::getInstance();
    } catch (Throwable $e) {
        // Mensaje de Database.php ya es seguro (no expone DB_PASS).
        $errors[] = "No se pudo conectar al destino MySQL: " . $e->getMessage();
        return compact('errors', 'warnings', 'sqlite', 'db', 'sourceCounts', 'destCounts', 'sourceColumnsByTable', 'destColumnTypesByTable');
    }

    $driver = $db->getDriverName();
    echo "Destino driver: " . $driver . PHP_EOL;
    if ($driver !== 'mysql') {
        $errors[] = "El driver de destino es '$driver', se esperaba 'mysql'. Abortando.";
        return compact('errors', 'warnings', 'sqlite', 'db', 'sourceCounts', 'destCounts', 'sourceColumnsByTable', 'destColumnTypesByTable');
    }

    // --- Tablas excluidas (solo informativo) ---
    echo PHP_EOL . "Tablas excluidas del ETL:" . PHP_EOL;
    foreach (EXCLUDED_TABLES as $table => $reason) {
        echo "  - $table: $reason" . PHP_EOL;
    }

    // --- Preflight por tabla ---
    echo PHP_EOL . "Tablas incluidas: " . implode(', ', TABLES) . PHP_EOL . PHP_EOL;

    foreach (TABLES as $table) {
        echo "--- $table ---" . PHP_EOL;

        $sourceExists = $sqlite->querySingle(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='" . SQLite3::escapeString($table) . "'"
        );
        if (!$sourceExists) {
            $errors[] = "$table: no existe en SQLite (origen).";
            continue;
        }

        $destExistsRow = $db->selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
            [':t' => $table]
        );
        $destExists = (int) ($destExistsRow['c'] ?? 0) > 0;
        if (!$destExists) {
            $errors[] = "$table: no existe en MariaDB (destino).";
            continue;
        }

        $srcCount = (int) $sqlite->querySingle('SELECT COUNT(*) FROM ' . $table);
        $dstCount = (int) ($db->selectOne('SELECT COUNT(*) AS c FROM ' . $table)['c'] ?? 0);
        $sourceCounts[$table] = $srcCount;
        $destCounts[$table] = $dstCount;
        echo "  Filas origen: $srcCount | Filas destino: $dstCount" . PHP_EOL;

        if ($dstCount > 0) {
            $errors[] = "$table: la tabla destino NO está vacía ($dstCount filas). Abortando antes de escribir.";
        }

        // Columnas: origen (PRAGMA) vs destino (information_schema)
        $sourceColumns = [];
        $res = $sqlite->query('PRAGMA table_info(' . $table . ')');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $sourceColumns[] = $row['name'];
        }
        $sourceColumnsByTable[$table] = $sourceColumns;

        $destColumnsRows = $db->select(
            "SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
             ORDER BY ORDINAL_POSITION",
            [':t' => $table]
        );
        $destColumns = array_map(fn ($r) => $r['COLUMN_NAME'], $destColumnsRows);
        $typeMap = [];
        foreach ($destColumnsRows as $r) {
            $typeMap[$r['COLUMN_NAME']] = $r['DATA_TYPE'];
        }
        $destColumnTypesByTable[$table] = $typeMap;

        $missingInDest = array_values(array_diff($sourceColumns, $destColumns));
        $extraInDest = array_values(array_diff($destColumns, $sourceColumns));
        if (!empty($missingInDest)) {
            $errors[] = "$table: columnas presentes en SQLite pero ausentes en MariaDB: " . implode(', ', $missingInDest);
        }
        if (!empty($extraInDest)) {
            $warnings[] = "$table: columnas presentes en MariaDB pero ausentes en SQLite (recibirán su DEFAULT): " . implode(', ', $extraInDest);
        }
        if (empty($missingInDest) && empty($extraInDest)) {
            echo "  Columnas: coinciden (" . count($sourceColumns) . ")" . PHP_EOL;
        }

        // PK: nulls y duplicados (en SQLite, fuente de verdad)
        $pk = PRIMARY_KEYS[$table] ?? null;
        if ($pk !== null && in_array($pk, $sourceColumns, true)) {
            $nullPk = (int) $sqlite->querySingle("SELECT COUNT(*) FROM $table WHERE $pk IS NULL");
            if ($nullPk > 0) {
                $errors[] = "$table: $nullPk fila(s) con $pk NULL.";
            }
            $dupPk = (int) $sqlite->querySingle(
                "SELECT COUNT(*) FROM (SELECT $pk FROM $table GROUP BY $pk HAVING COUNT(*) > 1)"
            );
            if ($dupPk > 0) {
                $errors[] = "$table: $dupPk valor(es) duplicado(s) de $pk.";
            }
            if ($nullPk === 0 && $dupPk === 0) {
                echo "  PK ($pk): sin NULLs, sin duplicados" . PHP_EOL;
            }
        }

        // NOT NULL en destino sin default -> verificar NULLs en origen
        $notNullCols = $db->select(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
             AND IS_NULLABLE = 'NO' AND COLUMN_DEFAULT IS NULL AND EXTRA NOT LIKE '%auto_increment%'",
            [':t' => $table]
        );
        foreach ($notNullCols as $colRow) {
            $col = $colRow['COLUMN_NAME'];
            if (!in_array($col, $sourceColumns, true)) {
                continue; // ya reportado arriba como columna faltante
            }
            $nullCount = (int) $sqlite->querySingle("SELECT COUNT(*) FROM $table WHERE $col IS NULL");
            if ($nullCount > 0) {
                $errors[] = "$table.$col: destino es NOT NULL sin default, pero origen tiene $nullCount fila(s) NULL.";
            }
        }

        // Columnas boolish (Marked/Promo) -> deben ser convertibles a 0/1
        if (isset(BOOLISH_COLUMNS[$table])) {
            foreach (BOOLISH_COLUMNS[$table] as $col) {
                if (!in_array($col, $sourceColumns, true)) {
                    continue;
                }
                $invalid = (int) $sqlite->querySingle(
                    "SELECT COUNT(*) FROM $table WHERE $col IS NOT NULL AND $col NOT IN (0, 1)"
                );
                if ($invalid > 0) {
                    $errors[] = "$table.$col: $invalid fila(s) con valor distinto de 0/1, requiere revisión manual antes de convertir a bit(1).";
                } else {
                    echo "  $col: convertible a 0/1 sin pérdida" . PHP_EOL;
                }
            }
        }
    }

    // --- Validación de relación real: chatbot_messages.session_id -> chatbot_sessions.id ---
    if (in_array('chatbot_messages', TABLES, true) && in_array('chatbot_sessions', TABLES, true)) {
        $orphans = (int) $sqlite->querySingle(
            'SELECT COUNT(*) FROM chatbot_messages m
             WHERE m.session_id IS NOT NULL
             AND NOT EXISTS (SELECT 1 FROM chatbot_sessions s WHERE s.id = m.session_id)'
        );
        if ($orphans > 0) {
            $errors[] = "chatbot_messages: $orphans fila(s) con session_id huérfano (sin chatbot_sessions.id correspondiente). Violaría la FK real en MariaDB.";
        } else {
            echo PHP_EOL . "chatbot_messages.session_id: sin huérfanos respecto a chatbot_sessions.id" . PHP_EOL;
        }
    }

    // --- Validación de relación sin FK real: telemetry_events.visitor_id -> telemetry_visitors.visitor_id (solo warning) ---
    if (in_array('telemetry_events', TABLES, true) && in_array('telemetry_visitors', TABLES, true)) {
        $orphanVisitors = (int) $sqlite->querySingle(
            "SELECT COUNT(*) FROM telemetry_events e
             WHERE e.visitor_id IS NOT NULL AND e.visitor_id != ''
             AND NOT EXISTS (SELECT 1 FROM telemetry_visitors v WHERE v.visitor_id = e.visitor_id)"
        );
        if ($orphanVisitors > 0) {
            $warnings[] = "telemetry_events: $orphanVisitors fila(s) con visitor_id sin telemetry_visitors correspondiente (no bloquea, no hay FK real).";
        }
    }

    return compact('errors', 'warnings', 'sqlite', 'db', 'sourceCounts', 'destCounts', 'sourceColumnsByTable', 'destColumnTypesByTable');
}

/** Imprime warnings y errores acumulados en el contexto. */
function printWarningsAndErrors(array $ctx): void
{
    echo PHP_EOL . "Warnings (" . count($ctx['warnings']) . "):" . PHP_EOL;
    foreach ($ctx['warnings'] as $w) {
        echo "  - $w" . PHP_EOL;
    }

    echo PHP_EOL . "Errores (" . count($ctx['errors']) . "):" . PHP_EOL;
    foreach ($ctx['errors'] as $e) {
        echo "  - $e" . PHP_EOL;
    }
}

/** Imprime la tabla de conteos origen -> destino recolectada en el preflight. */
function printCounts(array $ctx): void
{
    if (empty($ctx['sourceCounts'])) {
        return;
    }
    echo PHP_EOL . "Conteos (origen -> destino):" . PHP_EOL;
    foreach ($ctx['sourceCounts'] as $table => $srcCount) {
        $dstCount = $ctx['destCounts'][$table] ?? 'N/D';
        echo "  $table: $srcCount -> $dstCount" . PHP_EOL;
    }
}

function runDryRun(): int
{
    echo "=== MODO: DRY RUN ===" . PHP_EOL;
    $ctx = preflight();

    echo PHP_EOL . "=== RESUMEN ===" . PHP_EOL;
    printCounts($ctx);
    printWarningsAndErrors($ctx);

    echo PHP_EOL;
    if (empty($ctx['errors'])) {
        echo "DRY-RUN OK — listo para migrar" . PHP_EOL;
        return 0;
    }

    echo "DRY-RUN FAILED — corregir antes de ejecutar" . PHP_EOL;
    return 1;
}

function runExecute(): int
{
    echo "=== MODO: EXECUTE ===" . PHP_EOL;
    $ctx = preflight();

    echo PHP_EOL . "=== RESUMEN DEL PREFLIGHT ===" . PHP_EOL;
    printCounts($ctx);
    printWarningsAndErrors($ctx);

    if (!empty($ctx['errors'])) {
        echo PHP_EOL . "Preflight con errores — abortando antes de escribir cualquier dato." . PHP_EOL;
        echo PHP_EOL . "MIGRATION FAILED" . PHP_EOL;
        return 1;
    }

    echo PHP_EOL . "Preflight OK. Iniciando migración real (transacción por tabla, orden fijo)..." . PHP_EOL;

    $migration = migrateAll($ctx);

    if (!empty($migration['errors'])) {
        echo PHP_EOL . "Errores durante la migración:" . PHP_EOL;
        foreach ($migration['errors'] as $e) {
            echo "  - $e" . PHP_EOL;
        }
        echo PHP_EOL . "Tablas migradas antes del fallo: " . implode(', ', array_keys($migration['counts'])) . PHP_EOL;
        echo PHP_EOL . "MIGRATION FAILED" . PHP_EOL;
        return 1;
    }

    $postErrors = postMigrationChecks($ctx);
    if (!empty($postErrors)) {
        echo PHP_EOL . "Errores de validación post-migración:" . PHP_EOL;
        foreach ($postErrors as $e) {
            echo "  - $e" . PHP_EOL;
        }
        echo PHP_EOL . "MIGRATION FAILED" . PHP_EOL;
        return 1;
    }

    echo PHP_EOL . "MIGRATION OK" . PHP_EOL;
    return 0;
}

/**
 * Migra las tablas en el orden fijo de TABLES. Transacción por tabla:
 * si una tabla falla, se hace rollback SOLO de esa tabla y se aborta el
 * resto de la ejecución (no se continúa con las tablas siguientes).
 */
function migrateAll(array $ctx): array
{
    $sqlite = $ctx['sqlite'];
    $db = $ctx['db'];
    $pdo = $db->getConnection();

    $counts = [];
    $errors = [];

    foreach (TABLES as $table) {
        $columns = $ctx['sourceColumnsByTable'][$table] ?? null;
        if ($columns === null) {
            $errors[] = "$table: sin información de columnas del preflight, abortando.";
            break;
        }

        echo PHP_EOL . "Migrando $table..." . PHP_EOL;

        $columnTypes = $ctx['destColumnTypesByTable'][$table] ?? [];
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);
        $insertSql = 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $selectSql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;

        $pdo->beginTransaction();
        $rowNumber = 0;
        try {
            $stmt = $pdo->prepare($insertSql);
            $res = $sqlite->query($selectSql);

            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $rowNumber++;
                foreach ($columns as $col) {
                    $value = normalizeValue($table, $col, $row[$col], $columnTypes[$col] ?? null);
                    // Bind explícito por tipo: PDOStatement::execute($assocArray) enlaza
                    // todo como PDO::PARAM_STR, y MySQL interpreta un string enlazado en
                    // una columna bit(1) como cadena de bits por longitud de byte (no como
                    // literal numérico), causando "Data too long for column".
                    if ($value === null) {
                        $stmt->bindValue(':' . $col, null, PDO::PARAM_NULL);
                    } elseif (is_int($value)) {
                        $stmt->bindValue(':' . $col, $value, PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue(':' . $col, $value, PDO::PARAM_STR);
                    }
                }
                $stmt->execute();
            }

            $pdo->commit();
            $counts[$table] = $rowNumber;
            echo "  OK: $rowNumber fila(s) migrada(s) y commiteada(s)." . PHP_EOL;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "$table: fallo en la fila #$rowNumber — " . sanitizeErrorMessage($e->getMessage()) . ". Rollback aplicado a esta tabla.";
            echo "  ERROR: rollback aplicado a $table." . PHP_EOL;
            break;
        }
    }

    return ['counts' => $counts, 'errors' => $errors];
}

/**
 * Normaliza un valor leído de SQLite antes de insertarlo en MariaDB.
 * NULL se preserva como NULL; strings vacíos se preservan tal cual.
 */
function normalizeValue(string $table, string $col, $value, ?string $destDataType)
{
    if ($value === null) {
        return null;
    }

    if (isset(BOOLISH_COLUMNS[$table]) && in_array($col, BOOLISH_COLUMNS[$table], true)) {
        return ((int) $value) === 0 ? 0 : 1;
    }

    if ($destDataType === null) {
        return $value;
    }

    $intTypes = ['int', 'bigint', 'smallint', 'mediumint', 'tinyint'];
    $decimalTypes = ['decimal', 'numeric'];

    if (in_array($destDataType, $intTypes, true)) {
        return (int) $value;
    }

    if (in_array($destDataType, $decimalTypes, true)) {
        // String numérico: MariaDB conserva el valor exacto sin depender de
        // redondeos de punto flotante de PHP.
        return (string) $value;
    }

    // varchar/text/longtext/datetime/date/timestamp -> copiar tal cual.
    return $value;
}

/** Redacta el password de entorno si apareciera literal en un mensaje de error. */
function sanitizeErrorMessage(string $message): string
{
    $pass = getenv('DB_PASS');
    if ($pass !== false && $pass !== '' && str_contains($message, $pass)) {
        $message = str_replace($pass, '[REDACTED]', $message);
    }
    return $message;
}

/**
 * Validaciones de solo lectura después de escribir: conteos finales,
 * huérfanos de chatbot_messages, y presencia del vehículo de referencia EO6372.
 */
function postMigrationChecks(array $ctx): array
{
    $errors = [];
    $db = $ctx['db'];

    echo PHP_EOL . "=== VALIDACIÓN POST-MIGRACIÓN ===" . PHP_EOL;

    foreach (TABLES as $table) {
        $srcCount = $ctx['sourceCounts'][$table] ?? null;
        $dstCount = (int) ($db->selectOne('SELECT COUNT(*) AS c FROM ' . $table)['c'] ?? 0);
        echo "  $table: origen=$srcCount destino=$dstCount" . PHP_EOL;
        if ($srcCount !== $dstCount) {
            $errors[] = "$table: conteo final no coincide (origen=$srcCount, destino=$dstCount).";
        }
    }

    $orphans = (int) ($db->selectOne(
        'SELECT COUNT(*) AS c FROM chatbot_messages m LEFT JOIN chatbot_sessions s ON s.id = m.session_id WHERE s.id IS NULL'
    )['c'] ?? 0);
    if ($orphans > 0) {
        $errors[] = "chatbot_messages: $orphans fila(s) huérfana(s) tras la migración.";
    } else {
        echo "  chatbot_messages sin huérfanos: OK" . PHP_EOL;
    }

    $eo6372 = $db->selectOne("SELECT id, LicensePlate FROM Automarket_Invs_web WHERE LicensePlate = 'EO6372'");
    if ($eo6372) {
        echo "  EO6372 presente en destino: id=" . $eo6372['id'] . PHP_EOL;
    } else {
        $errors[] = "Automarket_Invs_web: no se encontró LicensePlate=EO6372 tras la migración.";
    }

    return $errors;
}

exit(main($argv));
