<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Create
if (isset($_POST['add_item'])) {
    $cat_id = (int)$_POST['category_id'];
    $item_name = trim($_POST['item_name']);
    $item_code = trim($_POST['item_code']);
    $brand = trim($_POST['brand']);
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['unit_price'];
    $min_stock = (int)$_POST['minimum_stock'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt_ins = $pdo->prepare("
            INSERT INTO inventory_items (school_id, category_id, item_name, item_code, brand, quantity, unit_price)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_ins->execute([$schoolId, $cat_id, $item_name, $item_code, $brand, $qty, $price]);
        
        $item_id = $pdo->lastInsertId();
        
        // Setup Minimum Stock Alert
        if ($min_stock > 0) {
            $stmt_alert = $pdo->prepare("INSERT INTO stock_alerts (school_id, inventory_item_id, current_stock, minimum_stock, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_alert->execute([$schoolId, $item_id, $qty, $min_stock]);
        }
        
        $pdo->commit();
        $message = "Item added successfully.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error adding item: " . $e->getMessage();
    }
}

// Fetch Categories
$stmt_cats = $pdo->prepare("SELECT id, category_name FROM inventory_categories WHERE school_id = ?");
$stmt_cats->execute([$schoolId]);
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

// Fetch Items
$stmt_items = $pdo->prepare("
    SELECT i.*, c.category_name, sa.minimum_stock
    FROM inventory_items i
    LEFT JOIN inventory_categories c ON i.category_id = c.id
    LEFT JOIN stock_alerts sa ON i.id = sa.inventory_item_id
    WHERE i.school_id = ?
    ORDER BY i.id DESC
");
$stmt_items->execute([$schoolId]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Manage Inventory Items | Admin Portal";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Inventory Master list</h5>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="fa fa-plus me-1"></i> Add New Item</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item Code</th>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th>Stock Qty</th>
                        <th>Price/Unit</th>
                        <th>Status</th>
                        <th class="pe-4 text-center">QR Code</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $i): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><code><?= htmlspecialchars($i['item_code']) ?></code></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($i['item_name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($i['brand']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($i['category_name']) ?></td>
                            <td>
                                <span class="badge <?= $i['quantity'] <= ($i['minimum_stock']??0) ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $i['quantity'] ?>
                                </span>
                            </td>
                            <td>₹<?= number_format($i['unit_price'], 2) ?></td>
                            <td>
                                <?php if($i['status'] == 'available'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success">Available</span>
                                <?php elseif($i['status'] == 'issued'): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info">Issued</span>
                                <?php elseif($i['status'] == 'maintenance'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <!-- Generate QR Code on the fly using Google Charts API or qrserver API -->
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showQR('<?= htmlspecialchars($i['item_code']) ?>', '<?= htmlspecialchars($i['item_name']) ?>')">
                                    <i class="fa fa-qrcode"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($items)): ?>
                        <tr><td colspan="7" class="text-center p-5 text-muted">No inventory items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Add New Inventory Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Choose...</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Item Code (Unique)</label>
                <input type="text" name="item_code" class="form-control" required placeholder="e.g. LPT-001">
            </div>
            <div class="col-md-6">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Brand / Make</label>
                <input type="text" name="brand" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Initial Quantity</label>
                <input type="number" name="quantity" class="form-control" value="1" min="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Unit Price (₹)</label>
                <input type="number" step="0.01" name="unit_price" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Minimum Stock Alert</label>
                <input type="number" name="minimum_stock" class="form-control" value="0">
            </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_item" class="btn btn-primary px-4">Save Item</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header border-0 pb-0 justify-content-center">
        <h6 class="modal-title fw-bold" id="qrItemName">Item Name</h6>
      </div>
      <div class="modal-body p-4">
        <img id="qrImage" src="" alt="QR Code" class="img-fluid border p-2 rounded" style="width: 200px; height: 200px;">
        <div class="mt-3 font-monospace fw-bold" id="qrItemCode"></div>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print"></i> Print QR</button>
        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function showQR(code, name) {
    document.getElementById('qrItemName').innerText = name;
    document.getElementById('qrItemCode').innerText = code;
    // Using a free QR code generator API
    document.getElementById('qrImage').src = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + encodeURIComponent(code);
    new bootstrap.Modal(document.getElementById('qrModal')).show();
}
</script>

<?php require_once('../includes/footer.php'); ?>
