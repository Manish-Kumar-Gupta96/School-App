<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Open Support Ticket | Student Portal";
$active_menu = "helpdesk";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Load student details
$stmt_stud = $pdo->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
$stmt_stud->execute([$student_id]);
$student = $stmt_stud->fetch(PDO::FETCH_ASSOC);
$fullName = ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '');

// Load ticket categories
$categories = $pdo->query("SELECT * FROM ticket_categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['create_ticket'])) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $priority = $_POST['priority']; // 'LOW', 'MEDIUM', 'HIGH', 'URGENT'

    if (empty($subject) || empty($description) || empty($category_id)) {
        $error = "Subject, Category, and Description are required.";
    } else {
        try {
            $pdo->beginTransaction();

            $ticket_no = 'TKT-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Insert ticket
            $stmt = $pdo->prepare("
                INSERT INTO tickets (ticket_no, user_id, user_type, subject, description, category_id, priority, status)
                VALUES (?, ?, 'STUDENT', ?, ?, ?, ?, 'OPEN')
            ");
            $stmt->execute([
                $ticket_no,
                $student_id,
                $subject,
                $description,
                $category_id,
                $priority
            ]);
            $ticket_id = $pdo->lastInsertId();

            // Handle Attachment Upload
            if (!empty($_FILES['attachment']['name'])) {
                $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['attachment']['name']);
                
                // Create folder if not exists
                $upload_dir = '../admin/helpdesk/attachments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $file_name)) {
                    $stmt_attach = $pdo->prepare("
                        INSERT INTO ticket_attachments (ticket_id, file_name)
                        VALUES (?, ?)
                    ");
                    $stmt_attach->execute([$ticket_id, $file_name]);
                }
            }

            $pdo->commit();
            $message = "Support Ticket " . $ticket_no . " opened successfully!";

            // Log Audit
            require_once('../admin/includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $student_id,
                $fullName,
                'Student',
                'Ticket Raised: ' . $ticket_no,
                'Helpdesk',
                $ticket_id
            );
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<div class="main-dashboard text-start" style="padding: 20px 0;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">🎫 Open Support Ticket</h2>
            <p class="text-muted mb-0">Open an inquiry for academics, tech support, or other school matters.</p>
        </div>
        <a href="tickets.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Support List
        </a>
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

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-header bg-primary text-white py-3" style="border-radius: 15px 15px 0 0;">
                    <h5 class="fw-bold mb-0"><i class="fa fa-plus-circle me-2"></i>New Help Request Ticket</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Subject / Title *</label>
                                <input type="text" name="subject" class="form-control" placeholder="e.g. Incomplete syllabus in Math" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Ticket Category *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category...</option>
                                    <?php foreach($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Severity / Priority *</label>
                                <select name="priority" class="form-select" required>
                                    <option value="LOW">Low</option>
                                    <option value="MEDIUM" selected>Medium</option>
                                    <option value="HIGH">High</option>
                                    <option value="URGENT">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Detailed Description *</label>
                            <textarea name="description" class="form-control" rows="8" placeholder="Please elaborate on your request or issue in detail..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Upload Attachment (Screenshot / Document)</label>
                            <input type="file" name="attachment" class="form-control">
                            <div class="form-text">Accepted formats: Images (JPG, PNG), PDF, TXT. Max size 2MB.</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="create_ticket" class="btn btn-success px-5 py-2 fw-semibold">
                                <i class="fa fa-check me-1"></i> Submit Ticket
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
