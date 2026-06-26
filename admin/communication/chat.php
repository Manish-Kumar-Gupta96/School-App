<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$admin_id = $_SESSION['admin_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_chat') {
        $receiver_id = (int)$_POST['receiver_id'];
        $message = trim($_POST['message']);
        
        if ($receiver_id > 0 && !empty($message)) {
            // Find or create conversation
            $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$admin_id, $receiver_id, $receiver_id, $admin_id]);
            $conv_id = $stmt->fetchColumn();
            
            if (!$conv_id) {
                $pdo->prepare("INSERT INTO chat_conversations (sender_id, receiver_id) VALUES (?, ?)")->execute([$admin_id, $receiver_id]);
                $conv_id = $pdo->lastInsertId();
            }
            
            // Insert Message
            $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$conv_id, $admin_id, $message]);
        }
        header("Location: chat.php?user=" . $receiver_id);
        exit;
    }
}

// Fetch Staff to chat with
$staff_list = $pdo->query("SELECT id, name, 'Teacher' as role FROM teachers WHERE status='Active'")->fetchAll(PDO::FETCH_ASSOC);

$active_chat = null;
$messages = [];
if (isset($_GET['user'])) {
    $active_user_id = (int)$_GET['user'];
    
    // Get chat partner name
    $stmt = $pdo->prepare("SELECT name FROM teachers WHERE id = ?");
    $stmt->execute([$active_user_id]);
    $active_chat = $stmt->fetchColumn();
    
    // Get conversation ID
    $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$admin_id, $active_user_id, $active_user_id, $admin_id]);
    $conv_id = $stmt->fetchColumn();
    
    if ($conv_id) {
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC");
        $stmt->execute([$conv_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$root_path = "../../";
$page_title = "Internal Chat | VIC School ERP";
$page_header = "Staff Messaging";
$active_menu = "communication";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container-fluid mt-4" style="height: calc(100vh - 150px);">
    <div class="row g-0 h-100 shadow-sm border rounded-4 overflow-hidden">
        
        <!-- Sidebar: User List -->
        <div class="col-md-4 col-lg-3 bg-white border-end h-100 d-flex flex-column">
            <div class="p-3 bg-light border-bottom">
                <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-users me-2"></i>Staff Directory</h5>
            </div>
            <div class="overflow-auto flex-grow-1 p-2">
                <ul class="list-group list-group-flush">
                    <?php foreach($staff_list as $s): ?>
                        <a href="?user=<?= $s['id'] ?>" class="list-group-item list-group-item-action border-0 mb-1 rounded <?= (isset($_GET['user']) && $_GET['user'] == $s['id']) ? 'bg-primary text-white' : '' ?>">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 40px; height: 40px;">
                                    <?= strtoupper(substr($s['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold <?= (isset($_GET['user']) && $_GET['user'] == $s['id']) ? 'text-white' : 'text-dark' ?>"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="small <?= (isset($_GET['user']) && $_GET['user'] == $s['id']) ? 'text-white-50' : 'text-muted' ?>"><?= htmlspecialchars($s['role']) ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="col-md-8 col-lg-9 bg-light h-100 d-flex flex-column">
            <?php if ($active_chat): ?>
                <!-- Chat Header -->
                <div class="bg-white p-3 border-bottom d-flex align-items-center shadow-sm">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3" style="width: 45px; height: 45px;">
                        <?= strtoupper(substr($active_chat, 0, 1)) ?>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($active_chat) ?></h5>
                        <small class="text-success fw-bold"><i class="fa fa-circle me-1" style="font-size: 8px;"></i>Online</small>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="flex-grow-1 overflow-auto p-4 d-flex flex-column gap-3" id="chatBox">
                    <?php if(empty($messages)): ?>
                        <div class="text-center text-muted mt-5">
                            <i class="fa fa-comments fs-1 mb-2 text-secondary opacity-50"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($messages as $m): 
                            $is_me = ($m['sender_id'] == $admin_id);
                        ?>
                            <div class="d-flex <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?>">
                                <div class="<?= $is_me ? 'bg-primary text-white' : 'bg-white text-dark border shadow-sm' ?> p-3 rounded-4" style="max-width: 75%; border-bottom-<?= $is_me ? 'right' : 'left' ?>-radius: 0;">
                                    <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                                    <div class="text-end mt-1 <?= $is_me ? 'text-white-50' : 'text-muted' ?>" style="font-size: 0.75rem;">
                                        <?= date('h:i A', strtotime($m['created_at'])) ?>
                                        <?php if($is_me): ?><i class="fa fa-check-double ms-1"></i><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Chat Input -->
                <div class="bg-white p-3 border-top">
                    <form method="POST" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="action" value="send_chat">
                        <input type="hidden" name="receiver_id" value="<?= htmlspecialchars($_GET['user']) ?>">
                        
                        <button type="button" class="btn btn-light rounded-circle"><i class="fa fa-paperclip"></i></button>
                        <input type="text" name="message" class="form-control rounded-pill border-0 bg-light px-4 py-2" placeholder="Type a message..." required autocomplete="off" autofocus>
                        <button type="submit" class="btn btn-primary rounded-circle" style="width: 45px; height: 45px;"><i class="fa fa-paper-plane"></i></button>
                    </form>
                </div>
                
                <script>
                    // Scroll to bottom
                    const chatBox = document.getElementById('chatBox');
                    chatBox.scrollTop = chatBox.scrollHeight;
                </script>

            <?php else: ?>
                <!-- Empty State -->
                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                    <div class="bg-white p-4 rounded-circle shadow-sm mb-3">
                        <i class="fa fa-comments text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold text-dark">VIC Connect</h4>
                    <p>Select a staff member from the left to start chatting.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
