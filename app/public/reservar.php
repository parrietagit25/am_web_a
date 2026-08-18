<?php
/**
 * Automarket - RAC Driver details (step 4)
 */
$activeUnit = 'rentacar';
$racStep = 4;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/rac-stepper.php';
require_once __DIR__ . '/../services/CaptchaService.php';
$racLocalCaptchaBypass = CaptchaService::isLocalCaptchaBypassAllowed();
?>

<section class="container mb-5" id="reserveNoData">
    <div class="card border-0 shadow-sm p-5 text-center rounded-4">
        <h4 class="fw-bold text-navy">Sesión de reserva incompleta</h4>
        <p class="text-muted">Seleccione vehículo y extras para continuar.</p>
        <a href="/rent-a-car.php" class="btn btn-theme rounded-pill px-4 text-white">Ir al buscador</a>
    </div>
</section>

<section class="container mb-5 d-none" id="reserveMain">
    <div class="mb-3">
        <a href="/extras.php" class="text-muted text-decoration-none small fw-semibold">
            <i class="bi bi-arrow-left"></i> Volver a Escoger Extras
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <form id="checkoutBookingForm" class="needs-validation" novalidate>
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h3 class="fw-bold text-navy mb-3">Datos del conductor principal</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="firstName" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">Ingrese su nombre.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
                            <input type="text" id="lastName" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">Ingrese su apellido.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" id="checkoutEmail" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">Correo inválido.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirmar correo <span class="text-danger">*</span></label>
                            <input type="email" id="emailConfirm" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">Los correos deben coincidir.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País tel.</label>
                            <select id="phonePrefix" class="form-select form-control-premium">
                                <option value="+507" selected>PA +507</option>
                                <option value="+1">US +1</option>
                                <option value="+57">CO +57</option>
                                <option value="+34">ES +34</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Teléfono <span class="text-danger">*</span></label>
                            <input type="tel" id="checkoutPhone" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">Ingrese su teléfono.</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h3 class="fw-bold text-navy mb-3">Documento de identidad</h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select id="docType" class="form-select form-control-premium">
                                <option value="LIC">Licencia de conducir</option>
                                <option value="PAS">Pasaporte</option>
                                <option value="ID">Cédula</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Número <span class="text-danger">*</span></label>
                            <input type="text" id="docNumber" class="form-control form-control-premium" required>
                            <div class="invalid-feedback">Ingrese el número de documento.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">País emisor</label>
                            <select id="countryCode" class="form-select form-control-premium">
                                <option value="PA" selected>Panamá</option>
                                <option value="US">Estados Unidos</option>
                                <option value="CO">Colombia</option>
                                <option value="CR">Costa Rica</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="birthDate" class="form-label fw-semibold">Fecha de nacimiento <span class="text-danger">*</span></label>
                            <input type="date" id="birthDate" name="birth_date" class="form-control form-control-premium" required autocomplete="bday" max="">
                            <div class="invalid-feedback" id="birthDateFeedback">Ingrese una fecha de nacimiento válida.</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h3 class="fw-bold text-navy mb-2">Información de vuelo <small class="text-muted fw-normal">(opcional)</small></h3>
                    <p class="text-muted small">Si llega al aeropuerto, esto nos ayuda a coordinar la entrega del vehículo.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Número de vuelo</label>
                            <input type="text" id="flightNumber" class="form-control form-control-premium" placeholder="CM202">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código de aerolínea</label>
                            <input type="text" id="airlineCode" class="form-control form-control-premium" placeholder="CM">
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <label class="form-label fw-semibold">Notas adicionales <small class="text-muted">(opcional)</small></label>
                    <textarea id="checkoutComments" class="form-control form-control-premium" rows="4" maxlength="500" placeholder="Horario de llegada, peticiones especiales…"></textarea>
                    <div class="text-end small text-muted"><span id="notesCount">0</span>/500</div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="checkoutPolicyCheck" required>
                    <label class="form-check-label text-muted small" for="checkoutPolicyCheck">
                        He leído y acepto los <a href="/terminos-condiciones.php" target="_blank" class="fw-semibold">Términos y Condiciones</a> de alquiler, incluyendo política de cancelación y depósito de garantía. <span class="text-danger">*</span>
                    </label>
                    <div class="invalid-feedback">Debe aceptar los términos.</div>
                </div>

                <?php if (!$racLocalCaptchaBypass): ?>
                <?php require __DIR__ . '/../includes/captcha-widget.php'; ?>
                <?php else: ?>
                <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>Verificación captcha omitida en entorno local de desarrollo.</p>
                <?php endif; ?>

                <button type="submit" class="btn btn-theme w-100 py-3 rounded-pill fw-bold text-white fs-5">
                    Confirmar y reservar <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white position-sticky" style="top:100px;" id="reserveSidebar"></div>
        </div>
    </div>
