<?php
require_once('config/database.php');

try {
    $pdo->beginTransaction();

    // Default password hash for 'password123'
    $default_pwd = password_hash('password123', PASSWORD_DEFAULT);

    // 1. Backfill Teachers
    $stmt_teachers = $pdo->query("SELECT * FROM teachers WHERE email NOT IN (SELECT email FROM users WHERE role_id = 4)");
    $teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
    $ins = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role_id, status, school_id) VALUES (?, ?, ?, 4, ?, ?)");
    foreach($teachers as $t) {
        $ins->execute([$t['name'], $t['email'], $default_pwd, $t['status'], $t['school_id']]);
    }

    // 2. Backfill Students
    $stmt_students = $pdo->query("SELECT * FROM students WHERE email NOT IN (SELECT email FROM users WHERE role_id = 8)");
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    $ins_st = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role_id, status, school_id) VALUES (?, ?, ?, 8, 'ACTIVE', ?)");
    foreach($students as $s) {
        $ins_st->execute([trim($s['first_name'] . ' ' . $s['last_name']), $s['email'], $default_pwd, $s['school_id']]);
    }

    // 3. Backfill Parents
    $stmt_parents = $pdo->query("SELECT * FROM parents WHERE email NOT IN (SELECT email FROM users WHERE role_id = 7)");
    $parents = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);
    $ins_pr = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role_id, status, school_id) VALUES (?, ?, ?, 7, ?, ?)");
    foreach($parents as $p) {
        $ins_pr->execute([$p['parent_name'], $p['email'], $default_pwd, $p['status'], $p['school_id']]);
    }

    // 4. Create Triggers
    $triggers = [
        "DROP TRIGGER IF EXISTS after_teacher_insert",
        "CREATE TRIGGER after_teacher_insert AFTER INSERT ON teachers FOR EACH ROW 
         BEGIN 
             INSERT IGNORE INTO users (name, email, password, plain_password, role_id, status, school_id) 
             VALUES (NEW.name, NEW.email, '$default_pwd', 'password123', 4, NEW.status, NEW.school_id); 
         END",

        "DROP TRIGGER IF EXISTS after_teacher_update",
        "CREATE TRIGGER after_teacher_update AFTER UPDATE ON teachers FOR EACH ROW 
         BEGIN 
             UPDATE users SET name = NEW.name, email = NEW.email, status = NEW.status 
             WHERE email = OLD.email AND role_id = 4; 
         END",

        "DROP TRIGGER IF EXISTS after_student_insert",
        "CREATE TRIGGER after_student_insert AFTER INSERT ON students FOR EACH ROW 
         BEGIN 
             INSERT IGNORE INTO users (name, email, password, plain_password, role_id, status, school_id) 
             VALUES (CONCAT(NEW.first_name, ' ', NEW.last_name), NEW.email, '$default_pwd', 'password123', 8, 'ACTIVE', NEW.school_id); 
         END",

        "DROP TRIGGER IF EXISTS after_student_update",
        "CREATE TRIGGER after_student_update AFTER UPDATE ON students FOR EACH ROW 
         BEGIN 
             UPDATE users SET name = CONCAT(NEW.first_name, ' ', NEW.last_name), email = NEW.email 
             WHERE email = OLD.email AND role_id = 8; 
         END",

        "DROP TRIGGER IF EXISTS after_parent_insert",
        "CREATE TRIGGER after_parent_insert AFTER INSERT ON parents FOR EACH ROW 
         BEGIN 
             INSERT IGNORE INTO users (name, email, password, plain_password, role_id, status, school_id) 
             VALUES (NEW.parent_name, NEW.email, '$default_pwd', 'password123', 7, NEW.status, NEW.school_id); 
         END",

        "DROP TRIGGER IF EXISTS after_parent_update",
        "CREATE TRIGGER after_parent_update AFTER UPDATE ON parents FOR EACH ROW 
         BEGIN 
             UPDATE users SET name = NEW.parent_name, email = NEW.email, status = NEW.status 
             WHERE email = OLD.email AND role_id = 7; 
         END"
    ];

    foreach($triggers as $sql) {
        $pdo->exec($sql);
    }

    $pdo->commit();
    echo "Synced users and created triggers successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
