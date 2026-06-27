<?php
ob_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/SessionManager.php';

SessionManager::startSecureSession();

// Restrict to internal staff operations parameters only
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['teacher', 'admin', 'superadmin'])) {
    die("Unauthorized stream download intent dropped.");
}

$targetClass = filter_input(INPUT_GET, 'class_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Class 10';

try {
    $db = getDBConnection();
    
    // Extract validated active students schema context row lines
    $stmt = $db->prepare("SELECT roll_no, name, class_name, created_at FROM students WHERE class_name = :class AND verification_status = 'ACTIVE' ORDER BY roll_no ASC");
    $stmt->execute([':class' => $targetClass]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clean system execution buffers ahead of raw generation streams headers
    ob_end_clean();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Verified_Students_' . str_replace(' ', '_', $targetClass) . '_' . date('Ymd') . '.csv');

    $outputChannel = fopen('php://output', 'w');
    
    // Inject CSV Tabular Excel Header Row Descriptor Block
    fputcsv($outputChannel, ['Roll Numbers Map', 'Student Full Name', 'Academic Division Section', 'Admission Approved Date']);

    foreach ($records as $row) {
        fputcsv($outputChannel, [
            $row['roll_no'],
            strtoupper($row['name']),
            $row['class_name'],
            date('Y-m-d', strtotime($row['created_at']))
        ]);
    }
    
    fclose($outputChannel);
    exit;

} catch (PDOException $e) {
    die("Pipeline Error Generating CSV Report: " . $e->getMessage());
}
?>
