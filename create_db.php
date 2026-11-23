<?php
// Database Initialization Script for iDam HoSZA
$host = "localhost";
$user = "root";
$pass = "";
$dbName = "idam_db";

$mysqli = new mysqli($host, $user, $pass);

if ($mysqli->connect_error) {
    die("Database Connection failed: " . $mysqli->connect_error . "\n");
}

echo "=== Initializing iDam Database ===\n";

// 1. Create Database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($sql) === TRUE) {
    echo "✓ Database `{$dbName}` confirmed.\n";
} else {
    die("Error creating database: " . $mysqli->error . "\n");
}

$mysqli->select_db($dbName);
$mysqli->set_charset("utf8mb4");

// 2. Table: system_settings
$tableSettings = "CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($tableSettings)) {
    echo "✓ Table `system_settings` ready.\n";
} else {
    echo "✗ Error creating `system_settings`: " . $mysqli->error . "\n";
}

// 3. Table: units
$tableUnits = "CREATE TABLE IF NOT EXISTS `units` (
    `units_id` INT AUTO_INCREMENT PRIMARY KEY,
    `units_code` VARCHAR(50) NOT NULL UNIQUE,
    `units_name` VARCHAR(150) NOT NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `remark` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($tableUnits)) {
    echo "✓ Table `units` ready.\n";
} else {
    echo "✗ Error creating `units`: " . $mysqli->error . "\n";
}

// 4. Table: users
$tableUsers = "CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `unit_id` VARCHAR(255) NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `role` TINYINT NOT NULL DEFAULT 1,
    `status` TINYINT NOT NULL DEFAULT 0,
    `reason_to_access` TEXT NULL,
    `remark` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($tableUsers)) {
    echo "✓ Table `users` ready.\n";
} else {
    echo "✗ Error creating `users`: " . $mysqli->error . "\n";
}

// 5. Table: storages
$tableStorages = "CREATE TABLE IF NOT EXISTS `storages` (
    `storage_id` INT AUTO_INCREMENT PRIMARY KEY,
    `units_id` INT NULL,
    `units_code` VARCHAR(50) NOT NULL,
    `duration_id` TINYINT NOT NULL DEFAULT 1,
    `storage_type` VARCHAR(50) NOT NULL,
    `storage_name` VARCHAR(150) NOT NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `remark` TEXT NULL,
    `created_by` VARCHAR(100) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($tableStorages)) {
    echo "✓ Table `storages` ready.\n";
} else {
    echo "✗ Error creating `storages`: " . $mysqli->error . "\n";
}

// 6. Table: containers
$tableContainers = "CREATE TABLE IF NOT EXISTS `containers` (
    `container_id` INT AUTO_INCREMENT PRIMARY KEY,
    `storage_id` INT NOT NULL,
    `duration_id` TINYINT NOT NULL DEFAULT 1,
    `container_number` INT NOT NULL,
    `container_label` VARCHAR(100) NOT NULL,
    `container_day` TINYINT NOT NULL DEFAULT 0,
    `status` TINYINT NOT NULL DEFAULT 1,
    `remark` TEXT NULL,
    `created_by` VARCHAR(100) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_storage` (`storage_id`),
    INDEX `idx_storage_num_day` (`storage_id`, `container_number`, `container_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($tableContainers)) {
    echo "✓ Table `containers` ready.\n";
} else {
    echo "✗ Error creating `containers`: " . $mysqli->error . "\n";
}

