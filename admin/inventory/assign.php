<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Issue Asset
if (isset($_POST['issue_asset'])) {
    $item_id = (int)$_POST['inventory_item_id'];
    $assigned_type = $_POST['assigned_to_type']; // teacher, student, room
    $assigned_id = (int)$_POST['assigned_to_id']; // This would realistically be a dropdown, but for now we'll accept ID text input
    $exp_date = $_POST['expected_return_date'];
    
    try {
        $pdo->beginTransaction();
        
        // Ensure item is available
        $stmt_check = $pdo->prepare("SELECT status FROM inventory_items WHERE id = ? AND school_id = ?");
        $stmt_check->execute([$item_id, $schoolId]);
        $it = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($it && $it['status'] === 'available') {
            // Insert assignment
            $stmt_ass = $pdo->prepare("INSERT INTO asset_assignments (school_id, inventory_item_id, assigned_to_type, assigned_to_id, assigned_date, expected_return_date, status) VALUES (?, ?, ?, ?, CURDATE(), ?, 'issued')");
            $stmt_ass->execute([$schoolId, $item_id, $assigned_type, $assigned_id, $exp_date ?: null]);
            
            // Update item status
            $stmt_upd = $pdo->prepare("UPDATE inventory_items SET status = 'issued' WHERE id = ?");
            $stmt_upd->execute([$item_id]);
            
            // Log history
            $stmt_hist = $pdo->prepare("INSERT INTO asset_history (school_id, inventory_item_id, action_type, description, action_date) VALUES (?, ?, 'Issued', ?, NOW())");
            $desc = "Issued to {$assigned_type} ID: {$assigned_id}";
            $stmt_hist->execute([$schoolId, $item_id, $desc]);
            
            $pdo->commit();
            $message = "Asset issued successfully.";
        } else {
            $pdo->rollBack();
            $error = "Item is not available for issue.";
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error issuing asset: " . $e->getMessage();
    }
}

// Handle Return Asset
if (isset($_POST['return_asset'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get assignment
        $stmt_get = $pdo->prepare("SELECT inventory_item_id FROM asset_assignments WHERE id = ? AND school_id = ? AND status = 'issued'");
        $stmt_get->execute([$assignment_id, $schoolId]);
        $ass = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if ($ass) {
            $item_id = $ass['inventory_item_id'];
            
            // Update assignment
            $stmt_upd_ass = $pdo->prepare("UPDATE asset_assignments SET status = 'returned', actual_return_date = CURDATE() WHERE id = ?");
            $stmt_upd_ass->execute([$assignment_id]);
            
            // Update item
            $stmt_upd_item = $pdo->prepare("UPDATE inventory_items SET status = 'available' WHERE id = ?");
            $stmt_upd_item->execute([$item_id]);
            
            // Log history
            $stmt_hist = $pdo->prepare("INSERT INTO asset_history (school_id, inventory_item_id, action_type, description, action_date) VALUES (?, ?, 'Returned', 'Asset returned to inventory', NOW())");
            $stmt_hist->execute([$schoolId, $item_id]);
            
            $pdo->commit();
            $message = "Asset returned successfully.";
        } else {
            $pdo->rollBack();
            $error = "Invalid or already returned assignment.";
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error returning asset: " . $e->getMessage();
    }
}

// Fetch available items
$stmt_avail = $pdo->prepare("SELECT id, item_name, item_code FROM inventory_items WHERE status = 'available' AND school_id = ?");
$stmt_avail->execute([$schoolId]);
$available_items = $stmt_avail->fetchAll(PDO::FETCH_ASSOC);

// Fetch current assignments
$stmt_active = $pdo->prepare("
    SELECT aa.*, i.item_name, i.item_code 
    FROM asset_assignments aa
    JOIN inventory_items i ON aa.inventory_item_id = i.id
    WHERE aa.school_id = ?
    ORDER BY aa.id DESC
");
$stmt_active->execute([$schoolId]);
$assignments = $stmt_active->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Issue & Return Assets | Admin Portal";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Asset Assignments</h5>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueModal"><i class="fa fa-share-square me-1"></i> Issue Asset</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0">Assignment Logs</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item</th>
                        <th>Assigned To</th>
                        <th>Issue Date</th>
                        <th>Expected Return</th>
                        <th>Status</th>
                        <th class="pe-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($assignments as $a): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($a['item_name']) ?></div>
                                <code><?= htmlspecialchars($a['item_code']) ?></code>
                            </td>
                            <td>
                                <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($a['assigned_to_type']) ?></span>
                                <div class="small fw-bold mt-1">ID: <?= htmlspecialchars($a['assigned_to_id']) ?></div>
                            </td>
                            <td><?= date('d M Y', strtotime($a['assigned_date'])) ?></td>
                            <td>
                                <?php if($a['expected_return_date']): ?>
                                    <?php 
                                        $exp = strtotime($a['expected_return_date']);
                                        $now = time();
                                        $is_late = ($a['status'] === 'issued' && $now > $exp);
                                    ?>
                                    <span class="<?= $is_late ? 'text-danger fw-bold' : '' ?>"><?= date('d M Y', $exp) ?></span>
                                    <?php if($is_late): ?>
                                        <i class="fa fa-exclamation-triangle text-danger" title="Overdue"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Permanent</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($a['status'] == 'issued'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa fa-hand-holding"></i> Issued</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fa fa-check"></i> Returned (<?= date('d M y', strtotime($a['actual_return_date'])) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if($a['status'] == 'issued'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                        <button type="submit" name="return_asset" class="btn btn-sm btn-success rounded-pill px-3" onclick="return confirm('Confirm asset has been returned in good condition?');">
                                            Return
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>Closed</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($assignments)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">No asset assignments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Issue Modal -->
<div class="modal fade" id="issueModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Issue New Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
            <label class="form-label fw-bold">Select Available Asset</label>
            <select name="inventory_item_id" class="form-select" required>
                <option value="">Choose...</option>
                <?php foreach($available_items as $ai): ?>
                    <option value="<?= $ai['id'] ?>"><?= htmlspecialchars($ai['item_name']) ?> (<?= htmlspecialchars($ai['item_code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3 row">
            <div class="col-6">
                <label class="form-label fw-bold">Assign To (Type)</label>
                <select name="assigned_to_type" class="form-select" required>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="room">Classroom/Lab</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label fw-bold">Assignee ID</label>
                <input type="number" name="assigned_to_id" class="form-control" required placeholder="User/Room ID">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Expected Return Date (Optional)</label>
            <input type="date" name="expected_return_date" class="form-control" min="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="issue_asset" class="btn btn-primary px-4">Issue Asset</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php require_once('../includes/footer.php'); ?>
