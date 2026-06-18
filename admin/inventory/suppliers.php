<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
ADD SUPPLIER
========================== */
if(isset($_POST['save_supplier'])){
    $supplier_name = trim($_POST['supplier_name']);
    $company_name  = trim($_POST['company_name']);
    $mobile_no     = trim($_POST['mobile_no']);
    $email         = trim($_POST['email']);
    $gst_no        = trim($_POST['gst_no']);
    $address       = trim($_POST['address']);
    $status        = $_POST['status'];

    if(empty($supplier_name)){
        $error = "Supplier Name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO inventory_suppliers(
                supplier_name, company_name, mobile_no, email, gst_no, address, status
            )
            VALUES(?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $supplier_name,
            $company_name,
            $mobile_no,
            $email,
            $gst_no,
            $address,
            $status
        ]);
        $message = "Supplier Registered Successfully!";
    }
}

/* ==========================
LOAD SUPPLIERS
========================== */
$suppliers = $pdo->query("
    SELECT *
    FROM inventory_suppliers
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Suppliers Management | VIC ERP";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage school vendors and supply suppliers</h5>
    <div class="d-flex gap-2">
        <a href="categories.php" class="btn btn-outline-primary">
            <i class="fa fa-tags me-1"></i> Categories
        </a>
        <a href="products.php" class="btn btn-outline-primary">
            <i class="fa fa-boxes me-1"></i> Products
        </a>
        <a href="suppliers.php" class="btn btn-primary">
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
    <!-- ADD SUPPLIER -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Register Supplier</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Supplier/Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Company / Store Name</label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. Laxmi Books Distributors">
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Mobile Number</label>
                        <input type="text" name="mobile_no" class="form-control" placeholder="e.g. 9876543210">
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. vendor@example.com">
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">GST Number</label>
                            <input type="text" name="gst_no" class="form-control" placeholder="e.g. GSTIN12345">
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
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" rows="3" class="form-control" placeholder="Office/Warehouse address..."></textarea>
                    </div>

                    <button type="submit" name="save_supplier" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Supplier
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST SUPPLIERS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Supplier Name</th>
                                <th>Company</th>
                                <th>Contact Details</th>
                                <th>GST No</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($suppliers) > 0): ?>
                                <?php foreach($suppliers as $supplier): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?= htmlspecialchars($supplier['id']) ?></td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($supplier['supplier_name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($supplier['company_name'] ?: '-') ?></td>
                                        <td>
                                            <div class="small">
                                                <?php if($supplier['mobile_no']): ?>
                                                    <span class="d-block text-dark"><i class="fa fa-phone me-1 text-muted small"></i><?= htmlspecialchars($supplier['mobile_no']) ?></span>
                                                <?php endif; ?>
                                                <?php if($supplier['email']): ?>
                                                    <span class="d-block text-muted"><i class="fa fa-envelope me-1 text-muted small"></i><?= htmlspecialchars($supplier['email']) ?></span>
                                                <?php endif; ?>
                                                <?php if(!$supplier['mobile_no'] && !$supplier['email']): ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="fw-mono text-muted small"><?= htmlspecialchars($supplier['gst_no'] ?: '-') ?></td>
                                        <td>
                                            <?php if($supplier['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-truck fs-2 mb-2 d-block"></i>
                                        No suppliers registered yet.
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