// 7. Table: specimens
$tableSpecimens = "CREATE TABLE IF NOT EXISTS `specimens` (
    `specimens_id` INT AUTO_INCREMENT PRIMARY KEY,
    `specimens_code` VARCHAR(100) NOT NULL,
    `container_id` INT NULL,
    `units_id` INT NULL,
    `slot_no` INT NULL,
    `status` TINYINT NOT NULL DEFAULT 1,
    `created_by` VARCHAR(100) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `request_to_dispose_at` DATETIME NULL,
    `request_to_dispose_by` VARCHAR(100) NULL,
    `disposed_at` DATETIME NULL,
    `disposed_by` VARCHAR(100) NULL,
    `cancelled_by` VARCHAR(100) NULL,
    `remark` TEXT NULL,
    INDEX `idx_specimens_code` (`specimens_code`),
    INDEX `idx_container_slot` (`container_id`, `slot_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($mysqli->query($tableSpecimens)) {
    echo "✓ Table `specimens` ready.\n";
} else {
    echo "✗ Error creating `specimens`: " . $mysqli->error . "\n";
}

// ============================================================
// 8. Default Seed Data
// ============================================================
echo "\n--- Seeding Initial Data ---\n";

// A. system_settings
$settingsSeeds = [
    ['maintenance_mode', '0'],
    ['maintenance_reason', 'The system is currently undergoing scheduled maintenance optimization.']
];

$stmt = $mysqli->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
foreach ($settingsSeeds as $s) {
    $stmt->bind_param("ss", $s[0], $s[1]);
    $stmt->execute();
}
$stmt->close();
echo "✓ System settings seeded.\n";

// B. units
$unitsSeeds = [
    ['HEM', 'Hematology Unit', 1, 'Main Hematology Lab'],
    ['BIO', 'Biochemistry Unit', 1, 'Main Biochemistry Lab'],
    ['MIC', 'Microbiology Unit', 1, 'Microbiology & Serology Lab'],
    ['BB', 'Blood Bank Unit', 1, 'Transfusion & Blood Bank Lab'],
    ['HIS', 'Histopathology Unit', 1, 'Tissue & Cellular Pathology Lab']
];

$stmt = $mysqli->prepare("INSERT IGNORE INTO `units` (`units_code`, `units_name`, `status`, `remark`) VALUES (?, ?, ?, ?)");
foreach ($unitsSeeds as $u) {
    $stmt->bind_param("ssis", $u[0], $u[1], $u[2], $u[3]);
    $stmt->execute();
}
$stmt->close();
echo "✓ Master laboratory units seeded.\n";

// Fetch unit IDs
$unitMap = [];
$res = $mysqli->query("SELECT `units_id`, `units_code` FROM `units`");
while ($row = $res->fetch_assoc()) {
    $unitMap[$row['units_code']] = $row['units_id'];
}

// C. users
$usersSeeds = [
    [null, 'malikmanan@unisza.edu.my', 3, 1, 'Primary System Administrator', 'Approved Super Admin'],
    [null, 'admin@unisza.edu.my', 3, 1, 'System Administrator', 'Approved Admin']
];

$stmt = $mysqli->prepare("INSERT IGNORE INTO `users` (`unit_id`, `email`, `role`, `status`, `reason_to_access`, `remark`) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($usersSeeds as $usr) {
    $stmt->bind_param("ssiiss", $usr[0], $usr[1], $usr[2], $usr[3], $usr[4], $usr[5]);
    $stmt->execute();
}
$stmt->close();
echo "✓ Administrator accounts seeded.\n";

// D. storages (if none exist)
$checkStorages = $mysqli->query("SELECT COUNT(*) AS cnt FROM `storages`");
$storagesCount = $checkStorages->fetch_assoc()['cnt'];

if ($storagesCount == 0) {
    $storageSeeds = [
        ['HEM', 1, 'Chiller', 'Chiller 1 - Hematology', 1, 'Primary 7-Day Chiller Storage'],
        ['HEM', 2, 'Freezer', 'Freezer 1 - Hematology (14D)', 1, '14-Day Deep Freezer'],
        ['BIO', 1, 'Chiller', 'Chiller 1 - Biochemistry', 1, 'Primary 7-Day Biochemistry Chiller'],
        ['BIO', 4, 'Chiller', 'Chiller B - Immunoassay (14D)', 1, '14-Day Immunoassay Chiller Storage'],
        ['BIO', 3, 'Freezer', 'Freezer 1 - Biochemistry (21D)', 1, '21-Day Long Term Storage']
    ];

    $stmt = $mysqli->prepare("INSERT INTO `storages` (`units_id`, `units_code`, `duration_id`, `storage_type`, `storage_name`, `status`, `remark`, `created_by`) VALUES (?, ?, ?, ?, ?, ?, ?, 'system')");
    foreach ($storageSeeds as $stg) {
        $uId = $unitMap[$stg[0]] ?? null;
        $stmt->bind_param("isisiss", $uId, $stg[0], $stg[1], $stg[2], $stg[3], $stg[4], $stg[5]);
        $stmt->execute();
    }
    $stmt->close();
    echo "✓ Initial laboratory storages seeded.\n";

    // E. containers for all 7 days (0=Sunday ... 6=Saturday)
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $resStg = $mysqli->query("SELECT `storage_id`, `duration_id` FROM `storages`");

    $stmtC = $mysqli->prepare("INSERT INTO `containers` (`storage_id`, `duration_id`, `container_number`, `container_label`, `container_day`, `status`, `created_by`) VALUES (?, ?, ?, ?, ?, 1, 'system')");
    while ($stgRow = $resStg->fetch_assoc()) {
        $stgId = $stgRow['storage_id'];
        $durId = $stgRow['duration_id'];

        for ($boxNum = 1; $boxNum <= 2; $boxNum++) {
            for ($d = 0; $d <= 6; $d++) {
                $label = "Box {$boxNum} - {$dayNames[$d]}";
                $stmtC->bind_param("iiisi", $stgId, $durId, $boxNum, $label, $d);
                $stmtC->execute();
            }
        }
    }
    $stmtC->close();
    echo "✓ Standard 7-day containers generated for active storages.\n";
}

echo "\n=== Database Setup Completed Successfully ===\n";
