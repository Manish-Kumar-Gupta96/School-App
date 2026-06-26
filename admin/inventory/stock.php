<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'receive_stock') {
        $item_id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($item_id > 0 && $quantity > 0) {
            try {
                $pdo->beginTransaction();
                
                // Add stock entry
                $stmt = $pdo->prepare("INSERT INTO stock_entries (item_id, quantity, entry_type) VALUES (?, ?, 'purchase')");
                $stmt->execute([$item_id, $quantity]);
                
                // Update current stock
                $pdo->prepare("UPDATE inventory_items SET current_stock = current_stock + ? WHERE id = ?")->execute([$quantity, $item_id]);
                
                // If this is tied to a PO, we could update the PO status, but for simplicity we assume manual receipt.
                if(!empty($_POST['po_id'])){
                    $pdo->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ?")->execute([(int)$_POST['po_id']]);
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Stock received successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error receiving stock: " . $e->getMessage();
            }
        }
        header("Location: stock.php");
        exit;
    } elseif ($_POST['action'] === 'issue_stock') {
        $item_id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        $department = trim($_POST['department']);
        
        if ($item_id > 0 && $quantity > 0 && !empty($department)) {
            try {
                $pdo->beginTransaction();
                
                // Verify stock availability
                $stmt_chk = $pdo->prepare("SELECT current_stock FROM inventory_items WHERE id = ? FOR UPDATE");
                $stmt_chk->execute([$item_id]);
                $curr = $stmt_chk->fetchColumn();
                
                if ($curr >= $quantity) {
                    $admin_id = $_SESSION['admin_id'] ?? 1;
                    $stmt = $pdo->prepare("INSERT INTO stock_issues (item_id, quantity, department, issued_by, issue_date) VALUES (?, ?, ?, ?, CURDATE())");
                    $stmt->execute([$item_id, $quantity, $department, $admin_id]);
                    
                    $pdo->prepare("UPDATE inventory_items SET current_stock = current_stock - ? WHERE id = ?")->execute([$quantity, $item_id]);
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Stock issued successfully.";
                } else {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Insufficient stock. Available: " . $curr;
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error issuing stock: " . $e->getMessage();
            }
        }
        header("Location: stock.php");
        exit;
    }
}

$items = $pdo->query("SELECT * FROM inventory_items ORDER BY item_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$approved_pos = $pdo->query("SELECT id, order_no FROM purchase_orders WHERE status = 'approved'")->fetchAll(PDO::FETCH_ASSOC);

$recent_entries = $pdo->query("
    SELECT s.*, i.item_name, i.unit 
    FROM stock_entries s 
    JOIN inventory_items i ON s.item_id = i.id 
    ORDER BY s.id DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$recent_issues = $pdo->query("
    SELECT s.*, i.item_name, i.unit 
    FROM stock_issues s 
    JOIN inventory_items i ON s.item_id = i.id 
    ORDER BY s.id DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Manage Stock Flow | VIC School ERP";
$page_header = "Stock Receipts & Issues";
$active_menu = "inventory";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
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

    <div class="row g-4">
        <!-- Goods Receipt -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa fa-truck-loading me-2"></i>Receive Stock (Goods In)</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="receive_stock">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Item <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select Item</option>
                                <?php foreach($items as $i): ?>
                                    <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['item_name']) ?> (Stock: <?= $i['current_stock'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Received Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Link to PO (Optional)</label>
                            <select name="po_id" class="form-select">
                                <option value="">Manual Entry (No PO)</option>
                                <?php foreach($approved_pos as $po): ?>
                                    <option value="<?= $po['id'] ?>"><?= htmlspecialchars($po['order_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100"><i class="fa fa-plus-circle me-2"></i> Receive Stock</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Issue Stock -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-people-carry me-2"></i>Issue Stock (Goods Out)</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="issue_stock">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Item <span class="text-danger">*</span></label>
                            <select name="item_id" class="form-select" required>
                                <option value="">Select Item</option>
                                <?php foreach($items as $i): ?>
                                    <?php if($i['current_stock'] > 0): ?>
                                        <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['item_name']) ?> (Stock: <?= $i['current_stock'] ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Issue Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Issuing Department <span class="text-danger">*</span></label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department</option>
                                <option value="Computer Lab">Computer Lab</option>
                                <option value="Science Lab">Science Lab</option>
                                <option value="Library">Library</option>
                                <option value="Accounts">Accounts</option>
                                <option value="Admin Office">Admin Office</option>
                                <option value="Classrooms">Classrooms</option>
                                <option value="Sports">Sports</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-share-square me-2"></i> Issue Stock</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Logs Tables -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-2"><h6 class="mb-0 text-muted fw-bold">Recent Receipts</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach($recent_entries as $e): ?>
                                <tr>
                                    <td class="ps-3"><?= htmlspecialchars($e['item_name']) ?></td>
                                    <td class="text-success fw-bold">+<?= $e['quantity'] ?> <?= $e['unit'] ?></td>
                                    <td class="text-muted small"><?= date('d M', strtotime($e['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-2"><h6 class="mb-0 text-muted fw-bold">Recent Issues</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach($recent_issues as $i): ?>
                                <tr>
                                    <td class="ps-3"><?= htmlspecialchars($i['item_name']) ?></td>
                                    <td class="text-danger fw-bold">-<?= $i['quantity'] ?> <?= $i['unit'] ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($i['department']) ?></td>
                                    <td class="text-muted small"><?= date('d M', strtotime($i['issue_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
