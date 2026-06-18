<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['issue_book'])){
    $book_id     = (int)$_POST['book_id'];
    $student_id  = (int)$_POST['student_id'];
    $issue_date  = $_POST['issue_date'];
    $due_date    = $_POST['due_date'];

    if(empty($book_id) || empty($student_id) || empty($issue_date) || empty($due_date)){
        $error = "All fields are required.";
    } else {
        // CHECK BOOK STOCK
        $bookStmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $bookStmt->execute([$book_id]);
        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

        if(!$book){
            $error = "Selected book was not found.";
        } elseif($book['available_quantity'] <= 0){
            $error = "Selected book is currently out of stock.";
        } else {
            try {
                $pdo->beginTransaction();

                // Save issue transaction
                $issue = $pdo->prepare("
                    INSERT INTO book_issues(book_id, student_id, issue_date, due_date, status)
                    VALUES(?,?,?,?, 'Issued')
                ");
                $issue->execute([$book_id, $student_id, $issue_date, $due_date]);

                // Reduce stock
                $update = $pdo->prepare("
                    UPDATE books
                    SET available_quantity = available_quantity - 1
                    WHERE id = ?
                ");
                $update->execute([$book_id]);

                $pdo->commit();
                $message = "Book Issued Successfully!";
            } catch(Exception $e) {
                $pdo->rollBack();
                $error = "Failed to issue book: " . $e->getMessage();
            }
        }
    }
}

// Fetch available books
$books = $pdo->query("SELECT * FROM books WHERE available_quantity > 0 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students for selector
$students = $pdo->query("SELECT id, admission_no, first_name, last_name FROM students ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Load recent issues (last 10 transactions)
$recentIssues = $pdo->query("
    SELECT bi.*, s.first_name, s.last_name, s.admission_no, b.title, b.book_code
    FROM book_issues bi
    LEFT JOIN students s ON bi.student_id = s.id
    LEFT JOIN books b ON bi.book_id = b.id
    ORDER BY bi.id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Issue Book | VIC ERP";
$page_header = "Book Issue Board";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Record book lending transactions</h5>
    <a href="books.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Catalog
    </a>
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
    <!-- ISSUE FORM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Issue Book Form</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student Borrower <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Borrower</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Book <span class="text-danger">*</span></label>
                        <select name="book_id" class="form-select" required>
                            <option value="">Select Book</option>
                            <?php foreach($books as $bk): ?>
                                <option value="<?= $bk['id'] ?>">
                                    <?= htmlspecialchars($bk['title']) ?> (Code: <?= htmlspecialchars($bk['book_code']) ?> | Stock: <?= $bk['available_quantity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="form-control" required>
                    </div>

                    <button type="submit" name="issue_book" class="btn btn-success w-100">
                        <i class="fa fa-book-reader me-1"></i> Issue Book
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- RECENT TRANSACTIONS HISTORY -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Recent Lending Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Borrower</th>
                                <th>Book Title</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($recentIssues) > 0): ?>
                                <?php foreach($recentIssues as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($row['admission_no']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= htmlspecialchars($row['title']) ?></div>
                                            <span class="text-muted small"><?= htmlspecialchars($row['book_code']) ?></span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($row['issue_date'])) ?></td>
                                        <td><?= date('d M Y', strtotime($row['due_date'])) ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Issued'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Lent</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Returned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-history fs-2 mb-2 d-block"></i>
                                        No recent issue records.
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
