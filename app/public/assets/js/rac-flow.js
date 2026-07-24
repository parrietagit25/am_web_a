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
        const raw = String(url).trim();
        if (!raw) return '';
        if (/^https?:\/\//i.test(raw) || raw.startsWith('data:')) return raw;

        // Imágenes del CMS / uploads del sitio: no anteponer Partner DO.
        if (raw.startsWith('/assets/') || raw.startsWith('assets/')) {
            return raw.startsWith('/') ? raw : '/' + raw;
        }

        const base = (typeof global.RAC_IMAGE_BASE === 'string' && global.RAC_IMAGE_BASE !== '')
            ? String(global.RAC_IMAGE_BASE).replace(/\/$/, '')
            : IMG_BASE;
        return base + (raw.startsWith('/') ? raw : '/' + raw);
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

    const UNDERAGE_PER_DAY = 25;

    function vehicleBilledDays(vehicle, calendarDays) {
        const rd = parseInt(vehicle?.rentalDays, 10);
        return rd > 0 ? rd : (calendarDays || 1);
    }

    function resolveVendorRateId(vehicle, rateType) {
        if (rateType === 'counter' && Array.isArray(vehicle?.rates)) {
            const counter = vehicle.rates.find(function (r) {
                const rc = String(r.rateCode || '').toUpperCase();
                return rc !== 'WEB' && rc !== 'BEST' && r.available !== false && r.vendorRateId;
            });
            if (counter?.vendorRateId) return String(counter.vendorRateId);
        }
        return String(vehicle?.vendorRateId || vehicle?.pricing?.quoteToken || '');
    }

    /** Tarifa del período según WebExclusivo o mostrador (sin mandatory ni cobertura). */
    function resolveRentalBase(vehicle, rateType, billedDays) {
        const days = Math.max(billedDays || 1, 1);
        const p = vehicle?.pricing || {};
        const norm = normalizeSelectedVehicleForExtras(vehicle, null, days);

        if (rateType === 'counter') {
            const counterTotal = parseFloat(vehicle?.priceCounterTotal ?? p.rateBaseCounter ?? NaN);
            if (!isNaN(counterTotal) && counterTotal > 0) return counterTotal;
            const daily = parseFloat(vehicle?.priceCounter ?? p.finalDailyRate ?? norm.dailyRate ?? 0) || 0;
            return daily * days;
        }

        if (norm.totalRate > 0) return norm.totalRate;
        if (norm.dailyRate > 0) return norm.dailyRate * days;
        return 0;
    }

    /**
     * Normaliza precios del vehículo seleccionado para UI de extras/reserva.
     * No es fuente de verdad para cobro — el servidor usa quote server-side.
     */
    function normalizeSelectedVehicleForExtras(vehicle, criteria, billedDays) {
        const days = Math.max(billedDays || 1, parseInt(vehicle?.rentalDays, 10) || 1);
        const p = vehicle?.pricing || {};
        let dailyRate = parseFloat(
            p.finalDailyRate ?? p.priceWeb ?? vehicle?.priceWeb ?? vehicle?.dailyRate ?? NaN
        );
        if (isNaN(dailyRate) || dailyRate <= 0) dailyRate = 0;

        let totalRate = parseFloat(p.finalTotalRate ?? p.rateBase ?? NaN);
        if (isNaN(totalRate) || totalRate <= 0) {
            totalRate = parseFloat(vehicle?.priceTotal ?? vehicle?.totalRate ?? NaN);
        }
        if (isNaN(totalRate) || totalRate <= 0) totalRate = 0;

        if (totalRate <= 0 && dailyRate > 0) {
            totalRate = Math.round(dailyRate * days * 100) / 100;
        }
        if (dailyRate <= 0 && totalRate > 0 && days > 0) {
            dailyRate = Math.round((totalRate / days) * 100) / 100;
        }

        return {
            vehicleCode: String(
                vehicle?.sippCode || vehicle?.vehicleCode || p.vehicle_code || p.vehicleCode || ''
            ).toUpperCase().trim(),
            vehicleName: String(vehicle?.category || vehicle?.vehicleCategory || vehicle?.name || '').trim(),
            dailyRate,
            totalRate,
            rentalDays: days,
            currency: String(p.currency || vehicle?.currency || 'USD'),
            rateSource: String(p.rateSource || ''),
            quoteToken: String(p.barsQuoteToken || ''),
        };
    }

    function applyNormalizedPricing(vehicle, billedDays) {
        if (!vehicle) return vehicle;
        const norm = normalizeSelectedVehicleForExtras(vehicle, null, billedDays);
        const merged = Object.assign({}, vehicle);
        merged.priceWeb = norm.dailyRate;
        if (norm.totalRate > 0) {
            merged.priceTotal = norm.totalRate;
        }
        merged.pricing = Object.assign({}, vehicle.pricing || {}, {
            finalDailyRate: norm.dailyRate,
            finalTotalRate: norm.totalRate,
            rateBase: norm.totalRate > 0 ? norm.totalRate : (vehicle.pricing || {}).rateBase,
        });
        return merged;
    }

    function buildAvailabilityPayload(criteria) {
        if (!criteria) return null;
        return {
            locationCode: criteria.locationCode,
            returnLocationCode: criteria.returnLocationCode || criteria.locationCode,
            pickupDate: criteria.pickupDate,
            pickupTime: criteria.pickupTime || '10:00',
            returnDate: criteria.returnDate,
            returnTime: criteria.returnTime || '10:00',
            age: criteria.age || '25',
            promoCode: criteria.promoCode || ''
        };
    }

    function fetchAvailability(criteria) {
        const payload = buildAvailabilityPayload(criteria);
        if (!payload?.locationCode) return Promise.reject(new Error('missing criteria'));
        return fetch('/api/disponibilidad.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); });
    }

    function findVehicleInResults(data, sippCode) {
        const list = data?.vehicles || [];
        return list.find(function (v) { return v.sippCode === sippCode; }) || null;
    }

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
    function getBillableMandatoryCharges(vehicle, criteria, billedDays) {
        const age = String(criteria?.age || '25');
        const days = Math.max(billedDays || 1, 1);
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
                    amount: UNDERAGE_PER_DAY * days
                });
            }
        }

        return result;
    }

    function sumBillableMandatory(vehicle, criteria, billedDays) {
        return getBillableMandatoryCharges(vehicle, criteria, billedDays).reduce(function (s, c) {
            return s + c.amount;
        }, 0);
    }

    /** SAF + UD + dropoff… Preferir pricing.mandatoryTotal del API. */
    function resolveMandatoryTotal(vehicle, criteria, billedDays) {
        const mt = parseFloat(vehicle?.pricing?.mandatoryTotal ?? NaN);
        if (!isNaN(mt) && mt >= 0) return mt;
        return resolveSafAmount(vehicle) + sumBillableMandatory(vehicle, criteria, billedDays);
    }

    /** Líneas de desglose: SAF + cargos no-SAF (UD, dropoff). */
    function resolveMandatoryLines(vehicle, criteria, billedDays) {
        const lines = [];
        const saf = resolveSafAmount(vehicle);
        if (saf > 0) {
            lines.push({ code: 'SAF', label: 'Cargo Administrativo (SAF)', amount: saf });
        }
        getBillableMandatoryCharges(vehicle, criteria, billedDays).forEach(function (c) {
            lines.push(c);
        });
        return lines;
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
    function rentalSubtotalBeforeCoverage(vehicle, criteria, days, rateType) {
        const billed = vehicleBilledDays(vehicle, days);
        const base = resolveRentalBase(vehicle, rateType || 'web', billed);
        return base + resolveMandatoryTotal(vehicle, criteria, billed);
    }

    function isBarsCacheVehicle(vehicle) {
        return !!(vehicle && vehicle.pricing && vehicle.pricing.rateSource === 'bars_cache');
    }

    function parseQuoteExpiresAt(value) {
        if (!value) return NaN;
        const raw = String(value).trim();
        if (!raw) return NaN;
        if (/Z$|[+-]\d{2}:?\d{2}$/.test(raw)) {
            return Date.parse(raw);
        }
        if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/.test(raw)) {
            return Date.parse(raw.replace(' ', 'T') + 'Z');
        }
        return Date.parse(raw);
    }

    function isBarsQuoteExpired(vehicle) {
        const pricing = vehicle && vehicle.pricing ? vehicle.pricing : {};
        if (!pricing.barsQuoteToken) return true;
        const expMs = parseQuoteExpiresAt(pricing.quoteExpiresAt);
        if (isNaN(expMs)) return false;
        return Date.now() > expMs;
    }

    function mergeVehiclePreservingQuote(previous, next) {
        if (!previous || !next) {
            return next || previous;
        }
        const prevPricing = previous.pricing || {};
        const nextPricing = next.pricing || {};
        const merged = Object.assign({}, next);
        if (prevPricing.barsQuoteToken || nextPricing.barsQuoteToken || prevPricing.rateSource === 'bars_cache' || nextPricing.rateSource === 'bars_cache') {
            merged.pricing = Object.assign({}, nextPricing, {
                rateSource: nextPricing.rateSource || prevPricing.rateSource || 'bars_cache',
                // Preferir token/expiración nuevos al recotizar (AM-ADJ-13).
                barsQuoteToken: nextPricing.barsQuoteToken || prevPricing.barsQuoteToken || '',
                quoteExpiresAt: nextPricing.quoteExpiresAt || prevPricing.quoteExpiresAt || '',
                baseDailyRate: nextPricing.baseDailyRate != null ? nextPricing.baseDailyRate : prevPricing.baseDailyRate,
                finalDailyRate: nextPricing.finalDailyRate != null ? nextPricing.finalDailyRate : prevPricing.finalDailyRate,
                finalTotalRate: nextPricing.finalTotalRate != null ? nextPricing.finalTotalRate : prevPricing.finalTotalRate,
            });
        }
        return merged;
    }

    function buildQuotePayload(criteria, vehicle, rateType) {
        return {
            vehicle_code: vehicle.sippCode,
            sippCode: vehicle.sippCode,
            locationCode: criteria.locationCode,
            returnLocationCode: criteria.returnLocationCode || criteria.locationCode,
            pickupDate: criteria.pickupDate,
            pickupTime: criteria.pickupTime || '10:00',
            returnDate: criteria.returnDate,
            returnTime: criteria.returnTime || '10:00',
            age: criteria.age || '25',
            rate_type: rateType || 'web',
        };
    }

    function ensureBarsQuote(criteria, vehicle, rateType, options) {
        if (!criteria || !vehicle || !vehicle.sippCode) {
            return Promise.reject(new Error('Datos de reserva incompletos.'));
        }
        const force = !!(options && options.force);
        const pricing = vehicle.pricing || {};
        const hasToken = !!pricing.barsQuoteToken;
        const expired = isBarsQuoteExpired(vehicle);
        if (hasToken && !force && !expired) {
            return Promise.resolve(vehicle);
        }
        if (!isBarsCacheVehicle(vehicle) && pricing.rateSource !== 'bars_cache' && !force) {
            return Promise.resolve(vehicle);
        }
        return fetch('/api/rac-rate-quote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(buildQuotePayload(criteria, vehicle, rateType)),
        }).then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message || 'No se pudo bloquear la tarifa.');
                }
                const incoming = Object.assign({}, vehicle, data.vehicle || {});
                incoming.pricing = Object.assign({}, (data.vehicle && data.vehicle.pricing) || {}, data.pricing || {}, {
                    barsQuoteToken: data.quote_token,
                    quoteExpiresAt: data.expires_at || (data.pricing && data.pricing.quoteExpiresAt) || '',
                    rateSource: 'bars_cache',
                });
                const merged = mergeVehiclePreservingQuote(vehicle, incoming);
                merged.pricing = Object.assign({}, merged.pricing || {}, incoming.pricing);
                sessionStorage.setItem('selectedVehicle', JSON.stringify(merged));
                return merged;
            });
    }

    /**
     * Preview server-side de totales (no crea reserva).
     * @returns {Promise<object>}
     */
    function previewRateTotals(criteria, vehicle, extrasSelection, rateType) {
        if (!criteria || !vehicle || !vehicle.sippCode) {
            return Promise.reject(new Error('Datos de reserva incompletos.'));
        }
        const pricing = vehicle.pricing || {};
        const payload = Object.assign({}, buildQuotePayload(criteria, vehicle, rateType), {
            quote_token: pricing.barsQuoteToken || '',
            rate_quote_token: pricing.barsQuoteToken || '',
            extras: extrasSelection || {},
        });
        return fetch('/api/rac-rate-preview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        }).then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) || 'No se pudo recalcular la tarifa.');
                }
                if (data.reservation_created) {
                    throw new Error('Respuesta de preview inválida.');
                }
                let merged = vehicle;
                if (data.quote_token) {
                    const incoming = Object.assign({}, vehicle, data.vehicle || {});
                    incoming.pricing = Object.assign({}, vehicle.pricing || {}, data.pricing || {}, {
                        barsQuoteToken: data.quote_token,
                        quoteExpiresAt: data.expires_at || '',
                        rateSource: 'bars_cache',
                    });
                    if (data.pricing && data.pricing.finalTotalRate != null) {
                        incoming.pricing.finalTotalRate = data.pricing.finalTotalRate;
                        incoming.pricing.rateBase = data.pricing.finalTotalRate;
                    }
                    merged = mergeVehiclePreservingQuote(vehicle, incoming);
                    merged.pricing = Object.assign({}, merged.pricing || {}, incoming.pricing);
                    sessionStorage.setItem('selectedVehicle', JSON.stringify(merged));
                }
                return {
                    vehicle: merged,
                    totals: data.totals || {},
                    protection: data.protection || {},
                    refreshed: !!data.refreshed,
                    quote_token: data.quote_token || null,
                    expires_at: data.expires_at || null,
                    reservation_created: false,
                };
            });
    }

    function refreshVehicleForExtras(criteria, vehicle) {
        if (isBarsCacheVehicle(vehicle)) {
            // No bloquear UI esperando quote — se crea/valida al preview o al continuar.
            return Promise.resolve(vehicle);
        }
        if (!window.RAC_FLOW?.fetchAvailability || !vehicle?.sippCode) {
            return Promise.resolve(vehicle);
        }
        return fetchAvailability(criteria)
            .then(function (data) {
                sessionStorage.setItem('searchResults', JSON.stringify(data));
                const fresh = findVehicleInResults(data, vehicle.sippCode);
                if (!fresh) {
                    return vehicle;
                }
                const rate = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';
                const enriched = mergeVehiclePreservingQuote(vehicle, Object.assign({}, fresh, {
                    _selectedRateType: rate,
                    vendorRateId: resolveVendorRateId(fresh, rate),
                }));
                sessionStorage.setItem('selectedVehicle', JSON.stringify(enriched));
                return enriched;
            })
            .catch(function () { return vehicle; });
    }

    global.RAC_FLOW = {
        calcDays,
        vehicleBilledDays,
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
        resolveRentalBase,
        normalizeSelectedVehicleForExtras,
        applyNormalizedPricing,
        resolveMandatoryTotal,
        resolveMandatoryLines,
        resolveVendorRateId,
        rentalSubtotalBeforeCoverage,
        buildAvailabilityPayload,
        fetchAvailability,
        findVehicleInResults,
        isBarsCacheVehicle,
        isBarsQuoteExpired,
        mergeVehiclePreservingQuote,
        ensureBarsQuote,
        previewRateTotals,
        refreshVehicleForExtras,
        buildQuotePayload,
        UNDERAGE_PER_DAY,
        IMG_BASE
    };
})(window);
