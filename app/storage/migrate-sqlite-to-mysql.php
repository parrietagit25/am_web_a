<?php
/**
 * Migración SQLite -> MariaDB (Automarket).
 *
 * Script CLI standalone. En DB1B-2C solo el modo --dry-run está habilitado;
 * --execute queda explícitamente bloqueado hasta DB1B-2E.
 *
 * Uso:
 *   php app/storage/migrate-sqlite-to-mysql.php --dry-run
 *   php app/storage/migrate-sqlite-to-mysql.php --execute   (bloqueado en este bloque)
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

/** Columnas bit/boolean de Automarket_Invs_web a validar como convertibles a 0/1. */
const BOOLISH_COLUMNS = [
    'Automarket_Invs_web' => ['Marked', 'Promo'],
];

function main(array $argv): int
{
    $mode = parseMode($argv);
    if ($mode === null) {
        return 1;
    }

    if ($mode === 'execute') {
        echo "--execute aún no está habilitado en DB1B-2C; usar bloque DB1B-2E." . PHP_EOL;
        return 1;
    }

    return runDryRun();
}

/**
 * Determina el modo solicitado. Devuelve 'dry-run', 'execute', o null si los
 * flags son inválidos (ninguno, ambos, o desconocido) — imprime el error.
 */
function parseMode(array $argv): ?string
{
    $args = array_slice($argv, 1);
    $hasDryRun = in_array('--dry-run', $args, true);
    $hasExecute = in_array('--execute', $args, true);

    if ($hasDryRun && $hasExecute) {
        echo "Error: no se puede usar --dry-run y --execute al mismo tiempo." . PHP_EOL;
        return null;
    }
    if (!$hasDryRun && !$hasExecute) {
        echo "Error: debe indicar --dry-run o --execute." . PHP_EOL;
        echo "Uso: php " . basename(__FILE__) . " --dry-run" . PHP_EOL;
        return null;
    }

    return $hasExecute ? 'execute' : 'dry-run';
}

/**
 * Corre el preflight completo de solo lectura contra SQLite y MariaDB.
 * No escribe nada en ninguna base. Devuelve el exit code final.
 */
