<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../includes/Csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

new BaseController();
// Secure Lock: Sirf login kiya hua teacher ya admin hi ise process kar sakta hai
BaseController::enforceRole(['teacher', 'admin']);

$db = getDBConnection();
$message = '';
$status = true;

// Dynamic simulation: Maan lete hain login teacher Class 10 ka class teacher hai
$myAssignedClass = 'Class 10'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_verify'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Validation Failed.");
    }

    $studentId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $decision = filter_input(INPUT_POST, 'decision', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($studentId && in_array($decision, ['APPROVE', 'REJECT'])) {
        try {
            $db->beginTransaction();

            if ($decision === 'APPROVE') {
                $stmt = $db->prepare("UPDATE students SET verification_status = 'ACTIVE' WHERE id = :id AND class_name = :class");
                $stmt->execute([':id' => $studentId, ':class' => $myAssignedClass]);
                
                // 2. Fetch student account credentials details to trigger communication broadcast
                $studQuery = $db->prepare("SELECT name, email, roll_no FROM students WHERE id = :id");
                $studQuery->execute([':id' => $studentId]);
                $studentData = $studQuery->fetch();

                if ($studentData) {
                    $defaultTempPassword = "student" . $studentData['roll_no'] . "@2026"; 
                    require_once __DIR__ . '/../includes/EmailProvider.php';
                    EmailProvider::sendWelcomeCredentials($studentData['email'], $studentData['name'], $defaultTempPassword, 'student');
                }
                
                AuditLogger::log('STUDENT_VERIFIED', "Class Teacher approved student ID #{$studentId} and credentials piped to email link.");
                $message = "Student allocation confirmed! Access details auto-forwarded to their respective email addresses.";
            } else {
                $stmt = $db->prepare("UPDATE students SET verification_status = 'REJECTED' WHERE id = :id AND class_name = :class");
                $stmt->execute([':id' => $studentId, ':class' => $myAssignedClass]);
                $message = "Student allocation declined.";
            }

            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "Database Error: " . $e->getMessage();
            $status = false;
        }
    }
}

// Fetch pending bache jo verification ke liye queue me hain
$stmt = $db->prepare("SELECT id, name, roll_no, created_at FROM students WHERE class_name = :class AND verification_status = 'PENDING_VERIFICATION'");
$stmt->execute([':class' => $myAssignedClass]);
$pendingStudents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Verification Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 800px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white font-weight-bold">
            <h6 class="mb-0">Class Verification System Hub — <?php echo $myAssignedClass; ?></h6>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> rounded-0 mb-0 small"><?php echo $message; ?></div>
            <?php endif; ?>

            <table class="table table-striped align-middle mb-0 text-center table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Proposed Roll</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendingStudents) === 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Is class me filhal koi pending admission validation nahi hai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingStudents as $row): ?>
                        <tr>
                            <td><code>#<?php echo $row['id']; ?></code></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><code><?php echo $row['roll_no']; ?></code></td>
                            <td>
                                <form action="" method="POST" class="d-inline-flex gap-2 align-items-center">
                                    <?php Csrf::injectInput(); ?>
                                    <input type="hidden" name="student_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action_verify" onclick="this.form.decision.value='APPROVE'" class="btn btn-success btn-sm py-0 font-weight-bold">Verify</button>
                                    <button type="submit" name="action_verify" onclick="this.form.decision.value='REJECT'" class="btn btn-outline-danger btn-sm py-0">Deny</button>
                                    <a href="/school-app/admin/admissions/view-docs.php?student_id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0">
                                        <i class="fas fa-eye"></i> Docs
                                    </a>
                                    <input type="hidden" name="decision" value="">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
