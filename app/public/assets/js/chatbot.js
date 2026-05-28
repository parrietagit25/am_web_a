/**
 * Chatbot IA — Automarket
 */
(function () {
    'use strict';

    var root = document.getElementById('am-chatbot-root');
    if (!root) return;

    var config;
    try {
        config = JSON.parse(root.getAttribute('data-chatbot') || '{}');
    } catch (e) {
        return;
    }
    if (!config.enabled) return;

    var activeUnit = root.getAttribute('data-active-unit') || '';
    var isOpen = false;
    var isBusy = false;

    var launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'am-chat-launcher';
    launcher.setAttribute('aria-label', config.assistant_name || 'Chat');
    launcher.innerHTML = '<i class="bi bi-chat-dots-fill"></i>';

    var panel = document.createElement('div');
    panel.className = 'am-chat-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', config.assistant_name || 'Asistente');

    panel.innerHTML =
        '<div class="am-chat-header">' +
            '<div>' +
                '<h6>' + escapeHtml(config.assistant_name || 'Asistente') + '</h6>' +
                '<small>IA · Automarket</small>' +
            '</div>' +
            '<div class="am-chat-header-actions">' +
                '<button type="button" class="am-chat-reset" title="Nueva conversación"><i class="bi bi-arrow-counterclockwise"></i></button>' +
                '<button type="button" class="am-chat-close" title="Cerrar"><i class="bi bi-x-lg"></i></button>' +
            '</div>' +
        '</div>' +
        '<div class="am-chat-messages" id="am-chat-messages"></div>' +
        '<div class="am-chat-suggestions" id="am-chat-suggestions"></div>' +
        '<div class="am-chat-input-row">' +
            '<textarea id="am-chat-input" rows="1" placeholder="' + (config.lang === 'en' ? 'Type your question…' : 'Escribe tu pregunta…') + '" maxlength="2000"></textarea>' +
            '<button type="button" class="am-chat-send" id="am-chat-send" aria-label="Enviar"><i class="bi bi-send-fill"></i></button>' +
        '</div>';

    root.appendChild(panel);
    root.appendChild(launcher);

    var messagesEl = document.getElementById('am-chat-messages');
    var suggestionsEl = document.getElementById('am-chat-suggestions');
    var inputEl = document.getElementById('am-chat-input');
    var sendBtn = document.getElementById('am-chat-send');

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatText(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function appendBubble(role, text) {
        var div = document.createElement('div');
        div.className = 'am-chat-bubble ' + role;
        div.innerHTML = formatText(text);
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return div;
    }

    function renderSuggestions() {
        suggestionsEl.innerHTML = '';
        var list = config.suggested_questions || [];
        if (!list.length || messagesEl.children.length > 1) return;
        list.forEach(function (q) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = q;
            btn.addEventListener('click', function () {
                inputEl.value = q;
                sendMessage();
            });
            suggestionsEl.appendChild(btn);
        });
    }

    function setOpen(open) {
        isOpen = open;
        panel.classList.toggle('is-open', open);
        launcher.classList.toggle('is-open', open);
        if (open && messagesEl.children.length === 0) {
            appendBubble('assistant', config.welcome_message || '');
            renderSuggestions();
        }
    }

    function setBusy(busy) {
        isBusy = busy;
        sendBtn.disabled = busy;
        inputEl.disabled = busy;
    }

    async function apiCall(body) {
        var res = await fetch('/api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        var data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error(data.message || 'Error');
        }
        return data;
    }

    async function sendMessage() {
        var text = (inputEl.value || '').trim();
        if (!text || isBusy) return;

        suggestionsEl.innerHTML = '';
        appendBubble('user', text);
        inputEl.value = '';
        setBusy(true);

        var typing = appendBubble('assistant', config.lang === 'en' ? 'Thinking…' : 'Pensando…');
        typing.classList.add('typing');

        try {
            var data = await apiCall({
                message: text,
                active_unit: activeUnit,
                page_url: window.location.pathname + window.location.search,
            });
            typing.remove();
            appendBubble('assistant', data.reply || '');
        } catch (err) {
            typing.remove();
            appendBubble('assistant', err.message || (config.lang === 'en' ? 'Something went wrong.' : 'Ocurrió un error.'));
        } finally {
            setBusy(false);
            inputEl.focus();
        }
    }

    async function resetChat() {
        if (isBusy) return;
        try {
            await apiCall({ action: 'reset' });
        } catch (e) { /* ignore */ }
        messagesEl.innerHTML = '';
        appendBubble('assistant', config.welcome_message || '');
        renderSuggestions();
    }

    launcher.addEventListener('click', function () {
        setOpen(!isOpen);
    });
    panel.querySelector('.am-chat-close').addEventListener('click', function () {
        setOpen(false);
    });
    panel.querySelector('.am-chat-reset').addEventListener('click', resetChat);
    sendBtn.addEventListener('click', sendMessage);
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
})();
