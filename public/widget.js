(function () {
    // 1. Config & State
    const script = document.currentScript;
    const tenantId = script.getAttribute('data-tenant-id');
    const baseUrl = new URL(script.src).origin;

    let container;
    let isOpen = false;
    let conversationId = null;
    let sessionToken = null;
    let apiKey = null;

    // 2. Styles
    const styles = `
        :host {
            --widget-primary: #3b82f6;
            --widget-bg: #ffffff;
            --widget-text: #1f2937;
            --widget-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --widget-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        * {
            box-sizing: border-box;
            font-family: var(--widget-font);
        }

        #antigravity-widget-trigger {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--widget-primary);
            box-shadow: var(--widget-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2147483647;
            transition: transform 0.2s, background 0.2s;
        }

        #antigravity-widget-trigger:hover {
            transform: scale(1.1);
        }

        #antigravity-widget-trigger svg {
            color: white;
            width: 30px;
            height: 30px;
        }

        #antigravity-chat-window {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 380px;
            height: 600px;
            max-height: calc(100vh - 110px);
            background: var(--widget-bg);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 2147483647;
            border: 1px solid rgba(0,0,0,0.05);
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .chat-header {
            background: var(--widget-primary);
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            display: flex;
            flex-direction: column;
        }

        .message.user { align-items: flex-end; }
        .message.bot { align-items: flex-start; }

        .message-bubble {
            padding: 10px 16px;
            border-radius: 18px;
            max-width: 85%;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .message.user .message-bubble {
            background: var(--widget-primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.bot .message-bubble {
            background: white;
            color: var(--widget-text);
            border-bottom-left-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        /* Vehicle Cards */
        .cards-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
            width: 100%;
        }

        .vehicle-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: transform 0.2s;
            max-width: 300px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .vehicle-card:hover {
            transform: translateY(-2px);
        }

        .vehicle-image {
            width: 100%;
            height: 140px;
            object-fit: cover;
            background: #cbd5e1;
        }

        .vehicle-info {
            padding: 12px;
        }

        .vehicle-title {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
            color: #1e293b;
        }

        .vehicle-meta {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .vehicle-price {
            font-weight: 800;
            color: var(--widget-primary);
            font-size: 16px;
        }

        .vehicle-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 0 12px 12px;
        }

        .btn-action {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: var(--widget-primary);
            color: white;
            border-color: var(--widget-primary);
        }

        .chat-input {
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            background: white;
        }

        .chat-input input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .chat-input input:focus {
            border-color: var(--widget-primary);
        }

        .chat-input button {
            background: var(--widget-primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    `;

    // 3. Initialize UI
    function init() {
        if (document.getElementById('antigravity-widget-container')) return;

        container = document.createElement('div');
        container.id = 'antigravity-widget-container';
        const shadow = container.attachShadow({ mode: 'open' });

        const styleTag = document.createElement('style');
        styleTag.textContent = styles;
        shadow.appendChild(styleTag);

        const trigger = document.createElement('div');
        trigger.id = 'antigravity-widget-trigger';
        trigger.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-1.074-.865 5.25 5.25 0 0 0 .832-2.382C3.577 16.482 3 14.312 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>`;

        const chatWindow = document.createElement('div');
        chatWindow.id = 'antigravity-chat-window';
        chatWindow.innerHTML = `
            <div class="chat-header">
                <span id="bot-name" style="font-weight: 700; letter-spacing: -0.025em;">Chat Support</span>
                <span id="close-chat" style="cursor: pointer; font-size: 24px; line-height: 1;">&times;</span>
            </div>
            <div id="messages" class="chat-messages"></div>
            <div class="chat-input">
                <input type="text" id="user-input" placeholder="Type a message...">
                <button id="send-message">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                </button>
            </div>
        `;

        shadow.appendChild(trigger);
        shadow.appendChild(chatWindow);
        document.body.appendChild(container);

        // Events
        trigger.onclick = () => toggleChat();
        shadow.getElementById('close-chat').onclick = () => toggleChat(false);
        shadow.getElementById('send-message').onclick = () => sendMessage();
        shadow.getElementById('user-input').onkeypress = (e) => {
            if (e.key === 'Enter') sendMessage();
        };

        // Load config
        fetchConfig();
    }

    async function fetchConfig() {
        try {
            const response = await fetch(`${baseUrl}/widget/config-by-tenant/${tenantId}`);
            if (!response.ok) throw new Error('Config fetch failed');

            const config = await response.json();
            apiKey = config.api_key;

            if (config.bot_name) {
                container.shadowRoot.getElementById('bot-name').textContent = config.bot_name;
            }

            if (config.widget_settings && config.widget_settings.primary_color) {
                container.shadowRoot.host.style.setProperty('--widget-primary', config.widget_settings.primary_color);
            }

            appendMessage('bot', { content: config.greeting_message || 'Hello! How can we help you today?' });
        } catch (err) {
            console.error('Failed to load chat widget config', err);
        }
    }

    function toggleChat(force) {
        isOpen = force !== undefined ? force : !isOpen;
        const window = container.shadowRoot.getElementById('antigravity-chat-window');
        window.style.display = isOpen ? 'flex' : 'none';

        if (isOpen && !sessionToken) {
            startConversation();
        }
    }

    async function startConversation() {
        if (!apiKey) return;
        try {
            const res = await fetch(`${baseUrl}/widget/${apiKey}/start`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ metadata: { url: window.location.href } })
            });
            const data = await res.json();
            sessionToken = data.session_token;
            conversationId = data.conversation_id;
        } catch (err) {
            console.error('Failed to start conversation', err);
        }
    }

    async function sendMessage() {
        const input = container.shadowRoot.getElementById('user-input');
        const text = input.value.trim();
        if (!text || !apiKey || !sessionToken) return;

        appendMessage('user', { content: text });
        input.value = '';

        try {
            const res = await fetch(`${baseUrl}/widget/${apiKey}/message`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_token: sessionToken,
                    message: text
                })
            });
            const data = await res.json();

            if (data.message) {
                appendMessage('bot', {
                    content: data.message.content,
                    vehicle_cards: data.vehicle_cards
                });
            } else if (data.response) {
                appendMessage('bot', {
                    content: data.response,
                    vehicle_cards: data.vehicle_cards
                });
            }
        } catch (err) {
            console.error('Failed to send message', err);
            appendMessage('error', { content: 'Failed to send message. Please try again.' });
        }
    }

    function appendMessage(role, data) {
        const messages = container.shadowRoot.getElementById('messages');
        const wrapper = document.createElement('div');
        wrapper.className = `message ${role}`;

        if (data.content) {
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.textContent = data.content;
            wrapper.appendChild(bubble);
        }

        if (data.vehicle_cards && data.vehicle_cards.length > 0) {
            const cardsContainer = document.createElement('div');
            cardsContainer.className = 'cards-container';

            data.vehicle_cards.forEach(card => {
                const cardEl = document.createElement('div');
                cardEl.className = 'vehicle-card';
                cardEl.innerHTML = `
                    <img src="${card.image_url || 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=400'}" class="vehicle-image" alt="${card.make} ${card.model}">
                    <div class="vehicle-info">
                        <div class="vehicle-title">${card.year} ${card.make} ${card.model}</div>
                        <div class="vehicle-meta">
                            <span>${card.mileage} mi</span>
                            <span class="vehicle-price">$${card.price}</span>
                        </div>
                    </div>
                    <div class="vehicle-actions">
                        ${card.cta.map(c => `<div class="btn-action" data-action="${c.action}" data-id="${card.id}">${c.label}</div>`).join('')}
                    </div>
                `;
                cardsContainer.appendChild(cardEl);
            });
            wrapper.appendChild(cardsContainer);
        }

        messages.appendChild(wrapper);

        // Add event listeners to actions
        wrapper.querySelectorAll('.btn-action').forEach(btn => {
            btn.onclick = () => {
                const action = btn.getAttribute('data-action');
                const id = btn.getAttribute('data-id');
                console.log('Action clicked:', action, id);
                inputMessage(`I'm interested in the ${btn.closest('.vehicle-card').querySelector('.vehicle-title').textContent}. Can you tell me more about ${btn.textContent}?`);
            };
        });

        messages.scrollTop = messages.scrollHeight;
    }

    function inputMessage(text) {
        const input = container.shadowRoot.getElementById('user-input');
        input.value = text;
        sendMessage();
    }

    // Run
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
