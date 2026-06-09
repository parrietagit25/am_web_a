/**
 * Shared RAC booking flow utilities (sessionStorage, branches, navigation).
 */
(function (global) {
    'use strict';

    const IMG_BASE = 'https://automarket-rentacar-fme3z.ondigitalocean.app';

    function calcDays(pickup, ret) {
        if (!pickup || !ret) return 1;
        const d1 = new Date(pickup + 'T12:00:00');
        const d2 = new Date(ret + 'T12:00:00');
        const diff = Math.round((d2 - d1) / 86400000);
        return diff > 0 ? diff : 1;
    }

    function branchLabel(code) {
        const branches = global.RAC_BRANCHES ? global.RAC_BRANCHES() : [];
        const b = branches.find(x => x.code === code);
        return b ? (b.shortName || b.name) : code;
    }

    function formatDateDisplay(iso) {
        if (!iso) return '—';
        const [y, m, d] = iso.split('-');
        return `${d}/${m}/${y}`;
    }

    function formatTimeDisplay(t24) {
        if (!t24) return '';
        const [h, m] = t24.split(':').map(Number);
        const period = h < 12 ? 'a.m.' : 'p.m.';
        const h12 = h === 0 ? 12 : (h > 12 ? h - 12 : h);
        return `${h12}:${String(m).padStart(2, '0')} ${period}`;
    }

    function resolveImage(url) {
        if (!url) return '';
        if (url.startsWith('http')) return url;
        return IMG_BASE + (url.startsWith('/') ? url : '/' + url);
    }

    function getCriteria() {
        try {
            const raw = sessionStorage.getItem('searchCriteria');
            return raw ? JSON.parse(raw) : null;
        } catch { return null; }
    }

    function getVehicle() {
        try {
            const raw = sessionStorage.getItem('selectedVehicle');
            return raw ? JSON.parse(raw) : null;
        } catch { return null; }
    }

    function getExtras() {
        try {
            const raw = sessionStorage.getItem('extrasSelection');
            return raw ? JSON.parse(raw) : null;
        } catch { return null; }
    }

    function buildResultsUrl(criteria) {
        if (!criteria) return '/resultados.php';
        const p = new URLSearchParams();
        p.set('l', criteria.locationCode || '');
        if (criteria.returnLocationCode && criteria.returnLocationCode !== criteria.locationCode) {
            p.set('rl', criteria.returnLocationCode);
        }
        p.set('d1', criteria.pickupDate || '');
        p.set('pt', criteria.pickupTime || '10:00');
        p.set('d2', criteria.returnDate || '');
        p.set('rt', criteria.returnTime || '10:00');
        p.set('a', criteria.age || '25');
        if (criteria.promoCode) p.set('pr', criteria.promoCode);
        return '/resultados.php?' + p.toString();
    }

    function goToStep(step) {
        const criteria = getCriteria();
        if (step === 1) {
            window.location.href = '/rent-a-car.php';
            return;
        }
        if (step === 2) {
            window.location.href = buildResultsUrl(criteria);
            return;
        }
        if (step === 3) {
            window.location.href = '/extras.php';
            return;
        }
        if (step === 4) {
            window.location.href = '/reservar.php';
        }
    }

    function requireVehicle(redirect) {
        const vehicle = getVehicle();
        const criteria = getCriteria();
        if (!vehicle || !criteria) {
            window.location.href = redirect || '/rent-a-car.php';
            return null;
        }
        return { vehicle, criteria };
    }

    function fmtMoney(n) {
        return '$' + Number(n || 0).toFixed(2);
    }

    global.RAC_FLOW = {
        calcDays,
        branchLabel,
        formatDateDisplay,
        formatTimeDisplay,
        resolveImage,
        getCriteria,
        getVehicle,
        getExtras,
        buildResultsUrl,
        goToStep,
        requireVehicle,
        fmtMoney,
        IMG_BASE
    };
})(window);
