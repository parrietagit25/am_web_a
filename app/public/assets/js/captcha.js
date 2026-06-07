/**
 * reCAPTCHA v3 — inyecta token en POST JSON a APIs de formularios públicos.
 */
(function (global) {
    'use strict';

    var cfg = global.AM_RECAPTCHA || {};
    var SITE_KEY = cfg.siteKey || '';
    var ENABLED = SITE_KEY.length > 0;

    var PROTECTED_PATHS = [
        '/api/contacto.php',
        '/api/seminuevos-lead.php',
        '/api/amcorp-lead.php',
        '/api/enviar-pipedrive.php',
        '/api/enviar-leasing-pipedrive.php',
        '/api/renting-lead.php',
        '/api/renting-cotizacion.php',
        '/api/rac-reservation.php',
        '/api/pago.php',
    ];

    function pathnameOf(url) {
        if (typeof url === 'string') {
            try {
                return new URL(url, global.location.origin).pathname;
            } catch (e) {
                return url.split('?')[0];
            }
        }
        if (url && typeof url.url === 'string') {
            return pathnameOf(url.url);
        }
        return '';
    }

    function isProtected(path) {
        return PROTECTED_PATHS.some(function (p) {
            return path === p || path.endsWith(p);
        });
    }

    function getToken(action) {
        action = action || 'submit';
        if (!ENABLED || !global.grecaptcha || !global.grecaptcha.execute) {
            return Promise.resolve('');
        }
        return new Promise(function (resolve, reject) {
            global.grecaptcha.ready(function () {
                global.grecaptcha.execute(SITE_KEY, { action: action }).then(resolve).catch(reject);
            });
        });
    }

    global.AmCaptcha = {
        enabled: ENABLED,
        getToken: getToken,
        withPayload: function (payload, action) {
            return getToken(action).then(function (token) {
                if (token) {
                    payload.captcha_token = token;
                }
                return payload;
            });
        },
    };

    if (!ENABLED || !global.fetch) {
        return;
    }

    var nativeFetch = global.fetch.bind(global);

    global.fetch = function (url, options) {
        options = options || {};
        var method = (options.method || 'GET').toUpperCase();
        var path = pathnameOf(url);

        if (method !== 'POST' || !isProtected(path) || !options.body || typeof options.body !== 'string') {
            return nativeFetch(url, options);
        }

        var action = 'submit';
        try {
            var parsed = JSON.parse(options.body);
            if (parsed && parsed.form_type) {
                action = String(parsed.form_type).toLowerCase().replace(/[^a-z0-9_]+/g, '_').slice(0, 32);
            }
        } catch (e) { /* not JSON */ }

        return getToken(action).then(function (token) {
            if (!token) {
                return nativeFetch(url, options);
            }
            try {
                var data = JSON.parse(options.body);
                data.captcha_token = token;
                var next = Object.assign({}, options, {
                    body: JSON.stringify(data),
                });
                return nativeFetch(url, next);
            } catch (e2) {
                return nativeFetch(url, options);
            }
        }).catch(function () {
            return nativeFetch(url, options);
        });
    };
})(window);
