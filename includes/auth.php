<?php
require_once(__DIR__ . '/AuthClass.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/permissions.php');

if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])){
    $execScript = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']);
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    if (strpos(strtolower($execScript), strtolower($projectRoot)) === 0) {
        $subpath = substr($execScript, strlen($projectRoot));
        $dirCount = substr_count(trim($subpath, '/'), '/');
        $backPath = str_repeat('../', $dirCount);
        header("Location: " . $backPath . "login.php");
    } else {
        header("Location: /login.php");
    }
    exit();
}

$role_name = ucfirst($_SESSION['role']); // 'Admin', 'Teacher', 'Student', 'Parent'

// Fetch role_id
$stmt_r = $pdo->prepare("SELECT id FROM roles WHERE role_name=?");
$stmt_r->execute([$role_name]);
$role_id = $stmt_r->fetchColumn() ?: 1;

// Fetch user details from appropriate table by matching email in users table
$user_id = $_SESSION['user_id'];
$user = [];

$stmt_email = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt_email->execute([$user_id]);
$email = $stmt_email->fetchColumn();

if ($email) {
    if ($_SESSION['role'] === 'admin') {
        $stmt_u = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt_u->execute([$email]);
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['admin_id'] = $user['id'];
        }
    } elseif ($_SESSION['role'] === 'teacher') {
        $stmt_u = $pdo->prepare("SELECT * FROM teachers WHERE email = ?");
        $stmt_u->execute([$email]);
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['teacher_id'] = $user['id'];
        }
    } elseif ($_SESSION['role'] === 'student') {
        $stmt_u = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt_u->execute([$email]);
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['student_id'] = $user['id'];
        }
    } elseif ($_SESSION['role'] === 'parent') {
        $stmt_u = $pdo->prepare("SELECT * FROM parents WHERE email = ?");
        $stmt_u->execute([$email]);
        $user = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['parent_id'] = $user['id'];
        }
    } elseif ($_SESSION['role'] === 'guard') {
        // Guards are considered staff, but we can just use the user ID
        $user = ['id' => $user_id, 'email' => $email, 'first_name' => 'Security', 'last_name' => 'Guard'];
        $_SESSION['guard_id'] = $user['id'];
    }
}

if ($user) {
    $user['role_name'] = $role_name;
    $user['role_id'] = $role_id;
}

/* ==========================
PERMISSION CHECK FUNCTION
========================== */
function checkPermission($role_id, $module, $action, $pdo){
    $stmt = $pdo->prepare("
        SELECT $action
        FROM permissions
        WHERE role_id=? AND module=?
    ");
    $stmt->execute([$role_id, $module]);
    return (bool)$stmt->fetchColumn();
}
?>
