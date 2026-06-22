<?php
require_once('../config/database.php');
$active_menu = "assignment";
$page_title = "Create Assignment | Teacher Portal";
require_once('includes/header.php');

$message = '';
$error = '';

if(isset($_POST['create'])){
    $class       = trim($_POST['class']);
    $subject     = trim($_POST['subject']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date    = $_POST['due_date'];
    
    $path = '';

    if(empty($class) || empty($subject) || empty($title)){
        $error = "Class, Subject, and Title are required.";
    } else {
        if(!empty($_FILES['file']['name'])){
            $file_name = $_FILES['file']['name'];
            $tmp       = $_FILES['file']['tmp_name'];
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if(!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg'])){
                $error = "Invalid file type. Only PDF, DOC, DOCX, JPG, and PNG are allowed.";
            } else {
                $upload_dir = "../uploads/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $new_filename = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name);
                $path = "uploads/" . $new_filename;
                move_uploaded_file($tmp, $upload_dir . $new_filename);
            }
        }

        if(empty($error)){
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO assignments (class, subject, title, description, due_date, file_path, created_by)
                    VALUES (?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                    $class,
                    $subject,
                    $title,
                    $description,
                    $due_date,
                    $path,
                    $_SESSION['teacher_id']
                ]);
                $assignment_id = $pdo->lastInsertId();

                // Resolve class_id, section_id, and subject_id for new columns compatibility
                $c_name = $class;
                $s_name = '';
                if (preg_match('/^([^-]+)-([A-Z])$/', $class, $m)) {
                    $c_name = $m[1];
                    $s_name = $m[2];
                }

                $stmt_c_id = $pdo->prepare("SELECT id FROM classes WHERE class_name = ? OR class_name = ?");
                $stmt_c_id->execute([$c_name, $class]);
                $res_c_id = (int)$stmt_c_id->fetchColumn();

                $stmt_s_id = $pdo->prepare("SELECT id FROM sections WHERE section_name = ?");
                $stmt_s_id->execute([$s_name]);
                $res_s_id = (int)$stmt_s_id->fetchColumn();

                $stmt_sub_id = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ?");
                $stmt_sub_id->execute([$subject]);
                $res_sub_id = (int)$stmt_sub_id->fetchColumn();

                $stmt_upd_ids = $pdo->prepare("
                    UPDATE assignments 
                    SET class_id = ?, section_id = ?, subject_id = ?, teacher_id = ? 
                    WHERE id = ?
                ");
                $stmt_upd_ids->execute([
                    $res_c_id > 0 ? $res_c_id : NULL,
                    $res_s_id > 0 ? $res_s_id : NULL,
                    $res_sub_id > 0 ? $res_sub_id : NULL,
                    (int)$_SESSION['teacher_id'],
                    $assignment_id
                ]);

                // Notify students & parents
                require_once('../helpers/notification_helper.php');
                
                // Fetch students
                $stmt_stud = $pdo->prepare("SELECT id, email, phone AS mobile FROM students WHERE class = ? OR class = ? OR (class = ? AND section = ?)");
                $stmt_stud->execute([$class, $c_name, $c_name, $s_name]);
                $students_list = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

                $stud_notif_rec = [];
                foreach ($students_list as $st) {
                    $stud_notif_rec[] = ['id' => $st['id'], 'type' => 'STUDENT', 'email' => $st['email'], 'mobile' => $st['mobile']];
                }

                if (!empty($stud_notif_rec)) {
                    $title_notif = "📚 New Homework Assignment: " . $title;
                    $msg_notif = "Subject: {$subject}\nTitle: $title\nDue Date: " . date('d M Y', strtotime($due_date)) . "\n\nPlease complete and submit on time.";
                    sendSystemNotification($pdo, $title_notif, $msg_notif, $stud_notif_rec);
                }

                // Fetch parents
                $stmt_parents = $pdo->prepare("
                    SELECT DISTINCT u.id, u.email, p.phone AS mobile
                    FROM users u
                    JOIN parent_student_map psm ON u.id = psm.parent_id
                    JOIN students s ON psm.student_id = s.id
                    LEFT JOIN parents p ON p.email = u.email
                    WHERE s.class = ? OR s.class = ? OR (s.class = ? AND s.section = ?)
                ");
                $stmt_parents->execute([$class, $c_name, $c_name, $s_name]);
                $parents_list = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

                $parent_notif_rec = [];
                foreach ($parents_list as $pr) {
                    $parent_notif_rec[] = ['id' => $pr['id'], 'type' => 'PARENT', 'email' => $pr['email'], 'mobile' => $pr['mobile']];
                }

                if (!empty($parent_notif_rec)) {
                    $title_p_notif = "📚 New Assignment Assigned to Child";
                    $msg_p_notif = "Dear Parent,\n\nA new assignment '{$title}' has been published for your child in {$subject}.\n\nDue Date: " . date('d M Y', strtotime($due_date)) . "\n\nPlease ensure your child completes it on time.";
                    sendSystemNotification($pdo, $title_p_notif, $msg_p_notif, $parent_notif_rec);
                }

                $pdo->commit();
                $message = "Assignment created and assigned successfully!";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to create assignment: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📝 Create Assignment</h2>
        <p class="text-muted mb-0">Publish homework worksheets or task guidelines with due dates and attachments.</p>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 p-4 mb-4 text-start" style="border-radius: 12px; max-width: 700px;">
    <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-plus me-2 text-success"></i>New Homework Task</h5>
    
    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Class Group</label>
            <input type="text" name="class" class="form-control" placeholder="e.g. 10-A" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Subject</label>
            <input type="text" name="subject" class="form-control" placeholder="e.g. Science" required>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">Assignment Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Physics Lab Worksheet 3" required>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">Detailed Instructions</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Enter questions or requirements description..."></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Due Date</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Attach Worksheet File (Optional)</label>
            <input type="file" name="file" class="form-control">
        </div>

        <div class="col-md-12 mt-4 text-end">
            <button type="submit" name="create" class="btn btn-success px-5 py-2 fw-semibold">
                <i class="fa fa-save me-1"></i> Create Assignment
            </button>
        </div>
    </form>
</div>

<?php require_once('includes/footer.php'); ?>
