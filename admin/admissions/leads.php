<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['admin', 'superadmin']);

$db = getDBConnection();
$message = '';

// Process Lead Stage Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Exception: CSRF token failure.");
    }

    $leadId = filter_input(INPUT_POST, 'lead_id', FILTER_VALIDATE_INT);
    $nextStatus = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $adminNotes = filter_input(INPUT_POST, 'admin_notes', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($leadId && !empty($nextStatus)) {
        try {
            $stmt = $db->prepare("UPDATE admission_enquiries SET status = :status, admin_notes = :notes WHERE id = :id");
            $stmt->execute([
                ':status' => $nextStatus,
                ':notes'  => $adminNotes,
                ':id'     => $leadId
            ]);

            AuditLogger::log('ADMISSION_LEAD_UPDATED', "Lead ID #{$leadId} modified status profile to {$nextStatus}");
            $message = "Lead state synced dynamically.";
        } catch (PDOException $e) {
            $message = "Update Interrupted Exception: " . $e->getMessage();
        }
    }
}

// Pull list entries out of system
$enquiries = $db->query("SELECT * FROM admission_enquiries ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admissions CRM Desk</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white font-weight-bold">
            Admissions Pipeline Management CRM Desk
        </div>
        <div class="card-body p-0">
            <?php if(!empty($message)): ?>
                <div class="alert alert-info rounded-0 mb-0 small py-2"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <table class="table table-striped align-middle table-sm mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>Date Received</th>
                        <th>Student Details</th>
                        <th>Guardian Contacts</th>
                        <th>Previous School</th>
                        <th>Current Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($enquiries) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No admission enquiries recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enquiries as $lead): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($lead['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($lead['student_name']); ?></strong><br>
                                    <span class="badge bg-secondary text-white"><?php echo htmlspecialchars($lead['class_requested']); ?></span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($lead['parent_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($lead['parent_phone']); ?></small>
                                </td>
                                <td><small><?php echo htmlspecialchars($lead['previous_school'] ?: 'N/A'); ?></small></td>
                                <td>
                                    <?php
                                        $badgeColor = 'secondary';
                                        if ($lead['status'] === 'APPROVED') $badgeColor = 'success';
                                        elseif ($lead['status'] === 'REJECTED') $badgeColor = 'danger';
                                        elseif ($lead['status'] === 'UNDER_REVIEW') $badgeColor = 'warning text-dark';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeColor; ?>"><?php echo $lead['status']; ?></span>
                                </td>
                                <td>
                                    <!-- Dynamic Action Form -->
                                    <form method="POST" action="" class="d-flex flex-column gap-1">
                                        <?php Csrf::injectInput(); ?>
                                        <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                        
                                        <div class="d-flex gap-1">
                                            <select name="status" class="form-select form-select-sm" style="width: 130px;">
                                                <option value="PENDING" <?php echo $lead['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="UNDER_REVIEW" <?php echo $lead['status'] == 'UNDER_REVIEW' ? 'selected' : ''; ?>>Under Review</option>
                                                <option value="APPROVED" <?php echo $lead['status'] == 'APPROVED' ? 'selected' : ''; ?>>Approve</option>
                                                <option value="REJECTED" <?php echo $lead['status'] == 'REJECTED' ? 'selected' : ''; ?>>Reject</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                        </div>
                                        <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Add Note" value="<?php echo htmlspecialchars($lead['admin_notes'] ?? ''); ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
