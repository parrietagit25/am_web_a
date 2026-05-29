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
        '<div class="am-chat-voice-row" id="am-chat-voice-row">' +
            '<label for="am-chat-voice-select" class="am-chat-voice-label">' + (config.lang === 'en' ? 'Voice' : 'Voz') + '</label>' +
            '<select id="am-chat-voice-select" class="am-chat-voice-select" title="' + (config.lang === 'en' ? 'Speaking voice' : 'Voz del asistente') + '"></select>' +
        '</div>' +
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
    var voiceSelectEl = document.getElementById('am-chat-voice-select');
    var micStream = null;
    var VOICE_STORAGE_KEY = 'am_chat_voice_uri';

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

    function hasUserMessages() {
        return messagesEl.querySelectorAll('.am-chat-bubble.user').length > 0;
    }

    function updateShortcutsVisibility() {
        if (hasUserMessages()) {
            suggestionsEl.classList.add('is-collapsed');
        } else {
            suggestionsEl.classList.remove('is-collapsed');
        }
    }

    function renderGuidedFlows() {
        var flows = config.guided_flows || [];
        if (!flows.length) return;

        var title = document.createElement('span');
        title.className = 'am-chat-flows-title';
        title.textContent = config.lang === 'en' ? 'Quick start:' : 'Inicio rápido:';
        suggestionsEl.appendChild(title);

        flows.forEach(function (f) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'am-chat-flow-btn';
            btn.textContent = f.label;
            btn.addEventListener('click', function () {
                startFlow(f.id);
            });
            suggestionsEl.appendChild(btn);
        });
    }

    function renderSuggestions() {
        suggestionsEl.innerHTML = '';
        if (!hasUserMessages()) {
            renderGuidedFlows();
            var list = config.suggested_questions || [];
            list.slice(0, 2).forEach(function (q) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = q;
                btn.addEventListener('click', function () {
                    inputEl.value = q;
                    sendMessage(false);
                });
                suggestionsEl.appendChild(btn);
            });
        }
        updateShortcutsVisibility();
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

    function getVoicesList() {
        return synth ? synth.getVoices() : [];
    }

    function pickVoice() {
        var voices = getVoicesList();
        if (!voices.length) return null;

        var savedUri = '';
        try {
            savedUri = localStorage.getItem(VOICE_STORAGE_KEY) || '';
        } catch (e) { /* */ }
        if (voiceSelectEl && voiceSelectEl.value) {
            savedUri = voiceSelectEl.value;
        }

        if (savedUri) {
            var byUri = voices.find(function (v) { return v.voiceURI === savedUri; });
            if (byUri) return byUri;
        }

        var nameHint = (config.voice_name || '').toLowerCase();
        if (nameHint) {
            var byName = voices.find(function (v) {
                return v.name && v.name.toLowerCase().indexOf(nameHint) !== -1;
            });
            if (byName) return byName;
        }

        var lang = config.lang === 'en' ? 'en' : 'es';
        return voices.find(function (v) {
            return v.lang && v.lang.toLowerCase().indexOf(lang) === 0;
        }) || voices.find(function (v) {
            return v.lang && v.lang.toLowerCase().indexOf('es') === 0;
        }) || voices[0] || null;
    }

    function populateVoiceSelect() {
        if (!voiceSelectEl || !synth) return;
        var voices = getVoicesList();
        var lang = config.lang === 'en' ? 'en' : 'es';
        var filtered = voices.filter(function (v) {
            return v.lang && v.lang.toLowerCase().indexOf(lang) === 0;
        });
        if (filtered.length < 2) filtered = voices;

        var prev = voiceSelectEl.value || localStorage.getItem(VOICE_STORAGE_KEY) || '';
        voiceSelectEl.innerHTML = '';
        filtered.forEach(function (v) {
            var opt = document.createElement('option');
            opt.value = v.voiceURI;
            opt.textContent = v.name + (v.lang ? ' (' + v.lang + ')' : '');
            voiceSelectEl.appendChild(opt);
        });

        if (prev) {
            voiceSelectEl.value = prev;
        } else if (config.voice_name) {
            var match = pickVoice();
            if (match) voiceSelectEl.value = match.voiceURI;
        }
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
        u.rate = parseFloat(config.voice_rate) || 1;
        u.pitch = parseFloat(config.voice_pitch) || 1;
        var voice = pickVoice();
        if (voice) {
            u.voice = voice;
            u.lang = voice.lang || u.lang;
        }
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

    function isSecureForMic() {
        return window.isSecureContext === true
            || location.protocol === 'https:'
            || location.hostname === 'localhost'
            || location.hostname === '127.0.0.1';
    }

    function releaseMicStream() {
        if (micStream) {
            micStream.getTracks().forEach(function (t) { t.stop(); });
            micStream = null;
        }
    }

    function ensureMicrophoneAccess() {
        return new Promise(function (resolve, reject) {
            if (!isSecureForMic()) {
                reject(new Error('insecure'));
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                resolve(true);
                return;
            }
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function (stream) {
                    releaseMicStream();
                    micStream = stream;
                    resolve(true);
                })
                .catch(function (err) {
                    reject(err);
                });
        });
    }

    function micPermissionMessage(err) {
        if (err && err.message === 'insecure') {
            return config.lang === 'en'
                ? 'The microphone requires HTTPS (secure connection). Open the site with https:// or use localhost.'
                : 'El micrófono requiere conexión segura (HTTPS). Abra el sitio con https:// o use localhost.';
        }
        return config.lang === 'en'
            ? 'Microphone blocked. Click the lock icon in the address bar → Site settings → allow Microphone, then try again.'
            : 'Micrófono bloqueado. Haga clic en el candado de la barra de direcciones → configuración del sitio → permitir Micrófono, e intente de nuevo.';
    }

    function recognitionErrorMessage(code) {
        if (code === 'not-allowed' || code === 'service-not-allowed') {
            return micPermissionMessage(null);
        }
        if (code === 'no-speech') {
            return config.lang === 'en'
                ? 'I didn\'t hear anything. Please speak again.'
                : 'No escuché nada. Por favor, hable de nuevo.';
        }
        if (code === 'aborted') {
            return '';
        }
        return config.lang === 'en'
            ? 'Could not use the microphone (' + code + '). Try Chrome or Edge.'
            : 'No pude usar el micrófono (' + code + '). Pruebe con Chrome o Edge.';
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
            if (!isBusy && voiceCallMode) {
                setStatus(config.lang === 'en' ? 'On call · speak' : 'En llamada · hable');
                setTimeout(function () {
                    if (voiceCallMode && !isBusy && !isListening) {
                        startListening(true);
                    }
                }, 400);
            } else if (!isBusy) {
                setStatus(config.lang === 'en' ? 'AI · Automarket' : 'IA · Automarket');
            }
        };
        r.onerror = function (ev) {
            isListening = false;
            micBtn.classList.remove('listening');
            var msg = recognitionErrorMessage(ev.error || '');
            if (msg && ev.error !== 'aborted') {
                appendBubble('assistant', msg);
            }
            if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') {
                voiceCallMode = false;
                callBtn.classList.remove('active');
                releaseMicStream();
            }
        };
        r.onresult = function (ev) {
            if (!ev.results || !ev.results.length) return;
            var text = ev.results[0][0].transcript;
            if (!text || !text.trim()) return;
            inputEl.value = text.trim();
            sendMessage(voiceCallMode);
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

    function beginRecognition() {
        if (!recognition) recognition = initRecognition();
        if (!recognition) return;
        try {
            recognition.start();
        } catch (e) {
            setTimeout(function () {
                if (!isListening && !isBusy) {
                    try { recognition.start(); } catch (e2) { /* */ }
                }
            }, 350);
        }
    }

    async function startListening(skipMicPrompt) {
        if (!SpeechRecognition) {
            appendBubble('assistant', config.lang === 'en'
                ? 'Voice is not supported in this browser. Use Chrome or Edge.'
                : 'La voz no está disponible en este navegador. Use Chrome o Edge.');
            return;
        }
        if (isBusy || isListening) return;
        stopListening();
        synth.cancel();

        if (!skipMicPrompt && !micStream) {
            setStatus(config.lang === 'en' ? 'Allow microphone…' : 'Permita el micrófono…');
            try {
                await ensureMicrophoneAccess();
            } catch (err) {
                appendBubble('assistant', micPermissionMessage(err));
                voiceCallMode = false;
                callBtn.classList.remove('active');
                setStatus(config.lang === 'en' ? 'AI · Automarket' : 'IA · Automarket');
                return;
            }
        }

        beginRecognition();
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
                    setTimeout(function () { startListening(true); }, 400);
                }
            });
        } else if (voiceCallMode && !isBusy) {
            setTimeout(function () { startListening(true); }, 500);
        }
    }

    async function sendMessage(fromVoice) {
        var text = (inputEl.value || '').trim();
        if (!text || isBusy) return;

        if (fromVoice) document.body.classList.add('am-chat-voice-last');

        appendBubble('user', text);
        updateShortcutsVisibility();
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
        updateShortcutsVisibility();
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
            startListening(false);
        }
    });
    callBtn.addEventListener('click', async function () {
        voiceCallMode = !voiceCallMode;
        callBtn.classList.toggle('active', voiceCallMode);
        if (voiceCallMode) {
            setOpen(true);
            setStatus(config.lang === 'en' ? 'Allow microphone…' : 'Permita el micrófono…');
            try {
                await ensureMicrophoneAccess();
            } catch (err) {
                voiceCallMode = false;
                callBtn.classList.remove('active');
                appendBubble('assistant', micPermissionMessage(err));
                setStatus(config.lang === 'en' ? 'AI · Automarket' : 'IA · Automarket');
                return;
            }
            setStatus(config.lang === 'en' ? 'On call' : 'En llamada');
            var msg = config.lang === 'en'
                ? 'Thank you for calling Automarket. How can I help you?'
                : 'Gracias por llamar a Automarket, ¿en qué puedo ayudarte?';
            if (!hasUserMessages()) {
                messagesEl.innerHTML = '';
                appendBubble('assistant', msg);
                renderSuggestions();
                updateShortcutsVisibility();
            }
            speakText(msg, function () {
                startListening(true);
            });
        } else {
            stopListening();
            releaseMicStream();
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

    if (synth && voiceSelectEl) {
        synth.onvoiceschanged = function () {
            populateVoiceSelect();
        };
        populateVoiceSelect();
        setTimeout(populateVoiceSelect, 600);
        voiceSelectEl.addEventListener('change', function () {
            try {
                localStorage.setItem(VOICE_STORAGE_KEY, voiceSelectEl.value);
            } catch (e) { /* */ }
        });
    }

    if (!SpeechRecognition) {
        micBtn.title = config.lang === 'en' ? 'Voice not supported' : 'Voz no disponible';
        callBtn.disabled = true;
    } else if (!isSecureForMic()) {
        callBtn.title = config.lang === 'en' ? 'Requires HTTPS' : 'Requiere HTTPS';
    }
})();
