<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'chat') {
    $prompt = trim($_POST['prompt']);
    
    if (!empty($prompt)) {
        // Send to Flask AI backend
        $ch = curl_init('http://127.0.0.1:5000/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $prompt]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response_json = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($response_json, true);
        $ai_response = $response['response'] ?? "I'm sorry, my AI engine is currently unreachable.";
        
        // Log it
        $stmt = $pdo->prepare("INSERT INTO ai_logs (school_id, user_id, prompt, response) VALUES (?, ?, ?, ?)");
        $stmt->execute([$school_id, $_SESSION['admin_id'], $prompt, $ai_response]);
        
        header("Location: dashboard.php");
        exit;
    }
}

// Fetch chat history
$chat_history = $pdo->query("SELECT * FROM ai_logs WHERE school_id=$school_id AND user_id={$_SESSION['admin_id']} ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "AI Copilot | VIC School ERP";
$page_header = "AI Copilot Engine";
$active_menu = "ai";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- AI Chat Interface -->
        <div class="col-md-8">
            <div class="card shadow border-0" style="border-radius: 15px; height: 75vh; display: flex; flex-direction: column;">
                <div class="card-header bg-dark text-white py-3 d-flex align-items-center" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                    <img src="https://ui-avatars.com/api/?name=AI&background=0d6efd&color=fff&rounded=true" width="40" class="me-3">
                    <div>
                        <h5 class="mb-0 fw-bold">SchoolOS Copilot</h5>
                        <small class="text-white-50"><i class="fa fa-circle text-success me-1" style="font-size: 0.6rem;"></i>Online - Powered by VIC AI</small>
                    </div>
                </div>
                <div class="card-body bg-light overflow-auto p-4 d-flex flex-column-reverse">
                    <!-- Chat History (Bottom Up) -->
                    <?php if (count($chat_history) > 0): ?>
                        <?php foreach ($chat_history as $msg): ?>
                            <!-- AI Msg -->
                            <div class="d-flex mb-4">
                                <img src="https://ui-avatars.com/api/?name=AI&background=0d6efd&color=fff&rounded=true" width="40" height="40" class="me-3 shadow-sm">
                                <div class="bg-white p-3 shadow-sm rounded-3 w-75 position-relative">
                                    <?= nl2br(htmlspecialchars($msg['response'])) ?>
                                    <div class="text-muted small position-absolute" style="bottom: -20px; left: 5px;"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
                                </div>
                            </div>
                            
                            <!-- User Msg -->
                            <div class="d-flex mb-4 justify-content-end">
                                <div class="bg-primary text-white p-3 shadow-sm rounded-3 w-75 position-relative">
                                    <?= htmlspecialchars($msg['prompt']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted my-auto">
                            <i class="fa fa-robot fs-1 mb-3 text-primary opacity-50"></i>
                            <h5 class="fw-bold">Welcome to SchoolOS AI</h5>
                            <p>Try asking about fees, admissions, or asking me to draft a notice.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white border-0 py-3" style="border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;">
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="chat">
                        <input type="text" name="prompt" class="form-control form-control-lg bg-light border-0" placeholder="Ask AI Copilot..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary btn-lg px-4 rounded-circle shadow-sm"><i class="fa fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- AI Capabilities Sidebar -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4 bg-dark text-white" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <i class="fa fa-brain fs-1 text-warning mb-3"></i>
                    <h5 class="fw-bold">Capabilities</h5>
                    <p class="small text-white-50">I am deeply integrated into SchoolOS. I can read ledgers, analyze student behavior, predict dropouts, and automate tasks.</p>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-bolt me-2 text-primary"></i>Quick Prompts</h6>
                </div>
                <div class="card-body pt-0 d-grid gap-2">
                    <button class="btn btn-light text-start text-dark border shadow-sm small prompt-btn" data-text="How much fee collected this month?">
                        <i class="fa fa-search-dollar text-success me-2"></i> How much fee collected this month?
                    </button>
                    <button class="btn btn-light text-start text-dark border shadow-sm small prompt-btn" data-text="Show fee defaulters.">
                        <i class="fa fa-users-slash text-danger me-2"></i> Show fee defaulters.
                    </button>
                    <button class="btn btn-light text-start text-dark border shadow-sm small prompt-btn" data-text="What is the admission forecast?">
                        <i class="fa fa-chart-line text-info me-2"></i> What is the admission forecast?
                    </button>
                    <button class="btn btn-light text-start text-dark border shadow-sm small prompt-btn" data-text="Generate a lesson plan for Science Chapter 8">
                        <i class="fa fa-book-reader text-primary me-2"></i> Generate a lesson plan for Science
                    </button>
                    <button class="btn btn-light text-start text-dark border shadow-sm small prompt-btn" data-text="Generate a bonafide certificate">
                        <i class="fa fa-file-pdf text-secondary me-2"></i> Automate certificate generation
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.prompt-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = document.querySelector('input[name="prompt"]');
        input.value = this.getAttribute('data-text');
        input.closest('form').submit();
    });
});
</script>

<?php require_once('../includes/footer.php'); ?>
