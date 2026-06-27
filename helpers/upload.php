<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

function uploadFile($file, $targetDir = 'uploads') {
    // Windows vs Linux path compatibility fix
    $baseDir = ROOT_PATH . DIRECTORY_SEPARATOR . $targetDir;
    
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Strict security check for dangerous executable extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['status' => false, 'message' => 'Extension not allowed. Security risk detected.'];
    }
    
    // Generate secure hashed filename
    $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
    $targetFilePath = $baseDir . DIRECTORY_SEPARATOR . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return ['status' => true, 'filename' => $newFileName, 'relative_path' => $targetDir . '/' . $newFileName];
    }
    
    return ['status' => false, 'message' => 'Failed to move uploaded file.'];
}
?>
