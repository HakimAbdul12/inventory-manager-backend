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
    let inactivityTimer = null;
    let inactivityWarningTimer = null;

    const styles = `
        :host {
            --widget-primary: #000000;
            --widget-primary-dark: #18181b;
            --widget-gradient: linear-gradient(135deg, #09090b 0%, #27272a 100%);
            --widget-bg: #ffffff;
            --widget-text: #09090b;
            --widget-muted: #71717a;
            --widget-border: rgba(0, 0, 0, 0.08);
            --widget-surface: #f4f4f5;
            --widget-surface-hover: #e4e4e7;
            --widget-success: #10b981;
            --widget-success-dark: #059669;
            --widget-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.18), 0 12px 24px -8px rgba(0, 0, 0, 0.08), 0 4px 8px -4px rgba(0, 0, 0, 0.04);
            --widget-font: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --spring-easing: cubic-bezier(0.175, 0.885, 0.32, 1.15);
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { box-sizing: border-box; font-family: var(--widget-font); margin: 0; padding: 0; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.25); }

        /* ── Trigger Bubble ─────────────────────────────────── */
        #ag-trigger {
            position: fixed; bottom: 24px; right: 24px;
            width: 56px; height: 56px; border-radius: 28px;
            background: var(--widget-gradient);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15), 0 4px 8px rgba(0, 0, 0, 0.08);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 2147483647;
            transition: transform 0.4s var(--spring-easing), box-shadow 0.4s ease;
            border: 1px solid rgba(255,255,255,0.1); outline: none;
        }
        #ag-trigger:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25); }
        
        #ag-trigger svg { color: white; width: 26px; height: 26px; }
        .icon-chat, .icon-close {
            position: absolute;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.85s cubic-bezier(0.34, 1.25, 0.64, 1), opacity 0.4s ease;
        }
        .icon-close { opacity: 0; transform: rotate(-180deg) scale(0.3); }
        .icon-chat { opacity: 1; transform: rotate(0) scale(1); }
        #ag-trigger.chat-open .icon-chat { opacity: 0; transform: rotate(180deg) scale(0.3); }
        #ag-trigger.chat-open .icon-close { opacity: 1; transform: rotate(0) scale(1); }

        #ag-trigger .pulse-ring {
            position: absolute; width: 100%; height: 100%; border-radius: 50%;
            border: 2px solid rgba(0,0,0,0.2); animation: pulseRing 3s infinite; opacity: 0;
        }
        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        /* ── Chat Window ───────────────────────────────────── */
        #ag-window {
            position: fixed; bottom: 96px; right: 24px;
            width: 380px; height: 680px; max-height: calc(100vh - 120px);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(40px) saturate(150%);
            -webkit-backdrop-filter: blur(40px) saturate(150%);
            border-radius: 24px;
            box-shadow: var(--widget-shadow);
            display: flex; flex-direction: column; overflow: hidden;
            z-index: 2147483647;
            border: 1px solid rgba(0,0,0,0.06);
            
            /* Emergence Animation Setup */
            transform-origin: calc(100% - 28px) calc(100% + 44px);
            opacity: 0;
            transform: perspective(1200px) rotateX(15deg) rotateY(15deg) scale(0.01);
            border-radius: 120px;
            pointer-events: none;
            visibility: hidden;
            /* Shrinking (closing) transition */
            transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease, border-radius 0.7s ease, visibility 0.7s;
        }
        #ag-window.ag-open {
            opacity: 1;
            transform: perspective(1200px) rotateX(0deg) rotateY(0deg) scale(1);
            border-radius: 24px;
            pointer-events: auto;
            visibility: visible;
            /* Blossoming (opening) transition */
            transition: transform 0.85s cubic-bezier(0.34, 1.25, 0.64, 1), opacity 0.5s ease, border-radius 0.85s ease, visibility 0s;
        }
        #ag-window.expanded {
            width: 100vw; height: 100vh; max-height: 100vh;
            bottom: 0; right: 0; border-radius: 0;
            transform-origin: calc(100% - 52px) calc(100% - 52px);
        }

        /* ── Resize Handle (top-left corner) ───────────────── */
        .ag-resize-handle {
            position: absolute; top: 0; left: 0;
            width: 20px; height: 20px;
            cursor: nwse-resize; z-index: 10;
        }
        .ag-resize-handle::after {
            content: ''; position: absolute; top: 6px; left: 6px;
            width: 6px; height: 6px;
            border-top: 2px solid rgba(0,0,0,0.15);
            border-left: 2px solid rgba(0,0,0,0.15);
            border-radius: 2px 0 0 0; transition: border-color 0.2s;
        }
        .ag-resize-handle:hover::after { border-color: rgba(0,0,0,0.3); }

        /* ── Header ────────────────────────────────────────── */
        .ag-header {
            padding: 20px 24px 16px; display: flex; justify-content: space-between; align-items: center;
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            color: var(--widget-text); position: relative; overflow: hidden;
            transition: background 0.6s ease; z-index: 10;
        }
        .ag-header.human-mode { background: rgba(236, 253, 245, 0.5); }
        .ag-header.connecting-mode { background: rgba(244, 244, 245, 0.5); }
        .ag-header-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
        .ag-header-avatar {
            width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
            background: var(--widget-gradient); flex-shrink: 0; font-size: 18px;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.2), 0 2px 8px rgba(0,0,0,0.1); color: white;
            border: 1px solid rgba(0,0,0,0.1); 
        }
        .ag-header.human-mode .ag-header-avatar {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.2), 0 2px 8px rgba(16, 185, 129, 0.2);
        }
        .ag-header-info { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
        .ag-header-name { font-weight: 700; font-size: 15px; letter-spacing: -0.02em; color: var(--widget-text); }
        .ag-header-status {
            font-size: 11.5px; font-weight: 500; color: var(--widget-muted); display: flex; align-items: center; gap: 6px;
        }
        .status-dot {
            width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
        }
        .status-dot.ai { background: var(--widget-text); }
        .status-dot.live { background: var(--widget-success); box-shadow: 0 0 0 2px rgba(16,185,129,0.2); animation: pulseDot 2s infinite; }
        .status-dot.connecting { background: #f59e0b; animation: pulseDot 1s infinite; }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .verified-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: var(--widget-surface); padding: 2px 6px; border-radius: 6px;
            font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
            color: var(--widget-text); border: 1px solid rgba(0,0,0,0.06);
        }
        .ag-header-actions { display: flex; gap: 2px; }
        .ag-header-btn {
            width: 32px; height: 32px; border-radius: 8px; border: none; background: transparent;
            color: var(--widget-muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .ag-header-btn:hover { background: var(--widget-surface); color: var(--widget-text); }
        .ag-header-btn svg { width: 16px; height: 16px; stroke-width: 2.2; }

        /* ── Welcome Screen ────────────────────────────────── */
        .ag-welcome {
            flex: 1; display: flex; flex-direction: column; padding: 40px 32px; background: transparent;
            overflow-y: auto; justify-content: center;
        }
        .ag-welcome-hero { text-align: center; margin-bottom: 36px; }
        .ag-welcome-icon {
            width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 24px;
            background: var(--widget-gradient);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15), inset 0 2px 4px rgba(255,255,255,0.2);
            transform: rotate(-4deg);
            border: 1px solid rgba(0,0,0,0.1);
        }
        .ag-welcome-icon svg { color: white; width: 32px; height: 32px; transform: rotate(4deg); stroke-width: 2; }
        .ag-welcome-title {
            font-size: 24px; font-weight: 800; color: var(--widget-text);
            letter-spacing: -0.04em; margin-bottom: 8px; line-height: 1.2;
        }
        .ag-welcome-sub { font-size: 14px; color: var(--widget-muted); line-height: 1.5; font-weight: 500; }
        .ag-form { display: flex; flex-direction: column; gap: 16px; }
        .ag-form-field { display: flex; flex-direction: column; gap: 6px; }
        .ag-form-label { font-size: 11px; font-weight: 700; color: var(--widget-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-left: 2px; }
        .ag-form-input {
            padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08);
            font-size: 14px; outline: none; transition: all 0.2s;
            background: rgba(255,255,255,0.8); color: var(--widget-text);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
            font-weight: 500;
        }
        .ag-form-input:focus {
            border-color: rgba(0,0,0,0.2); background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04), inset 0 2px 4px rgba(0,0,0,0.01);
        }
        .ag-form-input::placeholder { color: #a1a1aa; font-weight: 400; }
        .ag-btn-primary {
            padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); font-size: 14px; font-weight: 600;
            background: var(--widget-gradient); letter-spacing: 0.01em;
            color: white; cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 8px; position: relative; overflow: hidden;
        }
        .ag-btn-primary::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 50%;
            background: linear-gradient(rgba(255,255,255,0.1), transparent);
            border-radius: 12px 12px 0 0;
        }
        .ag-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }
        .ag-btn-primary:active { transform: translateY(1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ag-btn-skip {
            padding: 12px; border: none; background: none; font-size: 13px; font-weight: 600;
            color: var(--widget-muted); cursor: pointer; text-align: center; transition: color 0.2s;
        }
        .ag-btn-skip:hover { color: var(--widget-text); }

        /* ── Messages ──────────────────────────────────────── */
        .ag-messages {
            flex: 1; padding: 24px 20px; overflow-y: auto; background: transparent;
            display: flex; flex-direction: column; gap: 16px; scroll-behavior: smooth;
        }
        .msg { display: flex; flex-direction: column; animation: msgIn 0.4s var(--spring-easing) forwards; }
        .msg.user { align-items: flex-end; }
        .msg.bot { align-items: flex-start; }
        .msg.agent { align-items: flex-start; }
        .msg.system { align-items: center; }
        @keyframes msgIn {
            from { transform: translateY(12px) scale(0.96); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .msg-bubble {
            padding: 12px 16px; max-width: 85%;
            font-size: 14px; line-height: 1.5; word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            font-weight: 400; letter-spacing: -0.01em;
        }
        .msg.user .msg-bubble {
            background: var(--widget-text); color: white;
            border-radius: 18px 18px 4px 18px;
        }
        .msg.bot .msg-bubble {
            background: var(--widget-surface); color: var(--widget-text);
            border-radius: 18px 18px 18px 4px; border: 1px solid rgba(0,0,0,0.03);
        }
        .msg.agent .msg-bubble {
            background: #f0fdf4; color: #166534;
            border-radius: 18px 18px 18px 4px; border: 1px solid #dcfce7;
        }
        .msg-sender {
            font-size: 11px; font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 4px; color: var(--widget-muted);
            margin-left: 4px; margin-right: 4px;
        }
        .msg-sender.agent-sender { color: var(--widget-success-dark); }
        .msg.system .msg-bubble {
            background: transparent; color: var(--widget-muted); font-size: 11px; font-weight: 500;
            max-width: 90%; text-align: center; box-shadow: none; padding: 4px 12px;
        }

        /* ── Inactivity Popup ──────────────────────────────── */
        .ag-inactivity-popup {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            z-index: 100; display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.4s ease;
        }
        .ag-inactivity-popup.active { opacity: 1; pointer-events: auto; }
        .ag-inactivity-content {
            background: rgba(255,255,255,0.95); padding: 32px 24px; border-radius: 20px; width: 85%;
            text-align: center; box-shadow: 0 24px 48px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.05);
            transform: scale(0.95) translateY(10px); transition: all 0.5s var(--spring-easing);
        }
        .ag-inactivity-popup.active .ag-inactivity-content { transform: scale(1) translateY(0); }
        .ag-inactivity-title { font-size: 16px; font-weight: 800; color: var(--widget-text); margin-bottom: 6px; letter-spacing: -0.02em; }
        .ag-inactivity-text { font-size: 13px; color: var(--widget-muted); margin-bottom: 20px; line-height: 1.5; font-weight: 500; }
        .ag-inactivity-countdown { font-size: 36px; font-weight: 800; color: var(--widget-text); margin-bottom: 24px; font-variant-numeric: tabular-nums; }
        .ag-btn-keep-open {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);
            background: var(--widget-gradient); color: white; font-weight: 600; font-size: 14px;
            cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .ag-btn-keep-open:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(0,0,0,0.15); }

        /* ── Connecting Loader ─────────────────────────────── */
        .connecting-loader {
            display: flex; flex-direction: column; align-items: center; gap: 12px;
            padding: 32px; animation: msgIn 0.4s var(--spring-easing);
        }
        .dots-loader { display: flex; gap: 4px; }
        .dots-loader span {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--widget-text); opacity: 0.2;
            animation: dotBounce 1.4s infinite ease-in-out both;
        }
        .dots-loader span:nth-child(1) { animation-delay: -0.32s; }
        .dots-loader span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes dotBounce {
            0%, 80%, 100% { opacity: 0.2; transform: scale(0.8); }
            40% { opacity: 1; transform: scale(1.2); }
        }
        .connecting-text { font-size: 12px; color: var(--widget-muted); font-weight: 600; letter-spacing: 0.02em; }

        /* ── Vehicle Cards ─────────────────────────────────── */
        .cards-container { display: flex; flex-direction: column; gap: 12px; margin-top: 8px; width: 100%; }
        .vehicle-card {
            background: white; border-radius: 16px; border: 1px solid rgba(0,0,0,0.04);
            overflow: hidden; transition: all 0.3s ease;
            max-width: 300px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .vehicle-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); border-color: rgba(0,0,0,0.08); }
        .vehicle-image { width: 100%; height: 160px; object-fit: cover; background: var(--widget-surface); }
        .vehicle-info { padding: 14px 16px; }
        .vehicle-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; color: var(--widget-text); letter-spacing: -0.01em; }
        .vehicle-meta { font-size: 12px; color: var(--widget-muted); margin-bottom: 12px; display: flex; justify-content: space-between; font-weight: 500;}
        .vehicle-price { font-weight: 800; color: var(--widget-text); font-size: 16px; }
        .vehicle-actions { display: flex; flex-direction: column; gap: 8px; padding: 0 16px 16px; }
        .btn-action {
            width: 100%; padding: 10px; border-radius: 10px; font-size: 13px; font-weight: 600;
            cursor: pointer; text-align: center; border: 1px solid rgba(0,0,0,0.06);
            background: white; color: var(--widget-text); transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .btn-action:hover { background: var(--widget-surface); border-color: rgba(0,0,0,0.1); }

        /* ── Input Bar ─────────────────────────────────────── */
        .ag-input-bar {
            padding: 16px 20px 20px; border-top: 1px solid rgba(0,0,0,0.04);
            display: flex; gap: 12px; background: transparent;
            align-items: center; flex-direction: column; z-index: 10;
        }
        .ag-input-row { display: flex; gap: 8px; width: 100%; align-items: center; position: relative; }
        .ag-input-bar input[type="text"] {
            flex: 1; padding: 12px 16px; border: 1px solid rgba(0,0,0,0.08);
            border-radius: 20px; outline: none; font-size: 14px; font-weight: 400;
            transition: all 0.3s ease; background: rgba(255,255,255,0.7); color: var(--widget-text);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
        }
        .ag-input-bar input[type="text"]:focus {
            background: #ffffff; border-color: rgba(0,0,0,0.15);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .ag-input-bar input[type="text"]::placeholder { color: #a1a1aa; }
        .ag-action-btn {
            width: 36px; height: 36px; border-radius: 18px; border: none; cursor: pointer;
            background: rgba(255,255,255,0.7); color: var(--widget-muted); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.2s;
            border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .ag-action-btn:hover { background: #ffffff; color: var(--widget-text); border-color: rgba(0,0,0,0.1); }
        .ag-action-btn svg { width: 18px; height: 18px; stroke-width: 1.8; }
        .ag-action-btn.recording { color: white; background: #ef4444; border-color: #ef4444; animation: pulseRecord 1.5s infinite; }
        @keyframes pulseRecord { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .ag-input-bar button.send-btn {
            width: 38px; height: 38px; border-radius: 19px; border: none;
            background: var(--widget-text);
            color: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: all 0.3s var(--spring-easing); box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .ag-input-bar button.send-btn[disabled] { opacity: 0.5; pointer-events: none; }
        .ag-input-bar button.send-btn:hover { transform: scale(1.05); background: #27272a; }
        .ag-input-bar button.send-btn svg { width: 16px; height: 16px; stroke-width: 2.2; }
        .ag-attachments { display: none; width: 100%; padding: 8px 12px; border-radius: 16px; background: rgba(255,255,255,0.8); align-items: center; justify-content: space-between; font-size: 13px; color: var(--widget-text); font-weight: 500; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 4px; }
        .ag-attachments div { display: flex; align-items: center; gap: 10px; }
        .ag-attachments img { height: 40px; width: 40px; border-radius: 8px; object-fit: cover; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.04); }
        .ag-attachments-close { cursor: pointer; color: var(--widget-muted); padding: 4px; border-radius: 50%; transition: all 0.2s; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; }
        .ag-attachments-close:hover { background: rgba(0,0,0,0.06); color: var(--widget-text); }

        /* ── Powered By / Footer ───────────────────────────── */
        .ag-powered {
            text-align: center; padding: 0 0 12px; font-size: 10px; color: #a1a1aa; font-weight: 500;
            background: transparent; border-top: none; letter-spacing: 0.02em;
        }
        .ag-powered a { color: #71717a; text-decoration: none; transition: color 0.2s; }
        .ag-powered a:hover { color: var(--widget-text); }
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
        camera: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>',
        mic: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" /></svg>',
        stop: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z" /></svg>',
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
        trigger.innerHTML = `
            <div class="pulse-ring"></div>
            <div class="icon-chat">${ICONS.chat}</div>
            <div class="icon-close">${ICONS.close}</div>
        `;
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
                    <div class="ag-attachments" id="ag-attachments-preview"></div>
                    <div class="ag-input-row">
                        <input type="file" id="ag-file-upload" accept="image/*" style="display:none;">
                        <button class="ag-action-btn" id="btn-camera" title="Upload Image">${ICONS.camera}</button>
                        <button class="ag-action-btn" id="btn-mic" title="Record Voice Note">${ICONS.mic}</button>
                        <input type="text" id="ag-input" placeholder="Type a message...">
                        <button class="send-btn" id="btn-send">${ICONS.send}</button>
                    </div>
                </div>
                <div class="ag-powered">Powered by Antigravity AI</div>
            </div>

            <!-- Resize Handle (top-left corner for dragging) -->
            <div class="ag-resize-handle" id="ag-resize-handle"></div>
            
            <div class="ag-inactivity-popup" id="ag-inactivity-popup">
                <div class="ag-inactivity-content">
                    <div class="ag-inactivity-title">Are you still there?</div>
                    <div class="ag-inactivity-text">This chat will automatically close due to inactivity in:</div>
                    <div class="ag-inactivity-countdown" id="ag-inactivity-countdown">30s</div>
                    <button class="ag-btn-keep-open" id="btn-keep-open">Keep Chat Open</button>
                </div>
            </div>
        `;
    }

    function bindEvents() {
        $('btn-close').onclick = () => toggleChat(false);
        $('btn-expand').onclick = () => toggleExpand();
        $('btn-human').onclick = () => requestHuman();
        $('btn-send').onclick = () => sendMessage();
        $('btn-camera').onclick = () => $('ag-file-upload').click();
        $('ag-file-upload').onchange = (e) => handleAttachment(e.target.files[0]);
        $('btn-mic').onclick = () => toggleRecording();
        $('btn-skip').onclick = () => skipToChat();
        $('ag-input').onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };
        $('ag-lead-form').onsubmit = (e) => { e.preventDefault(); submitLeadAndStart(); };
        $('btn-keep-open').onclick = () => {
            const popup = $('ag-inactivity-popup');
            if (popup) popup.classList.remove('active');
            resetInactivityTimer();
        };
        initResizeHandle();
    }

    function $(id) { return shadow.getElementById(id); }

    let mediaRecorder = null;
    let audioChunks = [];
    let currentAttachment = null;
    let recordingTimer = null;
    let recordingSeconds = 0;

    function handleAttachment(file) {
        if (!file) return;
        currentAttachment = file;
        const preview = $('ag-attachments-preview');
        preview.style.display = 'flex';
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<div><img src="${e.target.result}"> <span>${file.name}</span></div><div class="ag-attachments-close" id="btn-clear-attach">✖</div>`;
                $('btn-clear-attach').onclick = clearAttachment;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('audio/')) {
            preview.innerHTML = `<div>🎤 Voice Note attached</div><div class="ag-attachments-close" id="btn-clear-attach">✖</div>`;
            $('btn-clear-attach').onclick = clearAttachment;
        }
    }

    function clearAttachment() {
        currentAttachment = null;
        $('ag-file-upload').value = '';
        $('ag-attachments-preview').style.display = 'none';
        $('ag-attachments-preview').innerHTML = '';
    }

    async function toggleRecording() {
        const micBtn = $('btn-mic');
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            micBtn.classList.remove('recording');
            micBtn.innerHTML = ICONS.mic;
            clearInterval(recordingTimer);
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = e => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const file = new File([audioBlob], 'voice-note.webm', { type: 'audio/webm' });
                handleAttachment(file);
                stream.getTracks().forEach(track => track.stop()); // Stop mic
            };

            mediaRecorder.start();
            micBtn.classList.add('recording');
            micBtn.innerHTML = ICONS.stop;
            recordingSeconds = 0;
            
            $('ag-attachments-preview').style.display = 'flex';
            $('ag-attachments-preview').innerHTML = `<div>Recording... <span id="record-time">0:00</span></div>`;
            
            recordingTimer = setInterval(() => {
                recordingSeconds++;
                const mins = Math.floor(recordingSeconds / 60);
                const secs = (recordingSeconds % 60).toString().padStart(2, '0');
                const timeStr = `${mins}:${secs}`;
                const timeEl = $('record-time');
                if (timeEl) timeEl.textContent = timeStr;

                if (recordingSeconds >= 60) {
                    toggleRecording(); // Auto stop at 60s
                }
            }, 1000);

        } catch (err) {
            console.error('Microphone access denied', err);
            alert('Please allow microphone access to record voice notes.');
        }
    }

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
        $('ag-window').classList.toggle('ag-open', isOpen);
        $('ag-trigger').classList.toggle('chat-open', isOpen);
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
        const hasAttachment = currentAttachment !== null;

        if (!text && !hasAttachment) return;
        if (!apiKey || !sessionToken) return;

        // Reset inactivity timer on any message
        resetInactivityTimer();

        const renderedContent = text || (currentAttachment?.type.startsWith('audio') ? '🎤 Voice Note' : '📸 Image attached');
        appendMessage('user', { content: renderedContent, attachment: currentAttachment });
        
        input.value = '';
        const attachmentToSend = currentAttachment;
        clearAttachment();
        showTypingIndicator();

        try {
            let res;
            if (attachmentToSend) {
                const formData = new FormData();
                formData.append('session_token', sessionToken);
                if (text) formData.append('message', text);
                formData.append('attachment', attachmentToSend);

                res = await fetch(`${baseUrl}/widget/${apiKey}/message`, {
                    method: 'POST',
                    body: formData
                });
            } else {
                res = await fetch(`${baseUrl}/widget/${apiKey}/message`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_token: sessionToken, message: text })
                });
            }

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
                        agent_name: data.agent_name,
                        metadata: data.message.metadata,
                        attachment: data.message.attachment
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

            // If the backend already transitioned to human AND an agent has accepted
            // (agent_name will be a real name when accepted, not default 'Support Agent')
            if (data.state === 'human' && data.agent_name && data.agent_name !== 'Support Agent' && chatState !== 'human') {
                removeConnectingLoader();
                agentName = data.agent_name;
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
                if (newState === 'human' && data.agent_name && chatState !== 'human') {
                    // Agent has actually accepted
                    removeConnectingLoader();
                    agentName = data.agent_name;
                    setChatState('human');
                    appendMessage('system', { content: `✅ ${agentName} has joined the chat!` });
                    resetInactivityTimer(); // Start the timer when handoff is successful
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

                if (data.state === 'human' && data.agent_name) {
                    // Agent has actually accepted — show joined message
                    clearInterval(statusPollInterval);
                    statusPollInterval = null;
                    removeConnectingLoader();
                    agentName = data.agent_name;
                    setChatState('human');
                    appendMessage('system', { content: `✅ ${agentName} has joined the chat!` });
                    startMessagePolling();
                    resetInactivityTimer(); // Start timer on fallback connection
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

            // Render local attachment from user
            if (data.attachment && data.attachment instanceof File) {
                if (data.attachment.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(data.attachment);
                    img.style.maxWidth = '100%';
                    img.style.borderRadius = '8px';
                    img.style.marginTop = '8px';
                    bubble.appendChild(img);
                } else if (data.attachment.type.startsWith('audio/')) {
                    const audio = document.createElement('audio');
                    audio.src = URL.createObjectURL(data.attachment);
                    audio.controls = true;
                    audio.style.marginTop = '8px';
                    audio.style.maxWidth = '100%';
                    bubble.appendChild(audio);
                }
            } 
            // Render remote attachment from backend metadata
            else if (data.metadata?.attachment_url) {
                if (data.metadata.attachment_type === 'image') {
                    const img = document.createElement('img');
                    img.src = data.metadata.attachment_url;
                    img.style.maxWidth = '100%';
                    img.style.borderRadius = '8px';
                    img.style.marginTop = '8px';
                    bubble.appendChild(img);
                } else if (data.metadata.attachment_type === 'audio' || data.metadata.attachment_type === 'video') {
                    const audio = document.createElement('audio');
                    audio.src = data.metadata.attachment_url;
                    audio.controls = true;
                    audio.style.marginTop = '8px';
                    audio.style.maxWidth = '100%';
                    bubble.appendChild(audio);
                }
            }

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
                        ${(card.cta || []).map(c => `<div class="btn-action" data-action="${c.action}" data-id="${card.id}" data-vdp-url="${card.vdp_url || ''}">${c.label}</div>`).join('')}
                    </div>
                `;
                cardsContainer.appendChild(cardEl);
            });
            wrapper.appendChild(cardsContainer);

            // Bind action buttons
            wrapper.querySelectorAll('.btn-action').forEach(btn => {
                btn.onclick = () => {
                    const action = btn.getAttribute('data-action');
                    const vdpUrl = btn.getAttribute('data-vdp-url');

                    if (action === 'view_details' && vdpUrl) {
                        window.open(vdpUrl, '_blank');
                        return;
                    }

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

    // ─── Inactivity & Disconnect Handling ───────────────────
    let countdownInterval = null;

    function resetInactivityTimer() {
        if (inactivityTimer) clearTimeout(inactivityTimer);
        if (countdownInterval) clearInterval(countdownInterval);
        
        inactivityWarningShown = false;
        
        const popup = $('ag-inactivity-popup');
        if (popup) popup.classList.remove('active');

        // Only enforce inactivity rules during human chat mode
        if (chatState !== 'human') return;

        // Warn after 90 seconds (1 min 30s) of inactivity format
        inactivityTimer = setTimeout(() => {
            if (chatState === 'human' && !inactivityWarningShown) {
                inactivityWarningShown = true;
                
                if (popup) {
                    popup.classList.add('active');
                    let timeLeft = 30;
                    const countdownEl = $('ag-inactivity-countdown');
                    if (countdownEl) countdownEl.textContent = `${timeLeft}s`;
                    
                    countdownInterval = setInterval(() => {
                        timeLeft--;
                        if (countdownEl) countdownEl.textContent = `${timeLeft}s`;
                        
                        if (timeLeft <= 0) {
                            clearInterval(countdownInterval);
                            popup.classList.remove('active');
                            if (chatState === 'human') {
                                disconnectConversation();
                            }
                        }
                    }, 1000);
                }
            }
        }, 90000); // 90 seconds
    }

    function disconnectConversation() {
        if (chatState !== 'human' || !apiKey || !sessionToken) return;
        
        // Use sendBeacon for reliable delivery when navigating away/closing
        const url = `${baseUrl}/widget/${apiKey}/disconnect`;
        const data = new FormData();
        data.append('session_token', sessionToken);
        navigator.sendBeacon(url, data);
        
        setChatState('ai');
        appendMessage('system', { content: 'This conversation has been closed due to inactivity or navigation. Feel free to start a new one!' });
    }

    // Handle page unload/refresh
    window.addEventListener('beforeunload', () => {
        if (chatState === 'human') {
            disconnectConversation();
        }
    });

    // Handle visibility changes (optional, but good for mobile)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && chatState === 'human') {
            // We could optionally disconnect here, but usually beforeunload is enough.
            // Keeping this listener in case we want to pause timers in the future.
        } else if (document.visibilityState === 'visible' && chatState === 'human') {
            // Wake back up, reset the inactivity timer
            resetInactivityTimer();
        }
    });

    // ─── Auto-initialize ──────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
