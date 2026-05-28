import { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../components/Header';
import Footer from '../components/Footer';
import Icon from '../components/Icon';
import { SUCURSALES, HORAS, EDADES } from '../utils/constants';
import { buildSearchUrl } from '../utils/urlParams';

// ─── Data ─────────────────────────────────────────────────────────────────────

const STATS = [
  { value: 12500, suffix: '+', label: 'Viajeros felices',        sub: 'Reservas completadas' },
  { value: 4.9,   suffix: '★', label: 'Calificación promedio',   sub: 'Verificado por Google', decimals: 1 },
  { value: 18,    suffix: '',  label: 'Sucursales',              sub: 'A nivel nacional' },
  { value: 15,    suffix: '+', label: 'Modelos disponibles',     sub: 'Flota renovada 2026' },
];

const TESTIMONIOS = [
  { name: 'Andrés R.', country: 'Colombia',       initials: 'AR', sucursal: 'Aeropuerto Tocumen', date: 'Marzo 2026',     rating: 5, text: 'Proceso de alquiler muy rápido. El Hyundai Tucson estaba impecable. Definitivamente volvería con Automarket.' },
  { name: 'Erwin V.',  country: 'Países Bajos',   initials: 'EV', sucursal: 'Albrook',             date: 'Febrero 2026',   rating: 5, text: 'Excellent service and clean vehicles. The RAV4 was perfect for exploring the countryside around Panama City.' },
  { name: 'María P.',  country: 'Panamá',          initials: 'MP', sucursal: 'David',               date: 'Enero 2026',     rating: 5, text: 'El equipo de David fue muy amable. El Accent estaba en perfectas condiciones y el precio fue muy conveniente.' },
  { name: 'Robert K.', country: 'Estados Unidos',  initials: 'RK', sucursal: 'Aeropuerto Tocumen', date: 'Diciembre 2025', rating: 5, text: 'Best car rental in Panama! Got the GAC GS8 — it was spacious and perfect for my family trip. Will come back!' },
  { name: 'Lucía M.',  country: 'España',          initials: 'LM', sucursal: 'Albrook',             date: 'Noviembre 2025', rating: 5, text: 'Servicio impecable. El Kia Carnival era justo lo que necesitábamos para todo el grupo. Sin sorpresas en la factura.' },
];

const PROMO_CARDS = [
  { img: '/images/promo-amas.webp',    title: 'Automarket Assistance',    subtitle: 'Cobertura integral AMAS', cta: 'Ver cobertura',      href: null },
  { img: '/images/promo-flytrip.webp', title: 'FlyTrip — Vuela + Alquila', subtitle: 'Beneficio exclusivo',   cta: 'Ver beneficio',      href: null },
  { img: '/images/promo-holafly.webp', title: 'HolaFly — eSIM Internacional', subtitle: '10% de descuento al alquilar', cta: 'Obtener descuento', href: 'https://esim.holafly.com' },
];

// ─── Schedule helpers (unchanged) ─────────────────────────────────────────────

const DAY_KEYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
const DAY_LABEL_ES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

function isDayClosed(sucursal, dateStr, kind) {
  if (!sucursal || !dateStr) return false;
  const weekday = new Date(`${dateStr}T00:00:00`).getDay();
  const slot = sucursal.dailyHours?.[DAY_KEYS[weekday]];
  if (slot == null) return true;
  if (kind === 'return' && (sucursal.closedReturnDays || []).includes(weekday)) return true;
  return false;
}

function findNextOpenDate(sucursal, fromDateStr, kind, maxDays = 14) {
  if (!sucursal) return fromDateStr;
  for (let i = 0; i <= maxDays; i++) {
    const d = new Date(`${fromDateStr}T00:00:00`);
    d.setDate(d.getDate() + i);
    const iso = d.toISOString().split('T')[0];
    if (!isDayClosed(sucursal, iso, kind)) return iso;
  }
  return null;
}

function generateTimeOptions(dailyHours, dateStr, fallback) {
  if (!dailyHours || !dateStr) return fallback;
  const date = new Date(`${dateStr}T00:00:00`);
  const slot = dailyHours[DAY_KEYS[date.getDay()]];
  if (!slot) return [];
  const [oh, om] = slot.open.split(':').map(Number);
  const [ch, cm] = slot.close.split(':').map(Number);
  const opts = [];
  let h = oh, m = om;
  while (h < ch || (h === ch && m <= cm)) {
    const period = h < 12 ? 'a.m.' : 'p.m.';
    const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
    opts.push({ value: `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`, display: `${h12}:${m.toString().padStart(2, '0')} ${period}` });
    m += 15;
    if (m >= 60) { m -= 60; h++; }
  }
  return opts;
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const inputBase = {
  width: '100%', padding: '10px 14px', borderRadius: 10,
  border: '2px solid var(--gray-200)', fontSize: 13, color: 'var(--navy)',
  background: '#fff', appearance: 'none', outline: 'none',
  transition: 'border-color .15s, box-shadow .15s', cursor: 'pointer', fontFamily: 'inherit',
};

// Halo aplicado cuando el campo tiene focus — feedback visual extra al borderColor
const focusHalo = '0 0 0 3px rgba(190,28,40,.08)';

const chevronBg = `url("data:image/svg+xml,%3Csvg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' xmlns='http://www.w3.org/2000/svg'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")`;

// ─── StickySearchBar ──────────────────────────────────────────────────────────

function StickySearchBar({ visible, locationName, pickupDate, returnDate, onSearch }) {
  return (
    <div style={{
      position: 'fixed', top: 0, left: 0, right: 0, zIndex: 900,
      background: '#fff', boxShadow: '0 4px 20px rgba(0,0,0,.1)',
      transform: visible ? 'translateY(0)' : 'translateY(-110%)',
      transition: 'transform .3s cubic-bezier(.16,1,.3,1)',
    }}>
      <div style={{ maxWidth: 1200, margin: '0 auto', padding: '10px 24px', display: 'flex', alignItems: 'center', gap: 14 }}>
        <img src="/images/logo-am.svg" alt="Automarket" style={{ height: 28, width: 'auto', flexShrink: 0 }} onError={e => { e.currentTarget.src = '/logo.png'; }} />
        <div style={{ flex: 1, display: 'flex', alignItems: 'center', gap: 10, background: 'var(--gray-50)', borderRadius: 9, padding: '7px 14px', border: '1px solid var(--gray-200)', minWidth: 0 }}>
          <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
            {locationName || 'Selecciona una sucursal'}
          </span>
          {pickupDate && returnDate && (
            <>
              <span style={{ color: 'var(--gray-300)', flexShrink: 0 }}>·</span>
              <span style={{ fontSize: 12, color: 'var(--gray-500)', whiteSpace: 'nowrap' }}>{pickupDate} → {returnDate}</span>
            </>
          )}
        </div>
        <button
          onClick={() => { document.getElementById('search-form-card')?.scrollIntoView({ behavior: 'smooth' }); }}
          style={{ fontSize: 12, fontWeight: 600, color: 'var(--navy)', border: '1px solid var(--gray-200)', borderRadius: 8, padding: '7px 14px', background: '#fff', cursor: 'pointer', whiteSpace: 'nowrap', fontFamily: 'inherit', flexShrink: 0 }}
        >
          Modificar
        </button>
        <button
          onClick={onSearch}
          style={{ background: 'var(--red)', color: '#fff', border: 'none', borderRadius: 8, padding: '8px 18px', fontSize: 13, fontWeight: 700, cursor: 'pointer', whiteSpace: 'nowrap', fontFamily: 'inherit', flexShrink: 0 }}
        >
          Buscar vehículos
        </button>
      </div>
    </div>
  );
}

// ─── Form fields (unchanged) ───────────────────────────────────────────────────

function FieldLabel({ icon, label, htmlFor }) {
  return (
    <label htmlFor={htmlFor} style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 6 }}>
      <span style={{ color: 'var(--red)' }}><Icon type={icon} size={14} /></span>
      <span style={{ fontSize: 11, fontWeight: 600, color: 'var(--gray-500)', textTransform: 'uppercase', letterSpacing: '.5px' }}>{label}</span>
    </label>
  );
}

