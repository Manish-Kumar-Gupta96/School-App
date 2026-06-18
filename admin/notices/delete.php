<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];

    // Fetch notice to check for attachment
    $stmt = $pdo->prepare("SELECT attachment FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch(PDO::FETCH_ASSOC);

    if($notice){
        // Delete attachment file if exists
        if(!empty($notice['attachment']) && file_exists("../../uploads/notices/" . $notice['attachment'])){
            unlink("../../uploads/notices/" . $notice['attachment']);
        }

        // Delete database record
        $stmt_delete = $pdo->prepare("DELETE FROM notices WHERE id = ?");
        $stmt_delete->execute([$id]);
    }
}

header("Location: index.php?deleted=1");
exit();
?>
