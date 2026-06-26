<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

if (!isset($_SESSION['parent_id'])) {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$conversation_id = $_GET['conversation_id'] ?? 0;

$root_path = "../";
$page_title = "Parent-Teacher Chat";
$page_header = "Communication";
$active_menu = "chat";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="row h-100">
    <div class="col-md-4 border-end bg-white h-100" style="min-height: 70vh;">
        <h5 class="p-3 border-bottom mb-0 fw-bold">Conversations</h5>
        <!-- List of conversations placeholder -->
        <div class="p-3 text-muted">Select a conversation...</div>
    </div>
    
    <div class="col-md-8 d-flex flex-column h-100 bg-light" style="min-height: 70vh;">
        <?php if ($conversation_id): ?>
            <div class="p-3 bg-white border-bottom shadow-sm">
                <h5 class="mb-0 fw-bold"><i class="fa fa-user-circle me-2 text-primary"></i> Chat Room</h5>
            </div>
            
            <div id="chat-box" class="flex-grow-1 p-4 overflow-auto d-flex flex-column gap-3">
                <!-- Messages will load here via AJAX -->
            </div>
            
            <div class="p-3 bg-white border-top">
                <form id="chatForm" enctype="multipart/form-data" class="d-flex gap-2">
                    <input type="hidden" name="conversation_id" id="conversation_id" value="<?= htmlspecialchars($conversation_id) ?>">
                    
                    <button type="button" class="btn btn-light border rounded-circle" onclick="document.getElementById('fileUpload').click()">
                        <i class="fa fa-paperclip text-muted"></i>
                    </button>
                    <input type="file" name="file" id="fileUpload" class="d-none">
                    
                    <textarea name="message" id="messageInput" class="form-control rounded-pill px-4" rows="1" placeholder="Type a message..." style="resize:none;"></textarea>
                    
                    <button type="submit" class="btn btn-primary rounded-circle px-3">
                        <i class="fa fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                <i class="fa fa-comments fs-1 mb-3 text-primary opacity-50"></i>
                <h5>Select a chat to start messaging</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const convoId = $('#conversation_id').val();

if(convoId > 0) {
    function loadMessages() {
        $.get('../api/get_messages.php', { conversation_id: convoId }, function(data) {
            let html = '';
            data.forEach(msg => {
                const alignClass = (msg.sender_role === 'parent') ? 'align-self-end bg-primary text-white' : 'align-self-start bg-white border text-dark';
                
                html += `<div class="p-3 rounded-4 shadow-sm mb-2 ${alignClass}" style="max-width:75%;">`;
                if(msg.message) html += `<div>${msg.message}</div>`;
                if(msg.attachment) {
                    if(msg.message_type === 'image') {
                        html += `<img src="../${msg.attachment}" class="img-fluid rounded mt-2" style="max-height: 200px;">`;
                    } else {
                        html += `<a href="../${msg.attachment}" target="_blank" class="btn btn-sm btn-light mt-2"><i class="fa fa-download"></i> Download File</a>`;
                    }
                }
                html += `<small class="d-block mt-1 ${msg.sender_role === 'parent' ? 'text-white-50' : 'text-muted'}" style="font-size:0.7rem;">${msg.created_at}</small>`;
                html += `</div>`;
            });
            
            const chatBox = $('#chat-box');
            // Only scroll down if new messages are added
            const isAtBottom = chatBox.scrollTop() + chatBox.innerHeight() >= chatBox[0].scrollHeight - 10;
            chatBox.html(html);
            if(isAtBottom) chatBox.scrollTop(chatBox[0].scrollHeight);
        });
    }

    // Initial Load
    loadMessages();
    
    // Auto Refresh
    setInterval(loadMessages, 3000);

    // Typing Indicator Trigger
    $('#messageInput').keyup(function() {
        $.post('../api/typing.php', { conversation_id: convoId });
    });

    // Send Message
    $('#chatForm').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        $.ajax({
            url: 'send_message.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#messageInput').val('');
                $('#fileUpload').val('');
                loadMessages();
            }
        });
    });
}
</script>

<?php require_once('../includes/footer.php'); ?>
