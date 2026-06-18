<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle Create / Edit Form Submission
if (isset($_POST['save_faq'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);

    if (empty($question) || empty($answer)) {
        $error = 'Question and Answer are required fields.';
    } else {
        try {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE faq_questions SET question = ?, answer = ? WHERE id = ?");
                $stmt->execute([$question, $answer, $id]);
                $success = 'FAQ updated successfully.';
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO faq_questions (question, answer) VALUES (?, ?)");
                $stmt->execute([$question, $answer]);
                $success = 'FAQ saved successfully.';
            }
        } catch (PDOException $e) {
            $error = 'Error saving FAQ: ' . $e->getMessage();
        }
    }
}

// Handle Delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM faq_questions WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'FAQ deleted successfully.';
    } catch (PDOException $e) {
        $error = 'Error deleting FAQ: ' . $e->getMessage();
    }
}

// Fetch all FAQs
$faqs = [];
try {
    $faqs = $pdo->query("SELECT * FROM faq_questions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database connection error: ' . $e->getMessage();
}

// Fetch single FAQ if editing
$edit_faq = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM faq_questions WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_faq = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silent
    }
}

$page_title = "Manage FAQs | VIC ERP";
$page_header = "AI Local FAQ Repository";
$active_menu = "ai-dashboard";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Local FAQ Database Manager</h2>
    </div>

    <!-- Quick sub-menu tabs -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="analytics.php" class="btn btn-sm btn-light">Dashboard Analytics</a>
            <a href="chat-history.php" class="btn btn-sm btn-light">Conversation History</a>
            <a href="faq-manager.php" class="btn btn-sm btn-primary">FAQ Database</a>
            <a href="admission-leads.php" class="btn btn-sm btn-light">Admission Leads</a>
            <a href="ai-settings.php" class="btn btn-sm btn-light">AI Configuration Settings</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add / Edit Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="fa <?= $edit_faq ? 'fa-edit text-warning' : 'fa-plus-circle text-primary' ?> me-2"></i>
                    <?= $edit_faq ? 'Modify FAQ Entry' : 'Add FAQ Entry' ?>
                </h5>
                <hr class="text-muted mt-0 mb-4">
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?= $edit_faq ? $edit_faq['id'] : 0 ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Question / Trigger Phrase</label>
                        <input type="text" name="question" class="form-control" placeholder="e.g. What is the school timing?" value="<?= $edit_faq ? htmlspecialchars($edit_faq['question']) : '' ?>" required style="border-radius: 8px;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">FAQ Answer Text</label>
                        <textarea name="answer" class="form-control" placeholder="Answer matching Levenshtein/similar queries..." rows="4" required style="border-radius: 8px;"><?= $edit_faq ? htmlspecialchars($edit_faq['answer']) : '' ?></textarea>
                    </div>

                    <button type="submit" name="save_faq" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                        <i class="fa fa-save me-2"></i> Save FAQ
                    </button>
                    
                    <?php if ($edit_faq): ?>
                        <a href="faq-manager.php" class="btn btn-outline-secondary w-100 mt-2 py-2 fw-semibold" style="border-radius: 8px;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- FAQs List Grid -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-success"></i>Configured FAQs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Question</th>
                                <th>Answer</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($faqs) > 0): ?>
                                <?php foreach ($faqs as $faq): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark" style="max-width: 200px;"><?= htmlspecialchars($faq['question']) ?></td>
                                        <td class="text-muted small" style="max-width: 300px;"><?= htmlspecialchars($faq['answer']) ?></td>
                                        <td class="pe-4 text-end">
                                            <a href="?edit=1&id=<?= $faq['id'] ?>" class="btn btn-sm btn-outline-warning me-1">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <a href="?delete=1&id=<?= $faq['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove this FAQ?')">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        No FAQs registered. Use the left form to create your first entry.
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
require_once($root_path . 'admin/includes/footer.php');
?>
