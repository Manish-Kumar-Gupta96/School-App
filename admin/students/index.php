<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../includes/access.php');

requirePermission($pdo, 'students_view');

/* ==========================
SEARCH
========================== */
$search = $_GET['search'] ?? '';

$sql = "
SELECT *
FROM students
WHERE first_name LIKE :search1
OR last_name LIKE :search2
OR admission_no LIKE :search3
ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'search1' => "%$search%",
    'search2' => "%$search%",
    'search3' => "%$search%"
]);
$students = $stmt->fetchAll();

// Layout setup
$root_path = "../../";
$page_title = "Student Management | VIC ERP";
$page_header = "Student Management";
$active_menu = "students";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Manage and View Student Records</h5>
    <?php if (hasPermission($pdo, $_SESSION['user_id'], 'students_add')): ?>
    <a href="add.php" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Add Student
    </a>
    <?php endif; ?>
</div>

<!-- ALERTS -->
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-trash me-2"></i> Student Deleted Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- SEARCH -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by name or admission number..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">
            <i class="fa fa-search me-1"></i> Search
        </button>
    </div>
</form>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Photo</th>
                        <th>Admission No</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Phone</th>
                        <th class="text-center" width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach($students as $student): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($student['id']) ?></td>
                                <td>
                                    <?php if($student['photo'] && file_exists("../../uploads/students/" . $student['photo'])) : ?>
                                        <img src="../../uploads/students/<?= htmlspecialchars($student['photo']) ?>" width="45" height="45" style="object-fit:cover; border-radius:50%; border: 2px solid var(--primary);">
                                    <?php else : ?>
                                        <img src="../../assets/images/default-user.png" width="45" height="45" style="object-fit:cover; border-radius:50%;">
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($student['admission_no']) ?></span></td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($student['first_name']) ?> <?= htmlspecialchars($student['last_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($student['class'] . ' ' . $student['section']) ?></td>
                                <td><?= htmlspecialchars($student['phone']) ?></td>
                                <td class="text-center pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?= $student['id'] ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <?php if (hasPermission($pdo, $_SESSION['user_id'], 'students_edit')): ?>
                                        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-outline-warning btn-sm">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission($pdo, $_SESSION['user_id'], 'students_delete')): ?>
                                        <a href="delete.php?id=<?= $student['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student profile?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fa fa-user-slash fs-2 mb-2 d-block"></i>
                                No student records found.
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
