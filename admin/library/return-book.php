<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* =========================
RETURN BOOK
========================= */
if(isset($_POST['return_book'])){
    $issue_id = (int)$_POST['issue_id'];

    // Load issue details
    $stmt = $pdo->prepare("
        SELECT bi.*, b.id AS book_id, b.title
        FROM book_issues bi
        LEFT JOIN books b ON bi.book_id = b.id
        WHERE bi.id=? AND bi.status='Issued'
    ");
    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$issue){
        $error = "Active lending record not found.";
    } else {
        try {
            $pdo->beginTransaction();

            $return_date = date('Y-m-d');
            $fine = 0;

            // Late fine check (₹5 per day)
            if(strtotime($return_date) > strtotime($issue['due_date'])){
                $daysLate = floor((strtotime($return_date) - strtotime($issue['due_date'])) / 86400);
                $fine = $daysLate * 5;
            }

            // Update issues status
            $update = $pdo->prepare("
                UPDATE book_issues
                SET return_date = ?, fine_amount = ?, status = 'Returned'
                WHERE id = ?
            ");
            $update->execute([$return_date, $fine, $issue_id]);

            // Increment book available quantity
            $stock = $pdo->prepare("
                UPDATE books
                SET available_quantity = available_quantity + 1
                WHERE id = ?
            ");
            $stock->execute([$issue['book_id']]);

            $pdo->commit();
            $message = "Book \"" . htmlspecialchars($issue['title']) . "\" returned successfully! Late Return Fine: ₹" . number_format($fine, 2);
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Failed to process book return: " . $e->getMessage();
        }
    }
}

/* =========================
LOAD ISSUED BOOKS
========================= */
$issuedBooks = $pdo->query("
    SELECT bi.*, s.first_name, s.last_name, s.admission_no, b.title, b.book_code
    FROM book_issues bi
    LEFT JOIN students s ON bi.student_id = s.id
    LEFT JOIN books b ON bi.book_id = b.id
    WHERE bi.status = 'Issued'
    ORDER BY bi.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Return Book | VIC ERP";
$page_header = "Book Returns & Fines";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Record and process returned books</h5>
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

<!-- ISSUED LIST TABLE -->
<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0 text-dark">Lent Books Queue</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4" width="80">Issue ID</th>
                        <th>Student Borrower</th>
                        <th>Book Details</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Current Late Fee</th>
                        <th class="text-center" width="160">Action</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if(count($issuedBooks) > 0): ?>
                        <?php foreach($issuedBooks as $book): 
                            // Live compute current late fee projection
                            $today = date('Y-m-d');
                            $projected_fine = 0;
                            if (strtotime($today) > strtotime($book['due_date'])) {
                                $days = floor((strtotime($today) - strtotime($book['due_date'])) / 86400);
                                $projected_fine = $days * 5;
                            }
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold">#<?= htmlspecialchars($book['id']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($book['first_name'] . ' ' . $book['last_name']) ?></div>
                                    <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($book['admission_no']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-primary mb-0"><?= htmlspecialchars($book['title']) ?></div>
                                    <span class="text-muted small"><?= htmlspecialchars($book['book_code']) ?></span>
                                </td>
                                <td><?= date('d M Y', strtotime($book['issue_date'])) ?></td>
                                <td>
                                    <?php 
                                    $is_overdue = strtotime(date('Y-m-d')) > strtotime($book['due_date']);
                                    ?>
                                    <span class="<?= $is_overdue ? 'text-danger fw-bold' : '' ?>">
                                        <?= date('d M Y', strtotime($book['due_date'])) ?>
                                        <?php if($is_overdue): ?>
                                            <span class="badge bg-danger-subtle text-danger ms-1 small">Overdue</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-<?= $projected_fine > 0 ? 'danger' : 'success' ?>">
                                        ₹ <?= number_format($projected_fine, 2) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form method="POST" onsubmit="return confirm('Confirm book return and fine processing?')">
                                        <input type="hidden" name="issue_id" value="<?= $book['id'] ?>">
                                        <button type="submit" name="return_book" class="btn btn-sm btn-success">
                                            <i class="fa fa-check-circle me-1"></i> Process Return
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa fa-check-double fs-2 mb-2 d-block"></i>
                                All books have been returned. No active issues.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
