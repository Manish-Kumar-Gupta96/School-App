<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['admin', 'superadmin']);

$db = getDBConnection();
$message = '';
$status = true;

// ACTION 1: Handle Subject Category Insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $catName = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (!empty($catName)) {
        try {
            $stmt = $db->prepare("INSERT INTO subject_categories (category_name) VALUES (:name) ON DUPLICATE KEY UPDATE category_name=:name");
            $stmt->execute([':name' => $catName]);
            $message = "Subject Category '$catName' dynamically added!";
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); $status = false; }
    }
}

// ACTION 2: Handle Master Subject Insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $subName = filter_input(INPUT_POST, 'subject_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $catId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    
    if (!empty($subName) && $catId) {
        try {
            $stmt = $db->prepare("INSERT INTO school_subjects (subject_name, category_id) VALUES (:name, :cat_id)");
            $stmt->execute([':name' => $subName, ':cat_id' => $catId]);
            $message = "Subject '$subName' successfully registered in master system.";
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); $status = false; }
    }
}

// ACTION 3: Handle Final Class-Section-Subject-Teacher Mapping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['map_structure'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $className = filter_input(INPUT_POST, 'class_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $sectionName = filter_input(INPUT_POST, 'section_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $teacherId = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);

    if (!empty($className) && !empty($sectionName) && $subjectId && $teacherId) {
        try {
            $stmt = $db->prepare("INSERT INTO unified_academic_mapping (class_name, section_name, subject_id, teacher_id) 
                                  VALUES (:class, :sec, :sub_id, :tid)
                                  ON DUPLICATE KEY UPDATE teacher_id = :tid");
            $stmt->execute([':class' => $className, ':sec' => $sectionName, ':sub_id' => $subjectId, ':tid' => $teacherId]);
            AuditLogger::log('ACADEMIC_MAP_LOCKED', "Mapped $className-$sectionName with Subject ID #$subjectId");
            $message = "Perfect Allocation! Class, Section, Subject aur Teacher successfully connect ho gaye hain.";
        } catch (PDOException $e) { $message = "Mapping Error: " . $e->getMessage(); $status = false; }
    }
}

// Fetch Master Lookups for View Generation
$categories = $db->query("SELECT * FROM subject_categories ORDER BY category_name ASC")->fetchAll();
$subjects = $db->query("SELECT s.id, s.subject_name, c.category_name FROM school_subjects s JOIN subject_categories c ON s.category_id = c.id ORDER BY s.subject_name ASC")->fetchAll();
$teachers = $db->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username ASC")->fetchAll();
$activeGrid = $db->query("SELECT m.class_name, m.section_name, s.subject_name, c.category_name, u.username as teacher_name 
                          FROM unified_academic_mapping m 
                          JOIN school_subjects s ON m.subject_id = s.id 
                          JOIN subject_categories c ON s.category_id = c.id
                          JOIN users u ON m.teacher_id = u.id 
                          ORDER BY m.class_name ASC, m.section_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Architecture Management Core</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>.panel-card { border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }</style>
</head>
<body class="bg-light">
<div class="container-fluid px-4 my-5">
    <h4 class="fw-bold text-dark mb-4"><i class="fas fa-graduation-cap"></i> School Structure & Category Alignment System</h4>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small py-2"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card panel-card mb-3">
                <div class="card-header bg-dark text-white fw-bold py-2 small">1. Add New Subject Category</div>
                <div class="card-body py-2">
                    <form action="" method="POST">
                        <?php Csrf::injectInput(); ?>
                        <div class="input-group">
                            <input type="text" name="category_name" class="form-control form-control-sm" placeholder="e.g., Scholastic Theory, Lab Extra" required>
                            <button type="submit" name="add_category" class="btn btn-sm btn-primary">Save Cat</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card panel-card mb-3">
                <div class="card-header bg-dark text-white fw-bold py-2 small">2. Register Subject inside Category</div>
                <div class="card-body py-2">
                    <form action="" method="POST">
                        <?php Csrf::injectInput(); ?>
                        <div class="mb-2">
                            <input type="text" name="subject_name" class="form-control form-control-sm" placeholder="Subject Name (e.g., Biology)" required>
                        </div>
                        <div class="mb-2">
                            <select name="category_id" class="form-select form-select-sm" required>
                                <option value="">-- Choose Category --</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_subject" class="btn btn-sm btn-secondary w-100 fw-bold">Register Subject</button>
                    </form>
                </div>
            </div>

            <div class="card panel-card">
                <div class="card-header bg-primary text-white fw-bold py-2 small">3. Lock Class-Section-Subject-Teacher Layer</div>
                <div class="card-body py-3">
                    <form action="" method="POST">
                        <?php Csrf::injectInput(); ?>
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="class_name" class="form-select form-select-sm" required>
                                    <option value="Class 10">Class 10</option>
                                    <option value="Class 11">Class 11</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="section_name" class="form-select form-select-sm" required>
                                    <option value="Sec-A">Sec-A</option>
                                    <option value="Sec-B">Sec-B</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <select name="subject_id" class="form-select form-select-sm" required>
                                    <option value="">-- Select Mapped Subject --</option>
                                    <?php foreach($subjects as $sub): ?>
                                        <option value="<?php echo $sub['id']; ?>"><?php echo $sub['subject_name']; ?> (<?php echo $sub['category_name']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <select name="teacher_id" class="form-select form-select-sm" required>
                                    <option value="">-- Choose Educator Faculty --</option>
                                    <?php foreach($teachers as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="map_structure" class="btn btn-sm btn-success w-100 fw-bold mt-3">Establish Live Matrix Connection</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card panel-card border-0">
                <div class="card-header bg-white font-weight-bold text-dark border-bottom py-3">Active Institutional Allocation Array (Clean Structure Logs)</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped align-middle text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Class Alignment</th>
                                <th>Section Division</th>
                                <th>Subject Realm</th>
                                <th>Category Scope</th>
                                <th>Assigned Faculty Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($activeGrid as $grid): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($grid['class_name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($grid['section_name']); ?></span></td>
                                <td><span class="text-primary fw-bold"><?php echo htmlspecialchars($grid['subject_name']); ?></span></td>
                                <td><small class="text-muted fw-semibold"><?php echo htmlspecialchars($grid['category_name']); ?></small></td>
                                <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($grid['teacher_name']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
