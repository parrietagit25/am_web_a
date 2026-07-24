<?php
/**
 * Lab RAC — ciclo completo (buscar → reservar → consultar).
 * Fuera del CMS y del admin. URL directa: /lab/rac-ciclo.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/lab-rac-auth.php';

lab_rac_require_access();

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$pickupDefault = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$returnDefault = (new DateTimeImmutable('+3 days'))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Lab RAC — ciclo de reserva</title>
  <style>
    :root {
      --bg: #0b1220;
      --panel: #152238;
      --panel2: #1a2b45;
      --line: #2a3b5c;
      --text: #e8eefc;
      --muted: #9bb0d0;
      --accent: #c51f17;
      --ok: #1f9d63;
      --warn: #d4a017;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, sans-serif;
      background: radial-gradient(1200px 600px at 10% -10%, #1e3358 0%, var(--bg) 55%);
      color: var(--text);
      min-height: 100vh;
    }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px 16px 64px; }
    h1 { margin: 0 0 6px; font-size: 1.55rem; }
    .sub { color: var(--muted); margin: 0 0 18px; font-size: .95rem; }
    .banner {
      background: #3a220f;
      border: 1px solid #8a5a22;
      color: #ffd9a8;
      padding: 12px 14px;
      border-radius: 12px;
      margin-bottom: 18px;
      font-size: .9rem;
    }
    .steps {
      display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px;
    }
    .step-btn {
      background: var(--panel); border: 1px solid var(--line); color: var(--muted);
      padding: 10px 14px; border-radius: 999px; cursor: pointer; font-weight: 600;
    }
    .step-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
    .panel {
      background: var(--panel); border: 1px solid var(--line); border-radius: 16px;
      padding: 18px; margin-bottom: 16px;
    }
    .grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    @media (max-width: 900px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }
    label { display: block; font-size: .78rem; color: var(--muted); margin-bottom: 4px; }
    input, select, textarea, button {
      font: inherit; width: 100%;
    }
    input, select, textarea {
      background: #0f1a2d; color: var(--text); border: 1px solid var(--line);
      border-radius: 10px; padding: 10px 12px;
    }
    textarea { min-height: 72px; resize: vertical; }
    .btn {
      background: var(--accent); color: #fff; border: 0; border-radius: 10px;
      padding: 12px 16px; font-weight: 700; cursor: pointer; width: auto;
    }
    .btn.secondary { background: #31466b; }
    .btn.ok { background: var(--ok); }
    .btn:disabled { opacity: .55; cursor: not-allowed; }
    .row-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
    .meta { color: var(--muted); font-size: .85rem; margin-top: 10px; }
    .meta code { color: #cde0ff; }
    .vehicles { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
    .vcard {
      background: var(--panel2); border: 1px solid var(--line); border-radius: 14px;
      padding: 12px; display: flex; flex-direction: column; gap: 8px;
    }
    .vcard.selected { border-color: #4ea1ff; box-shadow: 0 0 0 1px #4ea1ff inset; }
    .vcard img {
      width: 100%; height: 130px; object-fit: contain; background: #0c1526;
      border-radius: 10px;
    }
    .vcard h3 { margin: 0; font-size: 1rem; }
    .price { font-size: 1.15rem; font-weight: 700; color: #ffb4ae; }
    .badge {
      display: inline-block; font-size: .72rem; padding: 3px 8px; border-radius: 999px;
      background: #243552; color: #bcd0f0;
    }
    .log {
      background: #0a1220; border: 1px solid var(--line); border-radius: 12px;
      padding: 12px; max-height: 320px; overflow: auto; font-family: ui-monospace, Consolas, monospace;
      font-size: .78rem; white-space: pre-wrap; word-break: break-word;
    }
    .hidden { display: none !important; }
    .ok-text { color: #7dffa8; }
    .err-text { color: #ff8f8f; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Lab RAC — ciclo completo</h1>
    <p class="sub">Sandbox fuera de la web y del admin. Buscar → elegir → reservar → consultar. Cuando confirmes visualmente, migramos al flujo público.</p>

    <div class="banner">
      <strong>Atención:</strong> por defecto reserva/consulta van a <em>BARS SOAP (RentWorks)</em>, no a DigitalOcean.
      “Reservar real” crea reserva en RentWorks. Usa primero <em>Dry-run</em>.
    </div>

    <section class="panel">
      <div class="grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
        <div>
          <label for="backend">Backend reserva / consulta</label>
          <select id="backend">
            <option value="bars" selected>BARS SOAP local (RentWorks)</option>
            <option value="partner">Partner DigitalOcean (legado)</option>
          </select>
        </div>
        <div>
          <label>Estado</label>
          <p class="meta" id="backendMeta" style="margin:8px 0 0">Cargando…</p>
        </div>
      </div>
    </section>

    <div class="steps">
      <button type="button" class="step-btn active" data-step="1">1. Buscar</button>
      <button type="button" class="step-btn" data-step="2">2. Elegir</button>
      <button type="button" class="step-btn" data-step="3">3. Reservar</button>
      <button type="button" class="step-btn" data-step="4">4. Consultar</button>
    </div>

    <section class="panel" id="step1">
      <div class="grid">
        <div>
          <label for="locationCode">Retiro</label>
          <select id="locationCode"></select>
        </div>
        <div>
          <label for="returnLocationCode">Devolución</label>
          <select id="returnLocationCode"></select>
        </div>
        <div>
          <label for="pickupDate">Fecha retiro</label>
          <input type="date" id="pickupDate" value="<?= htmlspecialchars($pickupDefault) ?>">
        </div>
        <div>
          <label for="returnDate">Fecha devolución</label>
          <input type="date" id="returnDate" value="<?= htmlspecialchars($returnDefault) ?>">
        </div>
        <div>
          <label for="pickupTime">Hora retiro</label>
          <input type="time" id="pickupTime" value="10:00">
        </div>
        <div>
          <label for="returnTime">Hora devolución</label>
          <input type="time" id="returnTime" value="10:00">
        </div>
        <div>
          <label for="age">Edad</label>
          <select id="age">
            <option value="25">25+</option>
            <option value="23">23–24</option>
          </select>
        </div>
        <div>
          <label for="promoCode">Promo (opcional)</label>
          <input type="text" id="promoCode" placeholder="Código">
        </div>
      </div>
      <div class="row-actions">
        <button type="button" class="btn" id="btnSearch">Buscar disponibilidad</button>
        <button type="button" class="btn secondary" id="btnStatus">Estado lab</button>
      </div>
      <p class="meta" id="searchMeta">Sin búsqueda aún.</p>
    </section>

    <section class="panel hidden" id="step2">
      <div id="vehicles" class="vehicles"></div>
      <p class="meta" id="selectMeta">Elige un vehículo para continuar.</p>
      <div class="row-actions">
        <button type="button" class="btn secondary" data-goto="1">← Volver</button>
        <button type="button" class="btn" id="btnToReserve" disabled>Continuar a reservar →</button>
      </div>
    </section>

    <section class="panel hidden" id="step3">
      <p class="meta" id="chosenMeta">Sin vehículo.</p>
      <div class="grid">
        <div>
          <label for="first_name">Nombre</label>
          <input id="first_name" value="Lab">
        </div>
        <div>
          <label for="last_name">Apellido</label>
          <input id="last_name" value="TestCiclo">
        </div>
        <div>
          <label for="email">Email</label>
          <input type="email" id="email" value="lab.rac@example.com">
        </div>
        <div>
          <label for="phone">Teléfono</label>
          <input id="phone" value="60000000">
        </div>
        <div>
          <label for="phone_prefix">Prefijo</label>
          <input id="phone_prefix" value="+507">
        </div>
        <div>
          <label for="birth_date">Fecha nacimiento</label>
          <input type="date" id="birth_date" value="1995-01-15">
        </div>
        <div>
          <label for="doc_number">Documento</label>
          <input id="doc_number" value="">
        </div>
        <div>
          <label for="rate_type">Canal tarifa</label>
          <select id="rate_type">
            <option value="web">web</option>
            <option value="counter">counter</option>
          </select>
        </div>
        <div style="grid-column: 1 / -1;">
          <label for="remarks">Observaciones</label>
          <textarea id="remarks">Prueba laboratorio ciclo RAC</textarea>
        </div>
      </div>
      <div class="row-actions">
        <button type="button" class="btn secondary" data-goto="2">← Volver</button>
        <button type="button" class="btn secondary" id="btnDryRun">Dry-run (no envía)</button>
        <button type="button" class="btn ok" id="btnReserve">Reservar REAL (confirm=RESERVAR)</button>
      </div>
      <p class="meta" id="reserveMeta"></p>
    </section>

    <section class="panel hidden" id="step4">
      <div class="grid">
        <div>
          <label for="lookup_code">Código confirmación</label>
          <input id="lookup_code" placeholder="PCR-...">
        </div>
        <div>
          <label for="lookup_last_name">Apellido</label>
          <input id="lookup_last_name" value="TestCiclo">
        </div>
      </div>
      <div class="row-actions">
        <button type="button" class="btn" id="btnLookup">Consultar reserva</button>
        <button type="button" class="btn secondary" data-goto="1">Nueva búsqueda</button>
      </div>
      <p class="meta" id="lookupMeta"></p>
    </section>

    <section class="panel">
      <strong>Respuesta JSON</strong>
      <div class="log" id="log">Listo.</div>
    </section>
  </div>

  <script>
  const API = '/lab/rac-ciclo-api.php';
  const state = { search: null, vehicles: [], selected: null, lastConfirmation: null };
  function backend() { return $('backend').value || 'bars'; }

  function $(id) { return document.getElementById(id); }
  function showStep(n) {
    [1,2,3,4].forEach(i => {
      $('step' + i).classList.toggle('hidden', i !== n);
      document.querySelector('.step-btn[data-step="' + i + '"]').classList.toggle('active', i === n);
    });
  }
  function setLog(obj) {
    $('log').textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
  }
  async function api(action, body = {}, method = 'POST') {
    const opts = {
      method,
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      credentials: 'same-origin',
    };
    let url = API + '?action=' + encodeURIComponent(action);
    if (method === 'POST') {
      opts.body = JSON.stringify({ action, ...body });
    } else {
      const qs = new URLSearchParams({ action, ...body });
      url = API + '?' + qs.toString();
    }
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({ ok: false, error: 'JSON inválido' }));
    setLog(data);
    return data;
  }

  function money(v) {
    const n = Number(v);
    if (!Number.isFinite(n)) return '—';
    return '$' + n.toFixed(2);
  }

  function vehiclePrice(v) {
    const candidates = [
      v?.priceTotal,
      v?.pricing?.finalTotalRate,
      v?.pricing?.rateBase,
      v?.priceWeb,
      v?.pricing?.finalDailyRate,
      v?.pricing?.total,
      v?.total,
      v?.price,
      v?.rates?.[0]?.total,
    ];
    for (const c of candidates) {
      const n = Number(c);
      if (Number.isFinite(n) && n > 0) return n;
    }
    return null;
  }

  function vehicleDaily(v) {
    const n = Number(v?.priceWeb ?? v?.pricing?.finalDailyRate ?? null);
    return Number.isFinite(n) && n > 0 ? n : null;
  }

  function renderVehicles(list) {
    const box = $('vehicles');
    box.innerHTML = '';
    if (!list.length) {
      box.innerHTML = '<p class="meta">Sin vehículos en la respuesta.</p>';
      return;
    }
    list.forEach((v) => {
      const el = document.createElement('article');
      el.className = 'vcard' + (state.selected === v ? ' selected' : '');
      const img = v.imageUrl || v.image || v.thumbnail || '';
      const total = vehiclePrice(v);
      const daily = vehicleDaily(v);
      const priceLabel = daily
        ? `${money(daily)}/día · total ${money(total)}`
        : money(total);
      el.innerHTML = `
        ${img ? `<img src="${img}" alt="" loading="lazy">` : ''}
        <h3>${esc(v.name || v.vehicleName || v.sippCode || 'Vehículo')}</h3>
        <div><span class="badge">${esc(v.sippCode || 'SIPP')}</span>
             <span class="badge">${esc(v.rateCode || v.pricing?.rateCode || '')}</span></div>
        <div class="price">${priceLabel}</div>
        <button type="button" class="btn secondary">Seleccionar</button>
      `;
      el.querySelector('button').addEventListener('click', () => {
        state.selected = v;
        $('selectMeta').textContent = 'Seleccionado: ' + (v.name || v.sippCode);
        $('btnToReserve').disabled = false;
        $('chosenMeta').textContent = 'Vehículo: ' + (v.name || v.sippCode) + ' · ' + priceLabel;
        renderVehicles(list);
      });
      box.appendChild(el);
    });
  }

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    })[c]);
  }

  function fillBranches(branches) {
    const a = $('locationCode');
    const b = $('returnLocationCode');
    a.innerHTML = '';
    b.innerHTML = '';
    (branches || []).forEach(br => {
      const o1 = new Option(br.shortName || br.name || br.code, br.code);
      const o2 = new Option(br.shortName || br.name || br.code, br.code);
      a.add(o1);
      b.add(o2);
    });
    if ([...a.options].some(o => o.value === 'PTY')) a.value = 'PTY';
    if ([...b.options].some(o => o.value === 'PTY')) b.value = 'PTY';
  }

  document.querySelectorAll('.step-btn').forEach(btn => {
    btn.addEventListener('click', () => showStep(Number(btn.dataset.step)));
  });
  document.querySelectorAll('[data-goto]').forEach(btn => {
    btn.addEventListener('click', () => showStep(Number(btn.dataset.goto)));
  });

  $('btnToReserve').addEventListener('click', () => {
    if (!state.selected) return;
    if (!$('doc_number').value) {
      $('doc_number').value = 'LAB-' + Date.now().toString().slice(-8);
    }
    showStep(3);
  });

  $('btnStatus').addEventListener('click', async () => {
    await api('status', {}, 'GET');
  });

  $('btnSearch').addEventListener('click', async () => {
    $('btnSearch').disabled = true;
    $('searchMeta').textContent = 'Buscando…';
    try {
      const data = await api('search', {
        locationCode: $('locationCode').value,
        returnLocationCode: $('returnLocationCode').value,
        pickupDate: $('pickupDate').value,
        pickupTime: $('pickupTime').value,
        returnDate: $('returnDate').value,
        returnTime: $('returnTime').value,
        age: $('age').value,
        promoCode: $('promoCode').value,
      });
      state.search = data.search || null;
      state.vehicles = Array.isArray(data.vehicles) ? data.vehicles : [];
      state.selected = null;
      $('btnToReserve').disabled = true;
      $('searchMeta').innerHTML = data.ok
        ? `<span class="ok-text">OK</span> path=<code>${esc(data.path)}</code> · ${data.count||0} vehículos · ${data.elapsed_ms||'?'} ms`
        : `<span class="err-text">${esc(data.error || data.message || 'Error')}</span>`;
      renderVehicles(state.vehicles);
      if (data.ok) showStep(2);
    } finally {
      $('btnSearch').disabled = false;
    }
  });

  async function doReserve(dryRun) {
    if (!state.selected || !state.search) {
      $('reserveMeta').innerHTML = '<span class="err-text">Falta búsqueda/vehículo.</span>';
      return;
    }
    const body = {
      backend: backend(),
      dry_run: dryRun ? 1 : 0,
      confirm: dryRun ? '' : 'RESERVAR',
      first_name: $('first_name').value,
      last_name: $('last_name').value,
      email: $('email').value,
      phone: $('phone').value,
      phone_prefix: $('phone_prefix').value,
      birth_date: $('birth_date').value,
      doc_number: $('doc_number').value,
      rate_type: $('rate_type').value,
      remarks: $('remarks').value,
      search: state.search,
      vehicle: state.selected,
    };
    $('reserveMeta').textContent = dryRun
      ? 'Armando payload/OTA…'
      : ('Enviando reserva REAL vía ' + backend() + '…');
    const data = await api('reserve', body);
    if (data.ok && data.confirmation) {
      state.lastConfirmation = data.confirmation;
      $('lookup_code').value = data.confirmation;
      $('lookup_last_name').value = $('last_name').value;
      $('reserveMeta').innerHTML = `<span class="ok-text">Confirmación: ${esc(data.confirmation)}</span> · backend=${esc(data.backend||backend())}`;
      showStep(4);
    } else if (data.ok && data.dry_run) {
      $('reserveMeta').innerHTML = `<span class="ok-text">Dry-run OK (${esc(data.backend||backend())})</span> — revisa JSON/OTA.`;
    } else {
      $('reserveMeta').innerHTML = `<span class="err-text">${esc(data.error || 'Falló')}</span>`;
    }
  }

  $('btnDryRun').addEventListener('click', () => doReserve(true));
  $('btnReserve').addEventListener('click', () => {
    const be = backend();
    if (!confirm('¿Crear reserva REAL vía ' + be + ' (RentWorks/BARS o Partner)?')) return;
    doReserve(false);
  });

  $('btnLookup').addEventListener('click', async () => {
    $('lookupMeta').textContent = 'Consultando…';
    const data = await api('lookup', {
      backend: backend(),
      reservation_code: $('lookup_code').value,
      last_name: $('lookup_last_name').value,
    });
    $('lookupMeta').innerHTML = data.ok
      ? `<span class="ok-text">Reserva encontrada</span> · ${esc(data.path || data.backend || '')}`
      : `<span class="err-text">${esc(data.error || 'No encontrada')}</span>`;
  });

  (async function init() {
    const st = await api('status', {}, 'GET');
    const br = await api('branches', {}, 'GET');
    fillBranches(br.branches || []);
    $('searchMeta').textContent = st.ok
      ? `Partner: ${st.partner_configured ? 'OK' : 'NO'} · BARS pricing: ${st.bars_pricing_enabled ? 'ON' : 'OFF'}`
      : 'No se pudo leer estado';
    $('backendMeta').textContent = st.ok
      ? `BARS reserva SOAP: ${st.bars_reservation_configured ? 'OK' : 'NO'} · default=${st.default_reserve_backend || 'bars'}`
      : 'Sin estado';
  })();
  </script>
</body>
</html>
