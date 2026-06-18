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

// Handle Delete Lead action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM admission_leads WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Lead removed successfully.';
    } catch (PDOException $e) {
        $error = 'Error deleting lead: ' . $e->getMessage();
    }
}

// Fetch Leads
$leads = [];
try {
    $leads = $pdo->query("SELECT * FROM admission_leads ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

$page_title = "Admission Leads | VIC ERP";
$page_header = "AI Captured Leads";
$active_menu = "ai-dashboard";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Admission Leads Registry</h2>
    </div>

    <!-- Quick sub-menu tabs -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="analytics.php" class="btn btn-sm btn-light">Dashboard Analytics</a>
            <a href="chat-history.php" class="btn btn-sm btn-light">Conversation History</a>
            <a href="faq-manager.php" class="btn btn-sm btn-light">FAQ Database</a>
            <a href="admission-leads.php" class="btn btn-sm btn-primary">Admission Leads</a>
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

    <!-- Leads List Grid -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-user-plus me-2 text-primary"></i>Captured Candidate Leads</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Lead ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Query Description</th>
                        <th>Created Date</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($leads) > 0): ?>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $lead['id'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($lead['name']) ?></td>
                                <td><?= htmlspecialchars($lead['phone']) ?></td>
                                <td><?= htmlspecialchars($lead['email']) ?></td>
                                <td class="small text-secondary"><?= htmlspecialchars($lead['query']) ?></td>
                                <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?></td>
                                <td class="pe-4 text-end">
                                    <a href="?delete=1&id=<?= $lead['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this lead entry?')">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No leads captured by chatbot yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
