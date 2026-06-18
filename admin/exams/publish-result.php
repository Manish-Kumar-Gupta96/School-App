<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(isset($_GET['exam_id'])){
    $exam_id = (int)$_GET['exam_id'];

    // Get current status
    $stmt = $pdo->prepare("SELECT result_status FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if($exam){
        $new_status = ($exam['result_status'] === 'Published') ? 'Draft' : 'Published';
        $update = $pdo->prepare("UPDATE exams SET result_status = ? WHERE id = ?");
        $update->execute([$new_status, $exam_id]);

        require_once('../includes/audit-helper.php');
        addAuditLog(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['name'] ?? 'Admin',
            $_SESSION['role'] ?? 'Admin',
            'Result Status Toggled to ' . $new_status . ' (Exam ID: ' . $exam_id . ')',
            'Exams',
            $exam_id
        );
    }
}

header("Location: create-exam.php?saved=1");
exit();
?>
