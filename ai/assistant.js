document.addEventListener("DOMContentLoaded", function() {
    const launcher = document.getElementById("ai-chat-launcher");
    const container = document.getElementById("ai-chat-container");
    const closeBtn = document.getElementById("ai-chat-close");
    const sendBtn = document.getElementById("ai-chat-send");
    const inputField = document.getElementById("ai-chat-input");
    const chatBody = document.getElementById("ai-chat-body");

    if (!launcher || !container) return;

    // Toggle Chat visibility
    launcher.addEventListener("click", () => {
        container.classList.toggle("d-none");
        launcher.classList.add("d-none");
        inputField.focus();
        scrollToBottom();
    });

    closeBtn.addEventListener("click", () => {
        container.classList.add("d-none");
        launcher.classList.remove("d-none");
    });

    // Send logic
    function sendMessage() {
        const text = inputField.value.trim();
        if (!text) return;

        // Append user bubble
        appendMessage(text, 'user');
        inputField.value = '';

        // Append typing spinner
        const spinner = appendTypingIndicator();
        scrollToBottom();

        // Fetch text response from backend API
        fetch('/ai/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ query: text })
        })
        .then(response => response.json())
        .then(data => {
            spinner.remove();
            if (data && data.reply) {
                appendMessage(data.reply, 'assistant');
            } else {
                appendMessage("Sorry, I could not process that request.", 'assistant');
            }
            scrollToBottom();
        })
        .catch(err => {
            console.error(err);
            spinner.remove();
            appendMessage("Offline. Local model search was unable to reach connection endpoint.", 'assistant');
            scrollToBottom();
        });
    }

    sendBtn.addEventListener("click", sendMessage);
    inputField.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            sendMessage();
        }
    });

    function appendMessage(content, sender) {
        const wrap = document.createElement("div");
        wrap.className = `message ${sender} mb-3`;
        
        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        wrap.innerHTML = `
            <div class="msg-bubble p-2 rounded">${escapeHtml(content)}</div>
            <small class="text-muted d-block mt-1" style="font-size: 0.65rem; text-align: ${sender === 'user' ? 'right' : 'left'}">${timeStr}</small>
        `;
        chatBody.appendChild(wrap);
    }

    function appendTypingIndicator() {
        const wrap = document.createElement("div");
        wrap.className = "message assistant mb-3";
        wrap.innerHTML = `
            <div class="msg-bubble p-2 rounded text-muted font-italic" style="background-color: #fff; border: 1px solid rgba(0,0,0,0.05);">
                <i class="fa fa-spinner fa-spin"></i> Typing response...
            </div>
        `;
        chatBody.appendChild(wrap);
        return wrap;
    }

    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
