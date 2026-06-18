<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle Fee Head Creation
if (isset($_POST['add_fee_head'])) {
    $name = trim($_POST['name']);
    $amount = (float)$_POST['amount'];

    if (empty($name) || $amount <= 0) {
        $error = 'Please enter a valid Name and Amount.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO fee_heads (name, amount, school_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $amount, CURRENT_SCHOOL_ID]);
            $success = 'Fee Head added successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving Fee Head: ' . $e->getMessage();
        }
    }
}

// Handle Delete Fee Head
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM fee_heads WHERE id = ? AND school_id = ?");
        $stmt->execute([$id, CURRENT_SCHOOL_ID]);
        $success = 'Fee Head deleted successfully.';
    } catch (PDOException $e) {
        $error = 'Error deleting Fee Head: ' . $e->getMessage();
    }
}

// Fetch Fee Heads
$fee_heads = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM fee_heads WHERE school_id = ? ORDER BY id DESC");
    $stmt->execute([CURRENT_SCHOOL_ID]);
    $fee_heads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading Fee Heads: ' . $e->getMessage();
}

$page_title = "Manage Fee Heads | VIC ERP";
$page_header = "Fee Categories Configuration";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Fee Heads & Structures</h2>
        <a href="ledgers.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-2"></i> Back to Accounts
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add Fee Head form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-plus-circle me-2 text-primary"></i>Create Fee Head</h5>
                <hr class="text-muted mt-0 mb-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fee Head Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Admission Fee, Sports Fee" required style="border-radius: 8px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 5000.00" required style="border-radius: 8px;">
                    </div>
                    <button type="submit" name="add_fee_head" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                        <i class="fa fa-save me-2"></i> Save Fee Head
                    </button>
                </form>
            </div>
        </div>

        <!-- Fee Heads List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-success"></i>Configured Fee Heads</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Fee Name</th>
                                <th>Default Amount (₹)</th>
                                <th>Created Date</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($fee_heads) > 0): ?>
                                <?php foreach ($fee_heads as $head): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?= $head['id'] ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($head['name']) ?></td>
                                        <td class="fw-bold text-primary">₹ <?= number_format($head['amount'], 2) ?></td>
                                        <td class="text-muted small"><?= date('d M Y', strtotime($head['created_at'])) ?></td>
                                        <td class="pe-4 text-end">
                                            <a href="?delete=1&id=<?= $head['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this fee head?')">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No fee heads configured. Use the form to add one.
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
require_once($root_path . 'admin/includes/footer.php');
?>