function SelectField({ icon, label, value, onChange, options, placeholder }) {
  const [focused, setFocused] = useState(false);
  const id = label.toLowerCase().replace(/\s+/g, '-');
  return (
    <div style={{ flex: 1, minWidth: 0 }}>
      <FieldLabel icon={icon} label={label} htmlFor={id} />
      <div style={{ position: 'relative' }}>
        <select
          id={id}
          value={value}
          onChange={e => onChange(e.target.value)}
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
          style={{ ...inputBase, borderColor: focused ? 'var(--red)' : 'var(--gray-200)', boxShadow: focused ? focusHalo : 'none', backgroundImage: chevronBg, backgroundRepeat: 'no-repeat', backgroundPosition: 'right 12px center', paddingRight: 32 }}
        >
          <option value="">{placeholder}</option>
          {options.map(o => (
            <option key={o.value || o} value={o.value || o}>{o.display || o}</option>
          ))}
        </select>
      </div>
    </div>
  );
}

function DateField({ icon, label, value, onChange, min, note }) {
  const [focused, setFocused] = useState(false);
  const id = label.toLowerCase().replace(/\s+/g, '-');
  return (
    <div style={{ flex: 1, minWidth: 0 }}>
      <FieldLabel icon={icon} label={label} htmlFor={id} />
      <input id={id} type="date" value={value} min={min} onChange={e => onChange(e.target.value)} onFocus={() => setFocused(true)} onBlur={() => setFocused(false)} style={{ ...inputBase, borderColor: focused ? 'var(--red)' : 'var(--gray-200)', boxShadow: focused ? focusHalo : 'none' }} />
      {note && <div style={{ marginTop: 4, fontSize: 11, lineHeight: 1.4, color: 'var(--amber-dark, #b45309)', background: 'var(--amber-bg-light, #fef3c7)', padding: '4px 8px', borderRadius: 6 }}>{note}</div>}
    </div>
  );
}

function CustomCheckbox({ checked, onChange, label, id }) {
  return (
    <label htmlFor={id} style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13, color: 'var(--gray-600)', cursor: 'pointer', userSelect: 'none' }}>
      <input id={id} type="checkbox" checked={checked} onChange={e => onChange(e.target.checked)} style={{ position: 'absolute', opacity: 0, width: 1, height: 1, overflow: 'hidden', clip: 'rect(0,0,0,0)', whiteSpace: 'nowrap' }} />
      <div aria-hidden="true" style={{ width: 18, height: 18, borderRadius: 5, border: `2px solid ${checked ? 'var(--red)' : 'var(--gray-300)'}`, background: checked ? 'var(--red)' : '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'all .15s', flexShrink: 0 }}>
        {checked && <Icon type="check" size={10} color="#fff" />}
      </div>
      {label}
    </label>
  );
}

// ─── StatsBand ────────────────────────────────────────────────────────────────

function CountUpStat({ stat, started }) {
  const [val, setVal] = useState(0);
  const frameRef = useRef(null);
  useEffect(() => {
    if (!started) return;
    const duration = 1800;
    const startTime = performance.now();
    const tick = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      setVal(parseFloat((eased * stat.value).toFixed(stat.decimals || 0)));
      if (progress < 1) frameRef.current = requestAnimationFrame(tick);
    };
    frameRef.current = requestAnimationFrame(tick);
    return () => { if (frameRef.current) cancelAnimationFrame(frameRef.current); };
  }, [started, stat.value, stat.decimals]);

  const display = stat.decimals ? val.toFixed(stat.decimals) : Math.round(val).toLocaleString('en-US');
  return (
    <div style={{ textAlign: 'center', position: 'relative', zIndex: 1 }}>
      <div style={{ fontSize: 'clamp(28px, 3.5vw, 36px)', fontWeight: 800, color: 'var(--navy)', letterSpacing: -1, lineHeight: 1, marginBottom: 6, fontVariantNumeric: 'tabular-nums' }}>
        {display}<span style={{ color: 'var(--red)' }}>{stat.suffix}</span>
      </div>
      <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--gray-700)', marginBottom: 2 }}>{stat.label}</div>
      {stat.sub && <div style={{ fontSize: 11, color: 'var(--gray-400)' }}>{stat.sub}</div>}
    </div>
  );
}

