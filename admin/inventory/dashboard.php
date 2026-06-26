<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Assuming admin role is required
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Fetch metrics
$stmt_metrics = $pdo->prepare("
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_items,
        SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) as issued_items,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_items,
        SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_items
    FROM inventory_items
    WHERE school_id = ?
");
$stmt_metrics->execute([$schoolId]);
$metrics = $stmt_metrics->fetch(PDO::FETCH_ASSOC);

// Fetch Low Stock Alerts
$stmt_low_stock = $pdo->prepare("
    SELECT i.item_name, i.item_code, i.quantity, sa.minimum_stock
    FROM inventory_items i
    JOIN stock_alerts sa ON i.id = sa.inventory_item_id
    WHERE i.school_id = ? AND i.quantity <= sa.minimum_stock
    ORDER BY (i.quantity - sa.minimum_stock) ASC
    LIMIT 5
");
$stmt_low_stock->execute([$schoolId]);
$low_stock = $stmt_low_stock->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Inventory Dashboard | Admin Portal";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Inventory Dashboard</h5>
    <div class="d-flex gap-2">
        <a href="items.php" class="btn btn-primary"><i class="fa fa-boxes me-1"></i> Manage Items</a>
        <a href="purchase.php" class="btn btn-outline-info"><i class="fa fa-shopping-cart me-1"></i> Purchase Orders</a>
        <a href="assign.php" class="btn btn-outline-secondary"><i class="fa fa-exchange-alt me-1"></i> Issue Asset</a>
        <a href="maintenance.php" class="btn btn-outline-danger"><i class="fa fa-tools me-1"></i> Maintenance</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Total Items</h6>
                <h2 class="fw-bold mb-0"><?= number_format($metrics['total_items'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Available Stock</h6>
                <h2 class="fw-bold mb-0"><?= number_format($metrics['available_items'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Issued Assets</h6>
                <h2 class="fw-bold mb-0"><?= number_format($metrics['issued_items'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">In Maintenance</h6>
                <h2 class="fw-bold mb-0"><?= number_format($metrics['maintenance_items'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4 text-danger d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fa fa-exclamation-triangle me-2"></i> Low Stock Alerts</h6>
                <span class="badge bg-danger rounded-pill"><?= count($low_stock) ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Item Name</th>
                            <th>Item Code</th>
                            <th>Current Stock</th>
                            <th class="pe-4">Min. Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($low_stock) > 0): ?>
                            <?php foreach($low_stock as $ls): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($ls['item_name']) ?></td>
                                    <td><code><?= htmlspecialchars($ls['item_code']) ?></code></td>
                                    <td><span class="badge bg-danger"><?= $ls['quantity'] ?></span></td>
                                    <td class="pe-4"><span class="badge bg-secondary"><?= $ls['minimum_stock'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted p-4">Stock levels are healthy.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
