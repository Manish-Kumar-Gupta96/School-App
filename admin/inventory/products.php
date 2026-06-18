<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
ADD PRODUCT
========================== */
if(isset($_POST['save_product'])){
    $category_id    = (int)$_POST['category_id'];
    $product_name   = trim($_POST['product_name']);
    $sku            = trim($_POST['sku']);
    $unit           = trim($_POST['unit']);
    $purchase_price = !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : 0.00;
    $selling_price  = !empty($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0.00;
    $min_stock      = (int)$_POST['min_stock'];
    $description    = trim($_POST['description']);
    $status         = $_POST['status'];

    if(empty($category_id) || empty($product_name)){
        $error = "Category and Product Name are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO inventory_products(
                    category_id, product_name, sku, unit, purchase_price, 
                    selling_price, min_stock, current_stock, description, status
                )
                VALUES(?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ");
            $stmt->execute([
                $category_id,
                $product_name,
                $sku,
                $unit,
                $purchase_price,
                $selling_price,
                $min_stock,
                $description,
                $status
            ]);
            $message = "Product Added Successfully!";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = "SKU / Product Code must be unique. This SKU is already in use.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

/* ==========================
LOAD ACTIVE CATEGORIES
========================== */
$categories = $pdo->query("
    SELECT *
    FROM inventory_categories
    WHERE status='Active'
    ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
LOAD PRODUCTS
========================== */
$products = $pdo->query("
    SELECT p.*, c.category_name
    FROM inventory_products p
    LEFT JOIN inventory_categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Inventory Products | VIC ERP";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage product catalog and check stock alerts</h5>
    <div class="d-flex gap-2">
        <a href="categories.php" class="btn btn-outline-primary">
            <i class="fa fa-tags me-1"></i> Categories
        </a>
        <a href="products.php" class="btn btn-primary">
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
    <!-- ADD PRODUCT -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Add New Product</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="product_name" class="form-control" placeholder="e.g. Science Textbook Class 10" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">SKU / Product Code</label>
                        <input type="text" name="sku" class="form-control" placeholder="e.g. BK-SC-10">
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select" required>
                            <option value="Piece">Piece</option>
                            <option value="Box">Box</option>
                            <option value="Pack">Pack</option>
                            <option value="Kg">Kg</option>
                            <option value="Liter">Liter</option>
                        </select>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Purchase Price (₹)</label>
                            <input type="number" step="0.01" name="purchase_price" value="0.00" min="0" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Selling Price (₹)</label>
                            <input type="number" step="0.01" name="selling_price" value="0.00" min="0" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Min Stock Alert</label>
                            <input type="number" name="min_stock" value="5" min="0" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="2" class="form-control" placeholder="Brand, manufacturer, storage details..."></textarea>
                    </div>

                    <button type="submit" name="save_product" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST PRODUCTS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">SKU</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Prices (Buy/Sell)</th>
                                <th>Current Stock</th>
                                <th>Min Alert</th>
                                <th class="pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($products) > 0): ?>
                                <?php foreach($products as $product): ?>
                                    <?php 
                                    $is_low = $product['current_stock'] <= $product['min_stock'];
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-mono text-muted small"><?= htmlspecialchars($product['sku'] ?: '-') ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= htmlspecialchars($product['category_name'] ?: 'Uncategorized') ?></span></td>
                                        <td><?= htmlspecialchars($product['unit']) ?></td>
                                        <td>
                                            <span class="text-muted d-block small">Buy: ₹<?= number_format($product['purchase_price'], 2) ?></span>
                                            <span class="text-success fw-semibold small">Sell: ₹<?= number_format($product['selling_price'], 2) ?></span>
                                        </td>
                                        <td>
                                            <?php if($is_low): ?>
                                                <span class="badge bg-danger text-white px-3 py-2 fw-bold" style="font-size: 0.85rem;" title="Low stock alert!">
                                                    <i class="fa fa-exclamation-circle me-1 text-white"></i><?= (int)$product['current_stock'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success text-white px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                    <?= (int)$product['current_stock'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?= (int)$product['min_stock'] ?></td>
                                        <td class="pe-4">
                                            <?php if($product['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa fa-boxes fs-2 mb-2 d-block"></i>
                                        No products cataloged yet.
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
