<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_doc') {
        $student_id = (int)$_POST['student_id'];
        $template_id = (int)$_POST['template_id'];
        
        if ($student_id > 0 && $template_id > 0) {
            // Fetch Template
            $stmt = $pdo->prepare("SELECT * FROM document_templates WHERE id=? AND school_id=?");
            $stmt->execute([$template_id, $school_id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fetch Student Data
            $stmt = $pdo->prepare("SELECT s.*, p.father_name FROM students s LEFT JOIN parent_student_map psm ON s.id=psm.student_id LEFT JOIN parents p ON psm.parent_id=p.id WHERE s.id=? AND s.school_id=?");
            $stmt->execute([$student_id, $school_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template && $student) {
                // Determine Document Series
                $doc_no = strtoupper(substr($template['document_name'], 0, 3)) . "-" . date('Y') . "-" . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
                $verify_token = bin2hex(random_bytes(16));
                
                // Perform Replacements
                $html = $template['template_html'];
                $html = str_replace('{{student_name}}', htmlspecialchars($student['first_name'] . ' ' . $student['last_name']), $html);
                $html = str_replace('{{father_name}}', htmlspecialchars($student['father_name'] ?? 'N/A'), $html);
                $html = str_replace('{{class_name}}', htmlspecialchars($student['class'] ?? 'N/A'), $html);
                $html = str_replace('{{admission_no}}', htmlspecialchars($student['admission_number'] ?? 'N/A'), $html);
                $html = str_replace('{{dob}}', htmlspecialchars($student['date_of_birth'] ?? 'N/A'), $html);
                $html = str_replace('{{issue_date}}', date('d M Y'), $html);
                $html = str_replace('{{document_no}}', $doc_no, $html);
                $html = str_replace('{{qr_code_url}}', "https://vicschool.com/verify/" . $verify_token, $html); // Mock QR
                
                // Save record
                $stmt = $pdo->prepare("INSERT INTO generated_documents (school_id, document_type, reference_id, document_no, verification_token, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $template['document_name'], $student_id, $doc_no, $verify_token, $_SESSION['admin_id']]);
                $doc_id = $pdo->lastInsertId();
                
                // Output Preview/PDF
                // In a real scenario, we would use TCPDF or Dompdf to stream a PDF.
                // Here we will just set a success session and display the HTML preview block.
                $_SESSION['success'] = "Document ($doc_no) generated successfully!";
                $_SESSION['preview_html'] = $html;
            } else {
                $_SESSION['error'] = "Invalid student or template.";
            }
        }
        header("Location: generate.php");
        exit;
    }
}

// Fetch basic data for dropdowns
$templates = $pdo->query("SELECT * FROM document_templates WHERE school_id=$school_id")->fetchAll(PDO::FETCH_ASSOC);
$students = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name, ' (', admission_number, ')') as name FROM students WHERE school_id=$school_id ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent docs
$recent_docs = $pdo->query("
    SELECT g.*, CONCAT(s.first_name, ' ', s.last_name) as student_name
    FROM generated_documents g 
    LEFT JOIN students s ON g.reference_id = s.id 
    WHERE g.school_id=$school_id 
    ORDER BY g.created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Generate Document | VIC School ERP";
$page_header = "Certificate Generation";
$active_menu = "certificates";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-print me-2"></i>Generate Student Document</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="generate_doc">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Template <span class="text-danger">*</span></label>
                            <select name="template_id" class="form-select" required>
                                <option value="">-- Choose Document --</option>
                                <?php foreach($templates as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['document_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Student <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select" required>
                                <option value="">-- Choose Student --</option>
                                <?php foreach($students as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2"><i class="fa fa-file-pdf me-2"></i> Generate PDF & Save</button>
                    </form>
                </div>
            </div>

            <!-- Recent Docs -->
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-history me-2 text-info"></i>Recently Generated</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                <?php foreach($recent_docs as $d): ?>
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($d['document_type']) ?></div>
                                        <div class="text-muted small">
                                            <span class="badge bg-secondary"><?= htmlspecialchars($d['document_no']) ?></span> 
                                            <?= htmlspecialchars($d['student_name']) ?>
                                        </div>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button class="btn btn-sm btn-outline-success"><i class="fa fa-download"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-7">
            <!-- Output Preview -->
            <div class="card shadow-sm border-0 h-100" style="border-radius: 15px; background: #f8f9fa;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-eye me-2 text-secondary"></i>Live Document Preview</h5>
                    <?php if (isset($_SESSION['preview_html'])): ?>
                        <button class="btn btn-sm btn-success"><i class="fa fa-print me-1"></i>Print PDF</button>
                    <?php endif; ?>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4 overflow-auto">
                    <?php if (isset($_SESSION['preview_html'])): ?>
                        <div class="bg-white p-5 shadow-sm border w-100 h-100" style="max-width: 800px; min-height: 500px;">
                            <?= $_SESSION['preview_html']; unset($_SESSION['preview_html']); ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fa fa-file-invoice fs-1 mb-3 text-secondary opacity-50"></i>
                            <h5 class="fw-bold">No Document Generated</h5>
                            <p>Select a template and student to generate a document.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
