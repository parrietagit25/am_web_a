<?php
/**
 * Tabla temporal para sincronización de inventario desde el proceso Python.
 */
class InventorySyncSchema
{
    private static $ensured = false;

    public static function ensure(): void
    {
        if (self::$ensured) {
            return;
        }

        $db = Database::getInstance();
        $driver = $db->getDriverName();

        if ($driver === 'mysql') {
            $db->execute("CREATE TABLE IF NOT EXISTS Automarket_Invs_web_temp (
                id INT(11) NOT NULL AUTO_INCREMENT,
                Transmission VARCHAR(20) DEFAULT NULL,
                Color VARCHAR(20) DEFAULT NULL,
                Make VARCHAR(20) DEFAULT NULL,
                Km INT(10) DEFAULT NULL,
                Code VARCHAR(20) DEFAULT NULL,
                LicensePlate VARCHAR(20) DEFAULT NULL,
                Model VARCHAR(20) DEFAULT NULL,
                Chasis VARCHAR(50) DEFAULT NULL,
                Unit VARCHAR(20) DEFAULT NULL,
                Engine VARCHAR(30) DEFAULT NULL,
                Fuel VARCHAR(100) DEFAULT NULL,
                Price DECIMAL(11,2) DEFAULT NULL,
                PriceTax DECIMAL(11,2) DEFAULT NULL,
                Doors INT(2) DEFAULT NULL,
                CarType VARCHAR(20) DEFAULT NULL,
                CC INT(6) DEFAULT NULL,
                LocationCode INT(6) DEFAULT NULL,
                LocationName VARCHAR(50) DEFAULT NULL,
                Interior VARCHAR(30) DEFAULT NULL,
                Headline VARCHAR(50) DEFAULT NULL,
                Description VARCHAR(400) DEFAULT NULL,
                Photo VARCHAR(500) DEFAULT NULL,
                Status VARCHAR(30) DEFAULT NULL,
                Marked TINYINT(1) DEFAULT NULL,
                Promo TINYINT(1) DEFAULT NULL,
                PromoPrice DECIMAL(11,2) DEFAULT NULL,
                PromoPriceTax DECIMAL(11,2) DEFAULT NULL,
                LoadDate DATETIME DEFAULT NULL,
                Prefijo INT(30) DEFAULT NULL,
                date_update DATETIME DEFAULT NULL,
                Year INT(4) DEFAULT NULL,
                VIN VARCHAR(30) DEFAULT NULL,
                trg_updatefechaWeb DATETIME DEFAULT NULL,
                update_stat INT(1) DEFAULT NULL,
                stat_master INT(1) DEFAULT NULL,
                Fechaa_log TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                prioridad INT(1) DEFAULT NULL,
                Internacional VARCHAR(30) DEFAULT NULL,
                tipo_compra VARCHAR(30) DEFAULT NULL,
                foto_impel VARCHAR(500) DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_temp_vin (VIN)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $db->execute("CREATE TABLE IF NOT EXISTS Automarket_Invs_web_temp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                Transmission TEXT,
                Color TEXT,
                Make TEXT,
                Km INTEGER,
                Code TEXT,
                LicensePlate TEXT,
                Model TEXT,
                Chasis TEXT,
                Unit TEXT,
                Engine TEXT,
                Fuel TEXT,
                Price REAL,
                PriceTax REAL,
                Doors INTEGER,
                CarType TEXT,
                CC INTEGER,
                LocationCode INTEGER,
                LocationName TEXT,
                Interior TEXT,
                Headline TEXT,
                Description TEXT,
                Photo TEXT,
                Status TEXT,
                Marked INTEGER,
                Promo INTEGER,
                PromoPrice REAL,
                PromoPriceTax REAL,
                LoadDate TEXT,
                Prefijo INTEGER,
                date_update TEXT,
                Year INTEGER,
                VIN TEXT,
                trg_updatefechaWeb TEXT,
                update_stat INTEGER,
                stat_master INTEGER,
                Fechaa_log TEXT DEFAULT CURRENT_TIMESTAMP,
                prioridad INTEGER,
                Internacional TEXT,
                tipo_compra TEXT,
                foto_impel TEXT
            )");
            $db->execute("CREATE INDEX IF NOT EXISTS idx_temp_vin ON Automarket_Invs_web_temp (VIN)");
        }

        self::$ensured = true;
    }
}
