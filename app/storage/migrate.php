<?php
/**
 * Database Migration Script
 * Imports sql/Automarket_Invs_web.sql into the database.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/Database.php';

echo "Iniciando migración de base de datos...\n";

try {
    $db = Database::getInstance();
    $driver = $db->getDriverName();
    echo "Driver de base de datos detectado: " . strtoupper($driver) . "\n";

    $sqlPath = __DIR__ . '/sql/Automarket_Invs_web.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("No se encontró el archivo SQL en: " . $sqlPath);
    }

    $sqlContent = file_get_contents($sqlPath);
    echo "Archivo SQL cargado exitosamente. Tamaño: " . strlen($sqlContent) . " bytes.\n";

    $pdo = $db->getConnection();

    // Start a transaction for fast inserts
    $pdo->beginTransaction();

    if ($driver === 'sqlite') {
        echo "Preparando SQL para compatibilidad con SQLite...\n";
        
        // 1. Remove comments
        $lines = explode("\n", $sqlContent);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;
            // Skip comments and transaction statements
            if (str_starts_with($trimmed, '--')) continue;
            if (str_starts_with($trimmed, '/*') && str_ends_with($trimmed, '*/;')) continue;
            if (str_starts_with($trimmed, '/*')) continue;
            if (str_starts_with($trimmed, 'SET ')) continue;
            if (str_starts_with($trimmed, 'START TRANSACTION')) continue;
            if (str_starts_with($trimmed, 'COMMIT')) continue;
            
            $cleanLines[] = $line;
        }
        $sqlContent = implode("\n", $cleanLines);

        // 2. Remove MySQL ENGINE/CHARSET definitions
        $sqlContent = preg_replace('/ENGINE\s*=\s*\w+.*?DEFAULT\s+CHARSET\s*=\s*\w+.*?;/i', ';', $sqlContent);
        
        // 3. Remove MySQL COLLATE definitions inside table structure
        $sqlContent = preg_replace('/COLLATE\s*=\s*\w+/i', '', $sqlContent);

        // 4. Remove MySQL COMMENT definitions on columns
        $sqlContent = preg_replace('/COMMENT\s+\'[^\'\n]*\'/i', '', $sqlContent);

        // 5. Convert MySQL bit literals: b'1' -> 1, b'0' -> 0
        $sqlContent = preg_replace('/b\'1\'/i', '1', $sqlContent);
        $sqlContent = preg_replace('/b\'0\'/i', '0', $sqlContent);

        // 6. Handle ON UPDATE current_timestamp() and current_timestamp() function in SQLite defaults
        $sqlContent = preg_replace('/ON\s+UPDATE\s+current_timestamp\(\)/i', '', $sqlContent);
        $sqlContent = preg_replace('/current_timestamp\(\)/i', 'CURRENT_TIMESTAMP', $sqlContent);
        
        // 7. Remove any AUTO_INCREMENT definitions that aren't SQLite compatible
    }

    // Execute the SQL
    if ($driver === 'sqlite') {
        $sqlContent = str_replace("\r\n", "\n", $sqlContent);
        $statements = preg_split('/;(?=\s*\n)/', $sqlContent);
        
        $execCount = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Skip ALTER TABLE modifications for primary keys since we defined it in CREATE TABLE
            if (stripos($statement, 'ALTER TABLE') !== false && (stripos($statement, 'ADD PRIMARY KEY') !== false || stripos($statement, 'AUTO_INCREMENT') !== false)) {
                continue;
            }

            if (stripos($statement, 'CREATE TABLE') !== false) {
                // Make `id` the primary key in SQLite directly
                $statement = preg_replace('/`id` int\(\d+\) NOT NULL/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $statement);
            }

            try {
                $pdo->exec($statement);
                $execCount++;
            } catch (PDOException $ex) {
                echo "\n--- ERROR EN SENTENCIA SQL ---\n";
                echo $statement . "\n";
                echo "------------------------------\n";
                throw $ex;
            }
        }
        echo "Se ejecutaron $execCount bloques SQL en SQLite.\n";
    } else {
        // MySQL handles the entire file natively
        $pdo->exec($sqlContent);
        echo "Se ejecutó el archivo SQL completo en MySQL.\n";
    }

    $pdo->commit();
    echo "¡Migración completada con éxito!\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR DE MIGRACIÓN: " . $e->getMessage() . "\n";
    exit(1);
}
