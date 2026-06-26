<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'Student') {
    die("Access Denied.");
}

$student_id = $_SESSION['student_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Fetch assigned assets
$stmt_assets = $pdo->prepare("
    SELECT aa.*, i.item_name, i.item_code, i.status as item_status
    FROM asset_assignments aa
    JOIN inventory_items i ON aa.inventory_item_id = i.id
    WHERE aa.assigned_to_type = 'student' AND aa.assigned_to_id = ? AND aa.school_id = ? AND aa.status = 'issued'
");
$stmt_assets->execute([$student_id, $schoolId]);
$assets = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "My Assigned Assets | Student Portal";
$page_header = "My Inventory";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">My Assigned Assets</h5>
</div>

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
                        <th class="pe-4">Status</th>
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
                                    <?php 
                                        $exp = strtotime($a['expected_return_date']);
                                        $now = time();
                                        $is_late = ($now > $exp);
                                    ?>
                                    <span class="<?= $is_late ? 'text-danger fw-bold' : '' ?>"><?= date('d M Y', $exp) ?></span>
                                    <?php if($is_late): ?>
                                        <i class="fa fa-exclamation-triangle text-danger" title="Overdue"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Permanent</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4">
                                <?php if($a['item_status'] == 'maintenance'): ?>
                                    <span class="badge bg-danger"><i class="fa fa-tools"></i> Under Repair</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="fa fa-check-circle"></i> Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($assets)): ?>
                        <tr><td colspan="5" class="text-center p-5 text-muted">You do not have any assets assigned to you.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
