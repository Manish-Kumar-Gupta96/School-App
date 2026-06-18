<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
STOCK OUT
========================== */
if(isset($_POST['stock_out'])){
    $product_id = (int)$_POST['product_id'];
    $issue_to   = trim($_POST['issue_to']);
    $issue_type = $_POST['issue_type'];
    $quantity   = (int)$_POST['quantity'];
    $remarks    = trim($_POST['remarks']);

    if ($quantity <= 0) {
        $error = "Quantity must be greater than 0.";
    } else {
        try {
            $pdo->beginTransaction();

            /* CHECK STOCK with Row Level Locking */
            $stmt = $pdo->prepare("
                SELECT current_stock, product_name 
                FROM inventory_products 
                WHERE id=? FOR UPDATE
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$product){
                $error = "Product Not Found";
                $pdo->rollBack();
            } elseif($product['current_stock'] < $quantity){
                $error = "Insufficient Stock. Only " . (int)$product['current_stock'] . " units of '" . htmlspecialchars($product['product_name']) . "' available.";
                $pdo->rollBack();
            } else {
                $insert = $pdo->prepare("
                    INSERT INTO inventory_stock_out(
                        product_id, issue_to, issue_type, quantity, issue_date, remarks
                    )
                    VALUES(?, ?, ?, ?, CURDATE(), ?)
                ");
                $insert->execute([
                    $product_id,
                    $issue_to,
                    $issue_type,
                    $quantity,
                    $remarks
                ]);

                /* DEDUCT STOCK */
                $update = $pdo->prepare("
                    UPDATE inventory_products
                    SET current_stock = current_stock - ?
                    WHERE id=?
                ");
                $update->execute([
                    $quantity,
                    $product_id
                ]);

                $pdo->commit();
                $message = "Stock Issued and Inventory Updated Successfully!";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Transaction Failed: " . $e->getMessage();
        }
    }
}

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
ISSUE HISTORY
========================== */
$history = $pdo->query("
    SELECT so.*, p.product_name, p.sku
    FROM inventory_stock_out so
    LEFT JOIN inventory_products p ON so.product_id=p.id
    ORDER BY so.id DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Stock Out Management | VIC ERP";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record outgoing stocks issued to students, staff, or departments</h5>
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
        <a href="stock-out.php" class="btn btn-danger">
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
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-info-circle me-2"></i> <?= htmlspecialchars($message) ?>
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
    <!-- ISSUE STOCK FORM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Issue / Allocate Item</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Select Product Item <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php foreach($products as $product): ?>
                                <option value="<?= $product['id'] ?>" <?= ($product['current_stock'] <= 0) ? 'disabled style="color: #ccc;"' : '' ?>>
                                    <?= htmlspecialchars($product['product_name']) ?> 
                                    (Stock: <?= (int)$product['current_stock'] ?>) 
                                    <?= ($product['current_stock'] <= 0) ? '[OUT OF STOCK]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Issue Recipient Type <span class="text-danger">*</span></label>
                        <select name="issue_type" class="form-select" required>
                            <option value="Student">Student</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Department">Department</option>
                            <option value="Class">Class</option>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Issued To (Name / Room / Section) <span class="text-danger">*</span></label>
                        <input type="text" name="issue_to" class="form-control" placeholder="e.g. Rahul Verma / Class X-B" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Quantity to Issue <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" min="1" class="form-control" placeholder="0" required>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Remarks / Purpose</label>
                        <textarea name="remarks" rows="2" class="form-control" placeholder="e.g. Sports Day practice, Lab exams..."></textarea>
                    </div>

                    <button type="submit" name="stock_out" class="btn btn-danger w-100">
                        <i class="fa fa-minus-circle me-1"></i> Issue Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ISSUE HISTORY -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Stock Distribution History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Product Details</th>
                                <th>Issued To Type</th>
                                <th>Recipient</th>
                                <th>Qty Issued</th>
                                <th class="pe-4">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($history) > 0): ?>
                                <?php foreach($history as $row): ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y', strtotime($row['issue_date'])) ?></td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($row['product_name']) ?>
                                            <span class="d-block small text-muted fw-normal">SKU: <?= htmlspecialchars($row['sku'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= htmlspecialchars($row['issue_type']) ?></span>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['issue_to']) ?></td>
                                        <td class="fw-bold text-danger"><?= (int)$row['quantity'] ?></td>
                                        <td class="pe-4"><span class="small text-muted"><?= htmlspecialchars($row['remarks'] ?: '-') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-hand-holding fs-2 mb-2 d-block"></i>
                                        No stock allocation records found.
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
