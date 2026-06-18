<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================
GET STUDENT
========================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM students
    WHERE id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student){
    header("Location: index.php");
    exit();
}

/* ==========================
DELETE PHOTO
========================== */
if(
    !empty($student['photo'])
    &&
    file_exists(
        "../../uploads/students/" .
        $student['photo']
    )
){
    unlink(
        "../../uploads/students/" .
        $student['photo']
    );
}

/* ==========================
DELETE STUDENT
========================== */
$delete = $pdo->prepare("
    DELETE FROM students
    WHERE id = ?
");
$delete->execute([$id]);

header("Location: index.php?deleted=1");
exit();
?>
