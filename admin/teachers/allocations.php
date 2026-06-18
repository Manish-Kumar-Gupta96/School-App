<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Teacher Allocations & Mapping | Admin Control";
$active_menu = "teachers";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

// Load dropdown options
$teachers = $pdo->query("SELECT id, name, employee_id FROM teachers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$sections = $pdo->query("SELECT id, section_name FROM sections ORDER BY section_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT id, subject_name, class_name FROM subjects ORDER BY subject_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Subject Teacher Assignment Submission
if (isset($_POST['assign_subject_teacher'])) {
    $teacher_id    = (int)$_POST['teacher_id'];
    $class_id      = (int)$_POST['class_id'];
    $section_id    = (int)$_POST['section_id'];
    $subject_id    = (int)$_POST['subject_id'];
    $academic_year = trim($_POST['academic_year'] ?: '2026-27');

    if (empty($teacher_id) || empty($class_id) || empty($section_id) || empty($subject_id)) {
        $error = "All fields are required for Subject Teacher Assignment.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_class_assignments (teacher_id, class_id, section_id, subject_id, academic_year)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$teacher_id, $class_id, $section_id, $subject_id, $academic_year]);
            $assignment_id = $pdo->lastInsertId();

            // Log Audit
            require_once('../includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['name'] ?? 'Admin',
                $_SESSION['role'],
                "Assigned Teacher ID $teacher_id to Class ID $class_id - Section ID $section_id (Subject ID $subject_id)",
                'Teachers',
                $assignment_id
            );

            $message = "Subject Teacher Assignment saved successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Class Teacher Allocation Submission
if (isset($_POST['allocate_class_teacher'])) {
    $class_id   = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $teacher_id = (int)$_POST['teacher_id'];

    if (empty($class_id) || empty($section_id) || empty($teacher_id)) {
        $error = "All fields are required for Class Teacher Allocation.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO class_teachers (class_id, section_id, teacher_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)
            ");
            $stmt->execute([$class_id, $section_id, $teacher_id]);
            $allocation_id = $pdo->lastInsertId();

            // Log Audit
            require_once('../includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['name'] ?? 'Admin',
                $_SESSION['role'],
                "Allocated Teacher ID $teacher_id as Class Teacher for Class ID $class_id - Section ID $section_id",
                'Teachers',
                $allocation_id
            );

            $message = "Class Teacher Allocated successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Subject Assignment
if (isset($_GET['delete_subject_id'])) {
    $del_id = (int)$_GET['delete_subject_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM teacher_class_assignments WHERE id = ?");
        $stmt->execute([$del_id]);
        $message = "Subject Teacher Assignment removed successfully.";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete Class Teacher Allocation
if (isset($_GET['delete_class_teacher_id'])) {
    $del_id = (int)$_GET['delete_class_teacher_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM class_teachers WHERE id = ?");
        $stmt->execute([$del_id]);
        $message = "Class Teacher Allocation removed successfully.";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch dynamic Subject Assignments
$subject_assignments = $pdo->query("
    SELECT tca.id, t.name as teacher_name, t.employee_id, c.class_name, s.section_name, sub.subject_name, tca.academic_year
    FROM teacher_class_assignments tca
    JOIN teachers t ON tca.teacher_id = t.id
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    JOIN subjects sub ON tca.subject_id = sub.id
    ORDER BY c.id ASC, s.section_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch dynamic Class Teacher Allocations
$class_teacher_allocations = $pdo->query("
    SELECT ct.id, t.name as teacher_name, t.employee_id, c.class_name, s.section_name
    FROM class_teachers ct
    JOIN teachers t ON ct.teacher_id = t.id
    JOIN classes c ON ct.class_id = c.id
    JOIN sections s ON ct.section_id = s.id
    ORDER BY c.id ASC, s.section_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">👨‍🏫 Class & Subject Teacher Mapping</h2>
            <p class="text-muted mb-0">Assign subject teachers to specific classes/sections and appoint class incharges.</p>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" id="allocationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="subject-tab" data-bs-toggle="tab" data-bs-target="#subject-pane" type="button" role="tab" aria-controls="subject-pane" aria-selected="true">
                📚 Subject Teacher Assignments
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="class-tab" data-bs-toggle="tab" data-bs-target="#class-pane" type="button" role="tab" aria-controls="class-pane" aria-selected="false">
                🎓 Class Teacher Incharges
            </button>
        </li>
    </ul>

    <!-- Tab panes Content -->
    <div class="tab-content" id="allocationTabsContent">
        <!-- Subject Teacher Tab -->
        <div class="tab-pane fade show active" id="subject-pane" role="tabpanel" aria-labelledby="subject-tab">
            <div class="row g-4">
                <!-- Assign Form -->
                <div class="col-xl-4">
                    <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-plus-circle me-2 text-primary"></i>Assign Subject Teacher</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Teacher</label>
                                <select name="teacher_id" class="form-select" required>
                                    <option value="">Choose Teacher...</option>
                                    <?php foreach($teachers as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['employee_id']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Class</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Choose Class...</option>
                                    <?php foreach($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Section</label>
                                <select name="section_id" class="form-select" required>
                                    <option value="">Choose Section...</option>
                                    <?php foreach($sections as $s): ?>
                                        <option value="<?= $s['id'] ?>">Section <?= htmlspecialchars($s['section_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Subject</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">Choose Subject...</option>
                                    <?php foreach($subjects as $sub): ?>
                                        <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['class_name']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Academic Year</label>
                                <input type="text" name="academic_year" value="2026-27" class="form-control" placeholder="e.g. 2026-27" required>
                            </div>

                            <button type="submit" name="assign_subject_teacher" class="btn btn-primary w-100 fw-semibold">
                                <i class="fa fa-save me-1"></i> Assign Teacher
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Assignment Grid -->
                <div class="col-xl-8">
                    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; min-height: 400px;">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Teacher</th>
                                            <th>Class & Section</th>
                                            <th>Subject</th>
                                            <th>Session</th>
                                            <th class="pe-4 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($subject_assignments) > 0): ?>
                                            <?php foreach($subject_assignments as $sa): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($sa['teacher_name']) ?></div>
                                                        <span class="text-muted small">Emp ID: <?= htmlspecialchars($sa['employee_id']) ?></span>
                                                    </td>
                                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($sa['class_name']) ?> - Section <?= htmlspecialchars($sa['section_name']) ?></td>
                                                    <td><span class="badge bg-primary px-3 py-2" style="font-size: 0.8rem;"><?= htmlspecialchars($sa['subject_name']) ?></span></td>
                                                    <td class="text-muted font-monospace"><?= htmlspecialchars($sa['academic_year']) ?></td>
                                                    <td class="pe-4 text-center">
                                                        <a href="?delete_subject_id=<?= $sa['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this assignment?');">
                                                            <i class="fa fa-trash-alt me-1"></i> Remove
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">
                                                    <i class="fa fa-book fs-1 d-block mb-3 text-secondary"></i>
                                                    No subject teacher assignments loaded yet.
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
        </div>

        <!-- Class Teacher Tab -->
        <div class="tab-pane fade" id="class-pane" role="tabpanel" aria-labelledby="class-tab">
            <div class="row g-4">
                <!-- Assign Class Form -->
                <div class="col-xl-4">
                    <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-user-plus me-2 text-success"></i>Appoint Class Incharge</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Class</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">Choose Class...</option>
                                    <?php foreach($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Section</label>
                                <select name="section_id" class="form-select" required>
                                    <option value="">Choose Section...</option>
                                    <?php foreach($sections as $s): ?>
                                        <option value="<?= $s['id'] ?>">Section <?= htmlspecialchars($s['section_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Select Class Teacher</label>
                                <select name="teacher_id" class="form-select" required>
                                    <option value="">Choose Teacher...</option>
                                    <?php foreach($teachers as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['employee_id']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="allocate_class_teacher" class="btn btn-success w-100 fw-semibold">
                                <i class="fa fa-save me-1"></i> Allocate Class Teacher
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Class Incharge Grid -->
                <div class="col-xl-8">
                    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; min-height: 400px;">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Class & Section</th>
                                            <th>Class Teacher Incharge</th>
                                            <th>Employee ID</th>
                                            <th class="pe-4 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($class_teacher_allocations) > 0): ?>
                                            <?php foreach($class_teacher_allocations as $ct): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($ct['class_name']) ?> - Section <?= htmlspecialchars($ct['section_name']) ?></td>
                                                    <td class="fw-semibold text-primary"><?= htmlspecialchars($ct['teacher_name']) ?></td>
                                                    <td class="text-muted font-monospace"><?= htmlspecialchars($ct['employee_id']) ?></td>
                                                    <td class="pe-4 text-center">
                                                        <a href="?delete_class_teacher_id=<?= $ct['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this class teacher allocation?');">
                                                            <i class="fa fa-trash-alt me-1"></i> Remove
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="fa fa-user-circle fs-1 d-block mb-3 text-secondary"></i>
                                                    No Class Teacher Incharges allocated yet.
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
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
