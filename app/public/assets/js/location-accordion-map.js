/**
 * Automarket — mapas Leaflet en acordeones de sucursales (inicialización compartida).
 */
(function (window) {
    'use strict';

    var registry = {};

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function defaultIcon() {
        return L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
    }

    function invalidateMap(state) {
        if (!state || !state.map) {
            return;
        }
        requestAnimationFrame(function () {
            setTimeout(function () {
                state.map.invalidateSize();
                if (state.marker) {
                    state.marker.openPopup();
                }
            }, 50);
        });
    }

    function initMapEntry(entry) {
        var mapId = entry.mapId;
        var collapseId = entry.collapseId;
        var lat = entry.lat;
        var lng = entry.lng;
        var title = entry.title || '';
        var subtitle = entry.subtitle || '';

        if (mapId === '' || lat == null || lng == null || isNaN(lat) || isNaN(lng)) {
            return;
        }

        var mapEl = document.getElementById(mapId);
        if (!mapEl) {
            return;
        }

        var collapseEl = collapseId ? document.getElementById(collapseId) : null;

        function initMap() {
            if (typeof L === 'undefined') {
                return;
            }

            var state = registry[mapId];
            if (state && state.map) {
                invalidateMap(state);
                return;
            }

            state = { map: null, marker: null };
            registry[mapId] = state;

            state.map = L.map(mapId).setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(state.map);

            state.marker = L.marker([lat, lng], { icon: defaultIcon() }).addTo(state.map);

            if (title || subtitle) {
                var popupHtml = '';
                if (title) {
                    popupHtml += '<span class="fw-bold text-navy">' + escapeHtml(title) + '</span>';
                }
                if (subtitle) {
                    popupHtml += '<br><small class="text-muted">' + escapeHtml(subtitle) + '</small>';
                }
                state.marker.bindPopup(popupHtml);
                state.marker.openPopup();
            }

            invalidateMap(state);
        }

        if (collapseEl) {
            collapseEl.addEventListener('shown.bs.collapse', initMap);
        }

        if (entry.autoInit) {
            var boot = function () {
                requestAnimationFrame(function () {
                    setTimeout(initMap, 400);
                });
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        }
    }

    window.AMLocationAccordionMap = {
        init: function (configs) {
            if (!Array.isArray(configs)) {
                return;
            }
            configs.forEach(initMapEntry);
        },
        invalidate: function (mapId) {
            invalidateMap(registry[mapId]);
        }
    };
})(window);
