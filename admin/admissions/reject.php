<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied: Admin authorization required.");
}

if(!isset($_GET['id'])){
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================
CHECK RECORD
========================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE id = ?
");
$stmt->execute([$id]);
$admission = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$admission){
    header("Location:index.php");
    exit();
}

/* ==========================
UPDATE STATUS
========================== */
$update = $pdo->prepare("
    UPDATE admissions
    SET status='Rejected'
    WHERE id=?
");
$update->execute([$id]);

header("Location:index.php?rejected=1");
exit();
?>