</section>

<script src="/assets/js/rac-flow.js?v=4"></script>
<script>
const RAC_DRIVER_DRAFT_KEY = 'racDriverDraft';

function panamaTodayIso() {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/Panama',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(new Date());
}

function isValidBirthDateValue(value) {
    if (typeof value !== 'string') return false;
    const raw = value.trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) return false;
    const parts = raw.split('-').map(Number);
    const year = parts[0];
    const month = parts[1];
    const day = parts[2];
    if (year < 1900) return false;
    const dt = new Date(Date.UTC(year, month - 1, day));
    if (dt.getUTCFullYear() !== year || (dt.getUTCMonth() + 1) !== month || dt.getUTCDate() !== day) {
        return false;
    }
    return raw < panamaTodayIso();
}

function setBirthDateValidity(input) {
    if (!input) return false;
    const value = input.value || '';
    if (!value.trim()) {
        input.setCustomValidity('required');
        const fb = document.getElementById('birthDateFeedback');
        if (fb) fb.textContent = 'La fecha de nacimiento es obligatoria.';
        return false;
    }
    if (!isValidBirthDateValue(value)) {
        input.setCustomValidity('invalid');
        const fb = document.getElementById('birthDateFeedback');
        if (fb) fb.textContent = 'Ingrese una fecha de nacimiento válida.';
        return false;
    }
    input.setCustomValidity('');
    return true;
}

function readDriverDraft() {
    try {
        const raw = sessionStorage.getItem(RAC_DRIVER_DRAFT_KEY);
        if (!raw) return null;
        const data = JSON.parse(raw);
        return data && typeof data === 'object' ? data : null;
    } catch (e) {
        return null;
    }
}

function saveDriverDraft() {
    const draft = {
        firstName: document.getElementById('firstName')?.value || '',
        lastName: document.getElementById('lastName')?.value || '',
        checkoutEmail: document.getElementById('checkoutEmail')?.value || '',
        emailConfirm: document.getElementById('emailConfirm')?.value || '',
        phonePrefix: document.getElementById('phonePrefix')?.value || '',
        checkoutPhone: document.getElementById('checkoutPhone')?.value || '',
        docType: document.getElementById('docType')?.value || '',
        docNumber: document.getElementById('docNumber')?.value || '',
        countryCode: document.getElementById('countryCode')?.value || '',
        birthDate: document.getElementById('birthDate')?.value || '',
        flightNumber: document.getElementById('flightNumber')?.value || '',
        airlineCode: document.getElementById('airlineCode')?.value || '',
        checkoutComments: document.getElementById('checkoutComments')?.value || ''
    };
    try {
        sessionStorage.setItem(RAC_DRIVER_DRAFT_KEY, JSON.stringify(draft));
    } catch (e) { /* ignore quota */ }
}

function restoreDriverDraft() {
    const draft = readDriverDraft();
    if (!draft) return;
    const map = {
        firstName: 'firstName',
        lastName: 'lastName',
        checkoutEmail: 'checkoutEmail',
        emailConfirm: 'emailConfirm',
        phonePrefix: 'phonePrefix',
        checkoutPhone: 'checkoutPhone',
        docType: 'docType',
        docNumber: 'docNumber',
        countryCode: 'countryCode',
        birthDate: 'birthDate',
        flightNumber: 'flightNumber',
        airlineCode: 'airlineCode',
        checkoutComments: 'checkoutComments'
    };
    Object.keys(map).forEach(function (key) {
        const el = document.getElementById(map[key]);
        if (el && typeof draft[key] === 'string') {
            el.value = draft[key];
        }
    });
    const notesCount = document.getElementById('notesCount');
    const notes = document.getElementById('checkoutComments');
    if (notesCount && notes) notesCount.textContent = String(notes.value.length);
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = window.RAC_FLOW.requireVehicle('/rent-a-car.php');
    const extras = window.RAC_FLOW.getExtras();
    if (!ctx) return;
    if (!extras) {
        window.location.href = '/extras.php';
        return;
    }

    const rateType = sessionStorage.getItem('selectedRateType') || ctx.vehicle._selectedRateType || 'web';
    const startReserve = function(vehicle, criteria) {
        document.getElementById('reserveNoData').classList.add('d-none');
        document.getElementById('reserveMain').classList.remove('d-none');
        renderReservePage(vehicle, criteria, extras);
    };

    if (window.RAC_FLOW.isBarsCacheVehicle && window.RAC_FLOW.isBarsCacheVehicle(ctx.vehicle)) {
        window.RAC_FLOW.ensureBarsQuote(ctx.criteria, ctx.vehicle, rateType)
            .then(function (vehicle) { startReserve(vehicle, ctx.criteria); })
            .catch(function (err) {
                alert(err.message || 'La tarifa expiró. Vuelve a consultar disponibilidad.');
                window.location.href = window.RAC_FLOW.buildResultsUrl(ctx.criteria);
            });
        return;
    }

    startReserve(ctx.vehicle, ctx.criteria);
});

