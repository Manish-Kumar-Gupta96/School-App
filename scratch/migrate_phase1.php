<?php
require_once 'config/database.php';

echo "Starting safe database migration...\n";

// 1. Update `users` table
$usersColumns = [
    'last_login' => 'DATETIME NULL',
    'last_password_change' => 'DATETIME NULL',
    'password_expires_at' => 'DATETIME NULL',
    'failed_attempts' => 'INT DEFAULT 0',
    'locked_until' => 'DATETIME NULL',
    'remember_token' => 'VARCHAR(255) NULL',
    'remember_token_expiry' => 'DATETIME NULL',
    'created_by' => 'BIGINT NULL',
    'updated_by' => 'BIGINT NULL',
    'updated_at' => 'TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP',
    'deleted_at' => 'DATETIME NULL'
];

foreach ($usersColumns as $col => $def) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
        echo "Added $col to users table.\n";
    } catch (PDOException $e) {
        // Ignore if column already exists
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "Error adding $col: " . $e->getMessage() . "\n";
        }
    }
}

// 2. Create `active_sessions`
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS active_sessions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        session_id VARCHAR(255),
        login_time DATETIME,
        last_activity DATETIME,
        ip_address VARCHAR(100),
        browser VARCHAR(255),
        device VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created active_sessions table.\n";
} catch (PDOException $e) { echo "Error: " . $e->getMessage() . "\n"; }

// 3. Create `failed_logins`
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS failed_logins (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255),
        ip_address VARCHAR(100),
        attempt_time DATETIME,
        browser TEXT,
        INDEX(username),
        INDEX(ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created failed_logins table.\n";
} catch (PDOException $e) { echo "Error: " . $e->getMessage() . "\n"; }

// 4. Create `password_resets`
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT,
        reset_token VARCHAR(255),
        expires_at DATETIME,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created password_resets table.\n";
} catch (PDOException $e) { echo "Error: " . $e->getMessage() . "\n"; }

// 5. Create `remember_tokens`
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT,
        selector VARCHAR(255),
        validator_hash VARCHAR(255),
        expires_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created remember_tokens table.\n";
} catch (PDOException $e) { echo "Error: " . $e->getMessage() . "\n"; }

// 6. Create `security_settings`
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
        id INT PRIMARY KEY,
        max_login_attempts INT DEFAULT 5,
        lock_minutes INT DEFAULT 15,
        session_timeout INT DEFAULT 30,
        password_expiry_days INT DEFAULT 90,
        allow_multiple_login TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $pdo->exec("INSERT IGNORE INTO security_settings VALUES (1, 5, 15, 30, 90, 0)");
    echo "Created security_settings table.\n";
} catch (PDOException $e) { echo "Error: " . $e->getMessage() . "\n"; }

// Note: login_logs and audit_logs were already created in my previous passes,
// but I will alter login_logs to match the exact schema if needed.
try {
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN username VARCHAR(150)");
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN role_name VARCHAR(100)");
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN school_id BIGINT NULL");
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN browser VARCHAR(255)");
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN platform VARCHAR(255)");
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN device VARCHAR(255)");
    $pdo->exec("ALTER TABLE login_logs ADD COLUMN login_status ENUM('success','failed')");
} catch (PDOException $e) {
    // Ignore duplicate columns
}

echo "Migration complete.\n";
