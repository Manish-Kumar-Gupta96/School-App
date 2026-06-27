<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../includes/Csrf.php';

// Instantiate secure session and CSRF parameters
new BaseController();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School AI Assistant</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/fa/all.min.css'); ?>">
    <style>
        .chat-container { max-width: 600px; margin: 50px auto; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .chat-box { height: 400px; overflow-y: auto; padding: 20px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
        .msg { margin-bottom: 15px; padding: 10px 15px; border-radius: 15px; max-width: 80%; }
        .msg.user { background: #007bff; color: white; margin-left: auto; text-align: right; }
        .msg.ai { background: #e2e3e5; color: #383d41; margin-right: auto; }
    </style>
</head>
<body>

<div class="container">
    <div class="card chat-container">
        <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-robot me-2"></i>
            <h5 class="mb-0">School Smart Assistant</h5>
        </div>
        <div class="card-body chat-box" id="chatBox">
            <div class="msg ai">Hello! I am your School AI Assistant. How can I help you with your classes, fees, or timetable today?</div>
        </div>
        <div class="card-footer bg-white">
            <form id="chatForm" class="d-flex">
                <!-- Security Token Injection -->
                <?php Csrf::injectInput(); ?>
                <input type="text" id="userInput" class="form-control me-2" placeholder="Type your query here..." required autocomplete="off">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('chatForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const inputField = document.getElementById('userInput');
    const chatBox = document.getElementById('chatBox');
    const message = inputField.value.trim();
    
    if(!message) return;

    // Render User Message locally
    chatBox.innerHTML += `<div class="msg user">${escapeHtml(message)}</div>`;
    inputField.value = '';
    chatBox.scrollTop = chatBox.scrollHeight;

    // Render typing placeholder
    const typingId = 'typing-' + Date.now();
    chatBox.innerHTML += `<div class="msg ai" id="${typingId}"><i class="fas fa-spinner fa-spin"></i> Thinking...</div>`;
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const response = await fetch('/school-app/ai/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        
        const data = await response.json();
        document.getElementById(typingId).remove();

        if(data.status) {
            chatBox.innerHTML += `<div class="msg ai">${data.reply}</div>`;
        } else {
            chatBox.innerHTML += `<div class="msg ai text-danger">Error: ${data.message}</div>`;
        }
    } catch (error) {
        document.getElementById(typingId).remove();
        chatBox.innerHTML += `<div class="msg ai text-danger">Connection lost. Please try again.</div>`;
    }
    
    chatBox.scrollTop = chatBox.scrollHeight;
});

function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>
</body>
</html>
