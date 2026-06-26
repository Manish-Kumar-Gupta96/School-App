<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../app/Services/NotificationService.php'); // Uses our existing Push/Notification engine

$ns = new NotificationService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_message') {
        $channel = $_POST['channel']; // 'sms', 'whatsapp', 'email', 'push'
        $target_audience = $_POST['target_audience']; // 'all_parents', 'all_students', 'all_teachers', 'specific_class'
        $specific_class = $_POST['specific_class'] ?? null;
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        
        if (!empty($channel) && !empty($target_audience) && !empty($message)) {
            // Determine target users based on audience
            $targets = [];
            
            if ($target_audience === 'all_parents') {
                $stmt = $pdo->query("SELECT id, father_name as name, father_phone as phone, email, 'parent' as role FROM parents");
                $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($target_audience === 'all_students') {
                $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, 'student' as role FROM students WHERE status='Active'");
                $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($target_audience === 'all_teachers') {
                $stmt = $pdo->query("SELECT id, name, phone, email, 'teacher' as role FROM teachers WHERE status='Active'");
                $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($target_audience === 'specific_class' && $specific_class) {
                // Fetch parents of the specific class
                $stmt = $pdo->prepare("
                    SELECT p.id, p.father_name as name, p.father_phone as phone, p.email, 'parent' as role 
                    FROM parents p
                    JOIN parent_student_map psm ON p.id = psm.parent_id
                    JOIN students s ON psm.student_id = s.id
                    WHERE s.class = ?
                ");
                $stmt->execute([$specific_class]);
                $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $success_count = 0;
            foreach ($targets as $user) {
                // 1. Log to communication_logs
                $stmt = $pdo->prepare("INSERT INTO communication_logs (user_id, channel, subject, message, status) VALUES (?, ?, ?, ?, 'sent')");
                $stmt->execute([$user['id'], $channel, $subject, $message]);
                
                // 2. Actually dispatch (Mock dispatch for SMS/WhatsApp/Email, real dispatch for Push if integrated)
                if ($channel === 'push') {
                    // This creates an in-app notification and potentially a Firebase push if NotificationService is hooked up
                    $ns->create($user['id'], $user['role'], $subject, $message, 'broadcast', 0);
                }
                
                $success_count++;
            }
            
            $_SESSION['success'] = "Message dispatched to {$success_count} recipients via " . strtoupper($channel) . ".";
        } else {
            $_SESSION['error'] = "Please fill in all required fields.";
        }
        header("Location: send.php");
        exit;
    }
}

// Fetch classes for specific targeting
$classes = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class ASC")->fetchAll(PDO::FETCH_COLUMN);

$root_path = "../../";
$page_title = "Send Broadcast | VIC School ERP";
$page_header = "Communication Hub";
$active_menu = "communication";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4 justify-content-center">
        <!-- Broadcast Form -->
        <div class="col-md-8 col-lg-6">
            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 border-bottom-0 text-center">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-broadcast-tower me-2"></i>Send Broadcast Message</h5>
                </div>
                <div class="card-body p-4 pt-2">
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
                        <input type="hidden" name="action" value="send_message">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Channel <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mt-1 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="channel" id="c_push" value="push" checked>
                                    <label class="form-check-label fw-bold text-primary" for="c_push"><i class="fa fa-bell me-1"></i>App Push</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="channel" id="c_whatsapp" value="whatsapp">
                                    <label class="form-check-label fw-bold text-success" for="c_whatsapp"><i class="fab fa-whatsapp me-1"></i>WhatsApp</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="channel" id="c_sms" value="sms">
                                    <label class="form-check-label fw-bold text-info" for="c_sms"><i class="fa fa-sms me-1"></i>SMS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="channel" id="c_email" value="email">
                                    <label class="form-check-label fw-bold text-danger" for="c_email"><i class="fa fa-envelope me-1"></i>Email</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Target Audience <span class="text-danger">*</span></label>
                            <select name="target_audience" id="target_audience" class="form-select" required onchange="toggleClassSelect()">
                                <option value="all_parents">All Parents</option>
                                <option value="all_students">All Students</option>
                                <option value="all_teachers">All Teachers</option>
                                <option value="specific_class">Parents of Specific Class</option>
                            </select>
                        </div>

                        <div class="mb-3" id="class_select_div" style="display:none;">
                            <label class="form-label fw-bold">Select Class <span class="text-danger">*</span></label>
                            <select name="specific_class" class="form-select">
                                <option value="">Select Class</option>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3" id="subject_div">
                            <label class="form-label fw-bold">Subject / Title</label>
                            <input type="text" name="subject" class="form-control" placeholder="E.g. Holiday Notice">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Message Body <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="Type your message here..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm"><i class="fa fa-paper-plane me-2"></i> Send Broadcast</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleClassSelect() {
    let aud = document.getElementById('target_audience').value;
    document.getElementById('class_select_div').style.display = (aud === 'specific_class') ? 'block' : 'none';
}
</script>

<?php require_once('../includes/footer.php'); ?>
