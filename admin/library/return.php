<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Fetch fine rules
$fine_rule = $pdo->query("SELECT * FROM library_fine_rules LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$per_day_fine = $fine_rule ? (float)$fine_rule['per_day_fine'] : 10.00;
$max_fine = $fine_rule ? (float)$fine_rule['max_fine'] : 500.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'return_book') {
        $barcode = trim($_POST['barcode']);
        $fine_paid = isset($_POST['fine_paid']) ? (float)$_POST['fine_paid'] : 0.00;
        
        if (!empty($barcode)) {
            try {
                $pdo->beginTransaction();
                
                // Find issue record
                $stmt = $pdo->prepare("
                    SELECT li.*, c.book_id 
                    FROM library_issues li 
                    JOIN library_book_copies c ON li.book_copy_id = c.id 
                    WHERE c.barcode = ? AND li.status IN ('issued', 'overdue') 
                    ORDER BY li.id DESC LIMIT 1 FOR UPDATE
                ");
                $stmt->execute([$barcode]);
                $issue = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($issue) {
                    // Calculate expected fine again for safety
                    $expected_fine = 0;
                    $due_date = strtotime($issue['due_date']);
                    $today = strtotime(date('Y-m-d'));
                    if ($today > $due_date) {
                        $days_late = floor(($today - $due_date) / (60 * 60 * 24));
                        $expected_fine = min($days_late * $per_day_fine, $max_fine);
                    }
                    
                    // Update issue status and fine
                    $pdo->prepare("
                        UPDATE library_issues 
                        SET status = 'returned', return_date = CURDATE(), fine_amount = ? 
                        WHERE id = ?
                    ")->execute([$fine_paid, $issue['id']]);
                    
                    // Update copy status
                    $pdo->prepare("UPDATE library_book_copies SET status = 'available' WHERE id = ?")->execute([$issue['book_copy_id']]);
                    
                    // Increase available quantity
                    $pdo->prepare("UPDATE library_books SET available_quantity = available_quantity + 1 WHERE id = ?")->execute([$issue['book_id']]);
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Book returned successfully. Fine collected: ₹" . number_format($fine_paid, 2);
                } else {
                    $pdo->rollBack();
                    $_SESSION['error'] = "No active issue found for this barcode.";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to return book: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Please provide a valid barcode.";
        }
        header("Location: return.php");
        exit;
    }
}

// Fetch pending returns (issued/overdue)
$pending_returns = $pdo->query("
    SELECT li.*, c.barcode, b.title 
    FROM library_issues li 
    JOIN library_book_copies c ON li.book_copy_id = c.id 
    JOIN library_books b ON c.book_id = b.id 
    WHERE li.status IN ('issued', 'overdue')
    ORDER BY li.due_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Return Book | VIC School ERP";
$page_header = "Return Book";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row">
        <!-- Return Form -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa fa-undo me-2"></i>Scan & Return</h5>
                </div>
                <div class="card-body p-4">
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

                    <form method="POST" id="returnForm">
                        <input type="hidden" name="action" value="return_book">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Book Barcode <span class="text-danger">*</span></label>
                            <input type="text" id="barcodeInput" name="barcode" class="form-control form-control-lg" placeholder="Scan or enter barcode" required autofocus>
                        </div>

                        <!-- Fine Calculation Area (Populated by JS) -->
                        <div id="fineArea" class="d-none mb-4 p-3 rounded bg-light border border-warning">
                            <h6 class="text-danger fw-bold"><i class="fa fa-exclamation-triangle me-2"></i>Overdue Fine Applicable</h6>
                            <p class="mb-2">Calculated Fine: <span id="calcFineAmount" class="fw-bold fs-5 text-dark">₹0</span></p>
                            <label class="form-label fw-bold">Amount Collected (₹)</label>
                            <input type="number" step="0.01" name="fine_paid" id="finePaidInput" class="form-control" value="0.00">
                        </div>

                        <button type="button" onclick="checkFineAndSubmit()" class="btn btn-success btn-lg w-100"><i class="fa fa-check-circle me-2"></i> Confirm Return</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pending Returns Table -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-clock me-2 text-warning"></i>Pending Returns</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="pendingTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">Barcode</th>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Status / Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($pending_returns) > 0): ?>
                                    <?php foreach ($pending_returns as $r): ?>
                                        <?php 
                                            $due_date = strtotime($r['due_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $fine = 0;
                                            $days_late = 0;
                                            if ($today > $due_date) {
                                                $days_late = floor(($today - $due_date) / (60 * 60 * 24));
                                                $fine = min($days_late * $per_day_fine, $max_fine);
                                            }
                                        ?>
                                        <tr onclick="fillBarcode('<?= htmlspecialchars($r['barcode']) ?>')" style="cursor: pointer;">
                                            <td class="ps-4"><span class="badge bg-secondary"><?= htmlspecialchars($r['barcode']) ?></span></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></td>
                                            <td class="<?= $days_late > 0 ? 'text-danger fw-bold' : '' ?>"><?= date('d M', strtotime($r['due_date'])) ?></td>
                                            <td>
                                                <?php if($days_late > 0): ?>
                                                    <span class="badge bg-danger">Late by <?= $days_late ?> days</span>
                                                    <div class="text-danger small fw-bold mt-1" data-fine="<?= $fine ?>">Fine: ₹<?= number_format($fine, 2) ?></div>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Issued</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No pending returns.</td></tr>
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
function fillBarcode(barcode) {
    document.getElementById('barcodeInput').value = barcode;
    checkFineDisplay(barcode);
}

document.getElementById('barcodeInput').addEventListener('input', function() {
    checkFineDisplay(this.value);
});

function checkFineDisplay(barcode) {
    let rows = document.querySelectorAll('#pendingTable tbody tr');
    let fineArea = document.getElementById('fineArea');
    let fineInput = document.getElementById('finePaidInput');
    let calcFineSpan = document.getElementById('calcFineAmount');
    
    let fine = 0;
    rows.forEach(row => {
        let rowBarcode = row.querySelector('.badge.bg-secondary');
        if (rowBarcode && rowBarcode.innerText === barcode) {
            let fineElement = row.querySelector('[data-fine]');
            if (fineElement) {
                fine = parseFloat(fineElement.getAttribute('data-fine'));
            }
        }
    });

    if (fine > 0) {
        fineArea.classList.remove('d-none');
        calcFineSpan.innerText = '₹' + fine.toFixed(2);
        fineInput.value = fine.toFixed(2);
    } else {
        fineArea.classList.add('d-none');
        fineInput.value = 0;
    }
}

function checkFineAndSubmit() {
    let barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) {
        alert("Please enter a barcode");
        return;
    }
    document.getElementById('returnForm').submit();
}
</script>

<?php require_once('../includes/footer.php'); ?>
