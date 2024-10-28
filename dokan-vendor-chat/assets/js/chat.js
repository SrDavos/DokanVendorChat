const DokanChat = {
    init: function() {
        ChatDebug.log('Initializing DokanChat');
        this.initElements();
        this.bindEvents();
    },

    initElements: function() {
        ChatDebug.log('Initializing chat elements');
        this.chatBtns = document.querySelectorAll('.dokan-chat-btn');
        this.chatBox = document.getElementById('dokan-chat-box');
        this.messagesContainer = document.getElementById('dokan-chat-messages');
        this.messageInput = document.getElementById('dokan-chat-input');
        this.sendBtn = document.getElementById('dokan-chat-send');
        this.closeBtn = document.getElementById('dokan-chat-close');
        this.vendorName = document.getElementById('vendor-name');
        this.currentChat = null;
        this.pollInterval = null;
    },

    bindEvents: function() {
        ChatDebug.log('Binding events');
        
        this.chatBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const btn = e.currentTarget;
                this.openChat({
                    orderId: btn.dataset.orderId,
                    vendorId: btn.dataset.vendorId,
                    vendorName: btn.dataset.vendorName
                });
            });
        });

        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', () => this.sendMessage());
        }

        if (this.messageInput) {
            this.messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.closeChat());
        }
    },

    openChat: function(chatData) {
        ChatDebug.log('Opening chat');
        ChatDebug.log('Chat data:', chatData);
        
        this.currentChat = chatData;
        
        // Add chat-active class to wrapper
        const wrapper = document.querySelector('.dokan-chat-wrap');
        if (wrapper) {
            wrapper.classList.add('chat-active');
        }
        
        // Update header info
        if (this.vendorName) {
            this.vendorName.textContent = `Vendor's Order #${chatData.orderId}`;
        }
        
        this.loadMessages();
        this.startPolling();
        
        // Scroll messages to bottom
        if (this.messagesContainer) {
            setTimeout(() => {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }, 100);
        }
    },

    closeChat: function() {
        ChatDebug.log('Closing chat');
        this.stopPolling();
        
        // Remove chat-active class from wrapper
        const wrapper = document.querySelector('.dokan-chat-wrap');
        if (wrapper) {
            wrapper.classList.remove('chat-active');
        }
        
        this.currentChat = null;
    },

    startPolling: function() {
        ChatDebug.log('Starting polling');
        this.pollInterval = setInterval(() => this.loadMessages(), 5000);
    },

    stopPolling: function() {
        ChatDebug.log('Stopping polling');
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },

    loadMessages: async function() {
        if (!this.currentChat) {
            ChatDebug.log('ERROR: No active chat');
            return;
        }

        ChatDebug.log('Loading messages');
        
        try {
            const response = await fetch(
                `${dokan.rest.root}dokan/v1/chat/messages?order_id=${this.currentChat.orderId}&vendor_id=${this.currentChat.vendorId}`,
                {
                    headers: {
                        'X-WP-Nonce': dokan.rest.nonce
                    }
                }
            );

            if (!response.ok) throw new Error('Failed to load messages');
            
            const messages = await response.json();
            ChatDebug.log('Messages loaded: ' + JSON.stringify(messages));
            
            this.displayMessages(messages);
        } catch (error) {
            ChatDebug.log('ERROR: ' + error.message);
        }
    },

    sendMessage: async function() {
        const message = this.messageInput.value.trim();
        if (!message || !this.currentChat) return;

        ChatDebug.log('Sending message');
        
        try {
            const response = await fetch(dokan.rest.root + 'dokan/v1/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dokan.rest.nonce
                },
                body: JSON.stringify({
                    order_id: this.currentChat.orderId,
                    vendor_id: this.currentChat.vendorId,
                    message: message
                })
            });

            if (!response.ok) throw new Error('Failed to send message');
            
            const result = await response.json();
            if (result.success) {
                this.messageInput.value = '';
                await this.loadMessages();
            }
        } catch (error) {
            ChatDebug.log('ERROR: ' + error.message);
        }
    },

    displayMessages: function(messages) {
        if (!Array.isArray(messages)) return;
        
        this.messagesContainer.innerHTML = messages.map(msg => `
            <div class="chat-message ${msg.is_customer ? 'customer' : 'vendor'}">
                <div class="message-content">${msg.message}</div>
                <div class="message-time">${msg.created_at}</div>
            </div>
        `).join('');
        
        // Scroll to bottom
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    },

    scrollToBottom: function() {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    },

    formatTime: function(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },

    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

const ChatDebug = {
    log: function(...args) {
        const time = new Date().toLocaleTimeString();
        console.log(`[${time}]`, ...args);
    }
};

document.addEventListener('DOMContentLoaded', () => DokanChat.init());
