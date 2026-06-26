<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Receive PO
if (isset($_POST['receive_po'])) {
    $po_id = (int)$_POST['po_id'];
    try {
        $pdo->beginTransaction();
        
        // Mark PO as received
        $stmt_upd = $pdo->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ? AND school_id = ?");
        $stmt_upd->execute([$po_id, $schoolId]);
        
        // Fetch items and update inventory stock
        $stmt_items = $pdo->prepare("SELECT inventory_item_id, quantity FROM purchase_order_items WHERE purchase_order_id = ?");
        $stmt_items->execute([$po_id]);
        $po_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_stock = $pdo->prepare("UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?");
        foreach($po_items as $pi) {
            $stmt_stock->execute([$pi['quantity'], $pi['inventory_item_id']]);
        }
        
        $pdo->commit();
        $message = "Purchase Order marked as Received. Inventory stock updated successfully.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error receiving PO: " . $e->getMessage();
    }
}

// Handle Add Vendor
if (isset($_POST['add_vendor'])) {
    $v_name = trim($_POST['vendor_name']);
    $v_contact = trim($_POST['contact_person']);
    $v_phone = trim($_POST['phone']);
    try {
        $stmt_v = $pdo->prepare("INSERT INTO vendors (school_id, vendor_name, contact_person, phone) VALUES (?, ?, ?, ?)");
        $stmt_v->execute([$schoolId, $v_name, $v_contact, $v_phone]);
        $message = "Vendor added successfully.";
    } catch(Exception $e) {
        $error = "Error adding vendor: " . $e->getMessage();
    }
}

// Handle Add PO
if (isset($_POST['add_po'])) {
    $vendor_id = (int)$_POST['vendor_id'];
    $item_id = (int)$_POST['inventory_item_id'];
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $total = $qty * $price;
    $date = date('Y-m-d');
    
    try {
        $pdo->beginTransaction();
        
        $stmt_po = $pdo->prepare("INSERT INTO purchase_orders (school_id, vendor_id, purchase_date, total_amount, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt_po->execute([$schoolId, $vendor_id, $date, $total]);
        $po_id = $pdo->lastInsertId();
        
        $stmt_poi = $pdo->prepare("INSERT INTO purchase_order_items (purchase_order_id, inventory_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_poi->execute([$po_id, $item_id, $qty, $price]);
        
        $pdo->commit();
        $message = "Purchase Order generated successfully.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error creating PO: " . $e->getMessage();
    }
}

// Fetch Vendors
$stmt_vlist = $pdo->prepare("SELECT * FROM vendors WHERE school_id = ?");
$stmt_vlist->execute([$schoolId]);
$vendors = $stmt_vlist->fetchAll(PDO::FETCH_ASSOC);

// Fetch Items for Dropdown
$stmt_ilist = $pdo->prepare("SELECT id, item_name, item_code FROM inventory_items WHERE school_id = ?");
$stmt_ilist->execute([$schoolId]);
$inventory_items = $stmt_ilist->fetchAll(PDO::FETCH_ASSOC);

// Fetch POs
$stmt_pos = $pdo->prepare("
    SELECT po.*, v.vendor_name, 
           (SELECT COUNT(*) FROM purchase_order_items WHERE purchase_order_id = po.id) as item_count
    FROM purchase_orders po
    LEFT JOIN vendors v ON po.vendor_id = v.id
    WHERE po.school_id = ?
    ORDER BY po.id DESC
");
$stmt_pos->execute([$schoolId]);
$pos = $stmt_pos->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Purchases & Vendors | Admin Portal";
$page_header = "Inventory Management";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Purchase Orders & Vendors</h5>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal"><i class="fa fa-building me-1"></i> Add Vendor</button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPoModal"><i class="fa fa-plus me-1"></i> New Purchase Order</button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0">Recent Purchase Orders</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">PO No.</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="pe-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pos as $p): ?>
                        <tr>
                            <td class="ps-4 fw-bold">PO-<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($p['vendor_name']) ?></td>
                            <td><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
                            <td class="fw-bold text-dark">₹<?= number_format($p['total_amount'], 2) ?></td>
                            <td>
                                <?php if($p['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa fa-clock"></i> Pending</span>
                                <?php elseif($p['status'] == 'received'): ?>
                                    <span class="badge bg-success"><i class="fa fa-check-circle"></i> Received</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if($p['status'] == 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="po_id" value="<?= $p['id'] ?>">
                                        <button type="submit" name="receive_po" class="btn btn-sm btn-success rounded-pill px-3" onclick="return confirm('Mark as received and add to inventory?');">
                                            Mark Received
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>Completed</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($pos)): ?>
                        <tr><td colspan="6" class="text-center p-5 text-muted">No purchase orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Register Vendor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
            <label class="form-label">Vendor Company Name</label>
            <input type="text" name="vendor_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_vendor" class="btn btn-primary px-4">Save Vendor</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Add PO Modal -->
<div class="modal fade" id="addPoModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold">Create Purchase Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select" required>
                    <option value="">Select Vendor...</option>
                    <?php foreach($vendors as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vendor_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Inventory Item</label>
                <select name="inventory_item_id" class="form-select" required>
                    <option value="">Select Item...</option>
                    <?php foreach($inventory_items as $i): ?>
                        <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['item_name']) ?> (<?= htmlspecialchars($i['item_code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Order Quantity</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Unit Price (₹)</label>
                <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="col-12 text-muted small mt-2">
                * Note: Currently only single-item POs are supported in this UI view.
            </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_po" class="btn btn-primary px-4">Generate PO</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php require_once('../includes/footer.php'); ?>
