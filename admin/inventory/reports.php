<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
SUMMARY DATA
========================== */
$totalCategories = $pdo->query("
    SELECT COUNT(*) total FROM inventory_categories
")->fetch()['total'];

$totalProducts = $pdo->query("
    SELECT COUNT(*) total FROM inventory_products
")->fetch()['total'];

$totalSuppliers = $pdo->query("
    SELECT COUNT(*) total FROM inventory_suppliers
")->fetch()['total'];

$stockValue = $pdo->query("
    SELECT IFNULL(SUM(current_stock * purchase_price), 0) total FROM inventory_products
")->fetch()['total'];

$totalStockIn = $pdo->query("
    SELECT IFNULL(SUM(quantity), 0) total FROM inventory_stock_in
")->fetch()['total'];

$totalStockOut = $pdo->query("
    SELECT IFNULL(SUM(quantity), 0) total FROM inventory_stock_out
")->fetch()['total'];

/* ==========================
LOW STOCK REPORT
========================== */
$lowStockProducts = $pdo->query("
    SELECT p.*, c.category_name
    FROM inventory_products p
    LEFT JOIN inventory_categories c ON p.category_id = c.id
    WHERE p.current_stock <= p.min_stock
    ORDER BY p.current_stock ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
RECENT STOCK IN (20)
========================== */
$stockInReport = $pdo->query("
    SELECT si.*, p.product_name, p.sku, s.supplier_name
    FROM inventory_stock_in si
    LEFT JOIN inventory_products p ON si.product_id = p.id
    LEFT JOIN inventory_suppliers s ON si.supplier_id = s.id
    ORDER BY si.id DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
RECENT STOCK OUT (20)
========================== */
$stockOutReport = $pdo->query("
    SELECT so.*, p.product_name, p.sku
    FROM inventory_stock_out so
    LEFT JOIN inventory_products p ON so.product_id = p.id
    ORDER BY so.id DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
PURCHASE ORDERS (20)
========================== */
$purchaseOrders = $pdo->query("
    SELECT po.*, s.supplier_name, p.product_name, p.sku
    FROM inventory_purchase_orders po
    LEFT JOIN inventory_suppliers s ON po.supplier_id = s.id
    LEFT JOIN inventory_products p ON po.product_id = p.id
    ORDER BY po.id DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Inventory Reports & Analytics | VIC ERP";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Overview of current inventory valuations, alerts, and log analytics</h5>
    <div class="d-flex gap-2">
        <a href="categories.php" class="btn btn-outline-primary">
            <i class="fa fa-tags me-1"></i> Categories
        </a>
        <a href="products.php" class="btn btn-outline-primary">
            <i class="fa fa-boxes me-1"></i> Products
        </a>
        <a href="suppliers.php" class="btn btn-outline-primary">
            <i class="fa fa-truck me-1"></i> Suppliers
        </a>
        <a href="stock-in.php" class="btn btn-outline-success">
            <i class="fa fa-plus-circle me-1"></i> Stock In
        </a>
        <a href="stock-out.php" class="btn btn-outline-danger">
            <i class="fa fa-minus-circle me-1"></i> Stock Out
        </a>
        <a href="purchase-orders.php" class="btn btn-outline-info">
            <i class="fa fa-file-signature me-1"></i> Purchase Orders
        </a>
        <a href="reports.php" class="btn btn-secondary">
            <i class="fa fa-chart-line me-1"></i> Reports
        </a>
    </div>
</div>

<!-- STATS SUMMARY CARDS -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--primary-color) !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary-subtle text-primary p-3 rounded-3 me-3">
                        <i class="fa fa-boxes fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Total Items</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalProducts ?></h4>
                        <span class="text-muted small text-start d-block"><?= (int)$totalCategories ?> Categories</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success-subtle text-success p-3 rounded-3 me-3">
                        <i class="fa fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Stock Valuation</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start">₹ <?= number_format($stockValue, 2) ?></h4>
                        <span class="text-muted small text-start d-block">Asset net worth</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #0dcaf0 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-info-subtle text-info p-3 rounded-3 me-3">
                        <i class="fa fa-truck fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Suppliers</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalSuppliers ?></h4>
                        <span class="text-muted small text-start d-block">Active vendor base</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #f1a80a !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning-subtle text-warning p-3 rounded-3 me-3">
                        <i class="fa fa-exchange-alt fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Transactions</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalStockIn + (int)$totalStockOut ?></h4>
                        <span class="text-muted small text-start d-block">In: <?= (int)$totalStockIn ?> | Out: <?= (int)$totalStockOut ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- LOW STOCK ALERT PANEL -->
    <div class="col-12 mb-4">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-danger text-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="fa fa-exclamation-triangle me-2"></i>Low Stock Inventory Alert</h5>
                <span class="badge bg-white text-danger px-3 py-2 fw-bold"><?= count($lowStockProducts) ?> Items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">SKU</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Min. Required Stock</th>
                                <th class="pe-4">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($lowStockProducts) > 0): ?>
                                <?php foreach($lowStockProducts as $product): ?>
                                    <tr>
                                        <td class="ps-4 fw-mono small text-muted"><?= htmlspecialchars($product['sku'] ?: '-') ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($product['category_name']) ?></span></td>
                                        <td><?= htmlspecialchars($product['unit']) ?></td>
                                        <td class="text-muted"><?= (int)$product['min_stock'] ?></td>
                                        <td class="pe-4">
                                            <span class="badge bg-danger text-white px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                <?= (int)$product['current_stock'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-success fw-bold">
                                        <i class="fa fa-check-circle fs-3 mb-2 d-block text-success"></i>
                                        All inventory stocks are above minimum thresholds!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- RECENT STOCK IN -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-plus-circle me-2 text-success"></i>Recent Stock Receipts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Product Details</th>
                                <th>Quantity</th>
                                <th class="pe-4">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($stockInReport) > 0): ?>
                                <?php foreach($stockInReport as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y', strtotime($row['entry_date'])) ?></td>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['product_name']) ?></span>
                                            <span class="text-muted small">SKU: <?= htmlspecialchars($row['sku'] ?: '-') ?></span>
                                        </td>
                                        <td class="fw-bold text-success">+<?= (int)$row['quantity'] ?></td>
                                        <td class="pe-4 small"><span class="badge bg-light text-secondary border"><?= htmlspecialchars($row['invoice_no'] ?: '-') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        No recent stock arrivals.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT STOCK OUT -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-minus-circle me-2 text-danger"></i>Recent Stock Issuances</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Product Details</th>
                                <th>Issued To</th>
                                <th class="pe-4">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($stockOutReport) > 0): ?>
                                <?php foreach($stockOutReport as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y', strtotime($row['issue_date'])) ?></td>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($row['product_name']) ?></span>
                                            <span class="text-muted small">SKU: <?= htmlspecialchars($row['sku'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($row['issue_to']) ?></span>
                                            <span class="badge bg-light text-secondary small border"><?= htmlspecialchars($row['issue_type']) ?></span>
                                        </td>
                                        <td class="pe-4 fw-bold text-danger">-<?= (int)$row['quantity'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        No recent items issued.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- PURCHASE ORDER LOGS -->
    <div class="col-12 mb-4">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-file-signature me-2 text-info"></i>Procurement Orders Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">PO Number</th>
                                <th>Supplier / Vendor</th>
                                <th>Product Ordered</th>
                                <th>Quantity</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th class="pe-4">Order Date</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($purchaseOrders) > 0): ?>
                                <?php foreach($purchaseOrders as $po): ?>
                                    <?php 
                                    $statusClass = match($po['status']){
                                        'Pending' => 'bg-warning-subtle text-warning border-warning-subtle',
                                        'Approved' => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'Received' => 'bg-success-subtle text-success border-success-subtle',
                                        'Cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-mono text-dark fw-bold small"><?= htmlspecialchars($po['po_number']) ?></td>
                                        <td><span class="small text-muted"><?= htmlspecialchars($po['supplier_name']) ?></span></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($po['product_name']) ?></td>
                                        <td class="text-dark"><?= (int)$po['quantity'] ?></td>
                                        <td class="fw-bold text-primary">₹ <?= number_format($po['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge border px-3 py-2 <?= $statusClass ?>"><?= $po['status'] ?></span>
                                        </td>
                                        <td class="pe-4 small text-muted"><?= date('d M Y', strtotime($po['order_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No purchase orders logged.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
