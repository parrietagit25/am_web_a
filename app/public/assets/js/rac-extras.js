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
    const PROTECTION_ORDER = ['BASIC', 'STANDARD', 'PREMIUM'];
    const PROTECTION_LABELS = {
        BASIC: 'Protección Básica',
        STANDARD: 'Protección Estándar',
        PREMIUM: 'Protección Premium',
        NONE: 'Sin protección adicional'
    };

    function protectionCode(pkg) {
        return String(pkg.code || pkg.coverageType || '').toUpperCase().trim();
    }

    function filterProtectionPackages(list) {
        if (!Array.isArray(list)) return [];
        const byCode = {};
        list.forEach(function (pkg) {
            const code = protectionCode(pkg);
            if (PROTECTION_ORDER.indexOf(code) === -1) return;
            if (!byCode[code]) byCode[code] = pkg;
        });
        return PROTECTION_ORDER.map(function (code) { return byCode[code]; }).filter(Boolean);
    }

    function resolveProtectionPackages(vehicle) {
        const pricing = vehicle.pricing || {};
        const fromPricing = filterProtectionPackages(pricing.coveragePackages);
        if (fromPricing.length) return fromPricing;
        const fromCov = filterProtectionPackages(vehicle.availableCoverages);
        if (fromCov.length) return fromCov;
        return (vehicle.availableCoverages || []).filter(function (c) {
            return String(c.group || '').toLowerCase() === 'paquete';
        });
    }

    function resolveEquipmentList(vehicle) {
        const fromEquip = vehicle.availableEquipment || [];
        const fromCov = (vehicle.availableCoverages || []).filter(function (c) {
            const code = protectionCode(c);
            const group = String(c.group || '').toLowerCase();
            if (PROTECTION_ORDER.indexOf(code) !== -1) return false;
            if (group === 'paquete') return false;
            return true;
        });
        const seen = new Set();
        const merged = [];
        fromEquip.concat(fromCov).forEach(function (e) {
            const code = (e.code || '').toUpperCase();
            if (!code || seen.has(code)) return;
            seen.add(code);
            merged.push(e);
        });
        return merged;
    }

    function findCondadicCharge(vehicle) {
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
        if (!window.RAC_FLOW?.fetchAvailability || !vehicle?.sippCode) {
            return Promise.resolve(vehicle);
        }
        return window.RAC_FLOW.fetchAvailability(criteria)
            .then(function (data) {
                sessionStorage.setItem('searchResults', JSON.stringify(data));
                const fresh = window.RAC_FLOW.findVehicleInResults(data, vehicle.sippCode);
                if (!fresh) return vehicle;
                const rate = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';
                const enriched = Object.assign({}, fresh, {
                    _selectedRateType: rate,
                    vendorRateId: window.RAC_FLOW.resolveVendorRateId(fresh, rate)
                });
                sessionStorage.setItem('selectedVehicle', JSON.stringify(enriched));
                return enriched;
            })
            .catch(function () { return vehicle; });
    }

    function initExtrasPage(vehicle, criteria) {
        const calendarDays = window.RAC_FLOW.calcDays(criteria.pickupDate, criteria.returnDate);
        const billedDays = window.RAC_FLOW.vehicleBilledDays(vehicle, calendarDays);
        const rateType = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';

        document.getElementById('extrasNoVehicle')?.classList.add('d-none');
        document.getElementById('extrasMain')?.classList.remove('d-none');

        const rentalBase = window.RAC_FLOW.resolveRentalBase(vehicle, rateType, billedDays);
        const mandatoryTotal = window.RAC_FLOW.resolveMandatoryTotal(vehicle, criteria, billedDays);
        const mandatoryLines = window.RAC_FLOW.resolveMandatoryLines(vehicle, criteria, billedDays);
        const saf = window.RAC_FLOW.resolveSafAmount(vehicle);
        const nonSafMandatory = mandatoryLines.filter(function (l) { return l.code !== 'SAF'; });

        const packages = resolveProtectionPackages(vehicle);
        const equipment = resolveEquipmentList(vehicle);

        const packagesByCode = { NONE: { code: 'NONE', name: 'Sin protección adicional', amountTotal: 0 } };
        packages.forEach(function (pkg, i) {
            const code = protectionCode(pkg) || ('cov_' + i);
            packagesByCode[code] = pkg;
        });

        const defaultPkg = packages.find(function (p) { return p.isDefault; })
            || packages.find(function (p) { return protectionCode(p) === 'BASIC'; })
            || packages[0];
        let selectedProtection = defaultPkg ? protectionCode(defaultPkg) : 'NONE';

        const saved = window.RAC_FLOW.getExtras();
        if (saved && saved.protection) {
            const savedCode = String(saved.protection).toUpperCase();
            if (packagesByCode[savedCode]) selectedProtection = savedCode;
        }

        const selectedItems = new Set((saved && saved.items) ? saved.items.map(i => i.code) : []);
        let additionalDrivers = (saved && saved.additionalDrivers) ? parseInt(saved.additionalDrivers, 10) : 0;
        const driverCharge = findCondadicCharge(vehicle);

        renderVehicleHeader(vehicle, criteria, billedDays, rateType, rentalBase);
        renderSummarySidebar(vehicle, criteria, billedDays);
        renderProtection(packages, selectedProtection, billedDays);
        renderEquipment(equipment, selectedItems, additionalDrivers, driverCharge, billedDays);

        const state = {
            vehicle, criteria, rentalBase, saf, mandatoryTotal, nonSafMandatory, billedDays, rateType,
            packagesByCode, selectedProtection, selectedItems, additionalDrivers, equipment, driverCharge
        };

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
            const protCode = String(state.selectedProtection || 'NONE').toUpperCase();
            const pkg = state.packagesByCode[protCode] || {};
            let coverageAmt = 0;
            let covName = PROTECTION_LABELS.NONE;

            if (protCode !== 'NONE') {
                coverageAmt = parseFloat(pkg.amountTotal ?? 0) || 0;
                if (!coverageAmt && pkg.pricePerDay) {
                    coverageAmt = parseFloat(pkg.pricePerDay) * state.billedDays;
                }
                covName = PROTECTION_LABELS[protCode] || pkg.name || pkg.description || protCode;
            }

            const driverAmt = calcDriverTotal(state.driverCharge, state.additionalDrivers, state.billedDays);

            let equipAmt = 0;
            state.equipment.forEach(eq => {
                const code = (eq.code || '').toUpperCase();
                if (code === 'CONDADIC') return;
                if (!state.selectedItems.has(eq.code)) return;
                if (eq.unitName === 'day' || eq.pricePerDay) {
                    equipAmt += (parseFloat(eq.pricePerDay ?? 0) || 0) * state.billedDays;
                } else {
                    equipAmt += parseFloat(eq.amountTotal ?? 0) || 0;
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
            if (covRow) covRow.classList.toggle('d-none', protCode === 'NONE' && coverageAmt <= 0);

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
                if (e.target.checked) state.selectedItems.add(code);
                else state.selectedItems.delete(code);
                recalc();
            }
        });

        document.getElementById('extrasMain').addEventListener('click', function (e) {
            const btn = e.target.closest('[data-driver-delta]');
            if (!btn) return;
            const delta = parseInt(btn.getAttribute('data-driver-delta'), 10);
            state.additionalDrivers = Math.max(0, Math.min(3, state.additionalDrivers + delta));
            document.getElementById('driverCount').textContent = state.additionalDrivers;
            recalc();
        });

        document.getElementById('btnContinueExtras')?.addEventListener('click', function () {
            const btn = document.getElementById('btnContinueExtras');
            if (btn) btn.disabled = true;

            refreshVehicleFromApi(state.criteria, state.vehicle).then(function (freshVehicle) {
                state.vehicle = freshVehicle;
                state.rentalBase = window.RAC_FLOW.resolveRentalBase(
                    freshVehicle, state.rateType, state.billedDays
                );
                state.mandatoryTotal = window.RAC_FLOW.resolveMandatoryTotal(
                    freshVehicle, state.criteria, state.billedDays
                );
                state.saf = window.RAC_FLOW.resolveSafAmount(freshVehicle);
                state.nonSafMandatory = window.RAC_FLOW.resolveMandatoryLines(
                    freshVehicle, state.criteria, state.billedDays
                ).filter(function (l) { return l.code !== 'SAF'; });
                recalc();

                const items = [];
                state.selectedItems.forEach(code => {
                    const eq = state.equipment.find(x => x.code === code);
                    if (eq) items.push({ code: eq.code, description: eq.description || eq.code });
                });
                if (state.additionalDrivers > 0) {
                    items.push({
                        code: 'CONDADIC',
                        description: 'Conductor Adicional',
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
                    pricingSnapshot: freshVehicle.pricing || {},
                    coverage_name: state.coverageName,
                    coverage_deductible: state.coverageDeductible,
                    rate_type: state.rateType
                };

                sessionStorage.setItem('extrasSelection', JSON.stringify(extrasSelection));
                window.location.href = '/reservar.php';
            });
        });

        recalc();
        renderAlternatives(criteria, vehicle, billedDays, rateType);
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
                    <small class="text-muted d-block">${rateType === 'counter' ? 'Tarifa mostrador' : 'WebExclusivo'}</small>
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

    function renderProtection(packages, selected, billedDays) {
        const wrap = document.getElementById('protectionOptions');
        if (!wrap) return;

        let html = `
            <label class="border rounded-3 p-3 d-flex gap-3 align-items-center cursor-pointer ${selected === 'NONE' ? 'border-danger border-2' : ''}">
                <input type="radio" name="protection_code" class="form-check-input flex-shrink-0" value="NONE" ${selected === 'NONE' ? 'checked' : ''}>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-navy">Sin protección adicional</div>
                    <small class="text-muted d-block">Continúa bajo su propio riesgo. La cobertura puede adquirirse en mostrador.</small>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold text-navy">$0.00</div>
                </div>
            </label>`;

        if (!packages.length) {
            wrap.innerHTML = html + '<p class="text-muted small mt-2">No hay paquetes de protección en línea para este vehículo.</p>';
            return;
        }

        html += packages.map(function (pkg, i) {
            const code = protectionCode(pkg) || ('cov_' + i);
            const checked = code === String(selected || '').toUpperCase() ? 'checked' : '';
            const border = checked ? 'border-danger border-2' : '';
            const amt = parseFloat(pkg.amountTotal || 0) || (parseFloat(pkg.pricePerDay || 0) * billedDays);
            const perDay = parseFloat(pkg.pricePerDay || 0) || (billedDays > 0 ? amt / billedDays : 0);
            const title = PROTECTION_LABELS[code] || pkg.name || pkg.description || code;
            const desc = pkg.description || title;
            let badge = '';
            if (code === 'STANDARD') {
                badge = '<span class="badge bg-success text-uppercase ms-2" style="font-size:0.65rem;">Más popular</span>';
            } else if (code === 'PREMIUM') {
                badge = '<span class="badge bg-navy text-white text-uppercase ms-2" style="font-size:0.65rem;background:#081026;">Sin preocupaciones</span>';
            }
            return `
            <label class="border rounded-3 p-3 d-flex gap-3 align-items-center cursor-pointer ${border}">
                <input type="radio" name="protection_code" class="form-check-input flex-shrink-0" value="${code}" ${checked}>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-navy">${title}${badge}</div>
                    <small class="text-muted d-block">${desc}</small>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold text-danger">$${perDay.toFixed(2)}/día</div>
                    <small class="text-muted">Total: $${amt.toFixed(2)}</small>
                </div>
            </label>`;
        }).join('');

        wrap.innerHTML = html;
    }

    function renderEquipment(equipment, selectedItems, additionalDrivers, driverCharge, billedDays) {
        const wrap = document.getElementById('equipmentOptions');
        if (!wrap) return;

        const others = equipment.filter(e => (e.code || '').toUpperCase() !== 'CONDADIC');

        let html = `
            <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                <div><i class="bi bi-person-plus text-danger me-2"></i><strong>Conductor Adicional</strong>
                    <small class="text-muted d-block">${driverPriceLabel(driverCharge, billedDays)}</small></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-driver-delta="-1">−</button>
                    <span id="driverCount" class="fw-bold px-2">${additionalDrivers}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-driver-delta="1">+</button>
                </div>
            </div>`;

        others.forEach(eq => {
            const code = eq.code || '';
            const label = EQUIP_LABELS[code] || eq.description || code;
            const checked = selectedItems.has(code) ? 'checked' : '';
            let priceLabel = '';
            if (eq.unitName === 'day' || eq.pricePerDay) {
                const total = (parseFloat(eq.pricePerDay || 0)) * billedDays;
                priceLabel = `$${parseFloat(eq.pricePerDay || 0).toFixed(2)}/día · Total $${total.toFixed(2)}`;
            } else {
                priceLabel = `$${parseFloat(eq.amountTotal || 0).toFixed(2)}`;
            }
            html += `
            <label class="border rounded-3 p-3 d-flex gap-3 align-items-center cursor-pointer">
                <input type="checkbox" class="form-check-input equip-check" value="${code}" ${checked}>
                <div class="flex-grow-1"><strong>${label}</strong><small class="text-muted d-block">${priceLabel}</small></div>
            </label>`;
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
            btn.addEventListener('click', () => {
                const sipp = btn.getAttribute('data-sipp');
                const v = list.find(x => x.sippCode === sipp);
                if (!v) return;
                const rate = sessionStorage.getItem('selectedRateType') || 'web';
                sessionStorage.setItem('selectedVehicle', JSON.stringify(Object.assign({}, v, {
                    _selectedRateType: rate,
                    vendorRateId: window.RAC_FLOW.resolveVendorRateId(v, rate)
                })));
                sessionStorage.removeItem('extrasSelection');
                window.location.reload();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const ctx = window.RAC_FLOW.requireVehicle('/rent-a-car.php');
        if (!ctx) return;

        const loader = document.getElementById('extrasRefreshLoader');
        if (loader) loader.classList.remove('d-none');

        refreshVehicleFromApi(ctx.criteria, ctx.vehicle)
            .then(function (vehicle) {
                if (loader) loader.classList.add('d-none');
                initExtrasPage(vehicle, ctx.criteria);
            })
            .catch(function () {
                if (loader) loader.classList.add('d-none');
                initExtrasPage(ctx.vehicle, ctx.criteria);
            });
    });
})();
