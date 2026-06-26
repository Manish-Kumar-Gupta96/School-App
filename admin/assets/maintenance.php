<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'log_maintenance') {
        $asset_id = (int)$_POST['asset_id'];
        $vendor_name = trim($_POST['vendor_name']);
        $cost = (float)$_POST['cost'];
        $maintenance_date = $_POST['maintenance_date'] ?: date('Y-m-d');
        $remarks = trim($_POST['remarks']);
        
        if ($asset_id > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO asset_maintenance (asset_id, maintenance_date, vendor_name, cost, remarks) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$asset_id, $maintenance_date, $vendor_name, $cost, $remarks]);
                
                // Automatically set asset status to active if maintenance is logged, or keep it as maintenance based on a toggle? Let's assume logging completes it.
                $pdo->prepare("UPDATE assets SET status = 'active' WHERE id = ?")->execute([$asset_id]);
                
                $pdo->commit();
                $_SESSION['success'] = "Maintenance logged successfully and asset marked active.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error logging maintenance: " . $e->getMessage();
            }
        }
        header("Location: maintenance.php");
        exit;
    }
}

// Fetch assets currently in maintenance
$maintenance_assets = $pdo->query("
    SELECT * FROM assets 
    WHERE status = 'maintenance'
    ORDER BY asset_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch maintenance history
$history = $pdo->query("
    SELECT am.*, a.asset_name, a.asset_code
    FROM asset_maintenance am
    JOIN assets a ON am.asset_id = a.id
    ORDER BY am.maintenance_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Asset Maintenance | VIC School ERP";
$page_header = "Maintenance Logs";
$active_menu = "assets";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Log Maintenance Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-warning text-dark"><i class="fa fa-tools me-2"></i>Log Maintenance Ticket</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="log_maintenance">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">Select Asset (In Maintenance)</option>
                                <?php foreach($maintenance_assets as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['asset_code'] . ' - ' . $a['asset_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(count($maintenance_assets) == 0): ?>
                                <small class="text-muted d-block mt-1">No assets currently flagged for maintenance. Change an asset's status from the <a href="manage.php">Manage</a> page.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Vendor/Technician Name</label>
                            <input type="text" name="vendor_name" class="form-control" placeholder="e.g. Dell Support">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Cost (₹)</label>
                                <input type="number" step="0.01" name="cost" class="form-control" value="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Completion Date</label>
                                <input type="date" name="maintenance_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks / Issue Fixed</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Replaced screen, cleaned fans..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning text-dark w-100 fw-bold" <?= count($maintenance_assets) == 0 ? 'disabled' : '' ?>><i class="fa fa-check-circle me-2"></i> Complete Maintenance</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- History List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-history me-2 text-info"></i>Maintenance History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Asset Details</th>
                                    <th>Vendor</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($history) > 0): ?>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($h['asset_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($h['asset_code']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($h['vendor_name'] ?: 'Internal') ?></td>
                                            <td><?= date('d M Y', strtotime($h['maintenance_date'])) ?></td>
                                            <td class="fw-bold text-danger">₹<?= number_format($h['cost'], 2) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($h['remarks'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No maintenance history found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
