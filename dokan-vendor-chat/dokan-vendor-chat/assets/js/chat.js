(function($) {
    'use strict';
    
    // Wait for window load to avoid Elementor conflicts
    window.addEventListener('load', function() {
        setTimeout(function() {
            if ($('.dokan-chat-messages').length || $('.dokan-customer-chat').length) {
                initDokanChat();
            }
        }, 100);
    });

    function initDokanChat() {
        var DokanChat = {
            init: function() {
                this.initializeElements();
                this.restoreState();
                this.bindEvents();
            },
            
            initializeElements: function() {
                this.$chatMessages = $('.dokan-chat-messages');
                this.$ordersList = $('.dokan-table');
                this.$messagesContainer = $('#dokan-chat-messages-container');
                this.$messageInput = $('#dokan-chat-message');
                this.$sendButton = $('#dokan-chat-send');
                this.currentChatUser = null;
                this.currentOrderId = null;
                this.pollInterval = null;
            },
            
            // Add state management
            saveState: function() {
                if (this.currentOrderId && this.currentChatUser) {
                    localStorage.setItem('dokanChat', JSON.stringify({
                        orderId: this.currentOrderId,
                        userId: this.currentChatUser,
                        vendorName: $('.chat-with').text().replace('Chat with ', '')
                    }));
                } else {
                    localStorage.removeItem('dokanChat');
                }
            },
            
            restoreState: function() {
                try {
                    const savedState = localStorage.getItem('dokanChat');
                    if (savedState) {
                        const state = JSON.parse(savedState);
                        this.currentOrderId = state.orderId;
                        this.currentChatUser = state.userId;
                        if (this.currentOrderId && this.currentChatUser) {
                            this.openChat(state.vendorName);
                        }
                    }
                } catch (e) {
                    console.error('Error restoring chat state:', e);
                }
            },
            
            openChat: function(vendorName) {
                var self = this;
                $('.dokan-orders-area').addClass('chat-active');
                this.$chatMessages.addClass('active');
                $('.chat-with').text('Chat with ' + vendorName);
                $('.order-reference').text('Order #' + this.currentOrderId);
                
                this.loadMessages();
                this.startMessagePolling();
                this.saveState();
            },
            
            closeChat: function() {
                $('.dokan-orders-area').removeClass('chat-active');
                this.$chatMessages.removeClass('active');
                this.stopMessagePolling();
                this.currentOrderId = null;
                this.currentChatUser = null;
                this.saveState();
            },
            
            bindEvents: function() {
                var self = this;
                
                // Use event delegation for dynamic elements
                $(document).on('click', '.open-chat', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $btn = $(this);
                    self.currentOrderId = $btn.data('order-id');
                    self.currentChatUser = $btn.data('vendor-id');
                    self.openChat($btn.data('vendor-name'));
                });
                
                $(document).on('click', '.back-to-orders', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.closeChat();
                });
                
                this.$sendButton.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.sendMessage();
                });
                
                this.$messageInput.on('keypress', function(e) {
                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();
                        self.sendMessage();
                    }
                });
            },
            
            loadMessages: function() {
                var self = this;
                if (!self.currentChatUser || !self.currentOrderId) {
                    console.log('Missing user or order ID for loading messages'); // Debug
                    return;
                }

                console.log('Loading messages...', { // Debug
                    userId: self.currentChatUser,
                    orderId: self.currentOrderId
                });

                $.ajax({
                    url: dokan_chat_vars.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'dokan_load_chat_messages',
                        nonce: dokan_chat_vars.nonce,
                        user_id: self.currentChatUser,
                        order_id: self.currentOrderId
                    },
                    success: function(response) {
                        console.log('Messages loaded:', response); // Debug
                        if (response.success) {
                            self.$messagesContainer.html(response.data);
                            self.$messagesContainer.scrollTop(self.$messagesContainer[0].scrollHeight);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Load messages error:', { // Debug
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                    }
                });
            },

            sendMessage: function() {
                var self = this;
                var message = self.$messageInput.val().trim();
                
                console.log('Attempting to send message...'); // Debug
                console.log('Message input value:', message); // Debug
                console.log('Current chat user:', self.currentChatUser); // Debug
                console.log('Current order ID:', self.currentOrderId); // Debug
                
                if (!message || !self.currentChatUser || !self.currentOrderId) {
                    console.log('Missing data for sending message:', { // Debug
                        message: !!message,
                        userId: self.currentChatUser,
                        orderId: self.currentOrderId
                    });
                    return;
                }

                // Show loading state
                self.$sendButton.prop('disabled', true).text('Sending...');

                $.ajax({
                    url: dokan_chat_vars.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'dokan_send_chat_message',
                        nonce: dokan_chat_vars.nonce,
                        user_id: self.currentChatUser,
                        order_id: self.currentOrderId,
                        message: message
                    },
                    success: function(response) {
                        console.log('Message sent response:', response); // Debug
                        self.$sendButton.prop('disabled', false).text('Send');
                        
                        if (response.success) {
                            self.$messageInput.val('');
                            self.loadMessages();
                        } else {
                            alert('Failed to send message. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Send message error:', { // Debug
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        self.$sendButton.prop('disabled', false).text('Send');
                        alert('Error sending message. Please try again.');
                    }
                });
            },

            startMessagePolling: function() {
                var self = this;
                this.stopMessagePolling();
                console.log('Starting message polling...'); // Debug
                this.pollInterval = setInterval(function() {
                    if (self.currentChatUser && self.currentOrderId) {
                        self.loadMessages();
                    }
                }, 5000);
            },

            stopMessagePolling: function() {
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                    console.log('Message polling stopped'); // Debug
                }
            },

            initCustomerChat: function() {
                var self = this;
                
                // Click conversation
                $('.chat-conversation').on('click', function() {
                    var $conv = $(this);
                    self.currentOrderId = $conv.data('order-id');
                    self.currentChatUser = $conv.data('vendor-id');
                    
                    $('.conversations-list').hide();
                    $('.chat-messages-area').show();
                    $('.chat-with').text('Chat with ' + $conv.data('vendor-name'));
                    $('.order-reference').text('Order #' + self.currentOrderId);
                    
                    self.loadMessages();
                    self.startMessagePolling();
                });
                
                // Back to conversations
                $('.back-to-conversations').on('click', function(e) {
                    e.preventDefault();
                    $('.chat-messages-area').hide();
                    $('.conversations-list').show();
                    self.stopMessagePolling();
                });
            }
        };
        
        DokanChat.init();
    }
})(jQuery);
