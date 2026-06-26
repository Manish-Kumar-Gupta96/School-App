<?php
require_once 'config/database.php';

try {
    // 1. Update user_permissions table
    $pdo->exec("ALTER TABLE user_permissions ADD COLUMN allow_deny ENUM('allow','deny') DEFAULT 'allow'");
    echo "Added allow_deny to user_permissions.\n";
} catch (PDOException $e) {
    echo "Notice user_permissions: " . $e->getMessage() . "\n";
}

try {
    // 2. Create login_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        login_time DATETIME NOT NULL,
        logout_time DATETIME NULL,
        ip_address VARCHAR(100),
        device_info TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created login_logs table.\n";
} catch (PDOException $e) {
    echo "Notice login_logs: " . $e->getMessage() . "\n";
}

try {
    // 3. Seed Roles
    $roles = [
        ['super_admin', 'Full Control'],
        ['admin', 'School Admin'],
        ['teacher', 'Teacher Panel'],
        ['student', 'Student Panel'],
        ['parent', 'Parent Panel']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO roles (role_name, description) VALUES (?, ?)");
    foreach ($roles as $r) {
        $stmt->execute($r);
    }
    echo "Roles seeded.\n";
} catch (PDOException $e) {
    echo "Notice seeding roles: " . $e->getMessage() . "\n";
}

try {
    // 4. Seed Permissions
    $permissions = [
        ['dashboard_view','dashboard'],
        ['students_view','students'],
        ['students_add','students'],
        ['students_edit','students'],
        ['students_delete','students'],
        ['teachers_view','teachers'],
        ['teachers_add','teachers'],
        ['teachers_edit','teachers'],
        ['teachers_delete','teachers'],
        ['attendance_view','attendance'],
        ['attendance_add','attendance'],
        ['fees_view','fees'],
        ['fees_add','fees'],
        ['fees_edit','fees'],
        ['fees_delete','fees'],
        ['results_view','results'],
        ['results_add','results'],
        ['results_edit','results'],
        ['settings_view','settings'],
        ['settings_edit','settings']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (permission_name, module_name) VALUES (?, ?)");
    foreach ($permissions as $p) {
        $stmt->execute($p);
    }
    echo "Permissions seeded.\n";
} catch (PDOException $e) {
    echo "Notice seeding permissions: " . $e->getMessage() . "\n";
}
