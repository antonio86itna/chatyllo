(function(){
    'use strict';

    // Wait for DOM to be fully loaded (widget div renders at wp_footer priority 100).
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatyllo);
    } else {
        initChatyllo();
    }

    function initChatyllo() {

    const CFG = window.chatylloConfig || {};
    const root = document.getElementById('chatyllo-root');
    if (!root) return;

    /* ── State ────────────────────────────────────────────────────── */
    let isOpen      = false;
    let isTyping    = false;
    let history     = [];
    let aiActive    = CFG.aiActive;
    let msgCount    = 0;
    let maxMemory   = (CFG.maxHistory || 10);
    let hasConsent  = localStorage.getItem('chatyllo_consent') === '1' || !CFG.requireConsent;
    let sessionId   = localStorage.getItem('chatyllo_sid') || generateId();
    localStorage.setItem('chatyllo_sid', sessionId);

    /* ── Apply custom colors via CSS vars ─────────────────────────── */
    if (CFG.primaryColor) {
        root.style.setProperty('--chy-primary', CFG.primaryColor);
        root.style.setProperty('--chy-primary-dark', darkenColor(CFG.primaryColor, 20));
        root.style.setProperty('--chy-primary-light', lightenColor(CFG.primaryColor, 90));
    }
    /* textColor applies to header text and user message bubbles. */
    if (CFG.textColor) {
        root.style.setProperty('--chy-user-text', CFG.textColor);
    }
    if (CFG.size) {
        var sizeMap = { small: 48, medium: 60, large: 72 };
        var px = sizeMap[CFG.size] || parseInt(CFG.size, 10) || null;
        if (px) root.style.setProperty('--chy-bubble-size', px + 'px');
    }

    /* ── Build DOM ────────────────────────────────────────────────── */
    root.innerHTML = `
        <div class="chatyllo-panel" id="chatyllo-panel">
            <div class="chatyllo-header">
                <div class="chatyllo-header__avatar" id="chatyllo-avatar">
                    ${CFG.botAvatar ? `<img src="${esc(CFG.botAvatar)}" alt="${esc(CFG.botName)}"/>` : '<svg viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="rgba(255,255,255,.2)"/><path d="M20 12c-2.2 0-4 1.8-4 4v1c0 2.2 1.8 4 4 4s4-1.8 4-4v-1c0-2.2-1.8-4-4-4zm-7 16c0-3 2-5.5 5-6.3.6-.2 1.3-.2 2-.2s1.4 0 2 .2c3 .8 5 3.3 5 6.3" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>'}
                </div>
                <div class="chatyllo-header__info">
                    <div class="chatyllo-header__name">${esc(CFG.botName || 'Chatyllo')}</div>
                    <div class="chatyllo-header__status" id="chatyllo-mode-label">
                        <span class="chatyllo-header__status-dot ${aiActive ? 'chatyllo-header__status-dot--active' : 'chatyllo-header__status-dot--inactive'}"></span>
                        <span>${aiActive ? esc(CFG.i18n.aiMode) : esc(CFG.i18n.offlineMode)}</span>
                    </div>
                </div>
                <div class="chatyllo-header__actions">
                    <button class="chatyllo-header__btn" id="chatyllo-new-chat" title="${esc(CFG.i18n.newConversation)}">
                        <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                    </button>
                    <button class="chatyllo-header__btn" id="chatyllo-minimize" title="${esc(CFG.i18n.minimize)}">
                        <svg viewBox="0 0 24 24"><path d="M18 15l-6-6-6 6"/></svg>
                    </button>
                </div>
            </div>
            <div class="chatyllo-messages" id="chatyllo-messages"></div>
            <div class="chatyllo-input" id="chatyllo-input-area">
                <textarea class="chatyllo-input__field" id="chatyllo-input"
                    placeholder="${esc(CFG.placeholderText || CFG.i18n.send)}"
                    rows="1" maxlength="2000"></textarea>
                <button class="chatyllo-input__send" id="chatyllo-send" title="${esc(CFG.i18n.send)}">
                    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
            ${buildBrandingFooter()}
        </div>
        <button class="chatyllo-bubble" id="chatyllo-bubble" aria-label="${esc(CFG.i18n.send)}">
            <span class="chatyllo-bubble__status ${aiActive ? 'chatyllo-bubble__status--active' : 'chatyllo-bubble__status--inactive'}"></span>
            <svg class="chatyllo-icon-chat" viewBox="0 0 120 120" fill="#fff" xmlns="http://www.w3.org/2000/svg"><path d="M95.5 18H24.5C17.6 18 12 23.6 12 30.5v45C12 82.4 17.6 88 24.5 88H36l-8.5 19.2c-.6 1.4.8 2.8 2.2 2.1L58 95h37.5c6.9 0 12.5-5.6 12.5-12.5v-45C108 30.6 102.4 18 95.5 18z" fill="none" stroke="#fff" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/><path d="M42 60c6 12 30 12 36 0" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"/></svg>
            <svg class="chatyllo-icon-close" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    `;

    /* ── Branding footer builder ──────────────────────────────────── */
    function buildBrandingFooter() {
        
        // Default: powered_by (always shown in free build).
        return '<div class="chatyllo-footer"><a href="https://wpezo.com/plugins/chatyllo" target="_blank" rel="noopener">' + esc(CFG.i18n.poweredBy) + '</a></div>';
    }

    /* ── Refs ──────────────────────────────────────────────────────── */
    const bubble   = document.getElementById('chatyllo-bubble');
    const panel    = document.getElementById('chatyllo-panel');
    const messages = document.getElementById('chatyllo-messages');
    const input    = document.getElementById('chatyllo-input');
    const sendBtn  = document.getElementById('chatyllo-send');

    /* ── GDPR Consent Banner ─────────────────────────────────────── */
    if (CFG.requireConsent && !hasConsent) {
        var privacyLink = CFG.privacyPolicyUrl ? ' <a href="' + esc(CFG.privacyPolicyUrl) + '" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline">' + (CFG.i18n.privacyPolicy || 'Privacy Policy') + '</a>' : '';
        var consentHtml = '<div id="chatyllo-consent" class="chatyllo-consent">' +
            '<p>' + (CFG.i18n.consentText || 'This chat may store messages to improve our service.') + privacyLink + '</p>' +
            '<button id="chatyllo-consent-accept" class="chatyllo-consent__btn">' + (CFG.i18n.consentAccept || 'Accept & Chat') + '</button>' +
            '</div>';
        panel.querySelector('.chatyllo-messages').insertAdjacentHTML('beforebegin', consentHtml);
        document.getElementById('chatyllo-consent-accept').addEventListener('click', function() {
            hasConsent = true;
            localStorage.setItem('chatyllo_consent', '1');
            document.getElementById('chatyllo-consent').remove();
        });
    }

    /* ── Welcome message ──────────────────────────────────────────── */
    if (CFG.welcomeMessage) {
        addMessage(CFG.welcomeMessage, 'bot');
    }

    /* ── Toggle panel ─────────────────────────────────────────────── */
    bubble.addEventListener('click', togglePanel);
    document.getElementById('chatyllo-minimize').addEventListener('click', togglePanel);
    document.getElementById('chatyllo-new-chat').addEventListener('click', newConversation);

    function togglePanel() {
        isOpen = !isOpen;
        panel.classList.toggle('chatyllo-panel--open', isOpen);
        bubble.classList.toggle('chatyllo-bubble--open', isOpen);
        if (isOpen) {
            setTimeout(() => input.focus(), 350);
            scrollToBottom();
        }
    }

    /* ── Auto open delay ──────────────────────────────────────────── */
    if (CFG.openDelay && CFG.openDelay > 0) {
        setTimeout(() => { if (!isOpen) togglePanel(); }, CFG.openDelay);
    }

    /* ── Send message ─────────────────────────────────────────────── */
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea.
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });

    function sendMessage() {
        const msg = input.value.trim();
        if (!msg || isTyping) return;

        addMessage(msg, 'user');
        input.value = '';
        input.style.height = 'auto';

        // Add to history.
        history.push({ role: 'user', content: msg });
        msgCount++;

        // Auto-rotate session when AI memory limit is reached.
        if (aiActive && msgCount > maxMemory) {
            sessionId = generateId();
            localStorage.setItem('chatyllo_sid', sessionId);
            history = history.slice(-(maxMemory * 2));
            msgCount = Math.floor(history.length / 2);
        }

        showTyping();
        sendBtn.disabled = true;

        // AJAX call.
        const formData = new FormData();
        formData.append('action', 'chatyllo_chat');
        formData.append('nonce', CFG.nonce);
        formData.append('message', msg);
        formData.append('session_id', sessionId);
        formData.append('history', JSON.stringify(history));
        formData.append('page_referrer', document.referrer || '');
        formData.append('consent', hasConsent ? '1' : '0');

        fetch(CFG.ajaxUrl, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                hideTyping();
                sendBtn.disabled = false;

                if (data.success && data.data) {
                    const reply = data.data.reply || CFG.i18n.errorMessage;
                    addMessage(reply, 'bot');
                    history.push({ role: 'assistant', content: reply });

                    // Update AI status if proxy responded.
                    if (data.data.mode === 'faq' || data.data.mode === 'no_match') {
                        updateAiIndicator(false);
                    } else if (data.data.mode === 'ai') {
                        updateAiIndicator(true);
                    }
                } else {
                    addMessage(data.data?.message || CFG.i18n.errorMessage, 'bot');
                }
            })
            .catch(() => {
                hideTyping();
                sendBtn.disabled = false;
                addMessage(CFG.i18n.errorMessage, 'bot');
            });
    }

    /* ── Add message bubble ───────────────────────────────────────── */
    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.className = `chatyllo-msg chatyllo-msg--${sender}`;

        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        div.innerHTML = `
            <div class="chatyllo-msg__bubble">${formatMessage(text)}</div>
            <div class="chatyllo-msg__time">${time}</div>
        `;
        messages.appendChild(div);
        scrollToBottom();
    }

    /* ── Typing indicator ─────────────────────────────────────────── */
    function showTyping() {
        if (!CFG.typingIndicator) return;
        isTyping = true;
        const div = document.createElement('div');
        div.className = 'chatyllo-typing';
        div.id = 'chatyllo-typing';
        div.innerHTML = '<span class="chatyllo-typing__dot"></span><span class="chatyllo-typing__dot"></span><span class="chatyllo-typing__dot"></span>';
        messages.appendChild(div);
        scrollToBottom();
    }

    function hideTyping() {
        isTyping = false;
        const el = document.getElementById('chatyllo-typing');
        if (el) el.remove();
    }

    /* ── New conversation ─────────────────────────────────────────── */
    function newConversation() {
        history = [];
        msgCount = 0;
        messages.innerHTML = '';
        sessionId = generateId();
        localStorage.setItem('chatyllo_sid', sessionId);
        if (CFG.welcomeMessage) {
            addMessage(CFG.welcomeMessage, 'bot');
        }
    }

    /* ── Update AI indicator ──────────────────────────────────────── */
    function updateAiIndicator(active) {
        aiActive = active;
        const label = document.getElementById('chatyllo-mode-label');
        const dot   = label?.querySelector('.chatyllo-header__status-dot');
        const text  = label?.querySelector('span:last-child');
        const bDot  = root.querySelector('.chatyllo-bubble__status');

        if (dot) {
            dot.className = 'chatyllo-header__status-dot ' + (active ? 'chatyllo-header__status-dot--active' : 'chatyllo-header__status-dot--inactive');
        }
        if (text) {
            text.textContent = active ? CFG.i18n.aiMode : CFG.i18n.offlineMode;
        }
        if (bDot) {
            bDot.className = 'chatyllo-bubble__status ' + (active ? 'chatyllo-bubble__status--active' : 'chatyllo-bubble__status--inactive');
        }
    }

    /* ── Poll AI status periodically (with cleanup on page unload) ── */
    var statusPollInterval = setInterval(function() {
        const fd = new FormData();
        fd.append('action', 'chatyllo_status');
        fd.append('nonce', CFG.nonce);
        fetch(CFG.ajaxUrl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) updateAiIndicator(d.data.ai_active);
            })
            .catch(() => {});
    }, 60000); // Every 60s.

    // Cleanup interval on page unload to prevent memory leaks.
    window.addEventListener('beforeunload', function() {
        clearInterval(statusPollInterval);
    });

    /* ── Utilities ────────────────────────────────────────────────── */
    function scrollToBottom() {
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight;
        });
    }

    function formatMessage(text) {
        // Basic markdown: **bold**, *italic*, `code`, links.
        text = esc(text);
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
        text = text.replace(/`(.+?)`/g, '<code style="background:#F1F5F9;padding:1px 4px;border-radius:3px;font-size:13px">$1</code>');
        text = text.replace(/\n/g, '<br>');
        // Auto-link URLs.
        text = text.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline">$1</a>');
        return text;
    }

    function esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function generateId() {
        return 'chy_' + Date.now().toString(36) + Math.random().toString(36).substring(2, 8);
    }

    function darkenColor(hex, percent) {
        const num = parseInt(hex.replace('#',''), 16);
        const amt = Math.round(2.55 * percent);
        const R = Math.max((num >> 16) - amt, 0);
        const G = Math.max((num >> 8 & 0x00FF) - amt, 0);
        const B = Math.max((num & 0x0000FF) - amt, 0);
        return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
    }

    function lightenColor(hex, percent) {
        const num = parseInt(hex.replace('#',''), 16);
        const amt = Math.round(2.55 * percent);
        const R = Math.min((num >> 16) + amt, 255);
        const G = Math.min((num >> 8 & 0x00FF) + amt, 255);
        const B = Math.min((num & 0x0000FF) + amt, 255);
        return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
    }

    } // end initChatyllo

})();
