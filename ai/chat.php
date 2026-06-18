<?php
// AI Floating Chat Widget Template
// To include in public pages: include('ai/chat.php');
?>
<!-- AI Chat widget launcher -->
<div id="ai-chat-launcher" class="shadow-lg animate-bounce">
    <i class="fa fa-robot"></i> Ask AI
</div>

<!-- AI Chat box container -->
<div id="ai-chat-container" class="shadow-2xl d-none">
    <!-- Header -->
    <div class="ai-chat-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="avatar bg-success-subtle text-success me-2">
                <i class="fa fa-robot"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-white" style="font-size: 0.95rem;">VIC AI Assistant</h6>
                <small class="text-success-light" style="font-size: 0.75rem;"><i class="fa fa-circle-dot animate-pulse"></i> Online</small>
            </div>
        </div>
        <button id="ai-chat-close" class="btn text-white p-0 fs-5"><i class="fa fa-xmark"></i></button>
    </div>

    <!-- Message Body -->
    <div id="ai-chat-body" class="p-3">
        <div class="message assistant mb-3">
            <div class="msg-bubble p-2 rounded">
                Namaste! Main VIC School AI assistant hoon. Main timings, admissions, documents, transport, aur callback details ke queries solve kar sakta hoon.
            </div>
            <small class="text-muted" style="font-size: 0.65rem;">Just now</small>
        </div>
    </div>

    <!-- Message Inputs footer -->
    <div class="ai-chat-footer p-2 border-top bg-light d-flex gap-1">
        <input type="text" id="ai-chat-input" class="form-control form-control-sm" placeholder="Ask timing, admission docs...">
        <button id="ai-chat-send" class="btn btn-sm btn-primary"><i class="fa fa-paper-plane"></i></button>
    </div>
    
    <!-- WhatsApp Fallback footer -->
    <div class="p-2 text-center bg-white border-top border-light-subtle">
        <a href="https://wa.me/919876543210?text=I%20have%20an%20admission%20enquiry" target="_blank" class="btn btn-sm btn-outline-success w-100 py-1" style="font-size: 0.75rem; border-radius: 6px;">
            <i class="fab fa-whatsapp me-1"></i> Connect on WhatsApp
        </a>
    </div>
</div>

<style>
#ai-chat-launcher {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 12px 24px;
    border-radius: 50px;
    cursor: pointer;
    z-index: 9999;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 8px 20px rgba(29, 78, 216, 0.4);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
}
#ai-chat-launcher:hover {
    transform: scale(1.05);
    box-shadow: 0 10px 25px rgba(29, 78, 216, 0.6);
}
#ai-chat-container {
    position: fixed;
    bottom: 95px;
    right: 30px;
    width: 350px;
    height: 480px;
    background: white;
    border-radius: 16px;
    z-index: 9999;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(0, 0, 0, 0.08);
}
.ai-chat-header {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: white;
    padding: 14px 20px;
}
.text-success-light {
    color: #4ade80;
}
.avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}
#ai-chat-body {
    flex: 1;
    overflow-y: auto;
    background-color: #f8fafc;
}
.message {
    max-width: 85%;
}
.message.assistant {
    align-self: flex-start;
}
.message.user {
    align-self: flex-end;
    margin-left: auto;
}
.message.assistant .msg-bubble {
    background-color: #fff;
    color: #1e293b;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-bottom-left-radius: 0 !important;
}
.message.user .msg-bubble {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border-bottom-right-radius: 0 !important;
}
.msg-bubble {
    font-size: 0.82rem;
    line-height: 1.4;
    white-space: pre-line;
}
</style>

<!-- Load widget script handler -->
<script src="<?= $root_path ?? '' ?>ai/assistant.js"></script>
