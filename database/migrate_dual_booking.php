<?php
/**
 * BuildConnect — Dual Booking System Migration
 * Creates `bookings` and `booking_quotes` tables and enhances `workers` with coordinates.
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    echo "Starting Dual Booking System Migration...\n";

    // 1. Create bookings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_number VARCHAR(50) UNIQUE NOT NULL,
            client_id INT NOT NULL,
            provider_id INT NULL,
            provider_type ENUM('worker', 'contractor') NULL,
            booking_mode ENUM('instant', 'quote') NOT NULL,
            category VARCHAR(100) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) DEFAULT 'Ahmedabad',
            state VARCHAR(100) DEFAULT 'Gujarat',
            location_lat DECIMAL(10,7) DEFAULT 23.0225000,
            location_lng DECIMAL(10,7) DEFAULT 72.5714000,
            matching_radius_km INT DEFAULT 5,
            preferred_date DATE NOT NULL,
            preferred_time VARCHAR(50) NULL,
            urgency ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            price DECIMAL(10,2) NULL,
            budget_min DECIMAL(10,2) NULL,
            budget_max DECIMAL(10,2) NULL,
            duration_estimate VARCHAR(100) NULL,
            materials_included TINYINT(1) DEFAULT 0,
            special_requirements TEXT NULL,
            photos_json TEXT NULL,
            status ENUM('draft', 'posted', 'matching', 'quotes_received', 'accepted', 'confirmed', 'on_the_way', 'work_started', 'completed', 'cancelled', 'expired', 'disputed') DEFAULT 'posted',
            cancellation_reason TEXT NULL,
            cancelled_by INT NULL,
            accepted_at DATETIME NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_booking_status (status),
            INDEX idx_booking_mode (booking_mode),
            INDEX idx_booking_client (client_id),
            INDEX idx_booking_provider (provider_id),
            INDEX idx_booking_coords (location_lat, location_lng)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] Table 'bookings' verified/created.\n";

    // 2. Create booking_quotes table
    $db->exec("
        CREATE TABLE IF NOT EXISTS booking_quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            provider_id INT NOT NULL,
            provider_role ENUM('worker', 'contractor') NOT NULL,
            proposed_price DECIMAL(10,2) NOT NULL,
            scope_of_work TEXT NOT NULL,
            exclusions TEXT NULL,
            estimated_duration VARCHAR(100) NOT NULL,
            proposed_start_date DATE NOT NULL,
            materials_included ENUM('included', 'excluded', 'labor_only') DEFAULT 'labor_only',
            valid_until DATE NULL,
            notes TEXT NULL,
            status ENUM('submitted', 'revised', 'accepted', 'rejected', 'withdrawn', 'expired') DEFAULT 'submitted',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY uk_booking_provider (booking_id, provider_id),
            INDEX idx_quote_status (status),
            INDEX idx_quote_booking (booking_id),
            INDEX idx_quote_provider (provider_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " [OK] Table 'booking_quotes' verified/created.\n";

    // 3. Alter workers table to add GPS coordinates if not present
    $cols = $db->query("SHOW COLUMNS FROM workers LIKE 'location_lat'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE workers ADD COLUMN location_lat DECIMAL(10,7) DEFAULT 23.0225000 AFTER address");
        echo " [OK] Added column 'location_lat' to workers.\n";
    }

    $cols = $db->query("SHOW COLUMNS FROM workers LIKE 'location_lng'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE workers ADD COLUMN location_lng DECIMAL(10,7) DEFAULT 72.5714000 AFTER location_lat");
        echo " [OK] Added column 'location_lng' to workers.\n";
    }

    $cols = $db->query("SHOW COLUMNS FROM workers LIKE 'service_radius_km'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE workers ADD COLUMN service_radius_km INT DEFAULT 10 AFTER location_lng");
        echo " [OK] Added column 'service_radius_km' to workers.\n";
    }

    // 4. Populate sample coordinates for workers in Ahmedabad area so distance testing works
    $db->exec("
        UPDATE workers 
        SET location_lat = 23.0338000, location_lng = 72.5850000, service_radius_km = 10 
        WHERE user_id = 3 AND (location_lat IS NULL OR location_lat = 23.0225000)
    ");
    $db->exec("
        UPDATE workers 
        SET location_lat = 23.0150000, location_lng = 72.5600000, service_radius_km = 15 
        WHERE user_id = 5 AND (location_lat IS NULL OR location_lat = 23.0225000)
    ");
    echo " [OK] Worker location coordinates synchronized.\n";

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
