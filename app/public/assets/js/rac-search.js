/**
 * RAC search form — handoff rules (branches, hours, closed return days).
 */
(function () {
    const DAY_KEYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    let branches = [];
    let imageBase = '';

    const form = document.getElementById('reservationSearchForm');
    if (!form) return;

    const pickupLoc = document.getElementById('pickupLocation');
    const returnLoc = document.getElementById('returnLocation');
    const pickupDate = document.getElementById('pickupDate');
    const returnDate = document.getElementById('returnDate');
    const pickupTime = document.getElementById('pickupTime');
    const returnTime = document.getElementById('returnTime');
    const driverAge = document.getElementById('driverAge');
    const promoCode = document.getElementById('promoCode');
    const toggleReturn = document.getElementById('toggleReturnBranch');
    const returnWrap = document.getElementById('returnLocationWrapper');
    const toggleCoupon = document.getElementById('toggleCoupon');
    const couponWrap = document.getElementById('couponCodeWrapper');
    const alertBox = document.getElementById('racSearchAlert');

    function formatDate(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function branchByCode(code) {
        return branches.find(b => b.code === code) || null;
    }

    function isDayClosed(branch, dateStr, kind) {
        if (!branch || !dateStr) return true;
        const weekday = new Date(dateStr + 'T12:00:00').getDay();
        const slot = branch.dailyHours?.[DAY_KEYS[weekday]];
        if (slot == null) return true;
        if (kind === 'return' && (branch.closedReturnDays || []).includes(weekday)) return true;
        return false;
    }

    function findNextOpenDate(branch, fromDateStr, kind, maxDays = 14) {
        if (!branch) return fromDateStr;
        for (let i = 0; i <= maxDays; i++) {
            const d = new Date(fromDateStr + 'T12:00:00');
            d.setDate(d.getDate() + i);
            const iso = formatDate(d);
            if (!isDayClosed(branch, iso, kind)) return iso;
        }
        return null;
    }

    function timeOptionsFor(branch, dateStr) {
        if (!branch || !dateStr) return [{ value: '10:00', label: '10:00' }];
        const weekday = new Date(dateStr + 'T12:00:00').getDay();
        const slot = branch.dailyHours?.[DAY_KEYS[weekday]];
        if (!slot) return [];
        const [oh, om] = slot.open.split(':').map(Number);
        const [ch, cm] = slot.close.split(':').map(Number);
        const opts = [];
        let h = oh, m = om;
        while (h < ch || (h === ch && m <= cm)) {
            const val = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            opts.push({ value: val, label: val });
            m += 30;
            if (m >= 60) { m -= 60; h++; }
        }
        return opts;
    }

    function fillTimeSelect(select, options, preferred) {
        select.innerHTML = '';
        options.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.label;
            if (o.value === preferred) opt.selected = true;
            select.appendChild(opt);
        });
        if (!select.value && options[0]) select.value = options[0].value;
    }

    function refreshTimePickers() {
        const pBranch = branchByCode(pickupLoc.value);
        const rBranch = branchByCode(toggleReturn.checked ? returnLoc.value : pickupLoc.value);
        fillTimeSelect(pickupTime, timeOptionsFor(pBranch, pickupDate.value), '10:00');
        fillTimeSelect(returnTime, timeOptionsFor(rBranch, returnDate.value), '10:00');
    }

    function showAlert(msg, type) {
        if (!alertBox) return;
        alertBox.className = `alert alert-${type || 'warning'} rounded-3 font-poppins text-sm`;
        alertBox.textContent = msg;
        alertBox.classList.remove('d-none');
    }

    function hideAlert() {
        if (alertBox) alertBox.classList.add('d-none');
    }

    function validateReturnDate() {
        const rBranch = branchByCode(toggleReturn.checked ? returnLoc.value : pickupLoc.value);
        if (!rBranch || !returnDate.value) return;
        if (isDayClosed(rBranch, returnDate.value, 'return')) {
            const next = findNextOpenDate(rBranch, returnDate.value, 'return');
            if (next) {
                showAlert(`${rBranch.name} no acepta devoluciones ese día. Ajustamos al ${next}.`, 'info');
                returnDate.value = next;
            } else {
                showAlert(`${rBranch.name} no opera devoluciones en las fechas seleccionadas. Elija otra fecha.`, 'warning');
            }
        }
        refreshTimePickers();
    }

    function initDefaults() {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const ret = new Date(tomorrow);
        ret.setDate(ret.getDate() + 2);

        pickupDate.min = formatDate(today);
        pickupDate.value = formatDate(tomorrow);
        returnDate.min = formatDate(tomorrow);
        returnDate.value = formatDate(ret);

        if (pickupLoc.options.length > 1) pickupLoc.selectedIndex = 1;
        refreshTimePickers();
    }

    function buildResultsUrl(payload) {
        const p = new URLSearchParams();
        p.set('l', payload.locationCode);
        if (payload.returnLocationCode !== payload.locationCode) {
            p.set('rl', payload.returnLocationCode);
        }
        p.set('d1', payload.pickupDate);
        p.set('d2', payload.returnDate);
        if (payload.pickupTime !== '10:00') p.set('pt', payload.pickupTime);
        if (payload.returnTime !== '10:00') p.set('rt', payload.returnTime);
        if (payload.age !== '25') p.set('a', payload.age);
        if (payload.promoCode) p.set('pr', payload.promoCode);
        return '/resultados.php?' + p.toString();
    }

    fetch('/api/rac-sucursales.php')
        .then(r => r.json())
        .then(data => {
            branches = data.branches || [];
            imageBase = data.imageBase || '';
            window.RAC_IMAGE_BASE = imageBase;
            initDefaults();
        })
        .catch(() => initDefaults());

    if (toggleReturn) {
        toggleReturn.addEventListener('change', () => {
            if (toggleReturn.checked) {
                returnWrap.classList.remove('d-none');
                returnLoc.setAttribute('required', 'required');
                if (!returnLoc.value && pickupLoc.value) returnLoc.value = pickupLoc.value;
            } else {
                returnWrap.classList.add('d-none');
                returnLoc.removeAttribute('required');
            }
            validateReturnDate();
        });
    }

    if (toggleCoupon) {
        toggleCoupon.addEventListener('change', () => {
            couponWrap.classList.toggle('d-none', !toggleCoupon.checked);
            if (!toggleCoupon.checked && promoCode) promoCode.value = '';
        });
    }

    pickupLoc.addEventListener('change', () => {
        if (!toggleReturn.checked) return;
        if (!returnLoc.value) returnLoc.value = pickupLoc.value;
        refreshTimePickers();
    });

    returnLoc.addEventListener('change', validateReturnDate);
    pickupDate.addEventListener('change', () => {
        const p = new Date(pickupDate.value + 'T12:00:00');
        const minRet = new Date(p);
        minRet.setDate(minRet.getDate() + 1);
        returnDate.min = formatDate(minRet);
        if (returnDate.value <= pickupDate.value) {
            returnDate.value = formatDate(minRet);
        }
        const pBranch = branchByCode(pickupLoc.value);
        if (pBranch && isDayClosed(pBranch, pickupDate.value, 'pickup')) {
            showAlert('La sucursal de retiro no opera ese día. Elija otra fecha.', 'warning');
        } else hideAlert();
        refreshTimePickers();
    });
    returnDate.addEventListener('change', validateReturnDate);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        hideAlert();
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        form.classList.add('was-validated');

        const payload = {
            locationCode: pickupLoc.value,
            returnLocationCode: toggleReturn.checked ? returnLoc.value : pickupLoc.value,
            pickupDate: pickupDate.value,
            pickupTime: pickupTime.value,
            returnDate: returnDate.value,
            returnTime: returnTime.value,
            age: driverAge.value,
            promoCode: toggleCoupon.checked ? (promoCode.value || '').trim() : ''
        };

        let loader = document.getElementById('premiumLoaderOverlay');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'premiumLoaderOverlay';
            loader.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white';
            loader.style.cssText = 'z-index:99999;background:rgba(8,16,38,.9);backdrop-filter:blur(6px)';
            loader.innerHTML = `<div class="spinner-border text-danger" style="width:3.5rem;height:3.5rem"></div>
                <h3 class="mt-4 fw-bold font-montserrat text-center">Consultando Disponibilidad</h3>
                <p class="text-secondary-light font-poppins text-sm text-center px-3">Buscando vehículos en tiempo real…</p>`;
            document.body.appendChild(loader);
        } else {
            loader.classList.remove('d-none');
        }

        fetch('/api/disponibilidad.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                loader.classList.add('d-none');
                if (!ok || data.success === false) {
                    showAlert(data.message || 'No se pudo consultar la disponibilidad.', 'danger');
                    return;
                }
                sessionStorage.setItem('searchResults', JSON.stringify(data));
                sessionStorage.setItem('searchCriteria', JSON.stringify(payload));
                window.location.href = buildResultsUrl(payload);
            })
            .catch(err => {
                loader.classList.add('d-none');
                showAlert('Error de conexión. Intente nuevamente. ' + err.message, 'danger');
            });
    });

    window.RAC_BRANCHES = () => branches;
    window.RAC_SEARCH_HANDLED = true;
})();
