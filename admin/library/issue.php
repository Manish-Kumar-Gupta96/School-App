<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'issue_book') {
        $barcode = trim($_POST['barcode']);
        $user_type = $_POST['user_type'];
        $user_id = (int)$_POST['user_id'];
        $due_date = $_POST['due_date'];
        
        if (!empty($barcode) && !empty($user_type) && $user_id > 0 && !empty($due_date)) {
            try {
                $pdo->beginTransaction();
                
                // Find copy by barcode
                $stmt = $pdo->prepare("SELECT c.id, c.book_id, c.status, b.available_quantity FROM library_book_copies c JOIN library_books b ON c.book_id = b.id WHERE c.barcode = ? FOR UPDATE");
                $stmt->execute([$barcode]);
                $copy = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($copy && $copy['status'] === 'available') {
                    // Update copy status
                    $pdo->prepare("UPDATE library_book_copies SET status = 'issued' WHERE id = ?")->execute([$copy['id']]);
                    
                    // Decrease available quantity
                    $pdo->prepare("UPDATE library_books SET available_quantity = available_quantity - 1 WHERE id = ?")->execute([$copy['book_id']]);
                    
                    // Insert issue record
                    $pdo->prepare("
                        INSERT INTO library_issues (book_copy_id, user_id, user_type, issue_date, due_date, status) 
                        VALUES (?, ?, ?, CURDATE(), ?, 'issued')
                    ")->execute([$copy['id'], $user_id, $user_type, $due_date]);
                    
                    $pdo->commit();
                    $_SESSION['success'] = "Book issued successfully to $user_type ID $user_id.";
                } else {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Book copy not found or already issued.";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to issue book: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Please fill all required fields.";
        }
        header("Location: issue.php");
        exit;
    }
}

// Fetch all available copies for quick reference (optional)
$available_copies = $pdo->query("
    SELECT c.barcode, b.title 
    FROM library_book_copies c 
    JOIN library_books b ON c.book_id = b.id 
    WHERE c.status = 'available'
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Issue Book | VIC School ERP";
$page_header = "Issue Book";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-barcode me-2"></i>Scan & Issue Book</h5>
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

                    <form method="POST">
                        <input type="hidden" name="action" value="issue_book">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Book Barcode <span class="text-danger">*</span></label>
                            <input type="text" name="barcode" class="form-control form-control-lg" placeholder="Scan or enter barcode" required autofocus>
                            <div class="form-text">Example available barcodes: 
                                <?php 
                                    $examples = array_slice($available_copies, 0, 5);
                                    foreach($examples as $ex) echo htmlspecialchars($ex['barcode']) . ", "; 
                                ?>...
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">User Type <span class="text-danger">*</span></label>
                                <select name="user_type" class="form-select" required>
                                    <option value="student">Student</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">User ID <span class="text-danger">*</span></label>
                                <input type="number" name="user_id" class="form-control" placeholder="Enter System ID" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fa fa-hand-holding me-2"></i> Issue Book Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
