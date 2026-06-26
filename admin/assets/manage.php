<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_asset') {
        $asset_name = trim($_POST['asset_name']);
        $category = trim($_POST['category']);
        $serial_no = trim($_POST['serial_no']);
        $purchase_date = $_POST['purchase_date'];
        $purchase_price = (float)$_POST['purchase_price'];
        
        if (!empty($asset_name) && !empty($category)) {
            $asset_code = "AST-" . strtoupper(substr($category, 0, 3)) . "-" . time();
            
            $stmt = $pdo->prepare("INSERT INTO assets (asset_code, asset_name, category, serial_no, purchase_date, purchase_price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$asset_code, $asset_name, $category, $serial_no, $purchase_date, $purchase_price]);
            $_SESSION['success'] = "Asset registered successfully with code: " . $asset_code;
        } else {
            $_SESSION['error'] = "Asset name and category are required.";
        }
        header("Location: manage.php");
        exit;
    }
}

if (isset($_GET['status']) && isset($_GET['id'])) {
    $status = $_GET['status'];
    $id = (int)$_GET['id'];
    if (in_array($status, ['active', 'maintenance', 'disposed'])) {
        $pdo->prepare("UPDATE assets SET status = ? WHERE id = ?")->execute([$status, $id]);
        $_SESSION['success'] = "Status updated.";
    }
    header("Location: manage.php");
    exit;
}

$assets = $pdo->query("SELECT * FROM assets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Manage Assets | VIC School ERP";
$page_header = "Asset Register";
$active_menu = "assets";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Add Asset Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-plus me-2"></i>Register Asset</h5>
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
                        <input type="hidden" name="action" value="add_asset">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" name="asset_name" class="form-control" required placeholder="e.g. Dell XPS 15">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Laptop">Laptop</option>
                                <option value="Desktop">Desktop</option>
                                <option value="Projector">Projector</option>
                                <option value="Printer">Printer</option>
                                <option value="Furniture">Furniture</option>
                                <option value="Smart Board">Smart Board</option>
                                <option value="Vehicle">Vehicle</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Serial Number</label>
                            <input type="text" name="serial_no" class="form-control" placeholder="SN-XXXX-YYYY">
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Price (₹)</label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" value="0.00">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-save me-2"></i> Register Asset</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Asset List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-laptop me-2 text-warning"></i>Asset Master List</h5>
                    <input type="text" id="searchInput" class="form-control w-50" placeholder="Search asset code or name...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="assetTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Asset Details</th>
                                    <th>Category</th>
                                    <th>Purchase Info</th>
                                    <th>Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($assets) > 0): ?>
                                    <?php foreach ($assets as $a): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($a['asset_name']) ?></div>
                                                <div class="text-primary small">Code: <?= htmlspecialchars($a['asset_code']) ?></div>
                                                <div class="text-muted small">SN: <?= htmlspecialchars($a['serial_no'] ?: 'N/A') ?></div>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($a['category']) ?></span></td>
                                            <td>
                                                <div><?= date('d M Y', strtotime($a['purchase_date'])) ?></div>
                                                <div class="fw-bold text-success">₹<?= number_format($a['purchase_price'], 2) ?></div>
                                            </td>
                                            <td>
                                                <?php if($a['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif($a['status'] == 'maintenance'): ?>
                                                    <span class="badge bg-warning text-dark">Maintenance</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Disposed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        Change Status
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="?status=active&id=<?= $a['id'] ?>">Active</a></li>
                                                        <li><a class="dropdown-item" href="?status=maintenance&id=<?= $a['id'] ?>">Maintenance</a></li>
                                                        <li><a class="dropdown-item" href="?status=disposed&id=<?= $a['id'] ?>">Disposed</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No assets registered yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#assetTable tbody tr');
    rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; });
});
</script>

<?php require_once('../includes/footer.php'); ?>
