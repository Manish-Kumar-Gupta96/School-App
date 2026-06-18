<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
SAVE PARENT
========================== */
if(isset($_POST['save_parent'])){
    $parent_name = trim($_POST['parent_name']);
    $father_name = trim($_POST['father_name']);
    $mother_name = trim($_POST['mother_name']);
    $mobile      = trim($_POST['mobile']);
    $email       = trim($_POST['email']);
    $address     = trim($_POST['address']);
    $status      = $_POST['status'];

    if(empty($parent_name)){
        $error = "Parent Name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO parents(parent_name, father_name, mother_name, mobile, email, address, status)
            VALUES(?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $parent_name,
            $father_name,
            $mother_name,
            $mobile,
            $email,
            $address,
            $status
        ]);
        $message = "Parent Registered Successfully!";
    }
}

/* ==========================
LINK STUDENT
========================== */
if(isset($_POST['link_student'])){
    $parent_id = (int)$_POST['parent_id'];
    $student_id = (int)$_POST['student_id'];

    if(empty($parent_id) || empty($student_id)){
        $error = "Both Parent and Student are required to create a link.";
    } else {
        // Check if already linked
        $check = $pdo->prepare("SELECT COUNT(*) FROM parent_students WHERE parent_id=? AND student_id=?");
        $check->execute([$parent_id, $student_id]);
        if($check->fetchColumn() > 0){
            $error = "This student is already linked to the selected parent.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO parent_students (parent_id, student_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$parent_id, $student_id]);
            $message = "Student mapped to parent successfully!";
        }
    }
}

/* ==========================
LOAD PARENTS LIST WITH MAPPINGS
========================== */
$parents = $pdo->query("
    SELECT p.*, GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name, ' (', s.class, ')') SEPARATOR ', ') AS linked_students
    FROM parents p
    LEFT JOIN parent_students ps ON p.id = ps.parent_id
    LEFT JOIN students s ON ps.student_id = s.id
    GROUP BY p.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
LOAD STUDENTS FOR LINK SELECTOR
========================== */
$students = $pdo->query("
    SELECT id, CONCAT(first_name, ' ', last_name) AS student_name, class
    FROM students
    ORDER BY first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Parent Management | VIC ERP";
$page_header = "Parent Portal";
$active_menu = "parents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage school parent profiles and student linkage mappings</h5>
    <div class="d-flex gap-2">
        <a href="parent-list.php" class="btn btn-primary">
            <i class="fa fa-user-friends me-1"></i> Parent List
        </a>
        <a href="student-progress.php" class="btn btn-outline-primary">
            <i class="fa fa-chart-line me-1"></i> Student Progress
        </a>
        <a href="fee-status.php" class="btn btn-outline-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fee Status
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="notifications.php" class="btn btn-outline-primary">
            <i class="fa fa-bullhorn me-1"></i> Notifications
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
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
    <!-- REGISTRATION & LINKAGE CARDS -->
    <div class="col-lg-4 mb-4">
        <!-- Add Parent -->
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Add Parent</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Parent / Guardian Name <span class="text-danger">*</span></label>
                        <input type="text" name="parent_name" class="form-control" placeholder="Guardian Full Name" required>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Father Name</label>
                            <input type="text" name="father_name" class="form-control" placeholder="Father Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mother Name</label>
                            <input type="text" name="mother_name" class="form-control" placeholder="Mother Name">
                        </div>
                    </div>

                    <div class="row mb-3 text-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" placeholder="Mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="parent@example.com">
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" rows="2" class="form-control" placeholder="Home address..."></textarea>
                    </div>

                    <button type="submit" name="save_parent" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Parent
                    </button>
                </form>
            </div>
        </div>

        <!-- Student Linking mapping -->
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Link Student to Parent</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Choose Parent <span class="text-danger">*</span></label>
                        <select name="parent_id" class="form-select" required>
                            <option value="">Select Parent</option>
                            <?php foreach($parents as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['parent_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Choose Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['student_name']) ?> (Class <?= htmlspecialchars($student['class']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="link_student" class="btn btn-primary w-100">
                        <i class="fa fa-link me-1"></i> Map Student Link
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST PARENTS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Parent / Guardian</th>
                                <th>Contact Details</th>
                                <th>Mapped Students (Kids)</th>
                                <th>Family Details</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($parents) > 0): ?>
                                <?php foreach($parents as $row): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?= htmlspecialchars($row['id']) ?></td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($row['parent_name']) ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <?php if($row['mobile']): ?>
                                                    <span class="d-block text-dark"><i class="fa fa-phone me-1 text-muted small"></i><?= htmlspecialchars($row['mobile']) ?></span>
                                                <?php endif; ?>
                                                <?php if($row['email']): ?>
                                                    <span class="d-block text-muted"><i class="fa fa-envelope me-1 text-muted small"></i><?= htmlspecialchars($row['email']) ?></span>
                                                <?php endif; ?>
                                                <?php if(!$row['mobile'] && !$row['email']): ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($row['linked_students']): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle p-2 text-start d-inline-block small" style="white-space: normal; line-height: 1.4;">
                                                    <i class="fa fa-user-graduate me-1"></i><?= htmlspecialchars($row['linked_students']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small italic"><i class="fa fa-unlink me-1"></i>No student linked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <span class="d-block text-muted">Father: <?= htmlspecialchars($row['father_name'] ?: '-') ?></span>
                                            <span class="d-block text-muted">Mother: <?= htmlspecialchars($row['mother_name'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-user-friends fs-2 mb-2 d-block"></i>
                                        No parents registered in the database.
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
