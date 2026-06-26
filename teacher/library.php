<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch teacher info
$teacher = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ? OR id = ? LIMIT 1");
$teacher->execute([$user_id, $user_id]);
$teacher_data = $teacher->fetch(PDO::FETCH_ASSOC);
$teacher_id = $teacher_data ? $teacher_data['id'] : $user_id;

// Fetch issued books
$issued_books = $pdo->prepare("
    SELECT li.*, c.barcode, b.title, b.author 
    FROM library_issues li 
    JOIN library_book_copies c ON li.book_copy_id = c.id 
    JOIN library_books b ON c.book_id = b.id 
    WHERE li.user_id = ? AND li.user_type = 'teacher' 
    ORDER BY li.status ASC, li.due_date ASC
");
$issued_books->execute([$teacher_id]);
$my_books = $issued_books->fetchAll(PDO::FETCH_ASSOC);

// Fetch all digital resources for teachers
$resources = $pdo->query("
    SELECT r.*, c.class_name, s.subject_name 
    FROM digital_resources r
    LEFT JOIN classes c ON r.class_id = c.id
    LEFT JOIN subjects s ON r.subject_id = s.id
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch available books for search
$available_books = $pdo->query("
    SELECT b.title, b.author, b.isbn, b.available_quantity, c.category_name 
    FROM library_books b
    LEFT JOIN library_categories c ON b.category_id = c.id
    WHERE b.available_quantity > 0
    ORDER BY b.title ASC
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../";
$page_title = "Teacher Library | VIC School ERP";
$page_header = "Digital Research & Library";
$active_menu = "library";

require_once('includes/header.php');
require_once('includes/topbar.php');
?>

<div class="container mt-4">
    <ul class="nav nav-tabs mb-4" id="libraryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="digital-tab" data-bs-toggle="tab" data-bs-target="#digital" type="button" role="tab">Digital Resources</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="issued-tab" data-bs-toggle="tab" data-bs-target="#issued" type="button" role="tab">My Issued Books</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search" type="button" role="tab">Search Books</button>
        </li>
    </ul>

    <div class="tab-content" id="libraryTabsContent">
        <!-- Digital Resources Tab -->
        <div class="tab-pane fade show active" id="digital" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fa fa-folder-open me-2 text-primary"></i>Teaching Material</h5>
                    <input type="text" id="resourceSearch" class="form-control w-25" placeholder="Search resources...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="resourceTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Title</th>
                                    <th>Type</th>
                                    <th>Class / Subject</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($resources) > 0): ?>
                                    <?php foreach($resources as $r): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['resource_type']) ?></span></td>
                                            <td>
                                                <div class="text-dark"><?= htmlspecialchars($r['class_name'] ?: 'All Classes') ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($r['subject_name'] ?: 'All Subjects') ?></div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="<?= htmlspecialchars(strpos($r['file_path'], 'http') === 0 ? $r['file_path'] : $root_path . $r['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No resources available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Issued Books Tab -->
        <div class="tab-pane fade" id="issued" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Book Title</th>
                                    <th>Author</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($my_books) > 0): ?>
                                    <?php foreach ($my_books as $book): ?>
                                        <?php 
                                            $due_date = strtotime($book['due_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $days_late = ($book['status'] !== 'returned' && $today > $due_date) ? floor(($today - $due_date) / 86400) : 0;
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

        <!-- Search Books Tab -->
        <div class="tab-pane fade" id="search" role="tabpanel">
            <div class="card shadow-sm border-0">
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
    rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; });
});

document.getElementById('resourceSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#resourceTable tbody tr');
    rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; });
});
</script>

<?php require_once('includes/footer.php'); ?>
