<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Maintenance Request
if (isset($_POST['report_issue'])) {
    $item_id = (int)$_POST['inventory_item_id'];
    $issue_desc = trim($_POST['issue_description']);
    
    try {
        $pdo->beginTransaction();
        
        // Log request
        $stmt_req = $pdo->prepare("INSERT INTO maintenance_requests (school_id, inventory_item_id, reported_by_type, reported_by, issue_description, status, reported_at) VALUES (?, ?, 'teacher', ?, ?, 'pending', NOW())");
        $stmt_req->execute([$schoolId, $item_id, $teacher_id, $issue_desc]);
        
        // Update item status
        $stmt_upd = $pdo->prepare("UPDATE inventory_items SET status = 'maintenance' WHERE id = ?");
        $stmt_upd->execute([$item_id]);
        
        $pdo->commit();
        $message = "Maintenance request submitted. The admin team has been notified.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error reporting issue: " . $e->getMessage();
    }
}

// Fetch assigned assets
$stmt_assets = $pdo->prepare("
    SELECT aa.*, i.item_name, i.item_code, i.status as item_status
    FROM asset_assignments aa
    JOIN inventory_items i ON aa.inventory_item_id = i.id
    WHERE aa.assigned_to_type = 'teacher' AND aa.assigned_to_id = ? AND aa.school_id = ? AND aa.status = 'issued'
");
$stmt_assets->execute([$teacher_id, $schoolId]);
$assets = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "My Assigned Assets | Teacher Portal";
$page_header = "My Inventory";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">My Assigned Assets</h5>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item Name</th>
                        <th>Item Code</th>
                        <th>Assigned Date</th>
                        <th>Expected Return</th>
                        <th>Status</th>
                        <th class="pe-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($assets as $a): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($a['item_name']) ?></td>
                            <td><code><?= htmlspecialchars($a['item_code']) ?></code></td>
                            <td><?= date('d M Y', strtotime($a['assigned_date'])) ?></td>
                            <td>
                                <?php if($a['expected_return_date']): ?>
                                    <?= date('d M Y', strtotime($a['expected_return_date'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($a['item_status'] == 'maintenance'): ?>
                                    <span class="badge bg-danger"><i class="fa fa-tools"></i> In Maintenance</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fa fa-check-circle"></i> Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if($a['item_status'] != 'maintenance'): ?>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="reportIssue(<?= $a['inventory_item_id'] ?>, '<?= htmlspecialchars(addslashes($a['item_name'])) ?>')">
                                        Report Issue
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light rounded-pill px-3" disabled>Under Repair</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($assets)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">You do not have any assets assigned to you.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-danger">Report Asset Issue</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" name="inventory_item_id" id="report_item_id">
        <div class="mb-3">
            <label class="form-label fw-bold">Item</label>
            <input type="text" id="report_item_name" class="form-control" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Describe the Issue</label>
            <textarea name="issue_description" class="form-control" rows="4" required placeholder="E.g., Screen is flickering, keyboard not working..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="report_issue" class="btn btn-danger px-4">Submit Report</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script>
function reportIssue(itemId, name) {
    document.getElementById('report_item_id').value = itemId;
    document.getElementById('report_item_name').value = name;
    new bootstrap.Modal(document.getElementById('reportModal')).show();
}
</script>

<?php require_once('../includes/footer.php'); ?>
