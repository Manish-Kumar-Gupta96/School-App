<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['superadmin']); // Sirf system owner ke liye lock hai

$db = getDBConnection();
$report = [];
$actionMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_diagnostic'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Security Violation.");

    try {
        // 1. Check if there are any academic mappings pointing to non-existent teachers
        $stmt1 = $db->query("SELECT uam.id, uam.class_name, uam.section_name 
                             FROM unified_academic_mapping uam 
                             LEFT JOIN users u ON uam.teacher_id = u.id 
                             WHERE u.id IS NULL");
        $brokenTeachers = $stmt1->fetchAll();

        // 2. Count active verified students currently synced
        $activeStudentsCount = $db->query("SELECT COUNT(*) FROM students WHERE verification_status = 'ACTIVE'")->fetchColumn();
        $pendingStudentsCount = $db->query("SELECT COUNT(*) FROM students WHERE verification_status = 'PENDING_VERIFICATION'")->fetchColumn();

        $report['broken_mappings'] = count($brokenTeachers);
        $report['active_students'] = $activeStudentsCount;
        $report['pending_students'] = $pendingStudentsCount;
        
        AuditLogger::log('DATABASE_HEALTH_CHECK', "Executed global school structure diagnostics loop.");
    } catch (PDOException $e) {
        $actionMessage = "Diagnostic Fault: " . $e->getMessage();
    }
}

// Handle Auto-Repair Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repair_database'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Security Violation.");
    
    try {
        // Automatically delete mappings that have broken/deleted teacher connections
        $deletedRows = $db->exec("DELETE uam FROM unified_academic_mapping uam 
                                  LEFT JOIN users u ON uam.teacher_id = u.id 
                                  WHERE u.id IS NULL");
                                  
        AuditLogger::log('DATABASE_REPAIRED', "Cleaned up {$deletedRows} broken system mapping nodes.");
        $actionMessage = "System Cleaned! {$deletedRows} broken or corrupted alignment rows removed safely.";
    } catch (PDOException $e) {
        $actionMessage = "Repair Fault: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Optimization Desk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 700px;">
    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white font-weight-bold d-flex justify-content-between align-items-center">
            <h6 class="mb-0">VIC Core Database Integrity & Diagnostic Core</h6>
            <span class="badge bg-white text-danger">Superadmin Terminal</span>
        </div>
        <div class="card-body">
            <?php if (!empty($actionMessage)): ?>
                <div class="alert alert-warning small py-2"><?php echo $actionMessage; ?></div>
            <?php endif; ?>

            <p class="text-muted small">Is interface ke through aap school ki sabhi tables ke inter-connection links aur data structure alignment ko automatic parse aur realign kar sakte hain.</p>
            
            <form action="" method="POST" class="d-flex gap-2 mb-4">
                <?php Csrf::injectInput(); ?>
                <button type="submit" name="run_diagnostic" class="btn btn-dark btn-sm fw-bold">Run System Health Check</button>
                <?php if (isset($report['broken_mappings']) && $report['broken_mappings'] > 0): ?>
                    <button type="submit" name="repair_database" class="btn btn-success btn-sm fw-bold">Auto-Repair Broken Links</button>
                <?php endif; ?>
            </form>

            <?php if (!empty($report)): ?>
                <div class="border rounded bg-white p-3">
                    <h6 class="fw-bold border-bottom pb-2 mb-3 text-secondary">Diagnostic Execution Log Matrix</h6>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Corrupted / Broken Teacher Mappings:
                            <span class="badge bg-<?php echo $report['broken_mappings'] > 0 ? 'danger' : 'success'; ?>"><?php echo $report['broken_mappings']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Sync Students in Class Networks:
                            <span class="badge bg-primary"><?php echo $report['active_students']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Awaiting Class Verification Pipeline:
                            <span class="badge bg-warning text-dark"><?php echo $report['pending_students']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Database Status:
                            <span class="text-<?php echo $report['broken_mappings'] > 0 ? 'danger fw-bold' : 'success fw-bold'; ?>">
                                <?php echo $report['broken_mappings'] > 0 ? 'ACTION REQUIRED' : '100% HEALTHY'; ?>
                            </span>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
