<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT *
    FROM teachers
    WHERE name LIKE ?
    OR employee_id LIKE ?
    OR subject LIKE ?
    ORDER BY id DESC
");

$stmt->execute([
    "%$search%",
    "%$search%",
    "%$search%"
]);

$teachers = $stmt->fetchAll();

// Layout setup
$root_path = "../../";
$page_title = "Teacher Management | VIC ERP";
$page_header = "Teacher Management";
$active_menu = "teachers";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Manage and View Teacher Records</h5>
    <a href="add.php" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Add Teacher
    </a>
</div>

<!-- ALERTS -->
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-trash me-2"></i> Teacher Deleted Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-check me-2"></i> Teacher Added Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-check-double me-2"></i> Teacher Updated Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- SEARCH -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by name, employee ID or subject..." value="<?= htmlspecialchars($search) ?>">
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
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-center" width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($teachers) > 0): ?>
                        <?php foreach($teachers as $teacher): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($teacher['id']) ?></td>
                                <td>
                                    <?php if($teacher['photo'] && file_exists("../../uploads/teachers/" . $teacher['photo'])) : ?>
                                        <img src="../../uploads/teachers/<?= htmlspecialchars($teacher['photo']) ?>" width="45" height="45" style="object-fit:cover; border-radius:50%; border: 2px solid var(--primary);">
                                    <?php else : ?>
                                        <img src="../../assets/images/default-user.png" width="45" height="45" style="object-fit:cover; border-radius:50%;">
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($teacher['employee_id']) ?></span></td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($teacher['name']) ?>
                                </td>
                                <td><?= htmlspecialchars($teacher['subject']) ?></td>
                                <td><?= htmlspecialchars($teacher['phone']) ?></td>
                                <td>
                                    <?php if ($teacher['status'] == 'Active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?= $teacher['id'] ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <a href="edit.php?id=<?= $teacher['id'] ?>" class="btn btn-outline-warning btn-sm">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="delete.php?id=<?= $teacher['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this teacher profile?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa fa-user-slash fs-2 mb-2 d-block"></i>
                                No teacher records found.
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
