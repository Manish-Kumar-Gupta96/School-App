<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
UPDATE STATUS ACTION
========================== */
if(isset($_GET['action']) && $_GET['action'] == 'update_status'){
    $po_id = (int)$_GET['id'];
    $new_status = $_GET['status'];

    if(in_array($new_status, ['Pending', 'Approved', 'Received', 'Cancelled'])){
        try {
            $pdo->beginTransaction();

            // Fetch current PO details
            $stmt = $pdo->prepare("SELECT * FROM inventory_purchase_orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$po_id]);
            $po = $stmt->fetch(PDO::FETCH_ASSOC);

            if($po){
                $old_status = $po['status'];

                // Handle stock increment if moving to 'Received'
                if($old_status != 'Received' && $new_status == 'Received'){
                    $updateStock = $pdo->prepare("
                        UPDATE inventory_products
                        SET current_stock = current_stock + ?
                        WHERE id = ?
                    ");
                    $updateStock->execute([$po['quantity'], $po['product_id']]);
                }
                // Handle stock decrement if moving away from 'Received' (e.g. correction)
                elseif($old_status == 'Received' && $new_status != 'Received'){
                    $updateStock = $pdo->prepare("
                        UPDATE inventory_products
                        SET current_stock = current_stock - ?
                        WHERE id = ?
                    ");
                    $updateStock->execute([$po['quantity'], $po['product_id']]);
                }

                // Update PO Status
                $updatePO = $pdo->prepare("
                    UPDATE inventory_purchase_orders
                    SET status = ?
                    WHERE id = ?
                ");
                $updatePO->execute([$new_status, $po_id]);

                $pdo->commit();
                $message = "PO Status Updated to '$new_status' successfully!";
            } else {
                $pdo->rollBack();
                $error = "Purchase Order not found.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to update PO status: " . $e->getMessage();
        }
    }
}

/* ==========================
CREATE PURCHASE ORDER
========================== */
if(isset($_POST['create_po'])){
    $supplier_id   = (int)$_POST['supplier_id'];
    $product_id    = (int)$_POST['product_id'];
    $quantity      = (int)$_POST['quantity'];
    $rate          = (float)$_POST['rate'];
    $expected_date = !empty($_POST['expected_date']) ? $_POST['expected_date'] : null;
    $remarks       = trim($_POST['remarks']);

    if($quantity <= 0 || $rate < 0){
        $error = "Quantity must be greater than 0 and rate cannot be negative.";
    } else {
        $total_amount = $quantity * $rate;
        $po_number = "PO-" . date('Y') . "-" . rand(10000, 99999);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO inventory_purchase_orders(
                    po_number, supplier_id, product_id, quantity, rate, 
                    total_amount, order_date, expected_date, status, remarks
                )
                VALUES(?, ?, ?, ?, ?, ?, CURDATE(), ?, 'Pending', ?)
            ");
            $stmt->execute([
                $po_number,
                $supplier_id,
                $product_id,
                $quantity,
                $rate,
                $total_amount,
                $expected_date,
                $remarks
            ]);
            $message = "Purchase Order '$po_number' Created Successfully!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

/* ==========================
LOAD ACTIVE SUPPLIERS
========================== */
$suppliers = $pdo->query("
    SELECT *
    FROM inventory_suppliers
    WHERE status='Active'
    ORDER BY supplier_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
LOAD ACTIVE PRODUCTS
========================== */
$products = $pdo->query("
    SELECT *
    FROM inventory_products
    WHERE status='Active'
    ORDER BY product_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
PO LIST
========================== */
$purchaseOrders = $pdo->query("
    SELECT po.*, s.supplier_name, p.product_name, p.sku
    FROM inventory_purchase_orders po
    LEFT JOIN inventory_suppliers s ON po.supplier_id = s.id
    LEFT JOIN inventory_products p ON po.product_id = p.id
    ORDER BY po.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Purchase Orders | VIC ERP";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Procurement purchase order logs and order tracking sheets</h5>
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
        <a href="purchase-orders.php" class="btn btn-info text-white">
            <i class="fa fa-file-signature me-1"></i> Purchase Orders
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-line me-1"></i> Reports
        </a>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- CREATE PO FORM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Create Purchase Order</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Product Item <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php foreach($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="po_qty" min="1" class="form-control" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Est. Rate (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rate" id="po_rate" min="0" class="form-control" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Total Cost (₹)</label>
                        <input type="text" id="po_total" class="form-control bg-light fw-bold text-info" value="0.00" readonly>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Expected Delivery Date</label>
                        <input type="date" name="expected_date" class="form-control">
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Urgent requirement for science exams">
                    </div>

                    <button type="submit" name="create_po" class="btn btn-primary w-100">
                        <i class="fa fa-file-invoice me-1"></i> Create PO
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PO LIST -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Purchase Order Ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">PO Number</th>
                                <th>Supplier</th>
                                <th>Product Details</th>
                                <th>Qty</th>
                                <th>Total Cost</th>
                                <th>Expected Date</th>
                                <th>Status</th>
                                <th class="pe-4 text-center">Actions</th>
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
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($po['product_name']) ?>
                                            <span class="d-block small text-muted fw-normal">SKU: <?= htmlspecialchars($po['sku'] ?: '-') ?></span>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= (int)$po['quantity'] ?></td>
                                        <td class="fw-bold text-primary">₹ <?= number_format($po['total_amount'], 2) ?></td>
                                        <td class="small text-muted"><?= $po['expected_date'] ? date('d M Y', strtotime($po['expected_date'])) : '-' ?></td>
                                        <td>
                                            <span class="badge border px-3 py-2 <?= $statusClass ?>"><?= $po['status'] ?></span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Action
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <?php if($po['status'] == 'Pending'): ?>
                                                        <li><a class="dropdown-item text-primary small" href="?action=update_status&id=<?= $po['id'] ?>&status=Approved"><i class="fa fa-check me-2 text-primary"></i>Approve PO</a></li>
                                                        <li><a class="dropdown-item text-danger small" href="?action=update_status&id=<?= $po['id'] ?>&status=Cancelled"><i class="fa fa-times me-2 text-danger"></i>Cancel PO</a></li>
                                                    <?php elseif($po['status'] == 'Approved'): ?>
                                                        <li><a class="dropdown-item text-success small" href="?action=update_status&id=<?= $po['id'] ?>&status=Received"><i class="fa fa-check-double me-2 text-success"></i>Mark Received (Add Stock)</a></li>
                                                        <li><a class="dropdown-item text-danger small" href="?action=update_status&id=<?= $po['id'] ?>&status=Cancelled"><i class="fa fa-times me-2 text-danger"></i>Cancel PO</a></li>
                                                    <?php elseif($po['status'] == 'Received'): ?>
                                                        <li><span class="dropdown-item text-muted small"><i class="fa fa-lock me-2 text-muted"></i>Order Received</span></li>
                                                    <?php elseif($po['status'] == 'Cancelled'): ?>
                                                        <li><span class="dropdown-item text-muted small"><i class="fa fa-lock me-2 text-muted"></i>Order Cancelled</span></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa fa-file-invoice fs-2 mb-2 d-block"></i>
                                        No purchase orders issued yet.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('po_qty');
    const rateInput = document.getElementById('po_rate');
    const totalInput = document.getElementById('po_total');

    function calculateTotal() {
        const qty = parseInt(qtyInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        const total = qty * rate;
        totalInput.value = total.toFixed(2);
    }

    qtyInput.addEventListener('input', calculateTotal);
    rateInput.addEventListener('input', calculateTotal);
});
</script>

<?php
require_once('../includes/footer.php');
?>
