(function () {
    // ─── Config & State ───────────────────────────────────────
    const script = document.currentScript;
    const tenantId = script.getAttribute('data-tenant-id');
    const baseUrl = new URL(script.src).origin;
    console.log('[AgWidgetInit] Script Element:', script);
    console.log('[AgWidgetInit] Tenant ID:', tenantId);

    let container, shadow;
    let isOpen = false;
    let isExpanded = false;
    let conversationId = null;
    let sessionToken = null;
    let apiKey = null;
    let chatState = 'ai'; // ai | connecting | human
    let agentName = '';
    let statusPollInterval = null;
    let messagePollInterval = null;
    let lastMessageId = null;
    let renderedMessageIds = new Set();
    let configData = null;
    let pusher = null;
    let wsChannel = null;
    let wsConnected = false;

    // ─── Styles ───────────────────────────────────────────────
    const styles = `
        :host {
            --widget-primary: #3b82f6;
            --widget-primary-dark: #2563eb;
            --widget-bg: #ffffff;
            --widget-text: #1f2937;
            --widget-muted: #64748b;
            --widget-border: #e2e8f0;
            --widget-surface: #f8fafc;
            --widget-success: #10b981;
            --widget-success-dark: #059669;
            --widget-shadow: 0 20px 60px -15px rgba(0, 0, 0, 0.15);
            --widget-font: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { box-sizing: border-box; font-family: var(--widget-font); margin: 0; padding: 0; }

        /* ── Trigger Bubble ─────────────────────────────────── */
        #ag-trigger {
            position: fixed; bottom: 20px; right: 20px;
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, var(--widget-primary), var(--widget-primary-dark));
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.35);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 2147483647;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s;
            border: none; outline: none;
        }
        #ag-trigger:hover { transform: scale(1.08); box-shadow: 0 12px 40px rgba(59, 130, 246, 0.45); }
        #ag-trigger svg { color: white; width: 28px; height: 28px; }
        #ag-trigger .pulse-ring {
            position: absolute; width: 100%; height: 100%; border-radius: 50%;
            border: 2px solid var(--widget-primary); animation: pulseRing 2s infinite;
        }
        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        /* ── Chat Window ───────────────────────────────────── */
        #ag-window {
            position: fixed; bottom: 96px; right: 20px;
            width: 400px; height: 620px; max-height: calc(100vh - 110px);
            background: var(--widget-bg); border-radius: 20px;
            box-shadow: var(--widget-shadow);
            display: none; flex-direction: column; overflow: hidden;
            z-index: 2147483647;
            border: 1px solid rgba(0,0,0,0.06);
            animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            transition: border-radius 0.35s, background 0.35s;
        }
        #ag-window.expanded {
            width: 100vw; height: 100vh; max-height: 100vh;
            bottom: 0; right: 0; border-radius: 0;
        }
        @keyframes slideUp {
            from { transform: translateY(24px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ── Resize Handle (top-left corner) ───────────────── */
        .ag-resize-handle {
            position: absolute; top: 0; left: 0;
            width: 18px; height: 18px;
            cursor: nwse-resize; z-index: 10;
            background: transparent;
        }
        .ag-resize-handle::after {
            content: '';
            position: absolute; top: 4px; left: 4px;
            width: 8px; height: 8px;
            border-top: 2px solid rgba(255,255,255,0.5);
            border-left: 2px solid rgba(255,255,255,0.5);
            border-radius: 2px 0 0 0;
            transition: border-color 0.2s;
        }
        .ag-resize-handle:hover::after {
            border-color: rgba(255,255,255,0.9);
        }

        /* ── Header ────────────────────────────────────────── */
        .ag-header {
            padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;
            background: linear-gradient(135deg, var(--widget-primary), var(--widget-primary-dark));
            color: white; position: relative; overflow: hidden;
            transition: background 0.6s ease;
        }
        .ag-header.human-mode {
            background: linear-gradient(135deg, var(--widget-success), var(--widget-success-dark));
        }
        .ag-header.connecting-mode {
            background: linear-gradient(135deg, var(--widget-primary), #6366f1, var(--widget-primary));
            background-size: 200% 200%;
            animation: gradientShift 2s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .ag-header-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
        .ag-header-avatar {
            width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.2); flex-shrink: 0; font-size: 18px;
        }
        .ag-header-info { min-width: 0; }
        .ag-header-name { font-weight: 700; font-size: 15px; letter-spacing: -0.01em; }
        .ag-header-status {
            font-size: 11px; opacity: 0.85; display: flex; align-items: center; gap: 6px; margin-top: 2px;
        }
        .status-dot {
            width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
        }
        .status-dot.ai { background: rgba(255,255,255,0.6); }
        .status-dot.live { background: #4ade80; animation: pulseDot 1.5s infinite; }
        .status-dot.connecting { background: #fbbf24; animation: pulseDot 0.8s infinite; }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .verified-badge {
            display: inline-flex; align-items: center; gap: 3px;
            background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px;
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .ag-header-actions { display: flex; gap: 6px; }
        .ag-header-btn {
            width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(255,255,255,0.15);
            color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .ag-header-btn:hover { background: rgba(255,255,255,0.25); }
        .ag-header-btn svg { width: 16px; height: 16px; }

        /* ── Welcome Screen ────────────────────────────────── */
        .ag-welcome {
            flex: 1; display: flex; flex-direction: column; padding: 32px 24px; background: var(--widget-surface);
            overflow-y: auto;
        }
        .ag-welcome-hero {
            text-align: center; margin-bottom: 28px;
        }
        .ag-welcome-icon {
            width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 16px;
            background: linear-gradient(135deg, var(--widget-primary), var(--widget-primary-dark));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        }
        .ag-welcome-icon svg { color: white; width: 32px; height: 32px; }
        .ag-welcome-title {
            font-size: 22px; font-weight: 800; color: var(--widget-text);
            letter-spacing: -0.02em; margin-bottom: 8px;
        }
        .ag-welcome-sub {
            font-size: 14px; color: var(--widget-muted); line-height: 1.5;
        }
        .ag-form { display: flex; flex-direction: column; gap: 14px; }
        .ag-form-field { display: flex; flex-direction: column; gap: 4px; }
        .ag-form-label { font-size: 12px; font-weight: 600; color: var(--widget-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .ag-form-input {
            padding: 12px 14px; border-radius: 12px; border: 1.5px solid var(--widget-border);
            font-size: 14px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            background: white;
        }
        .ag-form-input:focus {
            border-color: var(--widget-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .ag-form-input::placeholder { color: #cbd5e1; }
        .ag-btn-primary {
            padding: 14px; border-radius: 14px; border: none; font-size: 14px; font-weight: 700;
            background: linear-gradient(135deg, var(--widget-primary), var(--widget-primary-dark));
            color: white; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
            margin-top: 6px;
        }
        .ag-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
        .ag-btn-primary:active { transform: translateY(0); }
        .ag-btn-skip {
            padding: 10px; border: none; background: none; font-size: 13px; font-weight: 600;
            color: var(--widget-muted); cursor: pointer; text-align: center; transition: color 0.2s;
        }
        .ag-btn-skip:hover { color: var(--widget-primary); }

        /* ── Messages ──────────────────────────────────────── */
        .ag-messages {
            flex: 1; padding: 20px; overflow-y: auto; background: var(--widget-surface);
            display: flex; flex-direction: column; gap: 12px;
            scroll-behavior: smooth;
        }
        .msg { display: flex; flex-direction: column; animation: msgIn 0.25s ease-out; }
        .msg.user { align-items: flex-end; }
        .msg.bot { align-items: flex-start; }
        .msg.agent { align-items: flex-start; }
        .msg.system { align-items: center; }
        @keyframes msgIn {
            from { transform: translateY(8px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .msg-bubble {
            padding: 12px 16px; border-radius: 20px; max-width: 82%;
            font-size: 14px; line-height: 1.55; word-break: break-word;
        }
        .msg.user .msg-bubble {
            background: linear-gradient(135deg, var(--widget-primary), var(--widget-primary-dark));
            color: white; border-bottom-right-radius: 6px;
        }
        .msg.bot .msg-bubble {
            background: white; color: var(--widget-text);
            border-bottom-left-radius: 6px;
            border: 1px solid var(--widget-border);
        }
        .msg.agent .msg-bubble {
            background: white; color: var(--widget-text);
            border-bottom-left-radius: 6px;
            border: 2px solid var(--widget-success);
        }
        .msg-sender {
            font-size: 11px; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;
        }
        .msg-sender.agent-sender { color: var(--widget-success-dark); }
        .msg.system .msg-bubble {
            background: transparent; color: var(--widget-muted); font-size: 12px;
            font-style: italic; max-width: 90%; text-align: center;
        }

        /* ── Connecting Loader ─────────────────────────────── */
        .connecting-loader {
            display: flex; flex-direction: column; align-items: center; gap: 14px;
            padding: 24px; animation: msgIn 0.3s ease-out;
        }
        .dots-loader { display: flex; gap: 6px; }
        .dots-loader span {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--widget-primary); opacity: 0.3;
            animation: dotBounce 1.4s infinite ease-in-out both;
        }
        .dots-loader span:nth-child(1) { animation-delay: -0.32s; }
        .dots-loader span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes dotBounce {
            0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
            40% { opacity: 1; transform: scale(1.2); }
        }
        .connecting-text { font-size: 13px; color: var(--widget-muted); font-weight: 600; }

        /* ── Vehicle Cards ─────────────────────────────────── */
        .cards-container { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; width: 100%; }
        .vehicle-card {
            background: white; border-radius: 14px; border: 1px solid var(--widget-border);
            overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;
            max-width: 300px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .vehicle-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .vehicle-image { width: 100%; height: 140px; object-fit: cover; background: #e2e8f0; }
        .vehicle-info { padding: 12px; }
        .vehicle-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; color: #1e293b; }
        .vehicle-meta { font-size: 13px; color: var(--widget-muted); margin-bottom: 6px; display: flex; justify-content: space-between; }
        .vehicle-price { font-weight: 800; color: var(--widget-primary); font-size: 16px; }
        .vehicle-actions { display: flex; flex-direction: column; gap: 6px; padding: 0 12px 12px; }
        .btn-action {
            width: 100%; padding: 8px; border-radius: 8px; font-size: 12px; font-weight: 600;
            cursor: pointer; text-align: center; border: 1.5px solid var(--widget-border);
            background: var(--widget-surface); color: #475569; transition: all 0.2s;
        }
        .btn-action:hover { background: var(--widget-primary); color: white; border-color: var(--widget-primary); }

        /* ── Input Bar ─────────────────────────────────────── */
        .ag-input-bar {
            padding: 14px 16px; border-top: 1px solid var(--widget-border);
            display: flex; gap: 10px; background: white; align-items: center;
        }
        .ag-input-bar input {
            flex: 1; padding: 12px 16px; border: 1.5px solid var(--widget-border);
            border-radius: 24px; outline: none; font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s; background: var(--widget-surface);
        }
        .ag-input-bar input:focus {
            border-color: var(--widget-primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.08);
            background: white;
        }
        .ag-input-bar button.send-btn {
            width: 44px; height: 44px; border-radius: 50%; border: none;
            background: linear-gradient(135deg, var(--widget-primary), var(--widget-primary-dark));
            color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .ag-input-bar button.send-btn:hover { transform: scale(1.05); box-shadow: 0 6px 16px rgba(59,130,246,0.4); }
        .ag-input-bar button.send-btn svg { width: 20px; height: 20px; }

        /* ── Powered By / Footer ───────────────────────────── */
        .ag-powered {
            text-align: center; padding: 6px; font-size: 10px; color: #94a3b8;
            background: white; border-top: 1px solid rgba(0,0,0,0.03);
        }
    `;

    // ─── SVG Icons ────────────────────────────────────────────
    const ICONS = {
        chat: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-1.074-.865 5.25 5.25 0 0 0 .832-2.382C3.577 16.482 3 14.312 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>',
        send: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>',
        close: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>',
        expand: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9m11.25-5.25v4.5m0-4.5h-4.5m4.5 0L15 9m-11.25 11.25v-4.5m0 4.5h4.5m-4.5 0L9 15m11.25 5.25v-4.5m0 4.5h-4.5m4.5 0L15 15" /></svg>',
        collapse: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" /></svg>',
        human: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>',
        check: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:10px;height:10px"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>',
        bot: '🤖',
        wave: '👋',
    };

    // ─── Initialize ───────────────────────────────────────────
    function init() {
        if (document.getElementById('ag-widget-root')) return;

        container = document.createElement('div');
        container.id = 'ag-widget-root';
        shadow = container.attachShadow({ mode: 'open' });

        const styleTag = document.createElement('style');
        styleTag.textContent = styles;
        shadow.appendChild(styleTag);

        // Trigger button
        const trigger = document.createElement('div');
        trigger.id = 'ag-trigger';
        trigger.innerHTML = `<div class="pulse-ring"></div>${ICONS.chat}`;
        trigger.onclick = () => toggleChat();

        // Chat window
        const win = document.createElement('div');
        win.id = 'ag-window';
        win.innerHTML = buildWindowHTML();

        shadow.appendChild(trigger);
        shadow.appendChild(win);
        document.body.appendChild(container);

        bindEvents();
        fetchConfig();
        initWebSocket(); // Load Pusher.js early
    }

    function buildWindowHTML() {
        return `
            <!-- Header -->
            <div class="ag-header" id="ag-header">
                <div class="ag-header-left">
                    <div class="ag-header-avatar" id="ag-avatar">${ICONS.bot}</div>
                    <div class="ag-header-info">
                        <div class="ag-header-name" id="ag-name">Chat Support</div>
                        <div class="ag-header-status" id="ag-status">
                            <span class="status-dot ai"></span>
                            <span id="ag-status-text">AI Assistant</span>
                        </div>
                    </div>
                </div>
                <div class="ag-header-actions">
                    <button class="ag-header-btn" id="btn-human" title="Request Human">${ICONS.human}</button>
                    <button class="ag-header-btn" id="btn-expand" title="Expand">${ICONS.expand}</button>
                    <button class="ag-header-btn" id="btn-close" title="Close">${ICONS.close}</button>
                </div>
            </div>

            <!-- Welcome Screen -->
            <div class="ag-welcome" id="ag-welcome">
                <div class="ag-welcome-hero">
                    <div class="ag-welcome-icon">${ICONS.chat}</div>
                    <h2 class="ag-welcome-title" id="ag-welcome-title">Welcome!</h2>
                    <p class="ag-welcome-sub" id="ag-welcome-sub">We'd love to know a bit about you. Or, jump straight into the chat!</p>
                </div>
                <form class="ag-form" id="ag-lead-form">
                    <div class="ag-form-field">
                        <label class="ag-form-label">Name</label>
                        <input class="ag-form-input" type="text" id="lead-name" placeholder="John Doe">
                    </div>
                    <div class="ag-form-field">
                        <label class="ag-form-label">Email</label>
                        <input class="ag-form-input" type="email" id="lead-email" placeholder="john@example.com">
                    </div>
                    <div class="ag-form-field">
                        <label class="ag-form-label">Phone</label>
                        <input class="ag-form-input" type="tel" id="lead-phone" placeholder="+1 234 567 890">
                    </div>
                    <button type="submit" class="ag-btn-primary">Start Chat</button>
                    <button type="button" class="ag-btn-skip" id="btn-skip">Skip to Chat →</button>
                </form>
            </div>

            <!-- Chat Area (hidden initially) -->
            <div id="ag-chat-area" style="display:none; flex:1; flex-direction:column; overflow:hidden;">
                <div id="ag-messages" class="ag-messages"></div>
                <div class="ag-input-bar">
                    <input type="text" id="ag-input" placeholder="Type a message...">
                    <button class="send-btn" id="btn-send">${ICONS.send}</button>
                </div>
                <div class="ag-powered">Powered by Antigravity AI</div>
            </div>

            <!-- Resize Handle (top-left corner for dragging) -->
            <div class="ag-resize-handle" id="ag-resize-handle"></div>
        `;
    }

    function bindEvents() {
        $('btn-close').onclick = () => toggleChat(false);
        $('btn-expand').onclick = () => toggleExpand();
        $('btn-human').onclick = () => requestHuman();
        $('btn-send').onclick = () => sendMessage();
        $('btn-skip').onclick = () => skipToChat();
        $('ag-input').onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };
        $('ag-lead-form').onsubmit = (e) => { e.preventDefault(); submitLeadAndStart(); };
        initResizeHandle();
    }

    function $(id) { return shadow.getElementById(id); }

    // ─── Config ───────────────────────────────────────────────
    async function fetchConfig() {
        try {
            const res = await fetch(`${baseUrl}/widget/config-by-tenant/${tenantId}`);
            if (!res.ok) throw new Error('Config fetch failed');
            configData = await res.json();
            apiKey = configData.api_key;

            if (configData.bot_name) $('ag-name').textContent = configData.bot_name;
            if (configData.greeting_message) $('ag-welcome-sub').textContent = configData.greeting_message;

            if (configData.widget_settings?.primary_color) {
                shadow.host.style.setProperty('--widget-primary', configData.widget_settings.primary_color);
            }
        } catch (err) {
            console.error('[AgWidget] Config load failed', err);
        }
    }

    // ─── Toggle Chat Window ───────────────────────────────────
    function toggleChat(force) {
        isOpen = force !== undefined ? force : !isOpen;
        $('ag-window').style.display = isOpen ? 'flex' : 'none';
    }

    function toggleExpand() {
        isExpanded = !isExpanded;
        const win = $('ag-window');
        win.classList.toggle('expanded', isExpanded);
        $('btn-expand').innerHTML = isExpanded ? ICONS.collapse : ICONS.expand;
        localStorage.setItem('ag-expanded', isExpanded ? '1' : '0');
    }

    // ─── Welcome → Chat Transition ───────────────────────────
    function skipToChat() {
        showChatUI();
        startConversation();
    }

    async function submitLeadAndStart() {
        const name = $('lead-name').value.trim();
        const email = $('lead-email').value.trim();
        const phone = $('lead-phone').value.trim();

        showChatUI();
        await startConversation();

        // Submit lead info if provided
        if (apiKey && sessionToken && (name || email || phone)) {
            try {
                await fetch(`${baseUrl}/widget/${apiKey}/submit-lead`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_token: sessionToken, name, email, phone })
                });
            } catch (err) {
                console.error('[AgWidget] Lead submit failed', err);
            }
        }
    }

    function showChatUI() {
        $('ag-welcome').style.display = 'none';
        $('ag-chat-area').style.display = 'flex';
        const greeting = configData?.greeting_message || 'Hello! How can we help you today?';
        appendMessage('bot', { content: greeting });
    }

    // ─── Conversation Lifecycle ──────────────────────────────
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
            // Subscribe to real-time updates
            subscribeToConversation();
        } catch (err) {
            console.error('[AgWidget] Start conversation failed', err);
        }
    }

    async function sendMessage() {
        const input = $('ag-input');
        const text = input.value.trim();
        if (!text || !apiKey || !sessionToken) return;

        appendMessage('user', { content: text });
        input.value = '';
        showTypingIndicator();

        try {
            const res = await fetch(`${baseUrl}/widget/${apiKey}/message`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_token: sessionToken, message: text })
            });
            removeTypingIndicator();
            const data = await res.json();

            if (data.message) {
                // Track last message ID for polling
                if (data.message.id) {
                    lastMessageId = data.message.id;
                    renderedMessageIds.add(String(data.message.id));
                }

                // In human mode, don't re-render the visitor's own message (already shown)
                if (chatState === 'human' && (data.message.sender_type === 'visitor' || data.info === 'Message sent to agent.')) {
                    // Just track the ID, don't render
                } else {
                    const sender = data.message.sender_type === 'human' ? 'agent' : 'bot';
                    appendMessage(sender, {
                        content: data.message.content,
                        vehicle_cards: data.vehicle_cards,
                        agent_name: data.agent_name
                    });
                }
            } else if (data.response) {
                appendMessage('bot', { content: data.response, vehicle_cards: data.vehicle_cards });
            }

            // Check if AI or backend logic triggered a human handoff
            if (data.request_human_handoff || (data.state === 'human' && chatState === 'ai')) {
                setChatState('connecting');
                showConnectingLoader();
                subscribeToConversation();
            }

            // Check if human handoff was suggested
            if (data.suggest_human) {
                appendMessage('system', { content: '💡 Type "talk to a human" or click the 🙋 button above to connect with a real person.' });
            }
        } catch (err) {
            removeTypingIndicator();
            console.error('[AgWidget] Send message failed', err);
            appendMessage('system', { content: 'Message failed to send. Please try again.' });
        }
    }

    // ─── Human Handoff ──────────────────────────────────────
    async function requestHuman() {
        if (chatState === 'human' || chatState === 'connecting') return;
        if (!apiKey || !sessionToken) {
            await startConversation();
            showChatUI();
        }

        setChatState('connecting');
        showConnectingLoader();

        // Subscribe BEFORE fetch so we catch the broadcast event
        subscribeToConversation();

        try {
            const res = await fetch(`${baseUrl}/widget/${apiKey}/request-human`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_token: sessionToken })
            });
            const data = await res.json();

            // Append the system message returned by the backend (e.g., "I've notified our team...")
            if (data.message && data.message.content) {
                appendMessage('system', { content: data.message.content });
            }

            // If the backend already transitioned to human, handle it directly
            // (in case the WS event was missed due to timing)
            if (data.state === 'human' && chatState !== 'human') {
                removeConnectingLoader();
                agentName = data.agent_name || 'Support Agent';
                setChatState('human');
                appendMessage('system', { content: `✅ ${agentName} has joined the chat!` });
            }
        } catch (err) {
            console.error('[AgWidget] Human request failed', err);
            setChatState('ai');
            removeConnectingLoader();
            appendMessage('system', { content: 'Could not connect. Our AI is still here to help!' });
        }
    }

    // ─── WebSocket Connection Monitor ─────────────────────────
    setInterval(() => {
        if (!pusher) return;

        if (!wsConnected) {
            // WS disconnected, check if we need to fall back
            if (chatState === 'human' && !messagePollInterval) {
                console.warn('[AgWidget] WS disconnected during human chat. Starting message polling fallback.');
                startMessagePolling();
            } else if (chatState === 'connecting' && !statusPollInterval) {
                console.warn('[AgWidget] WS disconnected while connecting. Starting status polling fallback.');
                startStatusPolling();
            }
        } else {
            // WS reconnected, stop polling
            if (messagePollInterval) {
                clearInterval(messagePollInterval);
                messagePollInterval = null;
                console.log('[AgWidget] WS reconnected. Stopped message polling.');
            }
            if (statusPollInterval) {
                clearInterval(statusPollInterval);
                statusPollInterval = null;
                console.log('[AgWidget] WS reconnected. Stopped status polling.');
            }
        }
    }, 5000);

    // ─── WebSocket Setup (Reverb via Pusher.js) ──────────────
    function initWebSocket() {
        return new Promise((resolve) => {
            if (window.Pusher) { connectPusher(); resolve(); return; }
            const s = document.createElement('script');
            s.src = 'https://js.pusher.com/8.3/pusher.min.js';
            s.onload = () => { connectPusher(); resolve(); };
            s.onerror = () => { console.warn('[AgWidget] Pusher.js failed to load, using polling fallback'); resolve(); };
            document.head.appendChild(s);
        });
    }

    function connectPusher() {
        try {
            pusher = new window.Pusher(window.__AG_REVERB_KEY || '8adrncxfohju9mwactdf', {
                wsHost: window.__AG_REVERB_HOST || new URL(baseUrl).hostname,
                wsPort: window.__AG_REVERB_PORT || 8080,
                wssPort: window.__AG_REVERB_PORT || 8080,
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: 'mt1',
            });
            pusher.connection.bind('connected', () => { wsConnected = true; console.log('[AgWidget] WebSocket connected'); });
            pusher.connection.bind('error', () => { wsConnected = false; });
            pusher.connection.bind('disconnected', () => { wsConnected = false; });
        } catch (e) {
            console.warn('[AgWidget] Pusher init failed', e);
        }
    }

    function subscribeToConversation() {
        if (!conversationId) return;

        // Unsubscribe from any previous channel
        if (wsChannel) {
            pusher?.unsubscribe('chat-conversation.' + wsChannel);
        }
        wsChannel = conversationId;

        if (pusher) {
            console.log('[AgWidget] Subscribing to public channel: chat-conversation.' + conversationId);
            const channel = pusher.subscribe('chat-conversation.' + conversationId);

            channel.bind('message.sent', (data) => {
                const msg = data.message;
                if (!msg || renderedMessageIds.has(String(msg.id))) return;
                renderedMessageIds.add(String(msg.id));
                lastMessageId = msg.id;

                console.log('[AgWidget] Received message via WS:', msg);

                if (msg.sender_type === 'human' || msg.sender_type === 'human_agent') {
                    appendMessage('agent', { content: msg.content, agent_name: agentName });
                } else if (msg.sender_type === 'ai' && msg.message_type === 'system') {
                    appendMessage('system', { content: msg.content });
                }
                // visitor messages are not rendered (already shown when sent)
            });

            channel.bind('state.changed', (data) => {
                const newState = data.new_state;
                if (newState === 'human' && chatState !== 'human') {
                    removeConnectingLoader();
                    agentName = data.agent_name || 'Support Agent';
                    setChatState('human');
                    appendMessage('system', { content: `✅ ${agentName} has joined the chat!` });
                } else if (newState === 'ai' && chatState === 'human') {
                    setChatState('ai');
                    appendMessage('system', { content: '🤖 The support agent has ended the session. I\'m your AI assistant — how can I help?' });
                } else if (newState === 'closed') {
                    setChatState('ai');
                    appendMessage('system', { content: 'This conversation has been closed. Feel free to start a new one!' });
                }
            });

            console.log('[AgWidget] Subscribed to WS channel:', conversationId);
        } else {
            // Fallback to polling if WS isn't connected
            console.log('[AgWidget] WS not connected, falling back to polling');
            startStatusPolling();
        }
    }

    // ─── Polling Fallback ─────────────────────────────────────
    function startStatusPolling() {
        if (statusPollInterval) clearInterval(statusPollInterval);
        showConnectingLoader();

        statusPollInterval = setInterval(async () => {
            try {
                const res = await fetch(`${baseUrl}/widget/${apiKey}/status?session_token=${sessionToken}`);
                const data = await res.json();

                if (data.state === 'human') {
                    clearInterval(statusPollInterval);
                    statusPollInterval = null;
                    removeConnectingLoader();
                    agentName = data.agent_name || 'Support Agent';
                    setChatState('human');
                    appendMessage('system', { content: `✅ ${agentName} has joined the chat!` });
                    startMessagePolling();
                } else if (data.state === 'ai' && chatState === 'human') {
                    clearInterval(statusPollInterval);
                    setChatState('ai');
                    appendMessage('system', { content: '🤖 The support agent has ended the session. I\'m your AI assistant — how can I help?' });
                }
            } catch (err) { /* continue polling */ }
        }, 3000);

        setTimeout(() => {
            if (statusPollInterval) {
                clearInterval(statusPollInterval);
                statusPollInterval = null;
                removeConnectingLoader();
                if (chatState === 'connecting') {
                    setChatState('ai');
                    appendMessage('system', { content: 'No agents available right now. Our AI assistant is still here to help!' });
                }
            }
        }, 600000);
    }

    function startMessagePolling() {
        if (messagePollInterval) clearInterval(messagePollInterval);

        messagePollInterval = setInterval(async () => {
            if (chatState !== 'human' && chatState !== 'connecting') {
                clearInterval(messagePollInterval);
                messagePollInterval = null;
                return;
            }
            try {
                let url = `${baseUrl}/widget/${apiKey}/messages?session_token=${sessionToken}`;
                if (lastMessageId) url += `&after=${lastMessageId}`;
                const res = await fetch(url);
                const data = await res.json();

                if (data.messages?.length > 0) {
                    data.messages.forEach(msg => {
                        if (renderedMessageIds.has(String(msg.id))) return;
                        renderedMessageIds.add(String(msg.id));
                        if (msg.sender_type === 'human' || msg.sender_type === 'human_agent') {
                            appendMessage('agent', { content: msg.content, agent_name: agentName });
                        } else if (msg.sender_type === 'ai' && msg.message_type === 'system') {
                            appendMessage('system', { content: msg.content });
                        }
                        lastMessageId = msg.id;
                    });
                }
            } catch (err) { console.error('[AgWidget] Message poll failed', err); }
        }, 2500);
    }

    function setChatState(state) {
        chatState = state;
        const header = $('ag-header');
        const statusDot = shadow.querySelector('.status-dot');
        const statusText = $('ag-status-text');
        const avatar = $('ag-avatar');

        header.className = 'ag-header';
        if (statusDot) statusDot.className = 'status-dot';

        switch (state) {
            case 'ai':
                if (statusDot) statusDot.classList.add('ai');
                statusText.textContent = 'AI Assistant';
                avatar.innerHTML = ICONS.bot;
                break;
            case 'connecting':
                header.classList.add('connecting-mode');
                if (statusDot) statusDot.classList.add('connecting');
                statusText.textContent = 'Connecting to agent...';
                avatar.innerHTML = '⏳';
                break;
            case 'human':
                header.classList.add('human-mode');
                if (statusDot) statusDot.classList.add('live');
                statusText.innerHTML = `<span class="verified-badge">${ICONS.check} Verified Human</span>`;
                avatar.textContent = agentName.charAt(0).toUpperCase();
                $('ag-name').textContent = agentName;
                $('btn-human').style.display = 'none';
                break;
        }
    }

    // ─── UI Helpers ─────────────────────────────────────────
    function showTypingIndicator() {
        const msgs = $('ag-messages');
        const loader = document.createElement('div');
        loader.id = 'typing-indicator';
        loader.className = 'msg bot';
        loader.innerHTML = '<div class="msg-bubble"><div class="dots-loader"><span></span><span></span><span></span></div></div>';
        msgs.appendChild(loader);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function removeTypingIndicator() {
        const el = $('typing-indicator');
        if (el) el.remove();
    }

    function showConnectingLoader() {
        const msgs = $('ag-messages');
        const loader = document.createElement('div');
        loader.id = 'connecting-loader';
        loader.className = 'connecting-loader';
        loader.innerHTML = `
            <div class="dots-loader"><span></span><span></span><span></span></div>
            <div class="connecting-text">Connecting to a human agent...</div>
        `;
        msgs.appendChild(loader);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function removeConnectingLoader() {
        const el = $('connecting-loader');
        if (el) el.remove();
    }

    function appendMessage(role, data) {
        const msgs = $('ag-messages');
        const wrapper = document.createElement('div');
        wrapper.className = `msg ${role}`;

        if (data.content) {
            // Agent name label for human messages
            if (role === 'agent' && data.agent_name) {
                const sender = document.createElement('div');
                sender.className = 'msg-sender agent-sender';
                sender.innerHTML = `${ICONS.check} ${data.agent_name}`;
                wrapper.appendChild(sender);
            }

            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble';
            bubble.textContent = data.content;
            wrapper.appendChild(bubble);
        }

        if (data.vehicle_cards?.length > 0) {
            const cardsContainer = document.createElement('div');
            cardsContainer.className = 'cards-container';
            data.vehicle_cards.forEach(card => {
                const cardEl = document.createElement('div');
                cardEl.className = 'vehicle-card';
                const imgUrl = card.image_url || 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80&w=400';
                cardEl.innerHTML = `
                    <img src="${imgUrl}" class="vehicle-image" alt="${card.make} ${card.model}" loading="lazy">
                    <div class="vehicle-info">
                        <div class="vehicle-title">${card.year} ${card.make} ${card.model}</div>
                        <div class="vehicle-meta">
                            <span>${card.mileage?.toLocaleString() || '0'} mi</span>
                            <span class="vehicle-price">$${card.price?.toLocaleString() || '0'}</span>
                        </div>
                    </div>
                    <div class="vehicle-actions">
                        ${(card.cta || []).map(c => `<div class="btn-action" data-action="${c.action}" data-id="${card.id}">${c.label}</div>`).join('')}
                    </div>
                `;
                cardsContainer.appendChild(cardEl);
            });
            wrapper.appendChild(cardsContainer);

            // Bind action buttons
            wrapper.querySelectorAll('.btn-action').forEach(btn => {
                btn.onclick = () => {
                    const title = btn.closest('.vehicle-card').querySelector('.vehicle-title').textContent;
                    $('ag-input').value = `I'm interested in the ${title}. Can you tell me more about ${btn.textContent}?`;
                    sendMessage();
                };
            });
        }

        msgs.appendChild(wrapper);
        msgs.scrollTop = msgs.scrollHeight;
    }

    // ─── Drag-to-Resize ─────────────────────────────────────
    function initResizeHandle() {
        const handle = $('ag-resize-handle');
        const win = $('ag-window');
        if (!handle || !win) return;

        let isResizing = false;
        let startX, startY, startW, startH, startRight, startBottom;

        handle.addEventListener('mousedown', onStart);
        handle.addEventListener('touchstart', onStart, { passive: false });

        function onStart(e) {
            if (isExpanded) return; // Don't resize in fullscreen
            e.preventDefault();
            e.stopPropagation();
            isResizing = true;

            const touch = e.touches ? e.touches[0] : e;
            startX = touch.clientX;
            startY = touch.clientY;

            const rect = win.getBoundingClientRect();
            startW = rect.width;
            startH = rect.height;
            startRight = window.innerWidth - rect.right;
            startBottom = window.innerHeight - rect.bottom;

            // Disable transition during drag
            win.style.transition = 'none';

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('touchend', onEnd);
        }

        function onMove(e) {
            if (!isResizing) return;
            e.preventDefault();

            const touch = e.touches ? e.touches[0] : e;
            const dx = startX - touch.clientX; // moving left = wider
            const dy = startY - touch.clientY; // moving up = taller

            const newW = Math.max(320, Math.min(startW + dx, window.innerWidth - 40));
            const newH = Math.max(400, Math.min(startH + dy, window.innerHeight - 40));

            win.style.width = newW + 'px';
            win.style.height = newH + 'px';
            win.style.maxHeight = newH + 'px';
            win.style.right = startRight + 'px';
            win.style.bottom = startBottom + 'px';
        }

        function onEnd() {
            if (!isResizing) return;
            isResizing = false;

            win.style.transition = 'border-radius 0.35s, background 0.35s';

            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onEnd);
            document.removeEventListener('touchmove', onMove);
            document.removeEventListener('touchend', onEnd);

            // Save custom size
            localStorage.setItem('ag-widget-w', win.style.width);
            localStorage.setItem('ag-widget-h', win.style.height);
        }

        // Restore saved size
        const savedW = localStorage.getItem('ag-widget-w');
        const savedH = localStorage.getItem('ag-widget-h');
        if (savedW && savedH) {
            win.style.width = savedW;
            win.style.height = savedH;
            win.style.maxHeight = savedH;
        }
    }

    // ─── Auto-initialize ──────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
