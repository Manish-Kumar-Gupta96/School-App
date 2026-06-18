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

// Handle save settings
if (isset($_POST['save_settings'])) {
    $api_key = trim($_POST['api_key']);
    $model = trim($_POST['model']);
    $temperature = (float)$_POST['temperature'];
    $token_limit = (int)$_POST['token_limit'];
    $system_prompt = trim($_POST['system_prompt']);

    try {
        $stmt_check = $pdo->query("SELECT COUNT(*) FROM ai_settings");
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $stmt = $pdo->prepare("UPDATE ai_settings SET api_key = ?, model = ?, temperature = ?, token_limit = ?, system_prompt = ? WHERE id = 1");
            $stmt->execute([$api_key, $model, $temperature, $token_limit, $system_prompt]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO ai_settings (id, api_key, model, temperature, token_limit, system_prompt) VALUES (1, ?, ?, ?, ?, ?)");
            $stmt->execute([$api_key, $model, $temperature, $token_limit, $system_prompt]);
        }
        $success = 'AI settings updated successfully.';
    } catch (PDOException $e) {
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// Fetch settings
$settings = null;
try {
    $stmt = $pdo->query("SELECT * FROM ai_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silent
}

// Default values
$api_key = $settings['api_key'] ?? '';
$model = $settings['model'] ?? 'gpt-5.5-mini';
$temperature = $settings['temperature'] ?? 0.70;
$token_limit = $settings['token_limit'] ?? 2000;
$system_prompt = $settings['system_prompt'] ?? '';

if (empty($system_prompt)) {
    $prompt_file = __DIR__ . '/../../ai/prompts/school-assistant.txt';
    if (file_exists($prompt_file)) {
        $system_prompt = file_get_contents($prompt_file);
    }
}
$page_title = "AI Engine Configuration | VIC ERP";
$page_header = "AI Core Configuration Settings";
$active_menu = "ai-dashboard";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">OpenAI LLM Integration Properties</h2>
    </div>

    <!-- Quick sub-menu tabs -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="analytics.php" class="btn btn-sm btn-light">Dashboard Analytics</a>
            <a href="chat-history.php" class="btn btn-sm btn-light">Conversation History</a>
            <a href="faq-manager.php" class="btn btn-sm btn-light">FAQ Database</a>
            <a href="admission-leads.php" class="btn btn-sm btn-light">Admission Leads</a>
            <a href="ai-settings.php" class="btn btn-sm btn-primary">AI Configuration Settings</a>
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

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h5 class="fw-bold text-dark mb-3"><i class="fa fa-sliders me-2 text-primary"></i>Model Parameters Configuration</h5>
        <hr class="text-muted mt-0 mb-4">
        
        <form method="POST" action="" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">OpenAI API Authorization Secret Key</label>
                <input type="password" name="api_key" class="form-control" placeholder="sk-proj-..." value="<?= htmlspecialchars($api_key) ?>" style="border-radius: 8px;">
                <p class="text-muted small mt-1">If blank, local Levenshtein/similar_text FAQ matcher is used as default.</p>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Target LLM Model Type</label>
                <select name="model" class="form-select" style="border-radius: 8px;">
                    <option value="gpt-5.5-mini" <?= $model === 'gpt-5.5-mini' ? 'selected' : '' ?>>GPT-5.5 Mini (Recommended)</option>
                    <option value="gpt-5.5" <?= $model === 'gpt-5.5' ? 'selected' : '' ?>>GPT-5.5 Large</option>
                    <option value="gpt-4o" <?= $model === 'gpt-4o' ? 'selected' : '' ?>>GPT-4o</option>
                    <option value="gpt-4o-mini" <?= $model === 'gpt-4o-mini' ? 'selected' : '' ?>>GPT-4o Mini</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Model Temperature</label>
                <input type="number" name="temperature" step="0.05" min="0.0" max="1.5" class="form-control" value="<?= htmlspecialchars($temperature) ?>" style="border-radius: 8px;">
                <p class="text-muted small mt-1">High values like 0.90 increase creativity, 0.20 yields exact matching outputs.</p>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Max Token limits</label>
                <input type="number" name="token_limit" class="form-control" value="<?= htmlspecialchars($token_limit) ?>" style="border-radius: 8px;">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold small">AI Agent System Instruction Prompts</label>
                <textarea name="system_prompt" class="form-control" rows="6" style="border-radius: 8px;"><?= htmlspecialchars($system_prompt) ?></textarea>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" name="save_settings" class="btn btn-primary px-4 fw-semibold" style="border-radius: 8px;">
                    <i class="fa fa-save me-2"></i> Save AI Engine Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