function StatsBand() {
  const [started, setStarted] = useState(false);
  const ref = useRef(null);
  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting) { setStarted(true); obs.disconnect(); } },
      { threshold: 0.3 }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);
  return (
    <div ref={ref} className="r-stats-band" style={{
      background: '#fff', borderRadius: 18, padding: '28px 32px',
      boxShadow: '0 4px 24px rgba(26,35,70,.06)',
      margin: '28px 0 40px',
      display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 24,
      position: 'relative', overflow: 'hidden',
    }}>
      <div style={{ position: 'absolute', inset: 0, background: 'radial-gradient(ellipse at top right, rgba(190,28,40,.04), transparent 50%)', pointerEvents: 'none' }} />
      {STATS.map((stat, i) => (
        <CountUpStat key={i} stat={stat} started={started} />
      ))}
    </div>
  );
}

// ─── FlotaSection ─────────────────────────────────────────────────────────────

function CarSilhouette() {
  return (
    <svg viewBox="0 0 240 110" xmlns="http://www.w3.org/2000/svg" style={{ width: '90%', maxWidth: 200, position: 'relative', zIndex: 1, opacity: .45 }}>
      <defs>
        <linearGradient id="carbody" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0%" stopColor="#9ca3af" />
          <stop offset="100%" stopColor="#4b5563" />
        </linearGradient>
      </defs>
      <ellipse cx="120" cy="98" rx="100" ry="6" fill="rgba(0,0,0,.15)" />
      <path d="M30 75 Q30 60 40 56 L70 50 Q85 30 110 28 L160 28 Q180 30 195 50 L215 56 Q225 60 225 75 L225 88 Q225 92 220 92 L195 92 Q193 100 183 100 Q173 100 171 92 L83 92 Q81 100 71 100 Q61 100 59 92 L35 92 Q30 92 30 88 Z" fill="url(#carbody)" />
      <path d="M88 50 L108 33 L155 33 L175 50 Z" fill="#1a2346" opacity=".55" />
      <line x1="130" y1="33" x2="130" y2="50" stroke="rgba(255,255,255,.4)" strokeWidth="1.5" />
      <circle cx="71" cy="93" r="11" fill="#1f2937" />
      <circle cx="71" cy="93" r="5" fill="#d1d5db" />
      <circle cx="183" cy="93" r="11" fill="#1f2937" />
      <circle cx="183" cy="93" r="5" fill="#d1d5db" />
      <ellipse cx="218" cy="68" rx="4" ry="3" fill="rgba(255,255,255,.6)" />
    </svg>
  );
}

function FleetCard({ v, onReserve }) {
  const [hov, setHov] = useState(false);
  const [imgError, setImgError] = useState(false);

  return (
    <div
      onMouseEnter={() => setHov(true)}
      onMouseLeave={() => setHov(false)}
      onClick={onReserve}
      className="r-flota-card"
      style={{
        background: '#fff', borderRadius: 16, overflow: 'hidden',
        boxShadow: hov ? '0 12px 32px rgba(26,35,70,.14)' : '0 1px 4px rgba(0,0,0,.06)',
        transform: hov ? 'translateY(-3px)' : 'translateY(0)',
        transition: 'all .3s cubic-bezier(.2,.8,.2,1)',
        cursor: 'pointer',
        scrollSnapAlign: 'start',
        flex: '0 0 clamp(240px, calc(33.333% - 14px), 300px)',
        flexShrink: 0,
      }}
    >
      {/* Imagen del vehículo — lo que el usuario quiere ver */}
      <div style={{ background: 'linear-gradient(160deg, #f8fafc 0%, #eef0f5 100%)', height: 180, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 18, position: 'relative', overflow: 'hidden' }}>
        {imgError || !v.image ? (
          <CarSilhouette />
        ) : (
          <img
            src={v.image}
            alt={v.name}
            onError={() => setImgError(true)}
            loading="lazy"
            style={{ maxWidth: '100%', maxHeight: '100%', objectFit: 'contain', transform: hov ? 'scale(1.05)' : 'scale(1)', transition: 'transform .4s cubic-bezier(.2,.8,.2,1)', filter: 'drop-shadow(0 6px 14px rgba(0,0,0,.10))' }}
          />
        )}
      </div>
      {/* Info mínima: categoría + nombre */}
      <div style={{ padding: '16px 18px 18px', textAlign: 'center' }}>
        <div style={{ fontSize: 10, fontWeight: 700, color: 'var(--gray-400)', textTransform: 'uppercase', letterSpacing: 1.2, marginBottom: 4 }}>{v.category}</div>
        <div style={{ fontSize: 15, fontWeight: 700, color: 'var(--navy)', lineHeight: 1.2 }}>{v.name}</div>
      </div>
    </div>
  );
}

