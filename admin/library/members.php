<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
ADD MEMBER
========================== */
if(isset($_POST['add_member'])){
    $member_type = $_POST['member_type'];
    $member_id   = ($member_type === 'Student') ? (int)($_POST['student_id'] ?? 0) : (int)($_POST['teacher_id'] ?? 0);
    $card_no     = "LIB" . date('Y') . rand(1000,9999);

    if(empty($member_id)){
        $error = "Please select a valid Student or Teacher to add.";
    } else {
        // Check if member already exists
        $check = $pdo->prepare("SELECT id FROM library_members WHERE member_type = ? AND member_id = ?");
        $check->execute([$member_type, $member_id]);
        
        if ($check->rowCount() > 0) {
            $error = "This person is already registered as a library member.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO library_members(library_card_no, member_type, member_id, join_date, status)
                VALUES(?,?,?,CURDATE(),'Active')
            ");
            $stmt->execute([$card_no, $member_type, $member_id]);
            $message = "Library Member Added Successfully! Card No: " . $card_no;
        }
    }
}

/* ==========================
LOAD MEMBERS
========================== */
$members = $pdo->query("
    SELECT 
        lm.*,
        CASE 
            WHEN lm.member_type = 'Student' THEN CONCAT(s.first_name, ' ', s.last_name)
            WHEN lm.member_type = 'Teacher' THEN t.name
        END AS member_name,
        CASE 
            WHEN lm.member_type = 'Student' THEN s.admission_no
            WHEN lm.member_type = 'Teacher' THEN t.employee_id
        END AS member_code
    FROM library_members lm
    LEFT JOIN students s ON lm.member_type = 'Student' AND lm.member_id = s.id
    LEFT JOIN teachers t ON lm.member_type = 'Teacher' AND lm.member_id = t.id
    ORDER BY lm.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch students for selector
$students = $pdo->query("SELECT id, admission_no, first_name, last_name FROM students ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers for selector (using column name)
$teachers = $pdo->query("SELECT id, employee_id, name FROM teachers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Library Members | VIC ERP";
$page_header = "Library Membership Card Registrar";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Enroll and manage library members</h5>
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
    <!-- ADD MEMBERSHIP CARD -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Enroll New Member</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Member Type <span class="text-danger">*</span></label>
                        <select name="member_type" id="member_type" class="form-select" onchange="toggleMemberSelector()" required>
                            <option value="Student">Student</option>
                            <option value="Teacher">Teacher</option>
                        </select>
                    </div>

                    <!-- Student dropdown group -->
                    <div class="mb-4" id="student_group">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select">
                            <option value="">Choose Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Teacher dropdown group -->
                    <div class="mb-4 d-none" id="teacher_group">
                        <label class="form-label fw-semibold">Select Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="teacher_id" class="form-select">
                            <option value="">Choose Teacher...</option>
                            <?php foreach($teachers as $tch): ?>
                                <option value="<?= $tch['id'] ?>">
                                    <?= htmlspecialchars($tch['employee_id']) ?> - <?= htmlspecialchars($tch['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="add_member" class="btn btn-success w-100">
                        <i class="fa fa-id-badge me-1"></i> Issue Card
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MEMBERS LIST TABLE -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Library Registry List</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Library Card No</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Admission/Employee ID</th>
                                <th>Join Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($members) > 0): ?>
                                <?php foreach($members as $m): ?>
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-dark px-3 py-2 fw-mono fs-6"><?= htmlspecialchars($m['library_card_no']) ?></span></td>
                                        <td>
                                            <?php if($m['member_type'] === 'Student'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Student</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Teacher</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($m['member_name'] ?: 'Unknown') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($m['member_code'] ?: 'N/A') ?></span></td>
                                        <td><?= date('d M Y', strtotime($m['join_date'])) ?></td>
                                        <td>
                                            <?php if($m['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-users-slash fs-2 mb-2 d-block"></i>
                                        No registered library members.
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

<script>
    function toggleMemberSelector() {
        const type = document.getElementById('member_type').value;
        const studentGroup = document.getElementById('student_group');
        const teacherGroup = document.getElementById('teacher_group');
        
        const studentSelect = document.getElementById('student_id');
        const teacherSelect = document.getElementById('teacher_id');

        if(type === 'Student') {
            studentGroup.classList.remove('d-none');
            teacherGroup.classList.add('d-none');
            studentSelect.required = true;
            teacherSelect.required = false;
            teacherSelect.value = '';
        } else {
            studentGroup.classList.add('d-none');
            teacherGroup.classList.remove('d-none');
            studentSelect.required = false;
            teacherSelect.required = true;
            studentSelect.value = '';
        }
    }
    document.addEventListener("DOMContentLoaded", toggleMemberSelector);
</script>

<?php
require_once('../includes/footer.php');
?>
