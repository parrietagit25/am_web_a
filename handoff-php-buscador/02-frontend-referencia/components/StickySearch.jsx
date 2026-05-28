import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { SUCURSALES } from '../utils/constants';

const STORAGE_KEY = 'searchPrefs';

const HIDDEN_PATHS = new Set([
  '/',                          // home ya tiene form completo
  '/extras', '/rent-a-car/extras',
  '/reserva', '/rent-a-car/reserva',
  '/mi-reserva', '/rent-a-car/mi-reserva',
  '/pago-seguro', '/rent-a-car/pago-seguro',
  '/admin',
]);

function todayPlus(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

function loadPrefs() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const p = JSON.parse(raw);
    if (!p || typeof p !== 'object') return null;
    return p;
  } catch { return null; }
}

function savePrefs(p) {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(p)); } catch { /* ignore */ }
}

export default function StickySearch() {
  const navigate = useNavigate();
  const location = useLocation();
  const [visible, setVisible] = useState(false);

  const isHidden = HIDDEN_PATHS.has(location.pathname) || location.pathname.startsWith('/agencia/');

  const saved = loadPrefs();
  const [location_, setLocation_] = useState(saved?.location || 'PTY');
  const [pickupDate, setPickupDate] = useState(saved?.pickupDate || todayPlus(1));
  const [returnDate, setReturnDate] = useState(saved?.returnDate || todayPlus(4));
  const [age, setAge] = useState(saved?.age || '25');

  useEffect(() => {
    if (isHidden) {
      setVisible(false);
      return;
    }
    const onScroll = () => setVisible(window.scrollY > 220);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, [isHidden]);

  if (isHidden) return null;

  function handleSubmit(e) {
    e.preventDefault();
    const prefs = { location: location_, pickupDate, returnDate, age };
    savePrefs(prefs);
    const params = new URLSearchParams({
      locationCode: location_,
      returnLocationCode: location_,
      pickupDate, pickupTime: '10:00',
      returnDate, returnTime: '10:00',
      age,
    });
    navigate(`/rent-a-car/seleccion?${params.toString()}`);
  }

  const fieldStyle = {
    border: '1.5px solid var(--gray-200)',
    borderRadius: 7,
    padding: '8px 10px',
    fontSize: 13,
    fontFamily: 'inherit',
    background: '#fff',
    color: 'var(--navy)',
    minWidth: 0,
  };

  return (
    <div
      className="r-sticky-search-bar"
      style={{
        position: 'fixed', top: 0, left: 0, right: 0,
        zIndex: 90,
        background: 'rgba(255,255,255,.96)',
        backdropFilter: 'blur(8px)',
        WebkitBackdropFilter: 'blur(8px)',
        borderBottom: '1px solid var(--gray-200)',
        boxShadow: visible ? '0 4px 16px rgba(26,35,70,.10)' : 'none',
        transform: visible ? 'translateY(0)' : 'translateY(-100%)',
        transition: 'transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s',
        padding: '10px 0',
      }}
      role="search"
      aria-hidden={!visible}
    >
      <form
        onSubmit={handleSubmit}
        className="r-sticky-search-form"
        style={{
          maxWidth: 1200, margin: '0 auto', padding: '0 16px',
          display: 'grid',
          gridTemplateColumns: 'minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, .8fr) auto',
          gap: 10, alignItems: 'center',
        }}
      >
        <select
          value={location_}
          onChange={e => setLocation_(e.target.value)}
          aria-label="Sucursal de recogida"
          style={{ ...fieldStyle, cursor: 'pointer' }}
        >
          {SUCURSALES.map(s => (
            <option key={s.code} value={s.code}>{s.name}</option>
          ))}
        </select>
        <input
          type="date"
          value={pickupDate}
          min={todayPlus(0)}
          onChange={e => setPickupDate(e.target.value)}
          aria-label="Fecha de recogida"
          style={fieldStyle}
        />
        <input
          type="date"
          value={returnDate}
          min={pickupDate}
          onChange={e => setReturnDate(e.target.value)}
          aria-label="Fecha de devolución"
          style={fieldStyle}
        />
        <select
          value={age}
          onChange={e => setAge(e.target.value)}
          aria-label="Edad del conductor"
          style={{ ...fieldStyle, cursor: 'pointer' }}
        >
          <option value="23">23-24 años</option>
          <option value="25">+25 años</option>
        </select>
        <button
          type="submit"
          style={{
            background: 'var(--red)', color: '#fff', border: 'none',
            borderRadius: 7, padding: '9px 18px',
            fontSize: 13, fontWeight: 800, letterSpacing: '.3px',
            cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 6,
            boxShadow: '0 2px 8px rgba(190,28,40,.25)',
            transition: 'all .15s',
          }}
          onMouseEnter={e => { e.currentTarget.style.background = 'var(--red-dark)'; }}
          onMouseLeave={e => { e.currentTarget.style.background = 'var(--red)'; }}
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          Buscar
        </button>
      </form>
    </div>
  );
}
