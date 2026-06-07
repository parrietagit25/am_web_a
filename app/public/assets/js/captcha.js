/**
 * reCAPTCHA v2 — checkbox visible; inyecta token en POST JSON a APIs de formularios.
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

    var CAPTCHA_MSG = 'Por favor marque la casilla «No soy un robot» antes de enviar.';

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

    function collectToken() {
        if (!ENABLED || !global.grecaptcha || typeof global.grecaptcha.getResponse !== 'function') {
            return '';
        }
        var token = '';
        for (var i = 0; i < 8; i++) {
            try {
                var t = global.grecaptcha.getResponse(i);
                if (t) {
                    token = t;
                    break;
                }
            } catch (e) {
                break;
            }
        }
        if (!token) {
            try {
                token = global.grecaptcha.getResponse() || '';
            } catch (e2) { /* */ }
        }
        return token;
    }

    function getToken() {
        if (!ENABLED) {
            return Promise.resolve('');
        }
        return new Promise(function (resolve, reject) {
            if (!global.grecaptcha || !global.grecaptcha.ready) {
                reject(new Error('captcha_unavailable'));
                return;
            }
            global.grecaptcha.ready(function () {
                var token = collectToken();
                if (token) {
                    resolve(token);
                } else {
                    reject(new Error('captcha_required'));
                }
            });
        });
    }

    function resetWidgets() {
        if (global.grecaptcha && typeof global.grecaptcha.reset === 'function') {
            try {
                global.grecaptcha.reset();
            } catch (e) { /* */ }
        }
    }

    global.AmCaptcha = {
        enabled: ENABLED,
        getToken: getToken,
        reset: resetWidgets,
        withPayload: function (payload) {
            return getToken().then(function (token) {
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

        return getToken().then(function (token) {
            try {
                var data = JSON.parse(options.body);
                data.captcha_token = token;
                var next = Object.assign({}, options, {
                    body: JSON.stringify(data),
                });
                return nativeFetch(url, next).then(function (res) {
                    if (!res.ok && res.status === 403) {
                        resetWidgets();
                    }
                    return res;
                });
            } catch (e) {
                return nativeFetch(url, options);
            }
        }).catch(function (err) {
            if (err && err.message === 'captcha_required') {
                alert(CAPTCHA_MSG);
            } else if (err && err.message === 'captcha_unavailable') {
                alert('No se pudo cargar la verificación de seguridad. Recargue la página.');
            }
            return Promise.reject(err);
        });
    };
})(window);
