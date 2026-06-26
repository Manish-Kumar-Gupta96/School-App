<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch student info
$student = $pdo->prepare("SELECT * FROM students WHERE user_id = ? OR id = ? LIMIT 1");
$student->execute([$user_id, $user_id]);
$student_data = $student->fetch(PDO::FETCH_ASSOC);
$student_id = $student_data ? $student_data['id'] : $user_id;
$class_id = $student_data ? $student_data['class_id'] : 0;

// Fetch issued books
$issued_books = $pdo->prepare("
    SELECT li.*, c.barcode, b.title, b.author 
    FROM library_issues li 
    JOIN library_book_copies c ON li.book_copy_id = c.id 
    JOIN library_books b ON c.book_id = b.id 
    WHERE li.user_id = ? AND li.user_type = 'student' 
    ORDER BY li.status ASC, li.due_date ASC
");
$issued_books->execute([$student_id]);
$my_books = $issued_books->fetchAll(PDO::FETCH_ASSOC);

// Fetch digital resources for this class (or all classes)
$digital_resources = $pdo->prepare("
    SELECT r.*, s.subject_name 
    FROM digital_resources r
    LEFT JOIN subjects s ON r.subject_id = s.id
    WHERE r.class_id = ? OR r.class_id = 0
    ORDER BY r.id DESC
");
$digital_resources->execute([$class_id]);
$resources = $digital_resources->fetchAll(PDO::FETCH_ASSOC);

// Fetch available books for search
$available_books = $pdo->query("
    SELECT b.title, b.author, b.isbn, b.available_quantity, c.category_name 
    FROM library_books b
    LEFT JOIN library_categories c ON b.category_id = c.id
    WHERE b.available_quantity > 0
    ORDER BY b.title ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fine Calculation Rules
$fine_rule = $pdo->query("SELECT * FROM library_fine_rules LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$per_day_fine = $fine_rule ? (float)$fine_rule['per_day_fine'] : 10.00;
$max_fine = $fine_rule ? (float)$fine_rule['max_fine'] : 500.00;

$root_path = "../";
$page_title = "My Library | VIC School ERP";
$page_header = "Smart Digital Library";
$active_menu = "library";

require_once('includes/header.php');
require_once('includes/topbar.php');
?>

<div class="container mt-4">
    <!-- AI Smart Suggestions Banner -->
    <div class="card border-0 mb-4 bg-primary text-white shadow" style="border-radius: 15px; background: linear-gradient(135deg, var(--primary-color) 0%, #4facfe 100%);">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa fa-robot me-2"></i> AI Smart Library</h4>
                <p class="mb-0 text-white-50">Search for any topic, and our AI will suggest the best books, notes, and videos tailored for Class <?= htmlspecialchars($student_data['class'] ?? '') ?>.</p>
            </div>
            <button class="btn btn-light text-primary fw-bold px-4 rounded-pill shadow-sm" onclick="alert('AI Suggestions API Integrated Here (Placeholder)')">Ask AI</button>
        </div>
    </div>

    <ul class="nav nav-pills mb-4" id="libraryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="issued-tab" data-bs-toggle="pill" data-bs-target="#issued" type="button" role="tab">My Issued Books</button>
        </li>
        <li class="nav-item ms-2" role="presentation">
            <button class="nav-link rounded-pill px-4" id="digital-tab" data-bs-toggle="pill" data-bs-target="#digital" type="button" role="tab">Digital Resources</button>
        </li>
        <li class="nav-item ms-2" role="presentation">
            <button class="nav-link rounded-pill px-4" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">Search Books</button>
        </li>
    </ul>

    <div class="tab-content" id="libraryTabsContent">
        <!-- My Issued Books Tab -->
        <div class="tab-pane fade show active" id="issued" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Book Title</th>
                                    <th>Author</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status & Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($my_books) > 0): ?>
                                    <?php foreach ($my_books as $book): ?>
                                        <?php 
                                            $due_date = strtotime($book['due_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $days_late = 0;
                                            $fine = 0;
                                            if ($book['status'] !== 'returned' && $today > $due_date) {
                                                $days_late = floor(($today - $due_date) / (60 * 60 * 24));
                                                $fine = min($days_late * $per_day_fine, $max_fine);
                                            }
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($book['title']) ?></td>
                                            <td class="text-muted"><?= htmlspecialchars($book['author']) ?></td>
                                            <td><?= date('d M Y', strtotime($book['issue_date'])) ?></td>
                                            <td class="<?= $days_late > 0 ? 'text-danger fw-bold' : '' ?>"><?= date('d M Y', strtotime($book['due_date'])) ?></td>
                                            <td>
                                                <?php if($book['status'] === 'returned'): ?>
                                                    <span class="badge bg-success">Returned</span>
                                                <?php elseif($days_late > 0): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                    <div class="text-danger small fw-bold mt-1">Fine: ₹<?= number_format($fine, 2) ?></div>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Issued</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">You have no issued books.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Digital Resources Tab -->
        <div class="tab-pane fade" id="digital" role="tabpanel">
            <div class="row g-4">
                <?php if(count($resources) > 0): ?>
                    <?php foreach($resources as $r): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0 h-100 card-hover" style="border-radius: 12px; border-top: 4px solid var(--primary-color) !important;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-info"><?= htmlspecialchars($r['resource_type']) ?></span>
                                        <span class="text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($r['title']) ?></h5>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($r['subject_name'] ?: 'General Subject') ?></p>
                                    <a href="<?= htmlspecialchars(strpos($r['file_path'], 'http') === 0 ? $r['file_path'] : $root_path . $r['file_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100"><i class="fa fa-eye me-2"></i> View Resource</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fa fa-folder-open fs-1 text-light mb-3"></i>
                        <h5>No digital resources available for your class yet.</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Books Tab -->
        <div class="tab-pane fade" id="search" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-search me-2 text-primary"></i>Search Library Catalog</h5>
                    <input type="text" id="catalogSearch" class="form-control w-50" placeholder="Search by ISBN, Title, Author, Category...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="catalogTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Availability</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($available_books) > 0): ?>
                                    <?php foreach($available_books as $b): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($b['title']) ?></div>
                                                <div class="text-muted small">ISBN: <?= htmlspecialchars($b['isbn'] ?: 'N/A') ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($b['author'] ?: 'Unknown') ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($b['category_name']) ?></span></td>
                                            <td>
                                                <span class="badge bg-success">Available (<?= $b['available_quantity'] ?>)</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">Library catalog is empty.</td></tr>
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
document.getElementById('catalogSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#catalogTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php require_once('includes/footer.php'); ?>
