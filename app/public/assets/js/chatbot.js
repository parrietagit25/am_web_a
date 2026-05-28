/**
 * Chatbot IA — Automarket (texto, voz y flujos guiados)
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
    var voiceCallMode = false;
    var isListening = false;
    var recognition = null;
    var synth = window.speechSynthesis;
    var preferredVoice = null;

    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    var launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'am-chat-launcher';
    launcher.setAttribute('aria-label', config.assistant_name || 'Chat');
    launcher.innerHTML = '<i class="bi bi-chat-dots-fill"></i>';

    var panel = document.createElement('div');
    panel.className = 'am-chat-panel';
    panel.setAttribute('role', 'dialog');

    panel.innerHTML =
        '<div class="am-chat-header">' +
            '<div>' +
                '<h6>' + escapeHtml(config.assistant_name || 'Asistente') + '</h6>' +
                '<small class="am-chat-status" id="am-chat-status">IA · Automarket</small>' +
            '</div>' +
            '<div class="am-chat-header-actions">' +
                '<button type="button" class="am-chat-call" id="am-chat-call" title="Modo llamada (voz)"><i class="bi bi-telephone-fill"></i></button>' +
                '<button type="button" class="am-chat-reset" title="Nueva conversación"><i class="bi bi-arrow-counterclockwise"></i></button>' +
                '<button type="button" class="am-chat-close" title="Cerrar"><i class="bi bi-x-lg"></i></button>' +
            '</div>' +
        '</div>' +
        '<div class="am-chat-flow-bar" id="am-chat-flow-bar"></div>' +
        '<div class="am-chat-messages" id="am-chat-messages"></div>' +
        '<div class="am-chat-suggestions" id="am-chat-suggestions"></div>' +
        '<div class="am-chat-input-row">' +
            '<button type="button" class="am-chat-mic" id="am-chat-mic" title="Hablar"><i class="bi bi-mic-fill"></i></button>' +
            '<textarea id="am-chat-input" rows="1" placeholder="' + (config.lang === 'en' ? 'Type or use the mic…' : 'Escribe o usa el micrófono…') + '" maxlength="2000"></textarea>' +
            '<button type="button" class="am-chat-send" id="am-chat-send" aria-label="Enviar"><i class="bi bi-send-fill"></i></button>' +
        '</div>';

    root.appendChild(panel);
    root.appendChild(launcher);

    var messagesEl = document.getElementById('am-chat-messages');
    var suggestionsEl = document.getElementById('am-chat-suggestions');
    var flowBarEl = document.getElementById('am-chat-flow-bar');
    var inputEl = document.getElementById('am-chat-input');
    var sendBtn = document.getElementById('am-chat-send');
    var micBtn = document.getElementById('am-chat-mic');
    var callBtn = document.getElementById('am-chat-call');
    var statusEl = document.getElementById('am-chat-status');

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatText(text) {
        return escapeHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    function appendBubble(role, text) {
        var div = document.createElement('div');
        div.className = 'am-chat-bubble ' + role;
        div.innerHTML = formatText(text);
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return div;
    }

    function setStatus(text) {
        if (statusEl) statusEl.textContent = text;
    }

    function renderFlowBar(flow) {
        flowBarEl.innerHTML = '';
        if (!flow || !flow.id) return;
        var badge = document.createElement('span');
        badge.className = 'am-chat-flow-badge';
        badge.innerHTML = '<i class="bi bi-signpost-split-fill me-1"></i>' + escapeHtml(flow.label || flow.id);
        flowBarEl.appendChild(badge);
    }

    function renderGuidedFlows() {
        suggestionsEl.innerHTML = '';
        var flows = config.guided_flows || [];
        if (!flows.length || messagesEl.querySelectorAll('.am-chat-bubble').length > 1) return;

        var title = document.createElement('p');
        title.className = 'am-chat-flows-title small text-muted mb-1 px-1';
        title.textContent = config.lang === 'en' ? 'Guided assistance:' : 'Asistencia guiada:';
        suggestionsEl.appendChild(title);

        flows.forEach(function (f) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'am-chat-flow-btn';
            btn.innerHTML = '<i class="bi bi-' + (f.icon || 'arrow-right-circle') + ' me-1"></i>' + escapeHtml(f.label);
            btn.addEventListener('click', function () {
                startFlow(f.id);
            });
            suggestionsEl.appendChild(btn);
        });
    }

    function renderSuggestions() {
        suggestionsEl.innerHTML = '';
        renderGuidedFlows();
        var list = config.suggested_questions || [];
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
        if (!open) {
            stopListening();
            synth.cancel();
        }
    }

    function setBusy(busy) {
        isBusy = busy;
        sendBtn.disabled = busy;
        inputEl.disabled = busy;
        micBtn.disabled = busy;
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

    function pickVoice() {
        if (!synth || preferredVoice) return preferredVoice;
        var voices = synth.getVoices();
        var lang = (config.lang === 'en') ? 'en' : 'es';
        preferredVoice = voices.find(function (v) {
            return v.lang && v.lang.indexOf(lang) === 0;
        }) || voices[0] || null;
        return preferredVoice;
    }

    function speakText(text, onEnd) {
        if (!synth || !text) {
            if (onEnd) onEnd();
            return;
        }
        synth.cancel();
        var plain = text.replace(/\*\*/g, '').replace(/<[^>]+>/g, '');
        var u = new SpeechSynthesisUtterance(plain);
        u.lang = config.lang === 'en' ? 'en-US' : 'es-PA';
        u.rate = 1;
        u.pitch = 1;
        var voice = pickVoice();
        if (voice) u.voice = voice;
        u.onend = function () {
            setStatus(config.lang === 'en' ? 'AI · Automarket' : 'IA · Automarket');
            if (onEnd) onEnd();
        };
        u.onerror = function () {
            if (onEnd) onEnd();
        };
        setStatus(config.lang === 'en' ? 'Speaking…' : 'Hablando…');
        synth.speak(u);
    }

    function initRecognition() {
        if (!SpeechRecognition) return null;
        var r = new SpeechRecognition();
        r.lang = config.lang === 'en' ? 'en-US' : 'es-PA';
        r.interimResults = false;
        r.maxAlternatives = 1;
        r.continuous = false;
        r.onstart = function () {
            isListening = true;
            micBtn.classList.add('listening');
            setStatus(config.lang === 'en' ? 'Listening…' : 'Escuchando…');
        };
        r.onend = function () {
            isListening = false;
            micBtn.classList.remove('listening');
            if (!isBusy) {
                setStatus(voiceCallMode
                    ? (config.lang === 'en' ? 'Call mode · speak' : 'Modo llamada · hable')
                    : (config.lang === 'en' ? 'AI · Automarket' : 'IA · Automarket'));
            }
        };
        r.onerror = function () {
            isListening = false;
            micBtn.classList.remove('listening');
        };
        r.onresult = function (ev) {
            var text = ev.results[0][0].transcript;
            inputEl.value = text;
            sendMessage();
        };
        return r;
    }

    function stopListening() {
        if (recognition && isListening) {
            try { recognition.stop(); } catch (e) { /* */ }
        }
        isListening = false;
        micBtn.classList.remove('listening');
    }

    function startListening() {
        if (!SpeechRecognition) {
            appendBubble('assistant', config.lang === 'en'
                ? 'Voice is not supported in this browser. Use Chrome or Edge.'
                : 'La voz no está disponible en este navegador. Use Chrome o Edge.');
            return;
        }
        if (!recognition) recognition = initRecognition();
        if (!recognition || isBusy || isListening) return;
        stopListening();
        synth.cancel();
        try {
            recognition.start();
        } catch (e) {
            setTimeout(function () {
                try { recognition.start(); } catch (e2) { /* */ }
            }, 300);
        }
    }

    function handleAssistantReply(data) {
        var reply = data.reply || '';
        renderFlowBar(data.flow);
        if (data.completed) {
            renderFlowBar(null);
            if (voiceCallMode) voiceCallMode = false;
            callBtn.classList.remove('active');
        }
        var shouldSpeak = data.speak !== false && (voiceCallMode || document.body.classList.contains('am-chat-voice-last'));
        document.body.classList.remove('am-chat-voice-last');
        if (shouldSpeak && reply) {
            speakText(reply, function () {
                if (voiceCallMode && !isBusy) {
                    setTimeout(startListening, 400);
                }
            });
        } else if (voiceCallMode && !isBusy) {
            setTimeout(startListening, 500);
        }
    }

    async function sendMessage(fromVoice) {
        var text = (inputEl.value || '').trim();
        if (!text || isBusy) return;

        if (fromVoice) document.body.classList.add('am-chat-voice-last');

        suggestionsEl.innerHTML = '';
        appendBubble('user', text);
        inputEl.value = '';
        setBusy(true);
        stopListening();

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
            handleAssistantReply(data);
        } catch (err) {
            typing.remove();
            appendBubble('assistant', err.message || (config.lang === 'en' ? 'Something went wrong.' : 'Ocurrió un error.'));
        } finally {
            setBusy(false);
            if (!voiceCallMode) inputEl.focus();
        }
    }

    async function startFlow(flowId) {
        if (isBusy) return;
        suggestionsEl.innerHTML = '';
        setBusy(true);
        stopListening();
        var typing = appendBubble('assistant', config.lang === 'en' ? 'Starting…' : 'Iniciando…');
        typing.classList.add('typing');
        try {
            var data = await apiCall({
                action: 'start_flow',
                flow_id: flowId,
                active_unit: activeUnit,
                page_url: window.location.pathname + window.location.search,
            });
            typing.remove();
            appendBubble('assistant', data.reply || '');
            handleAssistantReply(data);
        } catch (err) {
            typing.remove();
            appendBubble('assistant', err.message || 'Error');
        } finally {
            setBusy(false);
        }
    }

    async function resetChat() {
        if (isBusy) return;
        voiceCallMode = false;
        callBtn.classList.remove('active');
        stopListening();
        synth.cancel();
        try {
            await apiCall({ action: 'reset' });
        } catch (e) { /* */ }
        messagesEl.innerHTML = '';
        renderFlowBar(null);
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
    sendBtn.addEventListener('click', function () { sendMessage(false); });
    micBtn.addEventListener('click', function () {
        if (isListening) {
            stopListening();
        } else {
            document.body.classList.add('am-chat-voice-last');
            startListening();
        }
    });
    callBtn.addEventListener('click', function () {
        voiceCallMode = !voiceCallMode;
        callBtn.classList.toggle('active', voiceCallMode);
        if (voiceCallMode) {
            setStatus(config.lang === 'en' ? 'Call mode active' : 'Modo llamada activo');
            if (messagesEl.children.length === 0) {
                setOpen(true);
            }
            var msg = config.lang === 'en'
                ? 'Call mode on. I will listen after each reply. Say "cancel" to stop a process.'
                : 'Modo llamada activado. Escucharé después de cada respuesta. Diga "cancelar" para salir de un trámite.';
            appendBubble('assistant', msg);
            speakText(msg, function () {
                startListening();
            });
        } else {
            stopListening();
            synth.cancel();
            setStatus(config.lang === 'en' ? 'AI · Automarket' : 'IA · Automarket');
        }
    });
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(false);
        }
    });

    if (synth) {
        synth.onvoiceschanged = function () {
            pickVoice();
        };
    }

    if (!SpeechRecognition) {
        micBtn.title = config.lang === 'en' ? 'Voice not supported' : 'Voz no disponible';
        callBtn.disabled = true;
    }
})();
