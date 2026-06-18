<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$error = '';

// Clear history if requested
if (isset($_GET['clear_all'])) {
    try {
        $pdo->query("DELETE FROM ai_chat_history");
        header("Location: chat-history.php");
        exit;
    } catch (PDOException $e) {
        $error = 'Error wiping logs: ' . $e->getMessage();
    }
}

// Fetch chat logs
$chat_logs = [];
try {
    $chat_logs = $pdo->query("SELECT * FROM ai_chat_history ORDER BY id DESC LIMIT 150")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

$page_title = "AI Chat History | VIC ERP";
$page_header = "AI Conversation Audits";
$active_menu = "ai-dashboard";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">AI Chat Logs Audit</h2>
        <div>
            <a href="analytics.php" class="btn btn-outline-primary me-2">
                <i class="fa fa-chart-line me-1"></i> Analytics
            </a>
            <a href="?clear_all=1" class="btn btn-danger" onclick="return confirm('Wipe entire chat history logs?')">
                <i class="fa fa-trash-can me-1"></i> Clear Logs
            </a>
        </div>
    </div>

    <!-- Quick sub-menu tabs -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="analytics.php" class="btn btn-sm btn-light">Dashboard Analytics</a>
            <a href="chat-history.php" class="btn btn-sm btn-primary">Conversation History</a>
            <a href="faq-manager.php" class="btn btn-sm btn-light">FAQ Database</a>
            <a href="admission-leads.php" class="btn btn-sm btn-light">Admission Leads</a>
            <a href="ai-settings.php" class="btn btn-sm btn-light">AI Configuration Settings</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-start py-2 small" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Log Register -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-history me-2 text-primary"></i>Recent Interactions Logs</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>Session ID</th>
                        <th>Sender Role</th>
                        <th class="pe-4">Content</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($chat_logs) > 0): ?>
                        <?php foreach ($chat_logs as $log): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                <td class="font-monospace small text-secondary">#<?= substr($log['session_id'], 0, 10) ?>...</td>
                                <td>
                                    <?php if ($log['role'] === 'user'): ?>
                                        <span class="badge bg-info-subtle text-info"><i class="fa fa-user me-1"></i> User</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa fa-robot me-1"></i> Bot</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4">
                                    <div class="msg-bubble p-2 rounded small <?= $log['role'] === 'user' ? 'bg-light text-dark' : 'bg-primary-subtle text-primary fw-semibold' ?>" style="white-space: pre-line;">
                                        <?= htmlspecialchars($log['content']) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                                No conversation logs recorded.
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
