<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Fetch Dropdown Data
$teachers = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM teachers WHERE school_id = ?");
$teachers->execute([$schoolId]);
$teachers = $teachers->fetchAll(PDO::FETCH_ASSOC);

$classes = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ?");
$classes->execute([$schoolId]);
$classes = $classes->fetchAll(PDO::FETCH_ASSOC);

$sections = $pdo->prepare("SELECT id, section_name FROM sections WHERE school_id = ?");
$sections->execute([$schoolId]);
$sections = $sections->fetchAll(PDO::FETCH_ASSOC);

$subjects = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE school_id = ?");
$subjects->execute([$schoolId]);
$subjects = $subjects->fetchAll(PDO::FETCH_ASSOC);

// Fetch Existing Mappings
$stmt_maps = $pdo->prepare("
    SELECT tcs.id, CONCAT(t.first_name, ' ', t.last_name) as teacher_name, c.class_name, s.section_name, sub.subject_name
    FROM teacher_class_subjects tcs
    JOIN teachers t ON tcs.teacher_id = t.id
    JOIN classes c ON tcs.class_id = c.id
    JOIN sections s ON tcs.section_id = s.id
    JOIN subjects sub ON tcs.subject_id = sub.id
    WHERE tcs.school_id = ?
    ORDER BY tcs.created_at DESC
");
$stmt_maps->execute([$schoolId]);
$mappings = $stmt_maps->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../";
$page_title = "Teacher-Class Mapping | Admin Portal";
$page_header = "Academic Management";
$active_menu = "teacher_mapping";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Teacher to Class/Subject Mapping</h5>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h6 class="fw-bold mb-0">Assign Subject to Teacher</h6>
            </div>
            <div class="card-body p-4">
                <div id="alertMessage" class="d-none alert alert-info"></div>
                <form id="mappingForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Select Teacher --</option>
                            <?php foreach($teachers as $teacher): ?>
                                <option value="<?= $teacher['id']; ?>"><?= htmlspecialchars($teacher['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach($classes as $class): ?>
                                <option value="<?= $class['id']; ?>"><?= htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Section</label>
                        <select name="section_id" class="form-select" required>
                            <option value="">-- Select Section --</option>
                            <?php foreach($sections as $section): ?>
                                <option value="<?= $section['id']; ?>"><?= htmlspecialchars($section['section_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach($subjects as $subject): ?>
                                <option value="<?= $subject['id']; ?>"><?= htmlspecialchars($subject['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Assign Teacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h6 class="fw-bold mb-0">Existing Mappings</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Teacher</th>
                                <th>Class & Section</th>
                                <th>Subject</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($mappings as $map): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($map['teacher_name']) ?></td>
                                    <td><?= htmlspecialchars($map['class_name']) ?> - <?= htmlspecialchars($map['section_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($map['subject_name']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($mappings)): ?>
                                <tr><td colspan="3" class="text-center p-5 text-muted">No mappings exist.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$('#mappingForm').submit(function(e){
    e.preventDefault();
    $.ajax({
        url: 'ajax/save_teacher_mapping.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(response){
            $('#alertMessage').removeClass('d-none alert-danger').addClass('alert-success').text(response);
            setTimeout(() => location.reload(), 1500);
        },
        error: function(xhr) {
            $('#alertMessage').removeClass('d-none alert-success').addClass('alert-danger').text(xhr.responseText || "Error saving mapping");
        }
    });
});
</script>

<?php require_once('../includes/footer.php'); ?>
