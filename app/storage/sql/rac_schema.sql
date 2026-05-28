-- RAC reservations (reference — tables are created automatically via RacDatabaseSchema.php)
-- MySQL / MariaDB

CREATE TABLE IF NOT EXISTS rac_reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(24) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(200) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_comments TEXT NULL,
    location_code VARCHAR(20) NOT NULL,
    return_location_code VARCHAR(20) NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time VARCHAR(8) NOT NULL,
    return_date DATE NOT NULL,
    return_time VARCHAR(8) NOT NULL,
    driver_age VARCHAR(4) NOT NULL DEFAULT '25',
    promo_code VARCHAR(64) NULL,
    sipp_code VARCHAR(12) NULL,
    vehicle_name VARCHAR(200) NOT NULL,
    vehicle_category VARCHAR(100) NULL,
    vendor_rate_id VARCHAR(64) NULL,
    quote_token VARCHAR(64) NULL,
    rate_type VARCHAR(16) NOT NULL DEFAULT 'web',
    price_web DECIMAL(12,2) NULL,
    price_counter DECIMAL(12,2) NULL,
    price_total DECIMAL(12,2) NULL,
    price_total_estimated DECIMAL(12,2) NULL,
    coverage_code VARCHAR(32) NULL,
    equipment_json LONGTEXT NULL,
    vehicle_snapshot_json LONGTEXT NULL,
    search_snapshot_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_reservation_code (reservation_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rac_alert_emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    label VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
