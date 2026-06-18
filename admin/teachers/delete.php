<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================
GET TEACHER
========================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM teachers
    WHERE id = ?
");
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher){
    header("Location:index.php");
    exit();
}

/* ==========================
DELETE PHOTO
========================== */
if(!empty($teacher['photo']) && file_exists("../../uploads/teachers/" . $teacher['photo'])){
    unlink("../../uploads/teachers/" . $teacher['photo']);
}

/* ==========================
DELETE TEACHER
========================== */
$delete = $pdo->prepare("
    DELETE FROM teachers
    WHERE id = ?
");
$delete->execute([$id]);

header("Location:index.php?deleted=1");
exit();
?>