function runDryRun(): int
{
    $errors = [];
    $warnings = [];

    echo "=== MODO: DRY RUN ===" . PHP_EOL;
    echo "Origen SQLite: " . SQLITE_PATH . PHP_EOL;

    // --- Origen SQLite (solo lectura, nunca vía Database.php) ---
    if (!is_file(SQLITE_PATH)) {
        $errors[] = "No existe el archivo SQLite de origen: " . SQLITE_PATH;
        return finish($errors, $warnings, null, null, null);
    }

    try {
        $sqlite = new SQLite3(SQLITE_PATH, SQLITE3_OPEN_READONLY);
    } catch (Throwable $e) {
        $errors[] = "No se pudo abrir SQLite en modo solo lectura: " . $e->getMessage();
        return finish($errors, $warnings, null, null, null);
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
        return finish($errors, $warnings, $sqlite, null, null);
    }

    define('DB_REQUIRE_MYSQL', true);
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));

    require_once __DIR__ . '/../services/Database.php';

    try {
        $db = Database::getInstance();
    } catch (Throwable $e) {
        // Mensaje de Database.php ya es seguro (no expone DB_PASS).
        $errors[] = "No se pudo conectar al destino MySQL: " . $e->getMessage();
        return finish($errors, $warnings, $sqlite, null, null);
    }

    $driver = $db->getDriverName();
    echo "Destino driver: " . $driver . PHP_EOL;
    if ($driver !== 'mysql') {
        $errors[] = "El driver de destino es '$driver', se esperaba 'mysql'. Abortando.";
        return finish($errors, $warnings, $sqlite, $db, null);
    }

    // --- Tablas excluidas (solo informativo) ---
    echo PHP_EOL . "Tablas excluidas del ETL:" . PHP_EOL;
    foreach (EXCLUDED_TABLES as $table => $reason) {
        echo "  - $table: $reason" . PHP_EOL;
    }

    // --- Preflight por tabla ---
    $sourceCounts = [];
    $destCounts = [];
    $structureOk = true;

    echo PHP_EOL . "Tablas incluidas: " . implode(', ', TABLES) . PHP_EOL . PHP_EOL;

    foreach (TABLES as $table) {
        echo "--- $table ---" . PHP_EOL;

        // Existencia en origen
        $sourceExists = $sqlite->querySingle(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='" . SQLite3::escapeString($table) . "'"
        );
        if (!$sourceExists) {
            $errors[] = "$table: no existe en SQLite (origen).";
            $structureOk = false;
            continue;
        }

        // Existencia en destino
        $destExistsRow = $db->selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
            [':t' => $table]
        );
        $destExists = (int) ($destExistsRow['c'] ?? 0) > 0;
        if (!$destExists) {
            $errors[] = "$table: no existe en MariaDB (destino).";
            $structureOk = false;
            continue;
        }

        // Conteos
        $srcCount = (int) $sqlite->querySingle('SELECT COUNT(*) FROM ' . $table);
        $dstCount = (int) ($db->selectOne('SELECT COUNT(*) AS c FROM ' . $table)['c'] ?? 0);
        $sourceCounts[$table] = $srcCount;
        $destCounts[$table] = $dstCount;
        echo "  Filas origen: $srcCount | Filas destino: $dstCount" . PHP_EOL;

        if ($dstCount > 0) {
            $errors[] = "$table: la tabla destino NO está vacía ($dstCount filas). No se puede continuar hacia --execute.";
        }

        // Columnas: origen (PRAGMA) vs destino (information_schema)
        $sourceColumns = [];
        $res = $sqlite->query('PRAGMA table_info(' . $table . ')');
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $sourceColumns[] = $row['name'];
        }

        $destColumnsRows = $db->select(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t
             ORDER BY ORDINAL_POSITION",
            [':t' => $table]
        );
        $destColumns = array_map(fn ($r) => $r['COLUMN_NAME'], $destColumnsRows);

        $missingInDest = array_values(array_diff($sourceColumns, $destColumns));
        $extraInDest = array_values(array_diff($destColumns, $sourceColumns));
        if (!empty($missingInDest)) {
            $errors[] = "$table: columnas presentes en SQLite pero ausentes en MariaDB: " . implode(', ', $missingInDest);
            $structureOk = false;
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

    return finish($errors, $warnings, $sqlite, $db, ['source' => $sourceCounts, 'dest' => $destCounts, 'structureOk' => $structureOk]);
}

/**
 * Imprime el resumen final y devuelve el exit code (0 si OK, 1 si hay errores).
 */
function finish(array $errors, array $warnings, ?SQLite3 $sqlite, ?Database $db, ?array $counts): int
{
    echo PHP_EOL . "=== RESUMEN ===" . PHP_EOL;

    if ($counts !== null) {
        echo "Conteos (origen -> destino):" . PHP_EOL;
        foreach ($counts['source'] as $table => $srcCount) {
            $dstCount = $counts['dest'][$table] ?? 'N/D';
            echo "  $table: $srcCount -> $dstCount" . PHP_EOL;
        }
    }

    echo PHP_EOL . "Warnings (" . count($warnings) . "):" . PHP_EOL;
    foreach ($warnings as $w) {
        echo "  - $w" . PHP_EOL;
    }

    echo PHP_EOL . "Errores (" . count($errors) . "):" . PHP_EOL;
    foreach ($errors as $e) {
        echo "  - $e" . PHP_EOL;
    }

    echo PHP_EOL;
    if (empty($errors)) {
        echo "DRY-RUN OK — listo para DB1B-2D" . PHP_EOL;
        return 0;
    }

    echo "DRY-RUN FAILED — corregir antes de ejecutar" . PHP_EOL;
    return 1;
}

/**
 * Placeholder deliberado: sin lógica de INSERT en DB1B-2C. Se implementará en
 * DB1B-2E. No tiene ninguna ruta de llamada activa en este script.
 */
function migrateTable(string $table): void
{
    throw new RuntimeException('migrateTable() no está implementado todavía (pendiente de DB1B-2E).');
}

exit(main($argv));