function FlotaSection() {
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const trackRef = useRef(null);
  const pausedRef = useRef(false);

  useEffect(() => {
    fetch('/api/catalog')
      .then(r => r.json())
      .then(d => { setVehicles(d.vehicles || []); setLoading(false); })
      .catch(() => setLoading(false));
  }, []);

  const scroll = (dir) => {
    const track = trackRef.current;
    if (!track) return;
    const cardW = track.firstElementChild?.offsetWidth || 280;
    track.scrollBy({ left: dir * (cardW + 20), behavior: 'smooth' });
  };

  // Auto-scroll suave cada 4s, pausa en hover
  useEffect(() => {
    const id = setInterval(() => {
      if (pausedRef.current || !trackRef.current) return;
      const track = trackRef.current;
      const maxScroll = track.scrollWidth - track.clientWidth;
      if (track.scrollLeft >= maxScroll - 4) {
        track.scrollTo({ left: 0, behavior: 'smooth' });
      } else {
        const cardW = track.firstElementChild?.offsetWidth || 280;
        track.scrollBy({ left: cardW + 20, behavior: 'smooth' });
      }
    }, 4000);
    return () => clearInterval(id);
  }, []);

  const scrollToSearch = () => {
    document.getElementById('search-form-card')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <section id="flota" style={{ margin: '0 0 56px', scrollMarginTop: 24 }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', flexWrap: 'wrap', gap: 16, marginBottom: 24 }}>
        <div>
          <div style={{ fontSize: 11, fontWeight: 700, letterSpacing: 2, color: 'var(--red)', textTransform: 'uppercase', marginBottom: 6 }}>Nuestra Flota</div>
          <h2 style={{ fontSize: 'clamp(22px, 3vw, 30px)', fontWeight: 800, color: 'var(--navy)', lineHeight: 1.1, letterSpacing: -.5, margin: 0 }}>
            Descubre todos nuestros vehículos
          </h2>
        </div>
        <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
          <button onClick={() => scroll(-1)} aria-label="Anterior" style={{ width: 38, height: 38, borderRadius: '50%', border: '1px solid var(--gray-200)', background: '#fff', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'all .2s', flexShrink: 0, color: 'var(--navy)' }}
            onMouseEnter={e => { e.currentTarget.style.background = 'var(--navy)'; e.currentTarget.style.borderColor = 'var(--navy)'; e.currentTarget.style.color = '#fff'; }}
            onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.borderColor = 'var(--gray-200)'; e.currentTarget.style.color = 'var(--navy)'; }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button onClick={() => scroll(1)} aria-label="Siguiente" style={{ width: 38, height: 38, borderRadius: '50%', border: '1px solid var(--gray-200)', background: '#fff', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'all .2s', flexShrink: 0, color: 'var(--navy)' }}
            onMouseEnter={e => { e.currentTarget.style.background = 'var(--navy)'; e.currentTarget.style.borderColor = 'var(--navy)'; e.currentTarget.style.color = '#fff'; }}
            onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.borderColor = 'var(--gray-200)'; e.currentTarget.style.color = 'var(--navy)'; }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
          <button onClick={() => navigate('/rent-a-car/flota')} style={{ fontSize: 13, fontWeight: 700, color: 'var(--red)', background: 'transparent', border: '1px solid var(--red)', borderRadius: 8, padding: '8px 18px', cursor: 'pointer', fontFamily: 'inherit', display: 'inline-flex', alignItems: 'center', gap: 6, whiteSpace: 'nowrap' }}>
            Ver toda la flota
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </div>
      </div>

      {/* Carrusel */}
      {loading ? (
        <div style={{ display: 'flex', gap: 20, overflow: 'hidden' }}>
          {[0, 1, 2, 3].map(i => (
            <div key={i} className="skeleton" style={{ flex: '0 0 clamp(240px, calc(33.333% - 14px), 300px)', height: 250, borderRadius: 16, flexShrink: 0 }} />
          ))}
        </div>
      ) : (
        <div
          ref={trackRef}
          onMouseEnter={() => { pausedRef.current = true; }}
          onMouseLeave={() => { pausedRef.current = false; }}
          style={{ display: 'flex', gap: 20, overflowX: 'auto', scrollSnapType: 'x mandatory', paddingBottom: 8, scrollbarWidth: 'none' }}
        >
          {vehicles.map(v => (
            <FleetCard key={v.sippCode} v={v} onReserve={scrollToSearch} />
          ))}
        </div>
      )}
    </section>
  );
}

// ─── PromosSection ────────────────────────────────────────────────────────────

function PromoCard({ card }) {
  const [hov, setHov] = useState(false);
  return (
    <div
      onMouseEnter={() => setHov(true)}
      onMouseLeave={() => setHov(false)}
      style={{
        position: 'relative', borderRadius: 18, overflow: 'hidden', cursor: 'pointer',
        boxShadow: hov ? '0 14px 40px rgba(26,35,70,.18)' : '0 2px 8px rgba(0,0,0,.08)',
        transform: hov ? 'translateY(-3px)' : 'none',
        transition: 'all .35s cubic-bezier(.2,.8,.2,1)',
        aspectRatio: '16/10',
      }}
    >
      <img
        src={card.img}
        alt={card.title}
        loading="lazy"
        style={{ width: '100%', height: '100%', objectFit: 'cover', display: 'block', transform: hov ? 'scale(1.06)' : 'scale(1)', transition: 'transform .5s cubic-bezier(.2,.8,.2,1)' }}
      />
      {/* Gradient overlay */}
      <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(to top, rgba(0,0,0,.7) 0%, rgba(0,0,0,0) 55%)', pointerEvents: 'none' }} />
      {/* Content */}
      <div style={{ position: 'absolute', inset: 'auto 0 0 0', padding: '20px 22px', color: '#fff' }}>
        <div style={{ fontSize: 17, fontWeight: 800, lineHeight: 1.2, marginBottom: 4, textShadow: '0 2px 8px rgba(0,0,0,.4)' }}>{card.title}</div>
        <div style={{ fontSize: 12, opacity: .85, marginBottom: 12, lineHeight: 1.4, textShadow: '0 1px 4px rgba(0,0,0,.4)' }}>{card.subtitle}</div>
        <a
          href={card.href || '#'}
          target={card.href ? '_blank' : undefined}
          rel={card.href ? 'noopener noreferrer' : undefined}
          onClick={card.href ? undefined : e => e.preventDefault()}
          style={{
            display: 'inline-flex', alignItems: 'center', gap: 6,
            padding: '7px 14px', borderRadius: 20,
            background: hov ? 'var(--red)' : 'rgba(255,255,255,.18)',
            backdropFilter: 'blur(8px)',
            color: '#fff', fontSize: 12, fontWeight: 700, textDecoration: 'none',
            border: `1px solid ${hov ? 'var(--red)' : 'rgba(255,255,255,.3)'}`,
            transition: 'all .25s',
          }}
        >
          {card.cta}
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    </div>
  );
}

