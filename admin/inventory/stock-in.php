<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
SAVE STOCK IN
========================== */
if(isset($_POST['stock_in'])){
    $supplier_id   = (int)$_POST['supplier_id'];
    $product_id    = (int)$_POST['product_id'];
    $quantity      = (int)$_POST['quantity'];
    $purchase_rate = (float)$_POST['purchase_rate'];
    $invoice_no    = trim($_POST['invoice_no']);
    $remarks       = trim($_POST['remarks']);

    if ($quantity <= 0 || $purchase_rate < 0) {
        $error = "Quantity must be greater than 0 and purchase rate cannot be negative.";
    } else {
        try {
            $pdo->beginTransaction();

            $total_amount = $quantity * $purchase_rate;

            $stmt = $pdo->prepare("
                INSERT INTO inventory_stock_in(
                    supplier_id, product_id, quantity, purchase_rate, 
                    total_amount, invoice_no, entry_date, remarks
                )
                VALUES(?, ?, ?, ?, ?, ?, CURDATE(), ?)
            ");
            $stmt->execute([
                $supplier_id,
                $product_id,
                $quantity,
                $purchase_rate,
                $total_amount,
                $invoice_no,
                $remarks
            ]);

            /* UPDATE PRODUCT STOCK */
            $update = $pdo->prepare("
                UPDATE inventory_products
                SET current_stock = current_stock + ?
                WHERE id = ?
            ");
            $update->execute([
                $quantity,
                $product_id
            ]);

            $pdo->commit();
            $message = "Stock Added and Product Inventory Updated Successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction Failed: " . $e->getMessage();
        }
    }
}

/* ==========================
LOAD SUPPLIERS
========================== */
$suppliers = $pdo->query("
    SELECT *
    FROM inventory_suppliers
    WHERE status='Active'
    ORDER BY supplier_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
LOAD PRODUCTS
========================== */
$products = $pdo->query("
    SELECT *
    FROM inventory_products
    WHERE status='Active'
    ORDER BY product_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
STOCK HISTORY
========================== */
$history = $pdo->query("
    SELECT si.*, p.product_name, p.sku, s.supplier_name
    FROM inventory_stock_in si
    LEFT JOIN inventory_products p ON si.product_id = p.id
    LEFT JOIN inventory_suppliers s ON si.supplier_id = s.id
    ORDER BY si.id DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Stock In Management | VIC ERP";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record incoming inventory stocks and purchase arrivals</h5>
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
        <a href="stock-in.php" class="btn btn-success">
            <i class="fa fa-plus-circle me-1"></i> Stock In
        </a>
        <a href="stock-out.php" class="btn btn-outline-danger">
            <i class="fa fa-minus-circle me-1"></i> Stock Out
        </a>
        <a href="purchase-orders.php" class="btn btn-outline-info">
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
    <!-- STOCK IN ENTRY FORM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Log Stock Receipt</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Supplier / Vendor <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?> (<?= htmlspecialchars($supplier['company_name'] ?: 'No Company') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Product Item <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php foreach($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_name']) ?> (<?= htmlspecialchars($product['sku'] ?: 'No SKU') ?>) - Current: <?= (int)$product['current_stock'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" min="1" class="form-control" required placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rate (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purchase_rate" id="purchase_rate" min="0" class="form-control" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Total Amount (₹)</label>
                        <input type="text" id="total_amount" class="form-control bg-light fw-bold text-primary" value="0.00" readonly>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Invoice No</label>
                        <input type="text" name="invoice_no" class="form-control" placeholder="e.g. INV-10023">
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Received in good condition">
                    </div>

                    <button type="submit" name="stock_in" class="btn btn-success w-100">
                        <i class="fa fa-plus-circle me-1"></i> Add Stock
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- STOCK IN HISTORY -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Stock Receipt Ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Product Details</th>
                                <th>Supplier</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Total</th>
                                <th class="pe-4">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($history) > 0): ?>
                                <?php foreach($history as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y', strtotime($row['entry_date'])) ?></td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($row['product_name']) ?>
                                            <span class="d-block small text-muted fw-normal">SKU: <?= htmlspecialchars($row['sku'] ?: '-') ?></span>
                                        </td>
                                        <td><span class="small text-muted"><?= htmlspecialchars($row['supplier_name'] ?: 'Direct Store') ?></span></td>
                                        <td class="fw-semibold text-dark"><?= (int)$row['quantity'] ?></td>
                                        <td>₹ <?= number_format($row['purchase_rate'], 2) ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($row['total_amount'], 2) ?></td>
                                        <td class="pe-4"><span class="badge bg-light text-secondary border px-2 py-1"><?= htmlspecialchars($row['invoice_no'] ?: '-') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-receipt fs-2 mb-2 d-block"></i>
                                        No stock-in entries logged yet.
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
    const qtyInput = document.getElementById('quantity');
    const rateInput = document.getElementById('purchase_rate');
    const totalInput = document.getElementById('total_amount');

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
