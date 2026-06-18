<?php
session_start();
require_once('../config/database.php');

if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])){
    $assignment_id = (int)$_POST['assignment_id'];
    $student_id    = $_SESSION['student_id'];
    $text          = trim($_POST['text'] ?? '');
    $file_path     = '';

    // Validate file upload
    if(isset($_FILES['file']) && !empty($_FILES['file']['name'])){
        $file_name = $_FILES['file']['name'];
        $tmp       = $_FILES['file']['tmp_name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if(in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg'])){
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = "sub_" . time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name);
            $file_path = "uploads/" . $new_filename;
            move_uploaded_file($tmp, $upload_dir . $new_filename);
        } else {
            $_SESSION['error'] = "Invalid file type. Submission aborted.";
            header("Location: assignments.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_path, status)
        VALUES (?,?,?,?, 'SUBMITTED')
    ");
    $stmt->execute([
        $assignment_id,
        $student_id,
        $text,
        $file_path
    ]);

    $_SESSION['message'] = "Assignment submitted successfully!";
    header("Location: assignments.php");
    exit;
} else {
    header("Location: assignments.php");
    exit;
}
?>
