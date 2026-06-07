/**
 * Automarket — telemetría de visitantes (sitio público).
 */
(function () {
    'use strict';

    var API = '/api/telemetry.php';
    var HEARTBEAT_MS = 15000;
    var STORAGE_VISITOR = 'am_telemetry_vid';
    var STORAGE_SESSION = 'am_telemetry_sid';
    var STORAGE_HIT = 'am_telemetry_hit';

    var state = {
        visitorId: null,
        sessionId: null,
        hitId: null,
        pageStart: Date.now(),
        activeStart: Date.now(),
        activeMs: 0,
        maxScroll: 0,
        sending: false
    };

    function uuid() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID().replace(/-/g, '');
        }
        return 'v' + Date.now().toString(36) + Math.random().toString(36).slice(2, 12);
    }

    function getVisitorId() {
        try {
            var id = localStorage.getItem(STORAGE_VISITOR);
            if (!id) {
                id = uuid();
                localStorage.setItem(STORAGE_VISITOR, id);
            }
            return id;
        } catch (e) {
            return uuid();
        }
    }

    function getSessionId() {
        try {
            var id = sessionStorage.getItem(STORAGE_SESSION);
            if (!id) {
                id = uuid();
                sessionStorage.setItem(STORAGE_SESSION, id);
            }
            return id;
        } catch (e) {
            return uuid();
        }
    }

    function getUtm() {
        var p = new URLSearchParams(window.location.search);
        return {
            source: p.get('utm_source') || '',
            medium: p.get('utm_medium') || '',
            campaign: p.get('utm_campaign') || ''
        };
    }

    function detectEntityFromUrl() {
        var path = window.location.pathname.toLowerCase();
        var p = new URLSearchParams(window.location.search);
        var ctx = (window.AM_TELEMETRY && window.AM_TELEMETRY.context) || null;
        if (ctx && ctx.entity_type) {
            return {
                type: ctx.entity_type,
                id: ctx.entity_id || '',
                label: ctx.entity_label || '',
                meta: ctx.meta || {}
            };
        }
        if (path.indexOf('detalle.php') !== -1) {
            return {
                type: 'vehicle_seminuevo',
                id: p.get('placa') || p.get('id') || '',
                label: document.title || ''
            };
        }
        if (path.indexOf('leasing-publicacion.php') !== -1 || path.indexOf('renting-publicacion.php') !== -1) {
            return {
                type: 'publication',
                id: p.get('id') || '',
                label: document.title || ''
            };
        }
        if (path.indexOf('resultados.php') !== -1) {
            return {
                type: 'rac_search_results',
                id: p.get('l') || '',
                label: 'Resultados RAC'
            };
        }
        if (path.indexOf('reservar.php') !== -1) {
            try {
                var v = JSON.parse(sessionStorage.getItem('selectedVehicle') || 'null');
                if (v) {
                    return {
                        type: 'rac_vehicle_checkout',
                        id: v.sippCode || v.name || '',
                        label: v.name || v.category || 'Vehículo RAC',
                        meta: { rate_type: sessionStorage.getItem('selectedRateType') || '' }
                    };
                }
            } catch (e) {}
        }
        return null;
    }

    function detectClientDevice() {
        var ua = navigator.userAgent || '';
        var type = 'desktop';
        var os = 'Desconocido';
        var browser = 'Desconocido';

        if (/iPhone|iPod/i.test(ua)) {
            type = 'mobile';
            os = 'iOS';
        } else if (/iPad/i.test(ua)) {
            type = 'tablet';
            os = 'iPadOS';
        } else if (/Android/i.test(ua)) {
            type = /Mobile/i.test(ua) ? 'mobile' : 'tablet';
            os = 'Android';
        } else if (/Windows NT/i.test(ua)) {
            os = 'Windows';
        } else if (/Mac OS X|Macintosh/i.test(ua)) {
            os = 'macOS';
        } else if (/CrOS/i.test(ua)) {
            os = 'Chrome OS';
        } else if (/Linux/i.test(ua)) {
            os = 'Linux';
        }

        if (/Edg\//i.test(ua)) browser = 'Edge';
        else if (/OPR\//i.test(ua)) browser = 'Opera';
        else if (/CriOS/i.test(ua)) browser = 'Chrome (iOS)';
        else if (/FxiOS/i.test(ua)) browser = 'Firefox (iOS)';
        else if (/Chrome\//i.test(ua) && !/Edg\//i.test(ua)) browser = 'Chrome';
        else if (/Firefox\//i.test(ua)) browser = 'Firefox';
        else if (/Safari\//i.test(ua) && !/Chrome/i.test(ua)) browser = 'Safari';
        else if (/SamsungBrowser/i.test(ua)) browser = 'Samsung Internet';

        return {
            device_type: type,
            device: type,
            os: os,
            browser: browser,
            touch: (navigator.maxTouchPoints || 0) > 0,
            platform: navigator.platform || '',
            pixel_ratio: window.devicePixelRatio || 1
        };
    }

    function basePayload(type) {
        var entity = detectEntityFromUrl();
        var cfg = window.AM_TELEMETRY || {};
        var bodyClass = document.body.className || '';
        var unitMatch = bodyClass.match(/theme-(\w+)/);
        var clientDevice = detectClientDevice();
        return {
            type: type,
            visitor_id: state.visitorId,
            session_id: state.sessionId,
            page_path: window.location.pathname,
            page_query: window.location.search.replace(/^\?/, ''),
            page_title: document.title,
            business_unit: cfg.unit || (unitMatch ? unitMatch[1] : ''),
            referrer: document.referrer || '',
            language: navigator.language || '',
            timezone: (Intl.DateTimeFormat && Intl.DateTimeFormat().resolvedOptions().timeZone) || '',
            user_agent: navigator.userAgent || '',
            screen: { w: window.screen.width, h: window.screen.height },
            viewport: { w: window.innerWidth, h: window.innerHeight },
            pixel_ratio: window.devicePixelRatio || 1,
            client_device: clientDevice,
            utm: getUtm(),
            entity: entity,
            meta: cfg.context && cfg.context.meta ? cfg.context.meta : (entity && entity.meta ? entity.meta : {})
        };
    }

    function activeDurationSeconds() {
        var extra = document.hidden ? 0 : (Date.now() - state.activeStart);
        return Math.round((state.activeMs + extra) / 1000);
    }

    function send(payload, useBeacon) {
        var body = JSON.stringify(payload);
        if (useBeacon && navigator.sendBeacon) {
            try {
                return navigator.sendBeacon(API, new Blob([body], { type: 'application/json' }));
            } catch (e) {}
        }
        return fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: body,
            keepalive: true,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).catch(function () { return null; });
    }

    function updateScroll() {
        var doc = document.documentElement;
        var scrollTop = window.pageYOffset || doc.scrollTop || 0;
        var height = Math.max(doc.scrollHeight, doc.offsetHeight) - window.innerHeight;
        if (height <= 0) {
            state.maxScroll = Math.max(state.maxScroll, 100);
            return;
        }
        var pct = Math.round((scrollTop / height) * 100);
        state.maxScroll = Math.max(state.maxScroll, Math.min(100, pct));
    }

    function heartbeat(isExit) {
        if (!state.hitId) return;
        var payload = basePayload(isExit ? 'exit' : 'heartbeat');
        payload.hit_id = state.hitId;
        payload.duration = activeDurationSeconds();
        payload.scroll_depth = state.maxScroll;
        if (isExit) {
            send(payload, true);
        } else {
            send(payload, false);
        }
    }

    function trackPageView() {
        state.visitorId = getVisitorId();
        state.sessionId = getSessionId();
        state.pageStart = Date.now();
        state.activeStart = Date.now();
        state.activeMs = 0;

        send(basePayload('init'), false);

        send(basePayload('pageview'), false).then(function (res) {
            if (res && res.hit_id) {
                state.hitId = res.hit_id;
                try { sessionStorage.setItem(STORAGE_HIT, String(res.hit_id)); } catch (e) {}
            }
            if (res && res.visitor_id) {
                state.visitorId = res.visitor_id;
                try { localStorage.setItem(STORAGE_VISITOR, res.visitor_id); } catch (e) {}
            }
            if (res && res.session_id) {
                state.sessionId = res.session_id;
            }
        });
    }

    function trackCustom(name, data) {
        var payload = basePayload('event');
        payload.meta = Object.assign({ event_name: name }, data || {});
        send(payload, false);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            state.activeMs += Date.now() - state.activeStart;
        } else {
            state.activeStart = Date.now();
        }
    });

    window.addEventListener('scroll', updateScroll, { passive: true });
    window.addEventListener('beforeunload', function () { heartbeat(true); });
    window.addEventListener('pagehide', function () { heartbeat(true); });

    setInterval(function () {
        updateScroll();
        heartbeat(false);
    }, HEARTBEAT_MS);

    document.addEventListener('click', function (ev) {
        var el = ev.target.closest('a, button, [data-am-track]');
        if (!el) return;
        var href = el.getAttribute('href') || '';
        if (el.matches('[data-am-track], .btn-premium, .btn-theme, .btn-danger, .card-vehicle, .inventory-card')) {
            trackCustom('click', {
                tag: el.tagName,
                text: (el.innerText || '').slice(0, 120),
                href: href.slice(0, 200)
            });
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackPageView);
    } else {
        trackPageView();
    }

    window.AMTelemetry = { track: trackCustom };
})();
