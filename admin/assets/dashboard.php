<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Fetch Stats
$totalAssets = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$assignedAssets = $pdo->query("SELECT COUNT(DISTINCT asset_id) FROM asset_assignments")->fetchColumn();
$maintenanceDue = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'maintenance'")->fetchColumn();
$disposedAssets = $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'disposed'")->fetchColumn();

// Fetch Recent Assets
$recent_assets = $pdo->query("
    SELECT * FROM assets 
    ORDER BY id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Asset Dashboard | VIC School ERP";
$page_header = "Asset Management";
$active_menu = "assets";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <h2 class="fw-bold text-dark mb-4 text-start"><i class="fa fa-laptop-code text-primary me-2"></i>Asset Dashboard</h2>

    <!-- STATS CARDS -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Total Assets</h5>
                    <i class="fa fa-laptop text-primary fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalAssets ?></h3>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Assigned</h5>
                    <i class="fa fa-user-check text-success fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-success"><?= (int)$assignedAssets ?></h3>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">In Maintenance</h5>
                    <i class="fa fa-tools text-warning fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-warning"><?= (int)$maintenanceDue ?></h3>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Disposed</h5>
                    <i class="fa fa-trash-alt text-danger fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-danger"><?= (int)$disposedAssets ?></h3>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 15px;">
        <h5 class="fw-bold text-dark text-start mb-3">Quick Actions</h5>
        <div class="d-flex gap-3 flex-wrap">
            <a href="manage.php" class="btn btn-primary"><i class="fa fa-plus-circle me-2"></i> Register New Asset</a>
            <a href="assignments.php" class="btn btn-success"><i class="fa fa-exchange-alt me-2"></i> Assign Asset</a>
            <a href="maintenance.php" class="btn btn-warning text-dark"><i class="fa fa-wrench me-2"></i> Maintenance Logs</a>
        </div>
    </div>

    <!-- Recent Assets -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4 text-start">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-primary"></i>Recently Added Assets</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Asset Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_assets) > 0): ?>
                            <?php foreach($recent_assets as $a): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($a['asset_code']) ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($a['asset_name']) ?></div>
                                        <div class="text-muted small">SN: <?= htmlspecialchars($a['serial_no']) ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($a['category']) ?></span></td>
                                    <td><?= date('d M Y', strtotime($a['purchase_date'])) ?></td>
                                    <td>
                                        <?php if($a['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif($a['status'] == 'maintenance'): ?>
                                            <span class="badge bg-warning text-dark">Maintenance</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disposed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No assets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
