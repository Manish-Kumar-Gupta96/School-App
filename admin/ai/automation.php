<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_rule') {
        $rule_name = trim($_POST['rule_name']);
        $trigger_event = $_POST['trigger_event'];
        $conditions = json_encode(['threshold' => $_POST['condition_value']]);
        $actions = json_encode(['notify_channels' => $_POST['actions'] ?? []]);
        
        $stmt = $pdo->prepare("INSERT INTO automation_rules (school_id, rule_name, trigger_event, conditions, actions) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $rule_name, $trigger_event, $conditions, $actions]);
        $_SESSION['success'] = "Automation Rule created successfully. The AI Engine will now monitor this.";
        header("Location: automation.php");
        exit;
    } elseif ($_POST['action'] === 'toggle_rule') {
        $id = (int)$_POST['id'];
        $status = (int)$_POST['status'] === 1 ? 0 : 1;
        $pdo->prepare("UPDATE automation_rules SET status=? WHERE id=? AND school_id=?")->execute([$status, $id, $school_id]);
        header("Location: automation.php");
        exit;
    }
}

// Fetch rules
$rules = $pdo->query("SELECT * FROM automation_rules WHERE school_id=$school_id ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "AI Automation | VIC School ERP";
$page_header = "Workflow Automation Builder";
$active_menu = "ai";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Builder Form -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-cogs me-2"></i>Create Automation Rule</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_rule">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" name="rule_name" class="form-control" required placeholder="e.g. Fee Defaulter Alert">
                        </div>

                        <div class="p-3 bg-light rounded mb-3 border position-relative">
                            <span class="badge bg-secondary position-absolute" style="top: -10px; left: 15px;">IF (Trigger)</span>
                            <label class="form-label fw-bold mt-2">When this happens:</label>
                            <select name="trigger_event" class="form-select mb-2" required>
                                <option value="">Select Trigger Event...</option>
                                <option value="fee_due">Fee becomes Due</option>
                                <option value="attendance_drop">Attendance Drops Below Threshold</option>
                                <option value="exam_result">Exam Results Published</option>
                                <option value="inventory_low">Inventory Stock is Low</option>
                            </select>
                            
                            <label class="form-label fw-bold mt-2">Condition Value:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-greater-than"></i></span>
                                <input type="text" name="condition_value" class="form-control" placeholder="e.g. 15 Days">
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded mb-4 border position-relative">
                            <span class="badge bg-success position-absolute" style="top: -10px; left: 15px;">THEN (Action)</span>
                            <label class="form-label fw-bold mt-2">Do this:</label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="actions[]" value="whatsapp" id="a_wa">
                                <label class="form-check-label fw-bold" for="a_wa"><i class="fab fa-whatsapp text-success me-1"></i> Send WhatsApp Message</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="actions[]" value="sms" id="a_sms">
                                <label class="form-check-label fw-bold" for="a_sms"><i class="fa fa-sms text-primary me-1"></i> Send SMS Alert</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="actions[]" value="email" id="a_em">
                                <label class="form-check-label fw-bold" for="a_em"><i class="fa fa-envelope text-warning me-1"></i> Send Email</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actions[]" value="crm" id="a_crm">
                                <label class="form-check-label fw-bold" for="a_crm"><i class="fa fa-headset text-danger me-1"></i> Create CRM Follow-up Task</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm"><i class="fa fa-magic me-2"></i> Deploy Rule</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rules List -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-code-branch me-2 text-info"></i>Active Workflows</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                <?php if (count($rules) > 0): ?>
                                    <?php foreach ($rules as $r): 
                                        $act = json_decode($r['actions'], true);
                                        $channels = $act['notify_channels'] ?? [];
                                    ?>
                                        <tr>
                                            <td class="ps-4 py-3">
                                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($r['rule_name']) ?></div>
                                                <div class="text-muted small mb-2">
                                                    <span class="badge bg-secondary">IF <?= strtoupper($r['trigger_event']) ?></span> 
                                                    <i class="fa fa-arrow-right mx-1"></i> 
                                                    THEN Send (<?= implode(', ', $channels) ?>)
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="toggle_rule">
                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $r['status'] ?>">
                                                    <button type="submit" class="btn btn-sm <?= $r['status'] == 1 ? 'btn-success' : 'btn-outline-secondary' ?>" style="border-radius: 20px;">
                                                        <?= $r['status'] == 1 ? '<i class="fa fa-check me-1"></i> Active' : 'Paused' ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center py-5 text-muted">No automation rules created yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info border-info shadow-sm mt-4 d-flex align-items-center" role="alert" style="border-radius: 15px;">
                <i class="fa fa-info-circle fs-2 me-3 text-info"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">How AI Automation Works</h6>
                    <p class="mb-0 small">The Python AI Background Worker continuously scans the database ledgers and attendance records. When a trigger condition is met, it executes the mapped actions via API integrations.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
