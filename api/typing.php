<?php
// api/typing.php
require_once('../config/database.php');
session_start();

if (!isset($_POST['conversation_id'])) {
    exit;
}

$conversation_id = (int)$_POST['conversation_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Identify user and role from session
$user_id = null;
$role = null;

if (isset($_SESSION['parent_id'])) {
    $user_id = $_SESSION['parent_id'];
    $role = 'parent';
} elseif (isset($_SESSION['teacher_id'])) {
    $user_id = $_SESSION['teacher_id'];
    $role = 'teacher';
} else {
    exit;
}

try {
    // Check if entry exists
    $stmt_check = $pdo->prepare("SELECT id FROM typing_status WHERE user_id = ? AND role = ? AND conversation_id = ? AND school_id = ?");
    $stmt_check->execute([$user_id, $role, $conversation_id, $schoolId]);
    $exists = $stmt_check->fetchColumn();

    if ($exists) {
        $stmt_up = $pdo->prepare("UPDATE typing_status SET is_typing = 1, updated_at = NOW() WHERE id = ?");
        $stmt_up->execute([$exists]);
    } else {
        $stmt_in = $pdo->prepare("INSERT INTO typing_status (school_id, user_id, role, conversation_id, is_typing, updated_at) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt_in->execute([$schoolId, $user_id, $role, $conversation_id]);
    }

    echo "Typing status updated";
} catch (Exception $e) {
    // Silently fail for typing status
}