function renderReservePage(vehicle, criteria, extras) {
    const calendarDays = window.RAC_FLOW.calcDays(criteria.pickupDate, criteria.returnDate);
    const days = window.RAC_FLOW.vehicleBilledDays(vehicle, calendarDays);
    const totals = extras.totals || {};
    const covLabel = extras.protection === 'NONE'
        ? 'Sin protección adicional'
        : (extras.coverage_name || extras.protection || 'Protección');
    const mandatoryLines = (extras.mandatoryCharges || []).map(function (c) {
        return '<div class="d-flex justify-content-between"><span>' + c.label + '</span><span>' +
            window.RAC_FLOW.fmtMoney(c.amount) + '</span></div>';
    }).join('');
    const img = window.RAC_FLOW.resolveImage(vehicle.image);

    document.getElementById('reserveSidebar').innerHTML = `
        ${img ? `<img src="${img}" class="img-fluid mb-3" style="max-height:100px;object-fit:contain">` : ''}
        <h5 class="fw-bold text-navy">${vehicle.name || ''}</h5>
        <p class="text-muted small">${vehicle.category || ''} · ${vehicle.transmission || 'Automática'}</p>
        <hr>
        <div class="small mb-2"><span class="text-danger fw-semibold">RECOGIDA</span><br>
            ${window.RAC_FLOW.branchLabel(criteria.locationCode)}<br>
            ${window.RAC_FLOW.formatDateDisplay(criteria.pickupDate)} ${window.RAC_FLOW.formatTimeDisplay(criteria.pickupTime)}</div>
        <div class="small mb-3"><span class="text-danger fw-semibold">DEVOLUCIÓN</span><br>
            ${window.RAC_FLOW.branchLabel(criteria.returnLocationCode || criteria.locationCode)}<br>
            ${window.RAC_FLOW.formatDateDisplay(criteria.returnDate)} ${window.RAC_FLOW.formatTimeDisplay(criteria.returnTime)}</div>
        <span class="badge bg-warning text-dark mb-3">${days} días</span>
        <div class="small d-flex flex-column gap-2">
            <div class="d-flex justify-content-between"><span>Tarifa base</span><span>${window.RAC_FLOW.fmtMoney(totals.base)}</span></div>
            ${totals.saf > 0 ? `<div class="d-flex justify-content-between"><span>SAF</span><span>${window.RAC_FLOW.fmtMoney(totals.saf)}</span></div>` : ''}
            ${mandatoryLines}
            ${extras.protection && extras.protection !== 'NONE' ? `<div class="d-flex justify-content-between"><span>${covLabel}</span><span>${window.RAC_FLOW.fmtMoney(totals.coverage)}</span></div>` : ''}
            ${totals.drivers > 0 ? `<div class="d-flex justify-content-between"><span>Conductor adicional</span><span>${window.RAC_FLOW.fmtMoney(totals.drivers)}</span></div>` : ''}
            ${totals.equipment > 0 ? `<div class="d-flex justify-content-between"><span>Otros extras</span><span>${window.RAC_FLOW.fmtMoney(totals.equipment)}</span></div>` : ''}
            <div class="d-flex justify-content-between"><span>ITBMS (7%)</span><span>${window.RAC_FLOW.fmtMoney(totals.itbms)}</span></div>
        </div>
        <hr>
        <div class="d-flex justify-content-between fw-bold fs-5 text-navy">
            <span>Total estimado</span><span>${window.RAC_FLOW.fmtMoney(totals.total)}</span>
        </div>
        <p class="text-muted mt-2" style="font-size:0.75rem;">* El cargo final puede variar según servicios en mostrador. Impuestos incluidos.</p>`;

    const notes = document.getElementById('checkoutComments');
    const notesCount = document.getElementById('notesCount');
    notes.addEventListener('input', () => { notesCount.textContent = notes.value.length; saveDriverDraft(); });

    const birthDateInput = document.getElementById('birthDate');
    birthDateInput.max = panamaTodayIso();
    birthDateInput.addEventListener('input', function () {
        setBirthDateValidity(birthDateInput);
        saveDriverDraft();
    });
    birthDateInput.addEventListener('change', function () {
        setBirthDateValidity(birthDateInput);
        saveDriverDraft();
    });

    document.getElementById('emailConfirm').addEventListener('input', function() {
        if (this.value && this.value !== document.getElementById('checkoutEmail').value) {
            this.setCustomValidity('no-match');
        } else {
            this.setCustomValidity('');
        }
        saveDriverDraft();
    });

    [
        'firstName', 'lastName', 'checkoutEmail', 'phonePrefix', 'checkoutPhone',
        'docType', 'docNumber', 'countryCode', 'flightNumber', 'airlineCode'
    ].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', saveDriverDraft);
        if (el) el.addEventListener('change', saveDriverDraft);
    });

    restoreDriverDraft();
    setBirthDateValidity(birthDateInput);
    document.getElementById('checkoutBookingForm').addEventListener('submit', submitCheckoutBooking);
}

