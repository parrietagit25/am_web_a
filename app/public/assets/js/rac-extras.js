/**
 * RAC extras page — protection, optional equipment, totals, alternatives.
 * Paso 3: re-consulta disponibilidad + mandatoryTotal + cobertura opcional.
 */
(function () {
    'use strict';

    const EQUIP_LABELS = {
        CONDADIC: 'Conductor Adicional',
        SILLA: 'Silla de Bebé',
        AMAS: 'Automarket Assistance',
        PPASS: 'Panapass',
        DELIVERY: 'Servicio de Delivery'
    };

    const DRIVER_FALLBACK_PER_UNIT = 15;
    /** Solo equipos opcionales ofrecidos en línea (el API devuelve más). */
    const ALLOWED_EQUIPMENT_CODES = ['SILLA', 'PPASS', 'DELIVERY', 'AMAS'];
    const PROTECTION_ORDER = ['BASIC', 'STANDARD', 'PREMIUM'];
    const PROTECTION_LABELS = {
        BASIC: 'Protección Básica',
        STANDARD: 'Protección Estándar',
        PREMIUM: 'Protección Premium'
    };

    function protectionCode(pkg) {
        return String(pkg.code || pkg.coverageType || '').toUpperCase().trim();
    }

    function protectionAmount(pkg, billedDays) {
        const days = Math.max(parseInt(billedDays, 10) || 1, 1);
        let amt = parseFloat(pkg && pkg.amountTotal != null ? pkg.amountTotal : NaN);
        if (Number.isFinite(amt)) return amt;
        const perDay = parseFloat(pkg && pkg.pricePerDay != null ? pkg.pricePerDay : 0) || 0;
        return perDay * days;
    }

    /** Más barata primero (solo productos reales del admin / API). */
    function cheapestProtection(packages, billedDays) {
        const list = Array.isArray(packages) ? packages.slice() : [];
        if (!list.length) return null;
        list.sort(function (a, b) {
            const diff = protectionAmount(a, billedDays) - protectionAmount(b, billedDays);
            if (diff !== 0) return diff;
            return protectionCode(a).localeCompare(protectionCode(b));
        });
        return list[0];
    }

    function protectionTitle(pkg, code) {
        if (pkg && pkg.source === 'db' && pkg.name) {
            return String(pkg.name);
        }
        return PROTECTION_LABELS[code] || (pkg && (pkg.name || pkg.description)) || code;
    }

    function equipmentTitle(eq, code) {
        if (eq && eq.source === 'db' && eq.name) {
            return String(eq.name);
        }
        return EQUIP_LABELS[code] || (eq && (eq.name || eq.description)) || code;
    }

    function equipmentMaxQuantity(eq) {
        const maxQ = parseInt(eq && eq.maxQuantity, 10);
        return Number.isFinite(maxQ) && maxQ > 0 ? maxQ : 1;
    }

    function driverMaxQuantity(charge) {
        if (charge && charge.source === 'db') {
            return equipmentMaxQuantity(charge);
        }
        return 3;
    }

    function filterProtectionPackages(list) {
        if (!Array.isArray(list)) return [];
        const fromDb = list.some(function (p) { return p.source === 'db'; });
        if (fromDb) {
            return list.filter(function (pkg) {
                const code = protectionCode(pkg);
                return code !== '' && code !== 'NONE';
            });
        }
        const byCode = {};
        list.forEach(function (pkg) {
            const code = protectionCode(pkg);
            if (PROTECTION_ORDER.indexOf(code) === -1) return;
            if (!byCode[code]) byCode[code] = pkg;
        });
        return PROTECTION_ORDER.map(function (code) { return byCode[code]; }).filter(Boolean);
    }

    function resolveProtectionPackages(vehicle) {
        if (Array.isArray(vehicle._dbProtections) && vehicle._dbProtections.length) {
            return filterProtectionPackages(vehicle._dbProtections);
        }
        const pricing = vehicle.pricing || {};
        const fromPricing = filterProtectionPackages(pricing.coveragePackages);
        if (fromPricing.length) return fromPricing;
        return filterProtectionPackages(vehicle.availableCoverages);
    }

    function resolveEquipmentList(vehicle) {
        if (Array.isArray(vehicle._dbExtras) && vehicle._dbExtras.length) {
            return vehicle._dbExtras.slice();
        }
        const allowed = ALLOWED_EQUIPMENT_CODES;
        const merged = [];
        const seen = new Set();

        function pushIfAllowed(item) {
            const code = (item.code || '').toUpperCase();
            if (!code || seen.has(code) || allowed.indexOf(code) === -1) return;
            seen.add(code);
            merged.push(item);
        }

        (vehicle.availableEquipment || []).forEach(pushIfAllowed);
        // AMAS suele venir en availableCoverages, no en availableEquipment
        (vehicle.availableCoverages || []).forEach(function (c) {
            if ((c.code || '').toUpperCase() === 'AMAS') pushIfAllowed(c);
        });

        return merged;
    }

    function findCondadicCharge(vehicle) {
        if (Array.isArray(vehicle._dbExtras)) {
            const dbDriver = vehicle._dbExtras.find(function (e) {
                return (e.code || '').toUpperCase() === 'CONDADIC';
            });
            if (dbDriver) return dbDriver;
        }
        const lists = [
            vehicle.mandatoryCharges || [],
            vehicle.optionalCharges || [],
            vehicle.availableEquipment || [],
        ];
        for (const list of lists) {
            const hit = list.find(c => (c.code || '').toUpperCase() === 'CONDADIC');
            if (hit) return hit;
        }
        return null;
    }

    function calcDriverTotal(charge, drivers, days) {
        const count = parseInt(drivers, 10) || 0;
        if (count <= 0) return 0;
        if (!charge) return DRIVER_FALLBACK_PER_UNIT * count;
        if (charge.source === 'db') {
            const total = parseFloat(charge.amountTotal ?? 0) || 0;
            if (charge.unitName === 'day' || charge.pricePerDay) {
                return (parseFloat(charge.pricePerDay ?? 0) || 0) * days * count;
            }
            return total * count;
        }
        const amountTotal = parseFloat(charge.amountTotal ?? 0) || 0;
        if (amountTotal > 0) {
            return (amountTotal / Math.max(days, 1)) * days * count;
        }
        const perDay = parseFloat(charge.pricePerDay ?? 0) || 0;
        if (perDay > 0) return perDay * days * count;
        return DRIVER_FALLBACK_PER_UNIT * count;
    }

    function driverPriceLabel(charge, days) {
        if (!charge) return `$${DRIVER_FALLBACK_PER_UNIT.toFixed(2)} c/u`;
        if (charge.source === 'db') {
            const total = parseFloat(charge.amountTotal ?? 0) || 0;
            if (charge.unitName === 'day' || charge.pricePerDay) {
                const perDay = parseFloat(charge.pricePerDay ?? 0) || 0;
                return `$${perDay.toFixed(2)}/día · Total $${(perDay * days).toFixed(2)} c/u`;
            }
            return `$${total.toFixed(2)} c/u`;
        }
        const amountTotal = parseFloat(charge.amountTotal ?? 0) || 0;
        if (amountTotal > 0) {
            const perDriver = (amountTotal / Math.max(days, 1)) * days;
            return `$${perDriver.toFixed(2)} c/u (${days} día${days !== 1 ? 's' : ''})`;
        }
        const perDay = parseFloat(charge.pricePerDay ?? 0) || 0;
        if (perDay > 0) {
            return `$${perDay.toFixed(2)}/día · Total $${(perDay * days).toFixed(2)} c/u`;
        }
        return `$${DRIVER_FALLBACK_PER_UNIT.toFixed(2)} c/u`;
    }

    function refreshVehicleFromApi(criteria, vehicle) {
        if (window.RAC_FLOW?.refreshVehicleForExtras) {
            return window.RAC_FLOW.refreshVehicleForExtras(criteria, vehicle);
        }
        return Promise.resolve(vehicle);
    }

    function createQuoteForVehicle(criteria, vehicle, rateType) {
        if (!window.RAC_FLOW?.ensureBarsQuote) {
            return Promise.resolve(vehicle);
        }
        return window.RAC_FLOW.ensureBarsQuote(criteria, vehicle, rateType);
    }

    function resolveVehicleCode(vehicle) {
        return String(
            vehicle.sippCode
            || vehicle.vehicleCode
            || vehicle.pricing?.vehicleCode
            || vehicle.pricing?.vehicle_code
            || ''
        ).toUpperCase().trim();
    }

    function resolveVehicleCategory(vehicle) {
        return String(vehicle.category || vehicle.vehicleCategory || vehicle.name || '').trim();
    }

    function fetchDbAddons(criteria, vehicle, billedDays, rentalBase) {
        const norm = window.RAC_FLOW?.normalizeSelectedVehicleForExtras
            ? window.RAC_FLOW.normalizeSelectedVehicleForExtras(vehicle, criteria, billedDays)
            : null;
        const pickupDt = (criteria.pickupDate || '') + 'T' + (criteria.pickupTime || '10:00') + ':00';
        const returnDt = (criteria.returnDate || '') + 'T' + (criteria.returnTime || '10:00') + ':00';
        const params = new URLSearchParams({
            vehicle_code: norm?.vehicleCode || resolveVehicleCode(vehicle),
            vehicle_name: norm?.vehicleName || resolveVehicleCategory(vehicle),
            pickup_location: criteria.locationCode || '',
            return_location: criteria.returnLocationCode || criteria.locationCode || '',
            rental_days: String(billedDays),
            rental_base: String(rentalBase || 0),
            pickup_datetime: pickupDt,
            return_datetime: returnDt,
        });
        return fetch('/api/rac-addons.php?' + params.toString())
            .then(function (r) {
                if (!r.ok) {
                    console.warn('[rac-extras] rac-addons HTTP', r.status);
                    return null;
                }
                return r.json();
            })
            .then(function (data) {
                if (!data || (!data.success && !data.ok)) {
                    console.warn('[rac-extras] rac-addons respuesta inválida', data);
                    return null;
                }
                const hasProtections = Array.isArray(data.protections) && data.protections.length > 0;
                const hasExtras = Array.isArray(data.extras) && data.extras.length > 0;
                if (!hasProtections && !hasExtras) {
                    console.warn('[rac-extras] rac-addons sin productos para el contexto');
                    return null;
                }
                return data;
            })
            .catch(function (err) {
                console.warn('[rac-extras] rac-addons fetch error', err);
                return null;
            });
    }

    function mergeDbAddonsIntoVehicle(vehicle, dbData) {
        if (!dbData) return vehicle;
        const v = Object.assign({}, vehicle);
        const protections = Array.isArray(dbData.protections) ? dbData.protections : [];
        const extras = Array.isArray(dbData.extras) ? dbData.extras : [];

        if (protections.length) {
            v._dbProtections = protections;
            const paid = protections.filter(function (p) {
                return protectionCode(p) !== 'NONE';
            });
            v.pricing = Object.assign({}, v.pricing || {}, { coveragePackages: paid });
            v.availableCoverages = paid;
            v._addonsSource = 'db';
        }
        if (extras.length) {
            v.availableEquipment = extras;
            v._dbExtras = extras;
            v._addonsSource = 'db';
        }
        return v;
    }

    function buildVehicleContext(vehicle, criteria, billedDays) {
        const ctx = window.RAC_FLOW?.normalizeSelectedVehicleForExtras
            ? window.RAC_FLOW.normalizeSelectedVehicleForExtras(vehicle, criteria, billedDays)
            : {
                vehicleCode: resolveVehicleCode(vehicle),
                vehicleName: resolveVehicleCategory(vehicle),
                dailyRate: 0,
                totalRate: 0,
                rentalDays: billedDays,
                currency: 'USD',
                rateSource: String(vehicle?.pricing?.rateSource || ''),
                quoteToken: String(vehicle?.pricing?.barsQuoteToken || ''),
            };
        return Object.assign({}, ctx, {
            addonsSource: vehicle?._addonsSource || '',
            vehicle: vehicle || {},
        });
    }

    function resolveUiRentalBase(vehicle, vehicleContext, rateType, billedDays) {
        let base = window.RAC_FLOW.resolveRentalBase(vehicle, rateType, billedDays);
        if (base > 0) return base;
        if (vehicleContext.totalRate > 0) return vehicleContext.totalRate;
        if (vehicleContext.dailyRate > 0) {
            return Math.round(vehicleContext.dailyRate * billedDays * 100) / 100;
        }
        return 0;
    }

    function initExtrasPage(vehicle, criteria) {
        const calendarDays = window.RAC_FLOW.calcDays(criteria.pickupDate, criteria.returnDate);
        const billedDays = window.RAC_FLOW.vehicleBilledDays(vehicle, calendarDays);
        if (window.RAC_FLOW.applyNormalizedPricing) {
            vehicle = window.RAC_FLOW.applyNormalizedPricing(vehicle, billedDays);
        }
        const vehicleContext = buildVehicleContext(vehicle, criteria, billedDays);
        const rateType = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';

        document.getElementById('extrasNoVehicle')?.classList.add('d-none');
        document.getElementById('extrasMain')?.classList.remove('d-none');

        const rentalBase = resolveUiRentalBase(vehicle, vehicleContext, rateType, billedDays);
        const mandatoryTotal = window.RAC_FLOW.resolveMandatoryTotal(vehicle, criteria, billedDays);
        const mandatoryLines = window.RAC_FLOW.resolveMandatoryLines(vehicle, criteria, billedDays);
        const saf = window.RAC_FLOW.resolveSafAmount(vehicle);
        const nonSafMandatory = mandatoryLines.filter(function (l) { return l.code !== 'SAF'; });

        const packages = resolveProtectionPackages(vehicle).filter(function (pkg) {
            const code = protectionCode(pkg);
            return code !== '' && code !== 'NONE';
        });
        const equipment = resolveEquipmentList(vehicle);

        const packagesByCode = {};
        packages.forEach(function (pkg, i) {
            const code = protectionCode(pkg) || ('cov_' + i);
            packagesByCode[code] = pkg;
        });

        const cheapest = cheapestProtection(packages, billedDays);
        let selectedProtection = cheapest ? protectionCode(cheapest) : '';

        const saved = window.RAC_FLOW.getExtras();
        if (saved && saved.protection) {
            const savedCode = String(saved.protection).toUpperCase();
            if (savedCode !== 'NONE' && packagesByCode[savedCode]) {
                selectedProtection = savedCode;
            }
        }

        const selectedQty = {};
        if (saved && Array.isArray(saved.items)) {
            saved.items.forEach(function (item) {
                const raw = item && item.code ? String(item.code) : '';
                const code = raw.toUpperCase();
                if (!code || code === 'CONDADIC') return;
                const match = equipment.find(function (eq) {
                    return String(eq.code || '').toUpperCase() === code;
                });
                const key = match ? match.code : raw;
                const maxQ = match ? equipmentMaxQuantity(match) : 1;
                let qty = parseInt(item.quantity, 10);
                if (!Number.isFinite(qty) || qty < 1) qty = 1;
                selectedQty[key] = Math.min(maxQ, qty);
            });
        }
        const driverCharge = findCondadicCharge(vehicle);
        const driverMax = driverMaxQuantity(driverCharge);
        let additionalDrivers = (saved && saved.additionalDrivers) ? parseInt(saved.additionalDrivers, 10) : 0;
        if (!Number.isFinite(additionalDrivers) || additionalDrivers < 0) additionalDrivers = 0;
        additionalDrivers = Math.min(driverMax, additionalDrivers);

        try {
            renderVehicleHeader(vehicle, criteria, billedDays, rateType, rentalBase);
        } catch (err) {
            console.error('[rac-extras] renderVehicleHeader error', err);
        }
        try {
            renderSummarySidebar(vehicle, criteria, billedDays);
        } catch (err) {
            console.error('[rac-extras] renderSummarySidebar error', err);
        }
        try {
            renderProtection(packages, selectedProtection, billedDays, vehicleContext);
        } catch (err) {
            console.error('[rac-extras] renderProtection error', err);
            const wrap = document.getElementById('protectionOptions');
            if (wrap) {
                wrap.innerHTML = '<p class="text-muted small">No hay paquetes de protección disponibles para este vehículo.</p>';
            }
        }
        try {
            renderEquipment(equipment, selectedQty, additionalDrivers, driverCharge, billedDays, driverMax);
        } catch (err) {
            console.error('[rac-extras] renderEquipment error', err);
            const wrap = document.getElementById('equipmentOptions');
            if (wrap) {
                wrap.innerHTML = '<p class="text-muted small">No se pudieron cargar extras opcionales.</p>';
            }
        }

        const state = {
            vehicle, criteria, vehicleContext, rentalBase, saf, mandatoryTotal, nonSafMandatory, billedDays, rateType,
            packagesByCode, selectedProtection, selectedQty, additionalDrivers, equipment, driverCharge, driverMax,
            previewSeq: 0,
            previewTimer: null,
            previewInFlight: false,
            lastAppliedPreviewSeq: 0
        };

        function buildExtrasPayloadForPreview() {
            const items = [];
            Object.keys(state.selectedQty).forEach(function (code) {
                const qty = parseInt(state.selectedQty[code], 10) || 0;
                if (qty < 1) return;
                const eq = state.equipment.find(function (x) { return x.code === code; });
                if (!eq) return;
                items.push({
                    code: eq.code,
                    quantity: Math.min(qty, equipmentMaxQuantity(eq))
                });
            });
            return {
                protection: state.selectedProtection,
                items: items,
                additionalDrivers: state.additionalDrivers,
                mandatoryTotal: state.mandatoryTotal,
                totals: {
                    mandatory: state.mandatoryTotal,
                    saf: state.saf
                },
                rental_days: state.billedDays,
                billed_days: state.billedDays,
                vehicle_name: state.vehicle.category || state.vehicle.name || '',
                vehicle_category: state.vehicle.category || ''
            };
        }

        function applyServerTotals(preview) {
            const totals = preview.totals || {};
            const prot = preview.protection || {};
            state.rentalBase = typeof totals.base === 'number' ? totals.base : state.rentalBase;
            if (preview.vehicle) {
                state.vehicle = preview.vehicle;
            }
            const covName = prot.name || state.coverageName || '—';
            state.coverageName = covName;
            state.totals = {
                base: totals.base != null ? totals.base : state.rentalBase,
                saf: state.saf,
                mandatory: totals.mandatory != null ? totals.mandatory : state.mandatoryTotal,
                mandatoryLines: state.nonSafMandatory,
                coverage: totals.coverage != null ? totals.coverage : 0,
                equipment: totals.equipment != null ? totals.equipment : 0,
                drivers: totals.drivers != null ? totals.drivers : 0,
                extras: totals.extras != null ? totals.extras : 0,
                itbms: totals.itbms != null ? totals.itbms : 0,
                total: totals.total != null ? totals.total : 0,
                currency: totals.currency || 'USD',
                serverPreview: true
            };

            renderMandatoryRows(state.nonSafMandatory);
            document.getElementById('sumBase').textContent = window.RAC_FLOW.fmtMoney(state.totals.base);
            document.getElementById('sumSaf').textContent = window.RAC_FLOW.fmtMoney(state.saf);
            document.getElementById('sumCoverage').textContent = window.RAC_FLOW.fmtMoney(state.totals.coverage);
            const covRow = document.getElementById('sumCoverageRow');
            const protCode = String(state.selectedProtection || '').toUpperCase();
            if (covRow) covRow.classList.toggle('d-none', !protCode || state.totals.coverage <= 0);
            const extrasRow = document.getElementById('sumExtrasRow');
            if (extrasRow) extrasRow.classList.toggle('d-none', state.totals.equipment <= 0);
            document.getElementById('sumExtras').textContent = window.RAC_FLOW.fmtMoney(state.totals.equipment);
            document.getElementById('sumItbms').textContent = window.RAC_FLOW.fmtMoney(state.totals.itbms);
            document.getElementById('sumTotal').textContent = window.RAC_FLOW.fmtMoney(state.totals.total);
            document.getElementById('sumCoverageLabel').textContent = covName;

            const driverRow = document.getElementById('sumDriverRow');
            const driverVal = document.getElementById('sumDriver');
            if (driverRow && driverVal) {
                if (state.totals.drivers > 0) {
                    driverRow.classList.remove('d-none');
                    driverVal.textContent = window.RAC_FLOW.fmtMoney(state.totals.drivers);
                    document.getElementById('sumDriverLabel').textContent =
                        `Conductor adicional (×${state.additionalDrivers})`;
                } else {
                    driverRow.classList.add('d-none');
                }
            }

            const status = document.getElementById('extrasPreviewStatus');
            if (status) {
                status.textContent = preview.refreshed
                    ? 'Tarifa actualizada desde el servidor.'
                    : 'Resumen recalculado.';
            }
        }

        function setPreviewLoading(on) {
            const loader = document.getElementById('extrasRefreshLoader');
            if (loader) loader.classList.toggle('d-none', !on);
            state.previewInFlight = !!on;
            const btn = document.getElementById('btnContinueExtras');
            if (btn && !btn.dataset.continuing) {
                btn.disabled = !!on;
            }
        }

        function scheduleServerPreview() {
            if (!window.RAC_FLOW.previewRateTotals) return;
            if (state.previewTimer) clearTimeout(state.previewTimer);
            state.previewTimer = setTimeout(runServerPreview, 350);
        }

        function runServerPreview() {
            if (!window.RAC_FLOW.previewRateTotals) return Promise.resolve(null);
            const seq = ++state.previewSeq;
            setPreviewLoading(true);
            const extrasPayload = buildExtrasPayloadForPreview();
            return window.RAC_FLOW.previewRateTotals(
                state.criteria,
                state.vehicle,
                extrasPayload,
                state.rateType
            ).then(function (preview) {
                if (seq < state.lastAppliedPreviewSeq) return null;
                state.lastAppliedPreviewSeq = seq;
                applyServerTotals(preview);
                setPreviewLoading(false);
                return preview;
            }).catch(function (err) {
                if (seq !== state.previewSeq) return null;
                setPreviewLoading(false);
                const status = document.getElementById('extrasPreviewStatus');
                if (status) {
                    status.textContent = err.message || 'No se pudo recalcular la tarifa.';
                }
                return null;
            });
        }

        function renderMandatoryRows(charges) {
            const wrap = document.getElementById('sumMandatoryRows');
            if (!wrap) return;
            if (!charges.length) {
                wrap.innerHTML = '';
                return;
            }
            wrap.innerHTML = charges.map(function (c) {
                return '<div class="d-flex justify-content-between"><span class="text-muted">' +
                    c.label + '</span><span>' + window.RAC_FLOW.fmtMoney(c.amount) + '</span></div>';
            }).join('');
        }

        function recalc() {
            const protCode = String(state.selectedProtection || '').toUpperCase();
            const pkg = state.packagesByCode[protCode] || {};
            let coverageAmt = 0;
            let covName = '—';

            if (protCode && protCode !== 'NONE' && Object.keys(pkg).length) {
                coverageAmt = parseFloat(pkg.amountTotal ?? 0) || 0;
                if (!coverageAmt && pkg.pricePerDay) {
                    coverageAmt = parseFloat(pkg.pricePerDay) * state.billedDays;
                }
                covName = protectionTitle(pkg, protCode);
            }

            const driverAmt = calcDriverTotal(state.driverCharge, state.additionalDrivers, state.billedDays);

            let equipAmt = 0;
            state.equipment.forEach(eq => {
                const code = (eq.code || '').toUpperCase();
                if (code === 'CONDADIC') return;
                const qty = parseInt(state.selectedQty[eq.code], 10) || 0;
                if (qty < 1) return;
                const capped = Math.min(qty, equipmentMaxQuantity(eq));
                if (eq.unitName === 'day' || eq.pricePerDay) {
                    equipAmt += (parseFloat(eq.pricePerDay ?? 0) || 0) * state.billedDays * capped;
                } else {
                    equipAmt += (parseFloat(eq.amountTotal ?? 0) || 0) * capped;
                }
            });

            const extrasAmt = equipAmt + driverAmt;
            const subtotal = state.rentalBase + state.mandatoryTotal + coverageAmt + extrasAmt;
            const itbms = Math.round(subtotal * 0.07 * 100) / 100;
            const total = Math.round((subtotal + itbms) * 100) / 100;

            renderMandatoryRows(state.nonSafMandatory);
            document.getElementById('sumBase').textContent = window.RAC_FLOW.fmtMoney(state.rentalBase);
            document.getElementById('sumSaf').textContent = window.RAC_FLOW.fmtMoney(state.saf);
            document.getElementById('sumCoverage').textContent = window.RAC_FLOW.fmtMoney(coverageAmt);
            const covRow = document.getElementById('sumCoverageRow');
            if (covRow) covRow.classList.toggle('d-none', !protCode || coverageAmt <= 0);

            const extrasRow = document.getElementById('sumExtrasRow');
            if (extrasRow) extrasRow.classList.toggle('d-none', equipAmt <= 0);
            document.getElementById('sumExtras').textContent = window.RAC_FLOW.fmtMoney(equipAmt);
            document.getElementById('sumItbms').textContent = window.RAC_FLOW.fmtMoney(itbms);
            document.getElementById('sumTotal').textContent = window.RAC_FLOW.fmtMoney(total);
            document.getElementById('sumCoverageLabel').textContent = covName;

            const driverRow = document.getElementById('sumDriverRow');
            const driverVal = document.getElementById('sumDriver');
            if (driverRow && driverVal) {
                if (state.additionalDrivers > 0 && driverAmt > 0) {
                    driverRow.classList.remove('d-none');
                    driverVal.textContent = window.RAC_FLOW.fmtMoney(driverAmt);
                    document.getElementById('sumDriverLabel').textContent =
                        `Conductor adicional (×${state.additionalDrivers})`;
                } else {
                    driverRow.classList.add('d-none');
                }
            }

            state.totals = {
                base: state.rentalBase,
                saf: state.saf,
                mandatory: state.mandatoryTotal,
                mandatoryLines: state.nonSafMandatory,
                coverage: coverageAmt,
                equipment: equipAmt,
                drivers: driverAmt,
                extras: extrasAmt,
                itbms,
                total
            };
            state.coverageName = covName;
            state.coverageDeductible = protCode !== 'NONE' && pkg.deductible != null
                ? parseFloat(pkg.deductible) : null;
            scheduleServerPreview();
        }

        document.getElementById('extrasMain').addEventListener('change', function (e) {
            if (e.target.name === 'protection_code') {
                state.selectedProtection = e.target.value;
                document.querySelectorAll('#protectionOptions label').forEach(l => l.classList.remove('border-danger'));
                e.target.closest('label')?.classList.add('border-danger');
                recalc();
            }
            if (e.target.classList.contains('equip-check')) {
                const code = e.target.value;
                if (e.target.checked) state.selectedQty[code] = 1;
                else delete state.selectedQty[code];
                recalc();
            }
        });

        document.getElementById('extrasMain').addEventListener('click', function (e) {
            const driverBtn = e.target.closest('[data-driver-delta]');
            if (driverBtn) {
                const delta = parseInt(driverBtn.getAttribute('data-driver-delta'), 10);
                const maxD = state.driverMax || driverMaxQuantity(state.driverCharge);
                state.additionalDrivers = Math.max(0, Math.min(maxD, state.additionalDrivers + delta));
                const countEl = document.getElementById('driverCount');
                if (countEl) countEl.textContent = state.additionalDrivers;
                recalc();
                return;
            }
            const equipBtn = e.target.closest('[data-equip-delta]');
            if (!equipBtn) return;
            const code = equipBtn.getAttribute('data-equip-code') || '';
            const delta = parseInt(equipBtn.getAttribute('data-equip-delta'), 10);
            const eq = state.equipment.find(function (x) { return x.code === code; });
            if (!eq) return;
            const maxQ = equipmentMaxQuantity(eq);
            const current = parseInt(state.selectedQty[code], 10) || 0;
            const next = Math.max(0, Math.min(maxQ, current + delta));
            if (next < 1) delete state.selectedQty[code];
            else state.selectedQty[code] = next;
            const countEl = document.getElementById('equipCount_' + code);
            if (countEl) countEl.textContent = String(next);
            recalc();
        });

        document.getElementById('btnContinueExtras')?.addEventListener('click', function () {
            const btn = document.getElementById('btnContinueExtras');
            const available = Object.keys(state.packagesByCode || {});
            let prot = String(state.selectedProtection || '').toUpperCase();
            if (available.length) {
                if (!prot || prot === 'NONE' || !state.packagesByCode[prot]) {
                    const cheapest = cheapestProtection(
                        available.map(function (c) { return state.packagesByCode[c]; }),
                        state.billedDays
                    );
                    prot = cheapest ? protectionCode(cheapest) : '';
                    state.selectedProtection = prot;
                }
                if (!prot) {
                    if (btn) {
                        btn.disabled = false;
                        btn.dataset.continuing = '';
                        btn.textContent = btn.dataset.originalText || 'Continuar';
                    }
                    alert('Seleccione una protección para continuar.');
                    return;
                }
            }

            if (btn) {
                if (btn.disabled && btn.dataset.continuing === '1') return;
                btn.disabled = true;
                btn.dataset.continuing = '1';
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Recalculando tarifa…';
            }

            const forceExpired = window.RAC_FLOW.isBarsQuoteExpired
                ? window.RAC_FLOW.isBarsQuoteExpired(state.vehicle)
                : false;

            createQuoteForVehicle(state.criteria, state.vehicle, state.rateType)
                .then(function (quotedVehicle) {
                    if (forceExpired || (window.RAC_FLOW.isBarsCacheVehicle
                        && window.RAC_FLOW.isBarsCacheVehicle(quotedVehicle)
                        && window.RAC_FLOW.isBarsQuoteExpired
                        && window.RAC_FLOW.isBarsQuoteExpired(quotedVehicle))) {
                        return window.RAC_FLOW.ensureBarsQuote(state.criteria, quotedVehicle, state.rateType, { force: true });
                    }
                    return window.RAC_FLOW.ensureBarsQuote(state.criteria, quotedVehicle, state.rateType);
                })
                .then(function (quotedVehicle) {
                    state.vehicle = quotedVehicle;
                    sessionStorage.setItem('selectedVehicle', JSON.stringify(quotedVehicle));
                    if (!window.RAC_FLOW.previewRateTotals) {
                        return { vehicle: quotedVehicle, totals: state.totals, protection: { name: state.coverageName } };
                    }
                    return window.RAC_FLOW.previewRateTotals(
                        state.criteria,
                        quotedVehicle,
                        buildExtrasPayloadForPreview(),
                        state.rateType
                    );
                })
                .then(function (preview) {
                    if (preview && preview.totals) {
                        applyServerTotals(preview);
                    }
                    if (preview && preview.vehicle) {
                        state.vehicle = preview.vehicle;
                    }

                    const items = [];
                    Object.keys(state.selectedQty).forEach(function (code) {
                        const qty = parseInt(state.selectedQty[code], 10) || 0;
                        if (qty < 1) return;
                        const eq = state.equipment.find(function (x) { return x.code === code; });
                        if (!eq) return;
                        const capped = Math.min(qty, equipmentMaxQuantity(eq));
                        items.push({
                            code: eq.code,
                            name: eq.name || '',
                            description: eq.description || eq.name || eq.code,
                            quantity: capped
                        });
                    });
                    if (state.additionalDrivers > 0) {
                        items.push({
                            code: 'CONDADIC',
                            name: (state.driverCharge && state.driverCharge.name) || 'Conductor Adicional',
                            description: (state.driverCharge && state.driverCharge.description)
                                || (state.driverCharge && state.driverCharge.name)
                                || 'Conductor Adicional',
                            quantity: state.additionalDrivers
                        });
                    }

                    const extrasSelection = {
                        protection: state.selectedProtection,
                        items,
                        additionalDrivers: state.additionalDrivers,
                        mandatoryCharges: state.nonSafMandatory,
                        mandatoryTotal: state.mandatoryTotal,
                        totals: state.totals,
                        pricingSnapshot: (state.vehicle && state.vehicle.pricing) || {},
                        coverage_name: state.coverageName,
                        coverage_deductible: state.coverageDeductible,
                        rate_type: state.rateType
                    };

                    sessionStorage.setItem('extrasSelection', JSON.stringify(extrasSelection));
                    window.location.href = '/reservar.php';
                })
                .catch(function (err) {
                    if (btn) {
                        btn.disabled = false;
                        btn.dataset.continuing = '';
                        btn.textContent = btn.dataset.originalText || 'Continuar';
                    }
                    const status = document.getElementById('extrasPreviewStatus');
                    if (status) {
                        status.textContent = err.message || 'No se pudo recalcular la tarifa.';
                    }
                    alert(err.message || 'No se pudo bloquear la tarifa. Vuelva a seleccionar el vehículo.');
                });
        });

        try {
            recalc();
        } catch (err) {
            console.error('[rac-extras] recalc error', err);
        }
        try {
            renderAlternatives(criteria, vehicle, billedDays, rateType);
        } catch (err) {
            console.error('[rac-extras] renderAlternatives error', err);
        }
    }

    function renderVehicleHeader(vehicle, criteria, billedDays, rateType, rentalBase) {
        const el = document.getElementById('extrasVehicleHeader');
        if (!el) return;
        const img = window.RAC_FLOW.resolveImage(vehicle.image);
        el.innerHTML = `
            <div class="d-flex flex-wrap align-items-center gap-3">
                ${img ? `<img src="${img}" alt="" style="max-height:70px;object-fit:contain">` : ''}
                <div class="flex-grow-1">
                    <span class="badge bg-danger-subtle text-danger text-uppercase">${vehicle.category || ''}</span>
                    <h4 class="fw-bold text-navy mb-1">${vehicle.name || 'Vehículo'}</h4>
                    <small class="text-muted">${billedDays} día${billedDays !== 1 ? 's' : ''} · ${window.RAC_FLOW.branchLabel(criteria.locationCode)}</small>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block" data-rate-type="${rateType === 'counter' ? 'counter' : 'web'}" data-prepayment-available="false">${rateType === 'counter' ? 'Tarifa mostrador' : 'WebExclusivo'}</small>
                    <span class="fs-4 fw-bold text-navy">${window.RAC_FLOW.fmtMoney(rentalBase)}</span>
                    <small class="text-muted d-block">solo tarifa</small>
                </div>
            </div>`;
    }

    function renderSummarySidebar(vehicle, criteria, billedDays) {
        const box = document.getElementById('extrasBookingSummary');
        if (!box) return;
        box.innerHTML = `
            <h5 class="fw-bold text-navy mb-3"><i class="bi bi-calendar-check text-danger me-2"></i>Tu reserva</h5>
            <div class="small mb-2"><span class="text-danger fw-semibold text-uppercase">Recogida</span><br>
                ${window.RAC_FLOW.branchLabel(criteria.locationCode)}<br>
                <span class="text-muted">${window.RAC_FLOW.formatDateDisplay(criteria.pickupDate)} ${window.RAC_FLOW.formatTimeDisplay(criteria.pickupTime)}</span></div>
            <div class="small mb-3"><span class="text-danger fw-semibold text-uppercase">Devolución</span><br>
                ${window.RAC_FLOW.branchLabel(criteria.returnLocationCode || criteria.locationCode)}<br>
                <span class="text-muted">${window.RAC_FLOW.formatDateDisplay(criteria.returnDate)} ${window.RAC_FLOW.formatTimeDisplay(criteria.returnTime)}</span></div>
            <div class="bg-navy text-white rounded-3 px-3 py-2 d-flex justify-content-between small">
                <span>Días de renta</span><strong>${billedDays} día${billedDays !== 1 ? 's' : ''}</strong>
            </div>`;
    }

    function renderProtection(packages, selected, billedDays, vehicleContext) {
        const ctx = vehicleContext && typeof vehicleContext === 'object' ? vehicleContext : {};
        const wrap = document.getElementById('protectionOptions');
        if (!wrap) return;

        const safePackages = (Array.isArray(packages) ? packages : []).filter(function (pkg) {
            const code = protectionCode(pkg);
            return code !== '' && code !== 'NONE';
        });
        const selectedCode = String(selected || '').toUpperCase();
        const days = Math.max(parseInt(billedDays, 10) || 1, 1);

        if (!safePackages.length) {
            wrap.innerHTML = '<p class="text-muted small mb-0">No hay protecciones activas configuradas en el admin para este vehículo.</p>';
            return;
        }

        wrap.innerHTML = safePackages.map(function (pkg, i) {
            const code = protectionCode(pkg) || ('cov_' + i);
            const checked = code === selectedCode ? 'checked' : '';
            const border = checked ? 'border-danger border-2' : '';
            const amt = protectionAmount(pkg, days);
            const perDay = parseFloat(pkg.pricePerDay || 0) || (days > 0 ? amt / days : 0);
            const title = protectionTitle(pkg, code);
            const desc = (pkg.description && String(pkg.description).trim() !== '' && String(pkg.description) !== title)
                ? pkg.description
                : (pkg.description || title);
            return `
            <label class="border rounded-3 p-3 d-flex gap-3 align-items-center cursor-pointer ${border}">
                <input type="radio" name="protection_code" class="form-check-input flex-shrink-0" value="${code}" ${checked} required>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-navy">${title}</div>
                    <small class="text-muted d-block">${desc}</small>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold text-danger">$${perDay.toFixed(2)}/día</div>
                    <small class="text-muted">Total: $${amt.toFixed(2)}</small>
                </div>
            </label>`;
        }).join('');
    }

    function renderEquipment(equipment, selectedQty, additionalDrivers, driverCharge, billedDays, driverMax) {
        const wrap = document.getElementById('equipmentOptions');
        if (!wrap) return;

        const others = equipment.filter(e => (e.code || '').toUpperCase() !== 'CONDADIC');
        const qtyMap = selectedQty && typeof selectedQty === 'object' ? selectedQty : {};
        const maxDrivers = Math.max(1, parseInt(driverMax, 10) || driverMaxQuantity(driverCharge));

        let html = `
            <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                <div><i class="bi bi-person-plus text-danger me-2"></i><strong>Conductor Adicional</strong>
                    <small class="text-muted d-block">${driverPriceLabel(driverCharge, billedDays)}</small>
                    ${driverCharge && driverCharge.description
                        ? `<small class="text-muted d-block">${driverCharge.description}</small>`
                        : ''}</div>
                <div class="d-flex align-items-center gap-2" role="group" aria-label="Cantidad de conductores adicionales, máximo ${maxDrivers}">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-driver-delta="-1" aria-label="Quitar conductor adicional">−</button>
                    <span id="driverCount" class="fw-bold px-2" aria-live="polite">${additionalDrivers}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-driver-delta="1" aria-label="Agregar conductor adicional">+</button>
                </div>
            </div>`;

        others.forEach(eq => {
            const code = eq.code || '';
            const label = equipmentTitle(eq, code);
            const maxQ = equipmentMaxQuantity(eq);
            const qty = parseInt(qtyMap[code], 10) || 0;
            const desc = (eq.description && String(eq.description).trim() !== '' && String(eq.description) !== label)
                ? eq.description
                : '';
            let priceLabel = '';
            const amountTotal = parseFloat(eq.amountTotal ?? 0);
            if (eq.unitName === 'day' || eq.pricePerDay) {
                const total = (parseFloat(eq.pricePerDay || 0)) * billedDays;
                priceLabel = `$${parseFloat(eq.pricePerDay || 0).toFixed(2)}/día · Total $${total.toFixed(2)}`;
            } else {
                priceLabel = amountTotal > 0 ? `$${amountTotal.toFixed(2)}` : 'Sin cargo';
            }
            if (maxQ > 1) {
                html += `
            <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                <div class="flex-grow-1 pe-3">
                    <strong>${label}</strong>
                    <small class="text-muted d-block">${priceLabel}</small>
                    ${desc ? `<small class="text-muted d-block">${desc}</small>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2" role="group" aria-label="Cantidad de ${label}">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-equip-delta="-1" data-equip-code="${code}" aria-label="Quitar ${label}">−</button>
                    <span id="equipCount_${code}" class="fw-bold px-2" aria-live="polite">${qty}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-equip-delta="1" data-equip-code="${code}" aria-label="Agregar ${label}">+</button>
                </div>
            </div>`;
            } else {
                const checked = qty > 0 ? 'checked' : '';
                html += `
            <label class="border rounded-3 p-3 d-flex gap-3 align-items-center cursor-pointer">
                <input type="checkbox" class="form-check-input equip-check" value="${code}" ${checked} aria-label="${label}">
                <div class="flex-grow-1">
                    <strong>${label}</strong>
                    <small class="text-muted d-block">${priceLabel}</small>
                    ${desc ? `<small class="text-muted d-block">${desc}</small>` : ''}
                </div>
            </label>`;
            }
        });

        wrap.innerHTML = html;
    }

    function renderAlternatives(criteria, currentVehicle, billedDays, rateType) {
        const wrap = document.getElementById('alternativesRow');
        if (!wrap) return;
        let list = [];
        try {
            list = JSON.parse(sessionStorage.getItem('searchResultsVehicles') || '[]');
        } catch { list = []; }

        const alts = list.filter(v => v.sippCode !== currentVehicle.sippCode).slice(0, 2);
        if (!alts.length) {
            document.getElementById('alternativesSection')?.classList.add('d-none');
            return;
        }

        const currentTotal = window.RAC_FLOW.resolveRentalBase(currentVehicle, rateType, billedDays);

        wrap.innerHTML = alts.map(v => {
            const total = window.RAC_FLOW.resolveRentalBase(v, rateType, billedDays);
            const altDays = window.RAC_FLOW.vehicleBilledDays(v, billedDays);
            const diff = (total - currentTotal) / Math.max(altDays, 1);
            const diffLabel = Math.abs(diff) < 0.05 ? 'Mismo precio' : (diff > 0 ? `+$${diff.toFixed(2)}/día` : `$${Math.abs(diff).toFixed(2)} menos/día`);
            const img = window.RAC_FLOW.resolveImage(v.image);
            return `
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        ${img ? `<img src="${img}" class="img-fluid mb-2" style="max-height:80px;object-fit:contain">` : ''}
                        <h6 class="fw-bold text-navy">${v.name}</h6>
                        <small class="text-muted">${v.category} · ${v.transmission || 'Automática'}</small>
                        <div class="mt-2"><strong>${window.RAC_FLOW.fmtMoney(total)}</strong> tarifa
                            <span class="badge bg-danger-subtle text-danger ms-1">${diffLabel}</span></div>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill mt-3 w-100 rac-alt-btn" data-sipp="${v.sippCode}">Cambiar a este</button>
                    </div>
                </div>
            </div>`;
        }).join('');

        wrap.querySelectorAll('.rac-alt-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const sipp = btn.getAttribute('data-sipp');
                const v = list.find(x => x.sippCode === sipp);
                if (!v) return;
                const rate = sessionStorage.getItem('selectedRateType') || 'web';
                btn.disabled = true;
                btn.textContent = 'Actualizando…';
                createQuoteForVehicle(criteria, Object.assign({}, v, {
                    _selectedRateType: rate,
                    vendorRateId: window.RAC_FLOW.resolveVendorRateId(v, rate)
                }), rate).then(function (quoted) {
                    sessionStorage.setItem('selectedVehicle', JSON.stringify(quoted));
                    sessionStorage.removeItem('extrasSelection');
                    window.location.reload();
                }).catch(function (err) {
                    btn.disabled = false;
                    btn.textContent = 'Cambiar a este';
                    alert(err.message || 'No se pudo bloquear tarifa para este vehículo.');
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const ctx = window.RAC_FLOW.requireVehicle('/rent-a-car.php');
        if (!ctx) return;

        const loader = document.getElementById('extrasRefreshLoader');
        if (loader) loader.classList.remove('d-none');

        function finishInit(vehicle) {
            if (loader) loader.classList.add('d-none');
            try {
                initExtrasPage(vehicle, ctx.criteria);
            } catch (err) {
                console.error('[rac-extras] initExtrasPage error', err);
                if (loader) loader.classList.add('d-none');
                alert('No se pudo cargar extras. Vuelva a seleccionar el vehículo.');
            }
        }

        let workingVehicle = ctx.vehicle;

        refreshVehicleFromApi(ctx.criteria, workingVehicle)
            .then(function (vehicle) {
                workingVehicle = vehicle;
                const calendarDays = window.RAC_FLOW.calcDays(ctx.criteria.pickupDate, ctx.criteria.returnDate);
                const billedDays = window.RAC_FLOW.vehicleBilledDays(vehicle, calendarDays);
                const rateType = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';
                const rentalBase = window.RAC_FLOW.resolveRentalBase(vehicle, rateType, billedDays);
                return fetchDbAddons(ctx.criteria, vehicle, billedDays, rentalBase)
                    .then(function (dbData) {
                        return mergeDbAddonsIntoVehicle(vehicle, dbData);
                    });
            })
            .then(finishInit)
            .catch(function (err) {
                console.warn('[rac-extras] refresh/addons falló, usando vehículo en sesión', err);
                finishInit(workingVehicle);
            });
    });
})();
