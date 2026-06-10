/**
 * RAC results page — dual-rate cards, MISS/fallback, filters.
 */
(function () {
    const CATEGORY_ORDER = ['económico', 'economico', 'compacto', 'intermedio', 'estándar', 'estandar', 'full size', 'suv', 'premium', 'lujo', 'van', 'pick'];

    function calcDays(pickup, ret) {
        if (!pickup || !ret) return 1;
        const d1 = new Date(pickup + 'T12:00:00');
        const d2 = new Date(ret + 'T12:00:00');
        const diff = Math.round((d2 - d1) / (86400000));
        return diff > 0 ? diff : 1;
    }

    function parseUrlCriteria() {
        const p = new URLSearchParams(window.location.search);
        const unpack = (key, timeKey) => {
            const raw = p.get(key);
            if (!raw) return { date: '', time: '10:00' };
            if (raw.includes('T')) {
                const [date, time] = raw.split('T');
                return { date, time: time || '10:00' };
            }
            return { date: raw, time: p.get(timeKey) || '10:00' };
        };
        const d1 = unpack('d1', 'pt');
        const d2 = unpack('d2', 'rt');
        return {
            locationCode: p.get('l') || p.get('locationCode') || '',
            returnLocationCode: p.get('rl') || p.get('returnLocationCode') || p.get('l') || '',
            pickupDate: d1.date,
            pickupTime: d1.time,
            returnDate: d2.date,
            returnTime: d2.time,
            age: p.get('a') || p.get('age') || '25',
            promoCode: p.get('pr') || p.get('promoCode') || ''
        };
    }

    function reasonMessage(reason) {
        const map = {
            LOCATION_CLOSED: 'Esta sucursal no acepta devoluciones en esa fecha. Cambie la fecha de devolución.',
            NO_AVAILABILITY: 'No hay vehículos disponibles. Pruebe otras fechas o sucursales.',
            RATE_NOT_CONFIGURED: 'Tarifa no disponible para esa sucursal. Contacte a la sucursal.',
            BARS_TIMEOUT: 'El sistema está lento. Reintente en unos minutos.'
        };
        return map[reason] || 'Sin resultados para esta búsqueda. Ajuste fechas o sucursales.';
    }

    function fmt(n) {
        return Number(n || 0).toFixed(2);
    }

    function branchLabel(code) {
        const branches = window.RAC_BRANCHES ? window.RAC_BRANCHES() : [];
        const b = branches.find(x => x.code === code);
        return b ? b.name : code;
    }

    function criteriaKey(c) {
        if (!c || !c.locationCode) return '';
        return [
            c.locationCode,
            c.returnLocationCode || c.locationCode,
            c.pickupDate,
            c.pickupTime || '10:00',
            c.returnDate,
            c.returnTime || '10:00',
            c.age || '25',
            c.promoCode || ''
        ].join('|');
    }

    function vehicleBilledDays(vehicle, calendarDays) {
        if (window.RAC_FLOW?.vehicleBilledDays) {
            return window.RAC_FLOW.vehicleBilledDays(vehicle, calendarDays);
        }
        const rd = parseInt(vehicle.rentalDays, 10);
        return rd > 0 ? rd : calendarDays;
    }

    /** Paso 2: solo tarifa del período (priceTotal / priceCounterTotal), sin mandatory. */
    function vehicleRateTotals(vehicle, calendarDays) {
        const billedDays = vehicleBilledDays(vehicle, calendarDays);
        const isFallback = vehicle._isFallback === true;

        if (isFallback) {
            const daily = parseFloat(vehicle.basePrice ?? vehicle.priceWeb ?? 0) || 0;
            const webTotal = daily * billedDays;
            return { webTotal, counterTotal: webTotal * 1.07, billedDays, webPerDay: daily, counterPerDay: daily * 1.07 };
        }

        const webTotal = vehicle.priceTotal != null
            ? parseFloat(vehicle.priceTotal)
            : (parseFloat(vehicle.priceWeb || 0) * billedDays);

        let counterTotal = vehicle.priceCounterTotal != null
            ? parseFloat(vehicle.priceCounterTotal)
            : NaN;
        if (isNaN(counterTotal)) {
            counterTotal = parseFloat(vehicle.priceCounter || 0) * billedDays;
        }

        const webPerDay = billedDays > 0 ? webTotal / billedDays : parseFloat(vehicle.priceWeb || 0);
        const counterPerDay = billedDays > 0 ? counterTotal / billedDays : parseFloat(vehicle.priceCounter || 0);

        return { webTotal, counterTotal, billedDays, webPerDay, counterPerDay };
    }

    function renderCard(vehicle, calendarDays, vehicleIndex) {
        const isFallback = vehicle._isFallback === true;
        const rates = vehicleRateTotals(vehicle, calendarDays);
        const webTotal = rates.webTotal;
        const counterTotal = rates.counterTotal;
        const billedDays = rates.billedDays;

        const img = vehicle.image
            ? `<img src="${vehicle.image}" class="img-fluid vehicle-image-card" alt="" style="max-height:140px;object-fit:contain">`
            : `<div class="py-4"><i class="bi bi-car-front text-muted opacity-25" style="font-size:5rem"></i></div>`;

        const badge = isFallback
            ? `<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">Precio aproximado</span>`
            : '';

        return `
        <div class="col-lg-4 col-md-6 col-12 d-flex rac-vehicle-col" data-category="${(vehicle.category || '').toLowerCase()}">
            <div class="card vehicle-card border-0 shadow-sm rounded-4 w-100 overflow-hidden position-relative">
                ${badge}
                <span class="category-badge position-absolute bg-white px-3 py-1 text-navy rounded-pill fw-bold shadow-sm top-3 start-3 text-uppercase z-index-2">${vehicle.category || 'General'}</span>
                <div class="card-image-wrapper bg-white p-4 text-center d-flex align-items-center justify-content-center" style="height:180px;background-color:#fff !important;">${img}</div>
                <div class="card-body px-4 py-4 d-flex flex-column">
                    <h4 class="fw-bold text-navy fs-5 mb-2">${vehicle.name || 'Vehículo'}</h4>
                    <p class="text-muted text-sm mb-3">${vehicle.description ? vehicle.description.substring(0, 90) + '…' : ''}</p>
                    <div class="d-flex flex-wrap gap-2 mb-3 text-sm">
                        <span class="badge bg-light text-dark border"><i class="bi bi-people-fill text-danger"></i> ${vehicle.passengers || 5} Pax</span>
                        <span class="badge bg-light text-dark border"><i class="bi bi-gear-wide-connected text-danger"></i> ${vehicle.transmission || 'Automática'}</span>
                    </div>
                    <div class="row g-2 mt-auto">
                        <div class="col-6 border-end">
                            <small class="text-muted d-block">WebExclusivo</small>
                            <span class="fs-4 fw-bold text-navy">$${fmt(webTotal)}</span>
                            <small class="text-muted d-block">$${fmt(rates.webPerDay)}/día · ${billedDays} día${billedDays !== 1 ? 's' : ''}</small>
                            <button type="button" class="btn btn-theme btn-sm w-100 mt-2 rounded-pill rac-select-btn" data-rate="web" data-vehicle-index="${vehicleIndex}">Reservar Web</button>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">En mostrador</small>
                            <span class="fs-4 fw-bold text-navy">$${fmt(counterTotal)}</span>
                            <small class="text-muted d-block">$${fmt(rates.counterPerDay)}/día · ${billedDays} día${billedDays !== 1 ? 's' : ''}</small>
                            <button type="button" class="btn btn-outline-dark btn-sm w-100 mt-2 rounded-pill rac-select-btn" data-rate="counter" data-vehicle-index="${vehicleIndex}">Reservar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function renderSidebar(criteria, days) {
        const box = document.getElementById('sidebarSummary');
        if (!box) return;
        const fn = window.RAC_FLOW || {};
        const fmtD = fn.formatDateDisplay || (d => d);
        const fmtT = fn.formatTimeDisplay || (t => t);
        const br = fn.branchLabel || branchLabel;
        box.innerHTML = `
            <div class="small mb-3"><span class="text-danger fw-semibold text-uppercase">Recogida</span><br>
                ${br(criteria.locationCode)}<br>
                <span class="text-muted">${fmtD(criteria.pickupDate)} ${fmtT(criteria.pickupTime)}</span></div>
            <div class="small mb-3"><span class="text-danger fw-semibold text-uppercase">Devolución</span><br>
                ${br(criteria.returnLocationCode || criteria.locationCode)}<br>
                <span class="text-muted">${fmtD(criteria.returnDate)} ${fmtT(criteria.returnTime)}</span></div>
            <div class="bg-navy text-white rounded-3 px-3 py-2 d-flex justify-content-between small mb-2">
                <span>Días de renta</span><strong>${days}</strong></div>
            <div class="small text-muted">Edad conductor: ${criteria.age === '23' ? '23-24' : '+25'} años</div>`;
    }

    function renderCategoryFilters(vehicles) {
        const wrap = document.getElementById('categoryFilters');
        if (!wrap) return;
        const counts = {};
        vehicles.forEach(v => {
            const c = (v.category || 'General').toLowerCase();
            counts[c] = (counts[c] || 0) + 1;
        });
        let html = `<button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn active" data-category="all">Todos (${vehicles.length})</button>`;
        Object.keys(counts).sort().forEach(cat => {
            const slug = cat.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            html += `<button class="btn btn-outline-dark rounded-pill px-4 filter-category-btn" data-category="${slug}">${cat.charAt(0).toUpperCase() + cat.slice(1)} (${counts[cat]})</button>`;
        });
        wrap.innerHTML = html;
    }

    function normalizeCategory(str) {
        return (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function bindSelectButtons(vehicles) {
        document.querySelectorAll('.rac-select-btn').forEach(btn => {
            const idx = parseInt(btn.getAttribute('data-vehicle-index'), 10);
            const vehicle = vehicles[idx];
            if (!vehicle) return;
            btn.addEventListener('click', () => {
                const rate = btn.getAttribute('data-rate') || 'web';
                const vendorRateId = window.RAC_FLOW?.resolveVendorRateId
                    ? window.RAC_FLOW.resolveVendorRateId(vehicle, rate)
                    : (vehicle.vendorRateId || '');
                const enriched = Object.assign({}, vehicle, {
                    _selectedRateType: rate,
                    vendorRateId: vendorRateId
                });
                sessionStorage.setItem('selectedVehicle', JSON.stringify(enriched));
                sessionStorage.setItem('selectedRateType', rate);
                sessionStorage.removeItem('extrasSelection');
                window.location.href = '/extras.php';
            });
        });
    }

    function run() {
        const grid = document.getElementById('resultsVehiclesGrid');
        const loader = document.getElementById('resultsLoader');
        const noSearch = document.getElementById('noSearchWarning');
        const statusBox = document.getElementById('racResultsStatus');
        if (!grid) return;

        let criteria = null;
        let results = null;
        try {
            criteria = JSON.parse(sessionStorage.getItem('searchCriteria') || 'null');
            const activeKey = criteria ? criteriaKey(criteria) : '';
            const storedKey = sessionStorage.getItem('searchCriteriaKey') || '';
            if (activeKey && storedKey === activeKey) {
                results = JSON.parse(sessionStorage.getItem('searchResults') || 'null');
            }
        } catch (e) { /* ignore */ }

        if (!criteria || !criteria.locationCode) {
            criteria = parseUrlCriteria();
        }

        if (!criteria.locationCode) {
            if (loader) loader.classList.add('d-none');
            if (noSearch) noSearch.classList.remove('d-none');
            return;
        }

        if (!results && criteria.locationCode) {
            if (loader) loader.classList.remove('d-none');
            fetch('/api/disponibilidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    locationCode: criteria.locationCode,
                    returnLocationCode: criteria.returnLocationCode || criteria.locationCode,
                    pickupDate: criteria.pickupDate,
                    pickupTime: criteria.pickupTime || '10:00',
                    returnDate: criteria.returnDate,
                    returnTime: criteria.returnTime || '10:00',
                    age: criteria.age || '25',
                    promoCode: criteria.promoCode || ''
                })
            })
                .then(r => r.json())
                .then(data => {
                    sessionStorage.setItem('searchResults', JSON.stringify(data));
                    sessionStorage.setItem('searchCriteria', JSON.stringify(criteria));
                    sessionStorage.setItem('searchCriteriaKey', criteriaKey(criteria));
                    render(data, criteria);
                })
                .catch(() => {
                    if (loader) loader.classList.add('d-none');
                    grid.innerHTML = '<div class="col-12 text-center py-5 text-danger">Error al cargar disponibilidad.</div>';
                });
            return;
        }

        render(results, criteria);
    }

    function render(results, criteria) {
        const grid = document.getElementById('resultsVehiclesGrid');
        const loader = document.getElementById('resultsLoader');
        const summary = document.getElementById('searchSummaryText');
        const statusBox = document.getElementById('racResultsStatus');
        const debugBadges = document.getElementById('debugBadges');

        if (loader) loader.classList.add('d-none');

        const calendarDays = calcDays(criteria.pickupDate, criteria.returnDate);
        const loc = criteria.locationCode;
        const ret = criteria.returnLocationCode || loc;
        const firstVehicle = (results.vehicles || [])[0] || (results.catalogFallback || [])[0];
        const displayDays = firstVehicle ? vehicleBilledDays(firstVehicle, calendarDays) : calendarDays;

        if (summary) {
            summary.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i> Retiro: <strong>${branchLabel(loc)}</strong>` +
                (loc !== ret ? ` · Devolución: <strong>${branchLabel(ret)}</strong>` : '') +
                ` | <i class="bi bi-calendar-check me-1"></i> ${criteria.pickupDate} ${criteria.pickupTime} → ${criteria.returnDate} ${criteria.returnTime}` +
                ` · <strong>${displayDays}</strong> día(s)`;
        }

        if (debugBadges && (new URLSearchParams(location.search).get('debug') === '1')) {
            debugBadges.classList.remove('d-none');
            const ds = document.getElementById('debugSource');
            const dc = document.getElementById('debugCache');
            if (ds) ds.textContent = results.source || '—';
            if (dc) dc.textContent = results.xCache || '—';
        }

        let vehicles = results.vehicles || [];
        let html = '';

        if (results.miss && vehicles.length === 0 && (results.catalogFallback || []).length) {
            if (statusBox) {
                statusBox.className = 'alert alert-info rounded-4';
                statusBox.textContent = 'Cargando precios en vivo… Los precios mostrados son aproximados. Reintente en unos segundos.';
                statusBox.classList.remove('d-none');
            }
            vehicles = results.catalogFallback;
            scheduleMissRetry(criteria);
        } else if (vehicles.length === 0) {
            if (statusBox) {
                statusBox.className = 'alert alert-warning rounded-4';
                statusBox.textContent = reasonMessage(results.reason) + (results.branchNote ? ' ' + results.branchNote : '');
                statusBox.classList.remove('d-none');
            }
            grid.innerHTML = '<div class="col-12 text-center py-5"><a href="/rent-a-car.php" class="btn btn-theme rounded-pill px-4">Modificar búsqueda</a></div>';
            return;
        } else if (statusBox) {
            statusBox.classList.add('d-none');
        }

        vehicles.forEach((v, i) => { html += renderCard(v, calendarDays, i); });
        grid.innerHTML = html;
        bindSelectButtons(vehicles);

        try {
            sessionStorage.setItem('searchResultsVehicles', JSON.stringify(vehicles));
        } catch (e) { /* ignore */ }

        renderSidebar(criteria, displayDays);
        renderCategoryFilters(vehicles);

        document.querySelectorAll('.filter-category-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                document.querySelectorAll('.filter-category-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const cat = btn.getAttribute('data-category');
                document.querySelectorAll('.rac-vehicle-col').forEach(col => {
                    const c = normalizeCategory(col.getAttribute('data-category') || '');
                    const show = cat === 'all' || c.includes(cat) || cat.includes(c);
                    col.classList.toggle('d-none', !show);
                    col.classList.toggle('d-flex', show);
                });
            });
        });
    }

    let missRetryTimer = null;

    function scheduleMissRetry(criteria) {
        if (missRetryTimer) clearTimeout(missRetryTimer);
        missRetryTimer = setTimeout(function () {
            fetch('/api/disponibilidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    locationCode: criteria.locationCode,
                    returnLocationCode: criteria.returnLocationCode || criteria.locationCode,
                    pickupDate: criteria.pickupDate,
                    pickupTime: criteria.pickupTime || '10:00',
                    returnDate: criteria.returnDate,
                    returnTime: criteria.returnTime || '10:00',
                    age: criteria.age || '25',
                    promoCode: criteria.promoCode || ''
                })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    sessionStorage.setItem('searchResults', JSON.stringify(data));
                    sessionStorage.setItem('searchCriteriaKey', criteriaKey(criteria));
                    render(data, criteria);
                })
                .catch(function () { /* ignore */ });
        }, 30000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetch('/api/rac-sucursales.php')
            .then(r => r.json())
            .then(d => { window.RAC_BRANCHES = () => d.branches || []; })
            .finally(run);
    });
})();