function submitCheckoutBooking(e) {
    e.preventDefault();
    const form = e.target;
    const email = document.getElementById('checkoutEmail').value.trim();
    const emailConfirm = document.getElementById('emailConfirm').value.trim();
    if (email !== emailConfirm) {
        document.getElementById('emailConfirm').setCustomValidity('no-match');
    }
    const birthDateInput = document.getElementById('birthDate');
    setBirthDateValidity(birthDateInput);
    saveDriverDraft();
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }

    const vehicle = window.RAC_FLOW.getVehicle();
    const criteria = window.RAC_FLOW.getCriteria();
    const extras = window.RAC_FLOW.getExtras();
    const rateType = sessionStorage.getItem('selectedRateType') || 'web';
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const birthDate = birthDateInput.value.trim();

    const payload = {
        first_name: firstName,
        last_name: lastName,
        customer_name: firstName + ' ' + lastName,
        customer_email: email,
        email_confirm: emailConfirm,
        email: email,
        customer_phone: document.getElementById('checkoutPhone').value.trim(),
        phone_prefix: document.getElementById('phonePrefix').value,
        doc_type: document.getElementById('docType').value,
        doc_number: document.getElementById('docNumber').value.trim(),
        country_code: document.getElementById('countryCode').value,
        birth_date: birthDate,
        flight_number: document.getElementById('flightNumber').value.trim(),
        airline_code: document.getElementById('airlineCode').value.trim(),
        customer_comments: document.getElementById('checkoutComments').value.trim(),
        remarks: document.getElementById('checkoutComments').value.trim(),
        coverage_code: extras.protection === 'NONE' ? '' : extras.protection,
        coverage_name: extras.protection === 'NONE' ? 'Sin protección adicional' : (extras.coverage_name || ''),
        coverage_amount: extras.totals?.coverage,
        coverage_deductible: extras.coverage_deductible,
        price_rental_base: extras.totals?.base,
        price_saf: extras.totals?.saf,
        price_itbms: extras.totals?.itbms,
        price_total_estimated: extras.totals?.total,
        rate_type: rateType,
        rate_quote_token: vehicle?.pricing?.barsQuoteToken || '',
        extras: extras,
        search: criteria,
        vehicle: vehicle
    };

    const loader = document.createElement('div');
    loader.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white';
    loader.style.cssText = 'z-index:99999;background:rgba(8,16,38,.9);backdrop-filter:blur(6px)';
    loader.innerHTML = '<div class="spinner-border text-danger" style="width:3.5rem;height:3.5rem"></div><h3 class="mt-4 fw-bold">Procesando su reserva…</h3>';
    document.body.appendChild(loader);

    const postCheckout = function (body) {
        return fetch('/api/rac-checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
        .then(({ ok, data }) => {
            loader.remove();
            if (!ok || !data.success) {
                alert(data.message || 'No se pudo iniciar el pago.');
                return;
            }
            if (data.checkout_token) {
                sessionStorage.setItem('racCheckoutToken', data.checkout_token);
            }
            window.location.href = data.redirect || ('/pago.php?token=' + encodeURIComponent(data.checkout_token || ''));
        })
        .catch(() => {
            loader.remove();
            alert('Error de conexión. Intente nuevamente.');
        })
        .finally(function () {
            const existing = document.querySelector('[style*="z-index:99999"]');
            if (existing) existing.remove();
        });
    };

    if (window.AmCaptcha && typeof window.AmCaptcha.withPayload === 'function') {
        window.AmCaptcha.withPayload(payload).then(postCheckout).catch(function () {
            loader.remove();
        });
        return;
    }
    postCheckout(payload);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