function PromosSection() {
  return (
    <section style={{ margin: '0 0 56px' }}>
      <div style={{ marginBottom: 24 }}>
        <div style={{ fontSize: 11, fontWeight: 700, letterSpacing: 2, color: 'var(--red)', textTransform: 'uppercase', marginBottom: 6 }}>
          Beneficios exclusivos
        </div>
        <h2 style={{ fontSize: 'clamp(22px, 3vw, 32px)', fontWeight: 800, color: 'var(--navy)', lineHeight: 1.1, letterSpacing: -.5, margin: 0 }}>
          Promociones y alianzas
        </h2>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: 20 }}>
        {PROMO_CARDS.map(card => <PromoCard key={card.title} card={card} />)}
      </div>
    </section>
  );
}

// ─── BlogDestacado ────────────────────────────────────────────────────────────

function BlogDestacado() {
  const [hover, setHover] = useState(false);
  return (
    <section style={{ margin: '0 0 56px' }}>
      {/* Section header */}
      <div style={{ marginBottom: 24 }}>
        <div style={{ fontSize: 11, fontWeight: 700, letterSpacing: 2, color: 'var(--red)', textTransform: 'uppercase', marginBottom: 6 }}>
          Blog Destacado
        </div>
        <h2 style={{ fontSize: 'clamp(22px, 3vw, 32px)', fontWeight: 800, color: 'var(--navy)', lineHeight: 1.1, letterSpacing: -.5, margin: 0 }}>
          Lo que está pasando
        </h2>
      </div>

      {/* Card */}
      <div
        onMouseEnter={() => setHover(true)}
        onMouseLeave={() => setHover(false)}
        style={{
          background: '#fff', borderRadius: 20, overflow: 'hidden',
          boxShadow: hover ? '0 16px 48px rgba(26,35,70,.16)' : '0 1px 4px rgba(0,0,0,.06)',
          transform: hover ? 'translateY(-3px)' : 'none',
          transition: 'all .35s cubic-bezier(.2,.8,.2,1)',
          display: 'grid', gridTemplateColumns: 'minmax(0, 1.1fr) minmax(0, 1fr)',
          cursor: 'pointer',
        }}
        className="r-blog-grid"
      >
        {/* Image side */}
        <div style={{ position: 'relative', overflow: 'hidden', minHeight: 320, background: 'var(--gray-100)' }}>
          <img
            src="/images/david.webp"
            alt="Feria Internacional de David 2026"
            style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover', display: 'block', transform: hover ? 'scale(1.05)' : 'scale(1)', transition: 'transform .6s cubic-bezier(.2,.8,.2,1)' }}
            loading="lazy"
          />
          {/* Pulsing category badge */}
          <div style={{ position: 'absolute', top: 18, left: 18, background: 'rgba(255,255,255,.95)', color: 'var(--red)', fontSize: 11, fontWeight: 800, padding: '5px 12px', borderRadius: 20, letterSpacing: .5, textTransform: 'uppercase', backdropFilter: 'blur(6px)' }}>
            <span style={{ display: 'inline-block', width: 6, height: 6, borderRadius: '50%', background: 'var(--red)', marginRight: 6, verticalAlign: 'middle', animation: 'pulseRed 1.5s infinite' }} />
            Eventos · Marzo 2026
          </div>
        </div>

        {/* Content side */}
        <div style={{ padding: 'clamp(24px, 4vw, 40px)', display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
          <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--gray-400)', marginBottom: 10, display: 'flex', alignItems: 'center', gap: 8 }}>
            <span style={{ width: 24, height: 1, background: 'var(--red)' }} />
            5 MIN DE LECTURA
          </div>
          <h3 style={{ fontSize: 'clamp(20px, 2.5vw, 28px)', fontWeight: 800, color: 'var(--navy)', lineHeight: 1.15, letterSpacing: -.5, margin: '0 0 14px' }}>
            Feria Internacional de David 2026
          </h3>
          <p style={{ fontSize: 15, color: 'var(--gray-600)', lineHeight: 1.6, margin: '0 0 24px' }}>
            Uno de los eventos comerciales, agroindustriales y culturales más importantes de Panamá. Conoce cómo Automarket te lleva a vivir esta experiencia única en Chiriquí — con vehículos disponibles desde nuestra sucursal de David.
          </p>
          <div style={{ display: 'flex', alignItems: 'center', gap: 14, flexWrap: 'wrap' }}>
            <a
              href="/rent-a-car/blog/feria-internacional-david-2026"
              style={{
                padding: '12px 24px', borderRadius: 10, border: 'none',
                background: hover ? 'var(--red-dark)' : 'var(--red)', color: '#fff', fontSize: 14, fontWeight: 700,
                cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 8,
                boxShadow: '0 4px 14px rgba(190,28,40,.3)', transition: 'all .2s', textDecoration: 'none',
              }}
            >
              Conoce más
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </a>
            <a href="/rent-a-car/blog" style={{ color: 'var(--gray-500)', fontSize: 13, fontWeight: 600, textDecoration: 'none' }}>
              Ver todo el blog →
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}

// ─── TestimoniosSection ───────────────────────────────────────────────────────

function TestimonioCard({ t }) {
  return (
    <div style={{ background: '#fff', borderRadius: 16, padding: '28px 28px 24px', boxShadow: '0 2px 8px rgba(0,0,0,.06)', position: 'relative', overflow: 'hidden' }}>
      <div style={{ position: 'absolute', top: -16, right: 12, fontSize: 120, color: 'rgba(190,28,40,.05)', fontWeight: 900, lineHeight: 1, userSelect: 'none', pointerEvents: 'none' }}>&ldquo;</div>
      <div style={{ display: 'flex', gap: 2, marginBottom: 14 }}>
        {[0,1,2,3,4].map(i => (
          <svg key={i} width="15" height="15" viewBox="0 0 24 24" fill="#f59e0b" stroke="none" aria-hidden="true">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
          </svg>
        ))}
      </div>
      <p style={{ fontSize: 14, color: 'var(--gray-600)', lineHeight: 1.7, margin: '0 0 20px', fontStyle: 'italic', position: 'relative', zIndex: 1 }}>
        &ldquo;{t.text}&rdquo;
      </p>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
        <div style={{ width: 42, height: 42, borderRadius: '50%', background: 'linear-gradient(135deg, var(--navy), var(--red))', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontWeight: 700, fontSize: 13, flexShrink: 0 }}>
          {t.initials}
        </div>
        <div>
          <div style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)' }}>{t.name}</div>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: '2px 8px', marginTop: 2 }}>
            <span style={{ fontSize: 11, color: 'var(--gray-500)' }}>{t.country}</span>
            <span style={{ fontSize: 11, color: 'var(--gray-300)' }}>·</span>
            <span style={{ fontSize: 11, color: 'var(--gray-500)' }}>{t.sucursal}</span>
            <span style={{ fontSize: 11, color: 'var(--gray-300)' }}>·</span>
            <span style={{ fontSize: 11, color: 'var(--gray-400)' }}>{t.date}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

function TestimoniosSection() {
  const [current, setCurrent] = useState(0);
  const timerRef = useRef(null);
  const [paused, setPaused] = useState(false);

  useEffect(() => {
    if (paused) return;
    timerRef.current = setInterval(() => {
      setCurrent(c => (c + 1) % TESTIMONIOS.length);
    }, 5500);
    return () => clearInterval(timerRef.current);
  }, [paused]);

  const prev = () => { clearInterval(timerRef.current); setCurrent(c => (c - 1 + TESTIMONIOS.length) % TESTIMONIOS.length); };
  const next = () => { clearInterval(timerRef.current); setCurrent(c => (c + 1) % TESTIMONIOS.length); };

  const visible = [0, 1, 2].map(i => TESTIMONIOS[(current + i) % TESTIMONIOS.length]);

  return (
    <section style={{ marginTop: 40, marginBottom: 16 }} onMouseEnter={() => setPaused(true)} onMouseLeave={() => setPaused(false)}>
      <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', marginBottom: 24, flexWrap: 'wrap', gap: 12 }}>
        <div>
          <div style={{ fontSize: 11, fontWeight: 700, color: 'var(--red)', textTransform: 'uppercase', letterSpacing: 1.5, marginBottom: 6 }}>Experiencias reales</div>
          <h2 style={{ fontSize: 'clamp(22px, 3vw, 30px)', fontWeight: 800, color: 'var(--navy)', margin: 0, letterSpacing: '-.5px', maxWidth: 400 }}>Lo que dicen nuestros clientes</h2>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <button onClick={prev} aria-label="Anterior testimonio" style={{ width: 36, height: 36, borderRadius: '50%', border: '1px solid var(--gray-200)', background: '#fff', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'all .2s' }} onMouseEnter={e => { e.currentTarget.style.background = 'var(--navy)'; e.currentTarget.style.borderColor = 'var(--navy)'; e.currentTarget.style.color = '#fff'; }} onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.borderColor = 'var(--gray-200)'; e.currentTarget.style.color = 'inherit'; }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button onClick={next} aria-label="Siguiente testimonio" style={{ width: 36, height: 36, borderRadius: '50%', border: '1px solid var(--gray-200)', background: '#fff', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', transition: 'all .2s' }} onMouseEnter={e => { e.currentTarget.style.background = 'var(--navy)'; e.currentTarget.style.borderColor = 'var(--navy)'; e.currentTarget.style.color = '#fff'; }} onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.borderColor = 'var(--gray-200)'; e.currentTarget.style.color = 'inherit'; }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))', gap: 18 }}>
        {visible.map(t => <TestimonioCard key={`${t.country}-${t.date}-${t.name}`} t={t} />)}
      </div>

      {/* Dots */}
      <div style={{ display: 'flex', justifyContent: 'center', gap: 8, marginTop: 20 }}>
        {TESTIMONIOS.map((_, i) => (
          <button
            key={i}
            onClick={() => { clearInterval(timerRef.current); setCurrent(i); }}
            aria-label={`Ir al testimonio ${i + 1}`}
            style={{ width: i === current ? 20 : 8, height: 8, borderRadius: 4, border: 'none', background: i === current ? 'var(--red)' : 'var(--gray-200)', cursor: 'pointer', padding: 0, transition: 'all .3s' }}
          />
        ))}
      </div>
    </section>
  );
}

// ─── Reveal ───────────────────────────────────────────────────────────────────

function Reveal({ children, delay = 0 }) {
  const prefersReduced = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const ref = useRef(null);
  const [visible, setVisible] = useState(prefersReduced);

  useEffect(() => {
    if (prefersReduced) return;
    const el = ref.current;
    if (!el) return;
    if (el.getBoundingClientRect().top < window.innerHeight + 100) {
      setVisible(true);
      return;
    }
    const obs = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting) { setVisible(true); obs.disconnect(); } },
      { threshold: 0, rootMargin: '0px 0px 100px 0px' }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, [prefersReduced]);

  return (
    <div ref={ref} style={{
      opacity: visible ? 1 : 0,
      transform: visible ? 'translateY(0)' : 'translateY(20px)',
      transition: `opacity .55s ${delay}ms ease, transform .55s ${delay}ms ease`,
    }}>
      {children}
    </div>
  );
}

// ─── BuscadorVehiculos (main export) ──────────────────────────────────────────

export default function BuscadorVehiculos() {
  useEffect(() => { document.title = 'Automarket Rent-A-Car | Alquiler de Autos en Panamá'; }, []);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const agencia = params.get('agencia');
    if (agencia && /^[a-z0-9-]{2,60}$/.test(agencia)) sessionStorage.setItem('agent_slug', agencia);
  }, []);

  const navigate = useNavigate();
  const today    = new Date().toISOString().split('T')[0];
  const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];
  const dayAfter = new Date(Date.now() + 4 * 86400000).toISOString().split('T')[0];

  const [location,       setLocation]       = useState('');
  const [returnLocation, setReturnLocation] = useState('');
  const [pickupDate,     setPickupDate]     = useState(tomorrow);
  const [pickupTime,     setPickupTime]     = useState('10:00');
  const [returnDate,     setReturnDate]     = useState(dayAfter);
  const [returnTime,     setReturnTime]     = useState('10:00');
  const [age,            setAge]            = useState('25');
  const [devOtra,        setDevOtra]        = useState(false);
  const [pickupDateNote, setPickupDateNote] = useState('');
  const [returnDateNote, setReturnDateNote] = useState('');
  const [promo,          setPromo]          = useState(false);
  const [promoCode,      setPromoCode]      = useState('');
  const [stickyVisible,  setStickyVisible]  = useState(false);

  const selectedSucursal       = SUCURSALES.find(s => s.code === location) || null;
  const selectedReturnSucursal = devOtra ? (SUCURSALES.find(s => s.code === returnLocation) || null) : null;

  // Sticky bar: show when search card is scrolled out of view.
  // Coalesced through requestAnimationFrame so we never queue more than one
  // measurement+setState per frame, no matter how fast the user scrolls.
  useEffect(() => {
    let rafId = null;
    const handleScroll = () => {
      if (rafId !== null) return;
      rafId = requestAnimationFrame(() => {
        const el = document.getElementById('search-form-card');
        if (el) setStickyVisible(el.getBoundingClientRect().bottom < 0);
        rafId = null;
      });
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => {
      window.removeEventListener('scroll', handleScroll);
      if (rafId !== null) cancelAnimationFrame(rafId);
    };
  }, []);

  const pickupTimeOptions = useMemo(
    () => generateTimeOptions(selectedSucursal?.dailyHours, pickupDate, HORAS),
    [selectedSucursal, pickupDate]
  );
  const returnHoursSucursal = selectedReturnSucursal || selectedSucursal;
  const returnTimeOptions = useMemo(
    () => generateTimeOptions(returnHoursSucursal?.dailyHours, returnDate, HORAS),
    [returnHoursSucursal, returnDate]
  );

  useEffect(() => {
    if (pickupTimeOptions.length && !pickupTimeOptions.some(o => o.value === pickupTime)) {
      setPickupTime(pickupTimeOptions[0].value);
    }
  }, [pickupTimeOptions]);
  useEffect(() => {
    if (returnTimeOptions.length && !returnTimeOptions.some(o => o.value === returnTime)) {
      setReturnTime(returnTimeOptions[0].value);
    }
  }, [returnTimeOptions]);

  function handlePickupDateChange(val) {
    const ps = selectedSucursal;
    let effective = val;
    setPickupDateNote('');
    if (ps && isDayClosed(ps, val, 'pickup')) {
      const next = findNextOpenDate(ps, val, 'pickup');
      const weekday = DAY_LABEL_ES[new Date(`${val}T00:00:00`).getDay()];
      if (next) { setPickupDateNote(`${ps.shortName} no opera los ${weekday}. Ajustamos a la siguiente fecha disponible.`); effective = next; }
      else { setPickupDateNote(`${ps.shortName} no tiene fechas disponibles en las próximas 2 semanas. Cambia de sucursal o prueba otra fecha.`); }
    }
    setPickupDate(effective);
    if (returnDate <= effective) {
      const after = new Date(`${effective}T00:00:00`);
      after.setDate(after.getDate() + 1);
      let candidate = after.toISOString().split('T')[0];
      const rs = selectedReturnSucursal || ps;
      if (rs && isDayClosed(rs, candidate, 'return')) {
        const adjusted = findNextOpenDate(rs, candidate, 'return');
        if (adjusted) candidate = adjusted;
      }
      setReturnDate(candidate);
    }
  }

  function handleReturnDateChange(val) {
    const rs = (devOtra ? selectedReturnSucursal : null) || selectedSucursal;
    let effective = val;
    setReturnDateNote('');
    if (rs && isDayClosed(rs, val, 'return')) {
      const next = findNextOpenDate(rs, val, 'return');
      const weekday = DAY_LABEL_ES[new Date(`${val}T00:00:00`).getDay()];
      if (next) { setReturnDateNote(`${rs.shortName} no acepta devoluciones los ${weekday}. Ajustamos a la siguiente fecha disponible.`); effective = next; }
      else { setReturnDateNote(`${rs.shortName} no acepta devoluciones en las próximas 2 semanas. Cambia de sucursal o prueba otra fecha.`); }
    }
    setReturnDate(effective);
  }

  const handleSearch = () => {
    if (!location) return;
    if (pickupTimeOptions.length === 0 || returnTimeOptions.length === 0) return;
    try { sessionStorage.removeItem('selectedVehicle'); sessionStorage.removeItem('extrasSelection'); } catch { /* ignore */ }
    const qs = buildSearchUrl({ locationCode: location, returnLocationCode: devOtra && returnLocation && returnLocation !== location ? returnLocation : '', pickupDate, pickupTime, returnDate, returnTime, age, promoCode: (promo && promoCode.trim()) ? promoCode.trim().toUpperCase() : '' });
    navigate(qs ? `/rent-a-car/seleccion?${qs}` : '/rent-a-car/seleccion');
  };

  const isSearchDisabled = !location || pickupTimeOptions.length === 0 || returnTimeOptions.length === 0;

  return (
    <div style={{ minHeight: '100vh', background: 'var(--gray-100)' }}>
      <Header />

      {/* Sticky search overlay */}
      <StickySearchBar
        visible={stickyVisible}
        locationName={selectedSucursal?.name || ''}
        pickupDate={pickupDate}
        returnDate={returnDate}
        onSearch={handleSearch}
      />

      {/* ── Hero estilo legacy: imagen verano + form solapado arriba ─── */}
      <section className="r-hero" style={{ position: 'relative', overflow: 'hidden', isolation: 'isolate', background: 'linear-gradient(135deg, #1a2346 0%, #243366 60%, var(--red) 100%)' }}>
        <img
          src="/images/banner-verano.webp"
          alt="Verano Mode ON · Automarket Rent-A-Car"
          width="1920" height="600"
          loading="eager"
          fetchpriority="high"
          decoding="async"
          style={{ width: '100%', height: 'auto', display: 'block', aspectRatio: '1920/600', objectFit: 'cover', objectPosition: 'center 35%' }}
          onError={e => { e.currentTarget.style.display = 'none'; }}
        />
        {/* Form blanco solapado en la parte SUPERIOR del banner (estilo legacy) */}
        <div className="r-hero-form-wrap" style={{ position: 'absolute', top: 0, left: 0, right: 0, padding: '20px 24px 0', zIndex: 2 }}>
          <div id="search-form-card" style={{ maxWidth: 1200, margin: '0 auto', background: '#fff', borderRadius: 14, boxShadow: '0 8px 28px rgba(26,35,70,.16)', overflow: 'hidden' }}>
            <div className="r-card-body" style={{ padding: 20 }}>
              <div className="r-search-grid">
              <SelectField icon="map" label="Sucursal de recogida" value={location} onChange={setLocation} options={SUCURSALES.map(s => ({ value: s.code, display: s.name }))} placeholder="Seleccione una sucursal" />
              <DateField icon="calendar" label="Fecha recogida" value={pickupDate} onChange={handlePickupDateChange} min={today} note={pickupDateNote} />
              <SelectField icon="clock" label="Hora" value={pickupTime} onChange={setPickupTime} options={pickupTimeOptions} placeholder={pickupTimeOptions.length ? 'Hora' : 'Cerrada este día'} />
              <DateField icon="calendar" label="Fecha devolución" value={returnDate} onChange={handleReturnDateChange} min={pickupDate} note={returnDateNote} />
              <SelectField icon="clock" label="Hora" value={returnTime} onChange={setReturnTime} options={returnTimeOptions} placeholder={returnTimeOptions.length ? 'Hora' : 'Cerrada este día'} />
              <SelectField icon="user" label="Edad conductor" value={age} onChange={setAge} options={EDADES.map(e => ({ value: e.value, display: e.display }))} placeholder="Seleccione" />
            </div>

            {devOtra && (
              <div style={{ marginBottom: 16, maxWidth: 400, animation: 'fadeInUp .3s ease' }}>
                <SelectField icon="map" label="Sucursal de devolución" value={returnLocation} onChange={setReturnLocation} options={SUCURSALES.map(s => ({ value: s.code, display: s.name }))} placeholder="Seleccione sucursal de devolución" />
              </div>
            )}

            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 12, paddingTop: 16, borderTop: '1px solid var(--gray-100)' }}>
              <div style={{ display: 'flex', gap: 24, flexWrap: 'wrap', alignItems: 'center' }}>
                <CustomCheckbox checked={devOtra} onChange={setDevOtra} label="Devolver el auto en otra sucursal" id="cb-dev-otra" />
                <CustomCheckbox checked={promo} onChange={setPromo} label="¿Tienes un código promocional?" id="cb-promo" />
                {promo && (
                  <input type="text" aria-label="Código promocional" placeholder="Ingresa tu código" value={promoCode} onChange={e => setPromoCode(e.target.value)} style={{ ...inputBase, width: 180, padding: '8px 12px' }} />
                )}
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4 }}>
                <button
                  onClick={handleSearch}
                  disabled={isSearchDisabled}
                  title={!location ? 'Selecciona una sucursal para continuar' : isSearchDisabled ? 'La sucursal está cerrada en la fecha seleccionada' : undefined}
                  style={{
                    padding: '12px 36px', borderRadius: 12, border: 'none',
                    background: isSearchDisabled ? 'var(--gray-400)' : 'var(--red)',
                    color: '#fff', fontSize: 14, fontWeight: 700,
                    cursor: isSearchDisabled ? 'not-allowed' : 'pointer',
                    display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0,
                    boxShadow: isSearchDisabled ? 'none' : '0 4px 16px rgba(190,28,40,.3)',
                    transition: 'all .18s cubic-bezier(.2,.8,.2,1)',
                    fontFamily: 'inherit',
                    transform: 'translateY(0)',
                  }}
                  onMouseEnter={e => {
                    if (isSearchDisabled) return;
                    e.currentTarget.style.background = 'var(--red-dark)';
                    e.currentTarget.style.boxShadow = '0 6px 22px rgba(190,28,40,.40)';
                    e.currentTarget.style.transform = 'translateY(-1px)';
                  }}
                  onMouseLeave={e => {
                    e.currentTarget.style.background = isSearchDisabled ? 'var(--gray-400)' : 'var(--red)';
                    e.currentTarget.style.boxShadow = isSearchDisabled ? 'none' : '0 4px 16px rgba(190,28,40,.3)';
                    e.currentTarget.style.transform = 'translateY(0)';
                  }}
                  onMouseDown={e => { if (!isSearchDisabled) e.currentTarget.style.transform = 'scale(.97)'; }}
                  onMouseUp={e => { if (!isSearchDisabled) e.currentTarget.style.transform = 'translateY(-1px)'; }}
                >
                  <Icon type="search" size={16} color="#fff" /> Buscar vehículos
                </button>
                {!location && <span style={{ fontSize: 11, color: 'var(--gray-400)' }}>Selecciona una sucursal para continuar</span>}
                {location && isSearchDisabled && <span style={{ fontSize: 11, color: 'var(--gray-400)' }}>La sucursal está cerrada en la fecha seleccionada</span>}
              </div>
            </div>
            </div>
          </div>
        </div>
      </section>

      <main id="main-content" style={{ maxWidth: 1200, margin: '0 auto', padding: '24px 24px 0' }}>
        <Reveal delay={80}><StatsBand /></Reveal>
        <Reveal><FlotaSection /></Reveal>
        <Reveal><BlogDestacado /></Reveal>
        <Reveal><PromosSection /></Reveal>
        <Reveal delay={60}><TestimoniosSection /></Reveal>

        <div style={{ height: 48 }} />
      </main>

      <Footer />
    </div>
  );
}
