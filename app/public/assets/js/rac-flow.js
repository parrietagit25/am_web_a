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

    const UNDERAGE_FALLBACK = 75;

    function isOneWayRental(criteria) {
        if (!criteria) return false;
        const pick = String(criteria.locationCode || '').toUpperCase();
        const ret = String(criteria.returnLocationCode || criteria.locationCode || '').toUpperCase();
        return ret !== '' && ret !== pick;
    }

    function chargeAmount(charge) {
        return parseFloat(charge?.amountTotal ?? charge?.amount ?? 0) || 0;
    }

    function isDropoffCharge(charge) {
        const code = String(charge?.code || '').toUpperCase().trim();
        const desc = String(charge?.description || '').toUpperCase();
        if (['DROPOFF', 'DROP', 'ONEWAY', 'OWF', 'DRP'].includes(code)) return true;
        return desc.includes('DROP OFF') || desc.includes('DROPOFF') || desc.includes('ONE WAY') || desc.includes('ONE-WAY');
    }

    function isUnderageCharge(charge) {
        const code = String(charge?.code || '').toUpperCase().trim();
        const desc = String(charge?.description || '').toUpperCase();
        return code === 'UD' || desc.includes('UNDER AGE') || desc.includes('UNDERAGE');
    }

    function findChargeInLists(lists, predicate) {
        for (let i = 0; i < lists.length; i++) {
            const list = lists[i];
            if (!Array.isArray(list)) continue;
            const hit = list.find(predicate);
            if (hit) return hit;
        }
        return null;
    }

    /**
     * Cargos obligatorios de la API (UD, dropoff, etc.) excluyendo SAF.
     * UD solo aplica con edad 23; dropoff solo en devolución en otra sucursal.
     */
    function getBillableMandatoryCharges(vehicle, criteria) {
        const age = String(criteria?.age || '25');
        const oneWay = isOneWayRental(criteria);
        const mandatory = vehicle?.mandatoryCharges || [];
        const optional = vehicle?.optionalCharges || [];
        const result = [];
        const seen = new Set();

        function pushCharge(charge, fallbackCode) {
            const code = String(charge.code || '').toUpperCase().trim() || fallbackCode;
            const key = code + '|' + String(charge.description || '');
            if (seen.has(key)) return;
            const amt = chargeAmount(charge);
            if (amt <= 0) return;
            seen.add(key);
            let label = charge.description || fallbackCode;
            if (isUnderageCharge(charge)) {
                label = 'Cargo por edad (23-24 años)';
            } else if (isDropoffCharge(charge)) {
                label = charge.description || 'Devolución en otra sucursal';
            }
            result.push({ code: code || fallbackCode, label: label, amount: amt });
        }

        mandatory.forEach(function (c) {
            const code = String(c.code || '').toUpperCase().trim();
            if (code === 'SAF') return;
            if (isUnderageCharge(c) && age !== '23') return;
            if (isDropoffCharge(c) && !oneWay) return;
            const fb = isDropoffCharge(c) ? 'DROPOFF' : (isUnderageCharge(c) ? 'UD' : 'MAND');
            pushCharge(c, fb);
        });

        if (age === '23' && !result.some(function (c) { return c.code === 'UD'; })) {
            const ud = findChargeInLists([mandatory, optional], isUnderageCharge);
            if (ud) {
                pushCharge(ud, 'UD');
            } else {
                result.push({
                    code: 'UD',
                    label: 'Cargo por edad (23-24 años)',
                    amount: UNDERAGE_FALLBACK
                });
            }
        }

        return result;
    }

    function sumBillableMandatory(vehicle, criteria) {
        return getBillableMandatoryCharges(vehicle, criteria).reduce(function (s, c) {
            return s + c.amount;
        }, 0);
    }

    function resolveSafAmount(vehicle) {
        const fromPricing = parseFloat(vehicle?.pricing?.saf ?? 0) || 0;
        if (fromPricing > 0) return fromPricing;
        const saf = (vehicle?.mandatoryCharges || []).find(function (c) {
            return String(c.code || '').toUpperCase() === 'SAF';
        });
        return saf ? chargeAmount(saf) : 0;
    }

    /** Tarifa + SAF + cargos obligatorios (sin cobertura ni extras opcionales). */
    function rentalSubtotalBeforeCoverage(vehicle, criteria, days) {
        const rateBase = parseFloat(vehicle?.pricing?.rateBase ?? vehicle?.priceTotal ?? 0)
            || (parseFloat(vehicle?.priceWeb ?? 0) || 0) * Math.max(days || 1, 1);
        return rateBase + resolveSafAmount(vehicle) + sumBillableMandatory(vehicle, criteria);
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
        isOneWayRental,
        getBillableMandatoryCharges,
        sumBillableMandatory,
        resolveSafAmount,
        rentalSubtotalBeforeCoverage,
        UNDERAGE_FALLBACK,
        IMG_BASE
    };
})(window);
