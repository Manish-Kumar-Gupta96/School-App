<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE student_name LIKE ?
    OR application_no LIKE ?
    OR phone LIKE ?
    ORDER BY id DESC
");

$stmt->execute([
    "%$search%",
    "%$search%",
    "%$search%"
]);

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Admissions Management | VIC ERP";
$page_header = "Admissions Management";
$active_menu = "admissions";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Track and Process Student Admission Applications</h5>
    <a href="add.php" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Add Walk-in Application
    </a>
</div>

<!-- ALERTS -->
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-trash me-2"></i> Admission Deleted Successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['approved'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-user-check me-2"></i> Admission Approved Successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['rejected'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa fa-ban me-2"></i> Admission Rejected Successfully
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-check me-2"></i> Application Recorded Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa fa-check-double me-2"></i> Application Updated Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- SEARCH -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by student name, application number, or phone..." value="<?= htmlspecialchars($search) ?>">
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
                        <th>Application No</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-center" width="280">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($applications) > 0): ?>
                        <?php foreach($applications as $row): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($row['id']) ?></td>
                                <td class="fw-semibold"><span class="badge bg-secondary"><?= htmlspecialchars($row['application_no']) ?></span></td>
                                <td class="fw-semibold">
                                    <div class="d-flex align-items-center">
                                        <?php if($row['photo'] && file_exists("../../uploads/admissions/" . $row['photo'])) : ?>
                                            <img src="../../uploads/admissions/<?= htmlspecialchars($row['photo']) ?>" width="35" height="35" style="object-fit:cover; border-radius:50%; border: 2px solid var(--primary);" class="me-2">
                                        <?php else : ?>
                                            <img src="../../assets/images/default-user.png" width="35" height="35" style="object-fit:cover; border-radius:50%;" class="me-2">
                                        <?php endif; ?>
                                        <?= htmlspecialchars($row['student_name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['class_applied']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td>
                                    <?php if($row['status'] == 'Approved'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Approved</span>
                                    <?php elseif($row['status'] == 'Rejected'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="approve.php?id=<?= $row['id'] ?>" class="btn btn-outline-success btn-sm" onclick="return confirm('Approve this admission application and create a student profile?')">
                                                <i class="fa fa-check"></i> Approve
                                            </a>
                                            <a href="reject.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to reject this admission application?')">
                                                <i class="fa fa-ban"></i> Reject
                                            </a>
                                        <?php endif; ?>

                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-outline-warning btn-sm">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-outline-dark btn-sm" onclick="return confirm('Delete this application record permanently?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                                No admission applications found.
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
