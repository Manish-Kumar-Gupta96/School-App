<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Complete Maintenance
if (isset($_POST['complete_maintenance'])) {
    $maint_id = (int)$_POST['maintenance_id'];
    $item_id = (int)$_POST['inventory_item_id'];
    $cost = (float)$_POST['cost'];
    $desc = trim($_POST['description']);
    
    try {
        $pdo->beginTransaction();
        
        // Update maintenance request status
        $stmt_upd = $pdo->prepare("UPDATE maintenance_requests SET status = 'completed' WHERE id = ?");
        $stmt_upd->execute([$maint_id]);
        
        // Record Cost
        $stmt_cost = $pdo->prepare("INSERT INTO maintenance_costs (maintenance_id, amount, description) VALUES (?, ?, ?)");
        $stmt_cost->execute([$maint_id, $cost, $desc]);
        
        // Check if it's currently assigned, if not make it available
        $stmt_chk_ass = $pdo->prepare("SELECT id FROM asset_assignments WHERE inventory_item_id = ? AND status = 'issued'");
        $stmt_chk_ass->execute([$item_id]);
        $is_issued = $stmt_chk_ass->fetch();
        
        $new_status = $is_issued ? 'issued' : 'available';
        
        $stmt_item = $pdo->prepare("UPDATE inventory_items SET status = ? WHERE id = ?");
        $stmt_item->execute([$new_status, $item_id]);
        
        // Log history
        $stmt_hist = $pdo->prepare("INSERT INTO asset_history (school_id, inventory_item_id, action_type, description, action_date) VALUES (?, ?, 'Maintenance Completed', ?, NOW())");
        $stmt_hist->execute([$schoolId, $item_id, "Cost: ₹{$cost}. " . $desc]);
        
        $pdo->commit();
        $message = "Maintenance completed successfully. Cost recorded.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error updating maintenance: " . $e->getMessage();
    }
}

// Fetch Maintenance Requests
$stmt_req = $pdo->prepare("
    SELECT mr.*, i.item_name, i.item_code 
    FROM maintenance_requests mr
    JOIN inventory_items i ON mr.inventory_item_id = i.id
    WHERE mr.school_id = ?
    ORDER BY mr.reported_at DESC
");
$stmt_req->execute([$schoolId]);
$requests = $stmt_req->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Maintenance Management | Admin Portal";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Maintenance & Repairs</h5>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
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
        <h5 class="fw-bold mb-0">Active Maintenance Requests</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item</th>
                        <th>Reported By</th>
                        <th>Issue Description</th>
                        <th>Date Reported</th>
                        <th>Status</th>
                        <th class="pe-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['item_name']) ?></div>
                                <code><?= htmlspecialchars($r['item_code']) ?></code>
                            </td>
                            <td>
                                <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($r['reported_by_type']) ?></span>
                                <div class="small fw-bold mt-1">ID: <?= htmlspecialchars($r['reported_by']) ?></div>
                            </td>
                            <td>
                                <p class="m-0 small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($r['issue_description']) ?>">
                                    <?= htmlspecialchars($r['issue_description']) ?>
                                </p>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($r['reported_at'])) ?></td>
                            <td>
                                <?php if($r['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa fa-clock"></i> Pending</span>
                                <?php elseif($r['status'] == 'in_progress'): ?>
                                    <span class="badge bg-info"><i class="fa fa-spinner"></i> In Progress</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fa fa-check"></i> Completed</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if($r['status'] != 'completed'): ?>
                                    <button class="btn btn-sm btn-success rounded-pill px-3" onclick="openCompleteModal(<?= $r['id'] ?>, <?= $r['inventory_item_id'] ?>, '<?= htmlspecialchars(addslashes($r['item_name'])) ?>')">
                                        <i class="fa fa-wrench"></i> Complete
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>Closed</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($requests)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">No maintenance requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Log Repair Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" name="maintenance_id" id="maint_id_input">
        <input type="hidden" name="inventory_item_id" id="maint_item_id_input">
        
        <div class="mb-3">
            <label class="form-label fw-bold">Item</label>
            <input type="text" id="maint_item_name" class="form-control" readonly disabled>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Repair Cost (₹)</label>
            <input type="number" step="0.01" name="cost" class="form-control" value="0.00" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Repair Notes / Parts Replaced</label>
            <textarea name="description" class="form-control" rows="3" required></textarea>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="complete_maintenance" class="btn btn-primary px-4">Mark Completed</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCompleteModal(id, itemId, name) {
    document.getElementById('maint_id_input').value = id;
    document.getElementById('maint_item_id_input').value = itemId;
    document.getElementById('maint_item_name').value = name;
    new bootstrap.Modal(document.getElementById('completeModal')).show();
}
</script>

<?php require_once('../includes/footer.php'); ?>
