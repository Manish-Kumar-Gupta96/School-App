<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];

/* GET RECORD */
$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE id=?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
    header("Location:index.php");
    exit();
}

/* DELETE PHOTO */
if(
    !empty($row['photo']) &&
    file_exists(
        "../../uploads/admissions/" .
        $row['photo']
    )
){
    unlink(
        "../../uploads/admissions/" .
        $row['photo']
    );
}

/* DELETE DOCUMENT */
if(
    !empty($row['document']) &&
    file_exists(
        "../../uploads/admissions/" .
        $row['document']
    )
){
    unlink(
        "../../uploads/admissions/" .
        $row['document']
    );
}

/* DELETE RECORD */
$delete = $pdo->prepare("
    DELETE FROM admissions
    WHERE id=?
");
$delete->execute([$id]);

header("Location:index.php?deleted=1");
exit();
?>
