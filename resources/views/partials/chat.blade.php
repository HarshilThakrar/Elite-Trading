@if(auth()->check())
<div class="global-chat-widget d-print-none print-hide no-print">
    <!-- Chat Button -->
    <button class="chat-toggle-btn d-print-none print-hide no-print" id="chatToggleBtn">
        <i class="ph ph-chat-circle-dots"></i>
    </button>

    <!-- Chat Window -->
    <div class="chat-window d-none d-print-none print-hide no-print" id="chatWindow">
        <div class="chat-header">
            <h6 class="mb-0 fw-bold"><i class="ph ph-users me-2"></i>Global Chat</h6>
            <button class="close-chat-btn" id="closeChatBtn"><i class="ph ph-x"></i></button>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be loaded here -->
        </div>

        <div class="chat-input-area">
            <form id="chatForm">
                <div class="input-group">
                    <label for="chatAttachment" class="btn btn-light attachment-btn mb-0" title="Attach Image or Video">
                        <i class="ph ph-paperclip"></i>
                    </label>
                    <input type="file" id="chatAttachment" name="attachment" accept="image/*,video/*" class="d-none">
                    <input type="text" id="chatMessageInput" name="message" class="form-control" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="btn btn-primary send-btn">
                        <i class="ph ph-paper-plane-right"></i>
                    </button>
                </div>
                <div id="attachmentPreview" class="d-none small text-muted mt-1">
                    Attached: <span id="attachmentName"></span>
                    <i class="ph ph-x-circle ms-1 text-danger" style="cursor:pointer;" id="clearAttachment"></i>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.global-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
    font-family: 'Inter', sans-serif;
}

@media print {
    .global-chat-widget,
    .global-chat-widget *,
    .chat-toggle-btn,
    .chat-toggle-btn *,
    .chat-window,
    .chat-window *,
    #chatToggleBtn,
    #chatWindow,
    .chat-messages,
    .chat-input-area,
    [class*="chat-"] {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        position: absolute !important;
        left: -9999px !important;
        top: -9999px !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        pointer-events: none !important;
    }
}

.chat-toggle-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: var(--primary-color, #0d6efd);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-size: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
}

.chat-toggle-btn:hover {
    transform: scale(1.05);
}

.chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-header {
    background-color: var(--primary-color, #0d6efd);
    color: white;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close-chat-btn {
    background: transparent;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background-color: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-message {
    max-width: 85%;
    display: flex;
    flex-direction: column;
}

.chat-message.sent {
    align-self: flex-end;
}

.chat-message.received {
    align-self: flex-start;
}

.chat-message .meta {
    font-size: 11px;
    color: #6c757d;
    margin-bottom: 2px;
}

.chat-message.sent .meta {
    text-align: right;
}

.chat-message .bubble {
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    word-break: break-word;
}

.chat-message.sent .bubble {
    background-color: var(--primary-color, #0d6efd);
    color: white;
    border-top-right-radius: 2px;
}

.chat-message.received .bubble {
    background-color: white;
    color: #212529;
    border-top-left-radius: 2px;
    border: 1px solid #dee2e6;
}

.chat-attachment {
    max-width: 100%;
    margin-top: 5px;
    border-radius: 8px;
}

.chat-input-area {
    padding: 15px;
    background: white;
    border-top: 1px solid #dee2e6;
}

.chat-input-area .input-group {
    background: #f1f3f5;
    border-radius: 25px;
    padding: 4px;
}

.chat-input-area input {
    border: none;
    background: transparent;
    box-shadow: none !important;
}

.attachment-btn, .send-btn {
    border-radius: 50% !important;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById('chatToggleBtn');
    const chatWindow = document.getElementById('chatWindow');
    const closeBtn = document.getElementById('closeChatBtn');
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const attachmentInput = document.getElementById('chatAttachment');
    const previewDiv = document.getElementById('attachmentPreview');
    const attachmentName = document.getElementById('attachmentName');
    const clearAttachment = document.getElementById('clearAttachment');
    
    let chatOpen = false;
    let pollInterval = null;
    let lastMessageId = 0;
    const currentUserId = {{ auth()->id() ?? 'null' }};

    function toggleChat() {
        chatOpen = !chatOpen;
        if (chatOpen) {
            chatWindow.classList.remove('d-none');
            fetchMessages();
            pollInterval = setInterval(fetchMessages, 3000);
        } else {
            chatWindow.classList.add('d-none');
            clearInterval(pollInterval);
        }
    }

    if(toggleBtn) {
        toggleBtn.addEventListener('click', toggleChat);
    }
    if(closeBtn) {
        closeBtn.addEventListener('click', toggleChat);
    }

    if(attachmentInput) {
        attachmentInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                attachmentName.textContent = this.files[0].name;
                previewDiv.classList.remove('d-none');
            }
        });
    }

    if(clearAttachment) {
        clearAttachment.addEventListener('click', function() {
            attachmentInput.value = '';
            previewDiv.classList.add('d-none');
        });
    }

    function scrollToBottom() {
        if(chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    function formatTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function fetchMessages() {
        fetch('{{ route('chat.messages') }}')
            .then(res => res.json())
            .then(data => {
                let html = '';
                let newMaxId = lastMessageId;
                
                data.forEach(msg => {
                    if (msg.id > newMaxId) newMaxId = msg.id;
                    
                    const isSent = msg.user_id === currentUserId;
                    const userName = isSent ? 'You' : (msg.user ? msg.user.name : 'Unknown');
                    
                    html += `
                        <div class="chat-message ${isSent ? 'sent' : 'received'}">
                            <div class="meta">${userName} • ${formatTime(msg.created_at)}</div>
                            <div class="bubble">
                                ${msg.message ? `<div>${msg.message}</div>` : ''}
                                ${msg.file_path && msg.file_type === 'image' ? `<img src="/storage/${msg.file_path}" class="chat-attachment" alt="Image">` : ''}
                                ${msg.file_path && msg.file_type === 'video' ? `<video src="/storage/${msg.file_path}" class="chat-attachment" controls></video>` : ''}
                            </div>
                        </div>
                    `;
                });

                if (chatMessages && chatMessages.innerHTML !== html) {
                    chatMessages.innerHTML = html;
                    if (newMaxId > lastMessageId) {
                        setTimeout(scrollToBottom, 100);
                        lastMessageId = newMaxId;
                    }
                }
            })
            .catch(err => console.error(err));
    }

    if(chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('chatMessageInput');
            if (!messageInput.value.trim() && (!attachmentInput.files || !attachmentInput.files.length)) {
                return;
            }

            const formData = new FormData(this);
            
            // Optimistically clear input
            messageInput.value = '';
            attachmentInput.value = '';
            previewDiv.classList.add('d-none');

            fetch('{{ route('chat.send') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.error) {
                    console.error(data.error);
                } else {
                    fetchMessages();
                }
            })
            .catch(err => console.error(err));
        });
    }

    // Hide chat widget completely before browser print preview
    window.addEventListener('beforeprint', function() {
        const chatWidget = document.querySelector('.global-chat-widget');
        if (chatWidget) {
            chatWidget.style.setProperty('display', 'none', 'important');
        }
    });

    window.addEventListener('afterprint', function() {
        const chatWidget = document.querySelector('.global-chat-widget');
        if (chatWidget) {
            chatWidget.style.removeProperty('display');
        }
    });
});
</script>
@endif
