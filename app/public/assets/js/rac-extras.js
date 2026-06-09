/**
 * RAC extras page — protection, optional equipment, totals, alternatives.
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

    function run() {
        const ctx = window.RAC_FLOW.requireVehicle('/rent-a-car.php');
        if (!ctx) return;

        const { vehicle, criteria } = ctx;
        const days = window.RAC_FLOW.calcDays(criteria.pickupDate, criteria.returnDate);
        const rateType = sessionStorage.getItem('selectedRateType') || vehicle._selectedRateType || 'web';

        document.getElementById('extrasNoVehicle')?.classList.add('d-none');
        document.getElementById('extrasMain')?.classList.remove('d-none');

        renderVehicleHeader(vehicle, criteria, days, rateType);
        renderSummarySidebar(vehicle, criteria, days, rateType);

        const pricing = vehicle.pricing || {};
        const rentalBase = parseFloat(pricing.rateBase ?? vehicle.priceTotal ?? vehicle.priceWeb ?? 0) || 0;
        const saf = parseFloat(pricing.saf ?? 0) || 0;
        const packages = pricing.coveragePackages || vehicle.availableCoverages || [];
        const equipment = (vehicle.availableEquipment || []).filter(e => {
            const c = (e.code || '').toUpperCase();
            return c !== 'AMAS' || packages.some(p => (p.code || p.coverageType) === 'AMAS');
        });

        const packagesByCode = {};
        packages.forEach((pkg, i) => {
            const code = pkg.code || pkg.coverageType || ('cov_' + i);
            packagesByCode[code] = pkg;
        });

        let selectedProtection = packages.find(p => p.isDefault)?.code
            || packages.find(p => (p.code || p.coverageType) === 'BASIC')?.code
            || packages[0]?.code
            || packages[0]?.coverageType
            || 'BASIC';

        const saved = window.RAC_FLOW.getExtras();
        if (saved && saved.protection) selectedProtection = saved.protection;

        const selectedItems = new Set((saved && saved.items) ? saved.items.map(i => i.code) : []);
        let additionalDrivers = (saved && saved.additionalDrivers) ? parseInt(saved.additionalDrivers, 10) : 0;

        renderProtection(packages, selectedProtection, packagesByCode);
        renderEquipment(equipment, selectedItems, additionalDrivers);

        const state = {
            rentalBase, saf, days, packagesByCode,
            selectedProtection, selectedItems, additionalDrivers, equipment
        };

        function recalc() {
            const pkg = state.packagesByCode[state.selectedProtection] || {};
            let coverageAmt = parseFloat(pkg.amountTotal ?? 0) || 0;
            if (!coverageAmt && pkg.pricePerDay) {
                coverageAmt = parseFloat(pkg.pricePerDay) * state.days;
            }

            let extrasAmt = 0;
            state.equipment.forEach(eq => {
                const code = eq.code || '';
                if (code === 'CONDADIC') {
                    extrasAmt += (parseFloat(eq.amountTotal ?? eq.pricePerDay ?? 15) || 15) * state.additionalDrivers;
                    return;
                }
                if (state.selectedItems.has(code)) {
                    if (eq.unitName === 'day' || eq.pricePerDay) {
                        extrasAmt += (parseFloat(eq.pricePerDay ?? 0) || 0) * state.days;
                    } else {
                        extrasAmt += parseFloat(eq.amountTotal ?? 0) || 0;
                    }
                }
            });

            const subtotal = state.rentalBase + state.saf + coverageAmt + extrasAmt;
            const itbms = Math.round(subtotal * 0.07 * 100) / 100;
            const total = Math.round((subtotal + itbms) * 100) / 100;

            document.getElementById('sumBase').textContent = window.RAC_FLOW.fmtMoney(state.rentalBase);
            document.getElementById('sumSaf').textContent = window.RAC_FLOW.fmtMoney(state.saf);
            document.getElementById('sumCoverage').textContent = window.RAC_FLOW.fmtMoney(coverageAmt);
            document.getElementById('sumExtras').textContent = window.RAC_FLOW.fmtMoney(extrasAmt);
            document.getElementById('sumItbms').textContent = window.RAC_FLOW.fmtMoney(itbms);
            document.getElementById('sumTotal').textContent = window.RAC_FLOW.fmtMoney(total);

            const covName = pkg.name || pkg.description || state.selectedProtection;
            document.getElementById('sumCoverageLabel').textContent = covName;

            state.totals = { base: state.rentalBase, saf: state.saf, coverage: coverageAmt, extras: extrasAmt, itbms, total };
            state.coverageName = covName;
            state.coverageDeductible = pkg.deductible != null ? parseFloat(pkg.deductible) : null;
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
            const items = [];
            state.selectedItems.forEach(code => {
                const eq = state.equipment.find(x => x.code === code);
                if (eq) items.push({ code: eq.code, description: eq.description || eq.code });
            });

            const extrasSelection = {
                protection: state.selectedProtection,
                items,
                additionalDrivers: state.additionalDrivers,
                totals: state.totals,
                pricingSnapshot: vehicle.pricing || {},
                coverage_name: state.coverageName,
                coverage_deductible: state.coverageDeductible
            };

            sessionStorage.setItem('extrasSelection', JSON.stringify(extrasSelection));
            window.location.href = '/reservar.php';
        });

        recalc();
        renderAlternatives(criteria, vehicle, days);
    }

    function renderVehicleHeader(vehicle, criteria, days, rateType) {
        const el = document.getElementById('extrasVehicleHeader');
        if (!el) return;
        const img = window.RAC_FLOW.resolveImage(vehicle.image);
        const webTotal = vehicle.priceTotal != null ? vehicle.priceTotal : (vehicle.priceWeb || 0) * days;
        el.innerHTML = `
            <div class="d-flex flex-wrap align-items-center gap-3">
                ${img ? `<img src="${img}" alt="" style="max-height:70px;object-fit:contain">` : ''}
                <div class="flex-grow-1">
                    <span class="badge bg-danger-subtle text-danger text-uppercase">${vehicle.category || ''}</span>
                    <h4 class="fw-bold text-navy mb-1">${vehicle.name || 'Vehículo'}</h4>
                    <small class="text-muted">${days} días · ${window.RAC_FLOW.branchLabel(criteria.locationCode)}</small>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block">${rateType === 'counter' ? 'Tarifa mostrador' : 'WebExclusivo'}</small>
                    <span class="fs-4 fw-bold text-navy">${window.RAC_FLOW.fmtMoney(webTotal)}</span>
                </div>
            </div>`;
    }

    function renderSummarySidebar(vehicle, criteria, days) {
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
                <span>Días de renta</span><strong>${days} día${days !== 1 ? 's' : ''}</strong>
            </div>`;
    }

    function renderProtection(packages, selected, packagesByCode) {
        const wrap = document.getElementById('protectionOptions');
        if (!wrap) return;
        if (!packages.length) {
            wrap.innerHTML = '<p class="text-muted">La protección se confirmará al retirar el vehículo.</p>';
            return;
        }
        wrap.innerHTML = packages.map((pkg, i) => {
            const code = pkg.code || pkg.coverageType || ('cov_' + i);
            const checked = code === selected ? 'checked' : '';
            const border = checked ? 'border-danger' : '';
            const amt = parseFloat(pkg.amountTotal || 0) || (parseFloat(pkg.pricePerDay || 0) * (window.RAC_FLOW.calcDays(
                window.RAC_FLOW.getCriteria()?.pickupDate,
                window.RAC_FLOW.getCriteria()?.returnDate
            )));
            const perDay = parseFloat(pkg.pricePerDay || 0);
            const ded = parseFloat(pkg.deductible || 0);
            const badge = code === 'STANDARD' ? '<span class="badge bg-warning text-dark ms-1">Más popular</span>' :
                code === 'PREMIUM' ? '<span class="badge bg-success ms-1">Sin preocupaciones</span>' : '';
            return `
            <label class="border rounded-3 p-3 d-flex gap-3 align-items-start cursor-pointer ${border}">
                <input type="radio" name="protection_code" class="form-check-input mt-1" value="${code}" ${checked}>
                <div class="flex-grow-1">
                    <span class="fw-bold text-navy">${pkg.name || pkg.description || code}</span>${badge}
                    <small class="text-muted d-block">$${perDay.toFixed(2)}/día · Total $${amt.toFixed(2)} · Deducible $${ded.toFixed(0)}</small>
                </div>
            </label>`;
        }).join('');
    }

    function renderEquipment(equipment, selectedItems, additionalDrivers) {
        const wrap = document.getElementById('equipmentOptions');
        if (!wrap) return;

        const driverEq = equipment.find(e => (e.code || '').toUpperCase() === 'CONDADIC');
        const others = equipment.filter(e => (e.code || '').toUpperCase() !== 'CONDADIC');

        let html = '';
        if (driverEq || true) {
            const price = driverEq ? (parseFloat(driverEq.amountTotal ?? driverEq.pricePerDay ?? 15)) : 15;
            html += `
            <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between">
                <div><i class="bi bi-person-plus text-danger me-2"></i><strong>Conductor Adicional</strong>
                    <small class="text-muted d-block">$${price.toFixed(2)} c/u</small></div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-driver-delta="-1">−</button>
                    <span id="driverCount" class="fw-bold px-2">${additionalDrivers}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle" data-driver-delta="1">+</button>
                </div>
            </div>`;
        }

        others.forEach(eq => {
            const code = eq.code || '';
            const label = EQUIP_LABELS[code] || eq.description || code;
            const checked = selectedItems.has(code) ? 'checked' : '';
            const days = window.RAC_FLOW.calcDays(window.RAC_FLOW.getCriteria()?.pickupDate, window.RAC_FLOW.getCriteria()?.returnDate);
            let priceLabel = '';
            if (eq.unitName === 'day' || eq.pricePerDay) {
                const total = (parseFloat(eq.pricePerDay || 0)) * days;
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

        if (!html) html = '<p class="text-muted small">No hay extras opcionales para este vehículo.</p>';
        wrap.innerHTML = html;
    }

    function renderAlternatives(criteria, currentVehicle, days) {
        const wrap = document.getElementById('alternativesRow');
        if (!wrap) return;
        let list = [];
        try {
            list = JSON.parse(sessionStorage.getItem('searchResultsVehicles') || '[]');
        } catch { list = []; }
        if (!list.length) {
            try {
                const raw = JSON.parse(sessionStorage.getItem('searchResults') || 'null');
                list = Array.isArray(raw) ? raw : (raw && raw.vehicles ? raw.vehicles : []);
            } catch { list = []; }
        }

        const alts = list.filter(v => v.sippCode !== currentVehicle.sippCode).slice(0, 2);
        if (!alts.length) {
            document.getElementById('alternativesSection')?.classList.add('d-none');
            return;
        }

        const currentTotal = currentVehicle.priceTotal != null ? currentVehicle.priceTotal : (currentVehicle.priceWeb || 0) * days;

        wrap.innerHTML = alts.map(v => {
            const total = v.priceTotal != null ? v.priceTotal : (v.priceWeb || 0) * days;
            const diff = (total - currentTotal) / days;
            const diffLabel = Math.abs(diff) < 0.05 ? 'Mismo precio' : (diff > 0 ? `+$${diff.toFixed(2)}/día` : `$${Math.abs(diff).toFixed(2)} menos/día`);
            const img = window.RAC_FLOW.resolveImage(v.image);
            return `
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        ${img ? `<img src="${img}" class="img-fluid mb-2" style="max-height:80px;object-fit:contain">` : ''}
                        <h6 class="fw-bold text-navy">${v.name}</h6>
                        <small class="text-muted">${v.category} · ${v.transmission || 'Automática'}</small>
                        <div class="mt-2"><strong>${window.RAC_FLOW.fmtMoney(total)}</strong> total
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
                sessionStorage.setItem('selectedVehicle', JSON.stringify(Object.assign({}, v, { _selectedRateType: rate })));
                sessionStorage.removeItem('extrasSelection');
                window.location.reload();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', run);
})();
