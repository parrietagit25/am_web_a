import { useState, useEffect } from 'react';
import LoginModal from './LoginModal';

const PROMOS = [
  { dot: 'var(--red)', text: 'Precios especiales todos los miércoles' },
  { dot: '#fbbf24',    text: 'Horario especial para días feriados' },
  { dot: '#22c55e',    text: '¿Tienes empresa? Pregunta por alquileres corporativos' },
  { dot: 'var(--red)', text: 'Verano mode ON · Aprovecha nuestra temporada' },
];

const linkBase = {
  color: 'rgba(255,255,255,.85)',
  textDecoration: 'none',
  fontWeight: 500,
  display: 'inline-flex',
  alignItems: 'center',
  gap: 5,
  whiteSpace: 'nowrap',
  fontSize: 11.5,
};

export default function TopBar() {
  const [idx, setIdx] = useState(0);
  const [loginOpen, setLoginOpen] = useState(false);

  useEffect(() => {
    const t = setInterval(() => setIdx(i => (i + 1) % PROMOS.length), 4500);
    return () => clearInterval(t);
  }, []);

  return (
    <>
    <LoginModal open={loginOpen} onClose={() => setLoginOpen(false)} />
    <div style={{ background: 'var(--navy)', color: 'rgba(255,255,255,.9)', fontSize: 12, padding: '8px 24px', borderBottom: '1px solid rgba(255,255,255,.06)', overflow: 'hidden' }}>
      <div style={{ maxWidth: 1200, margin: '0 auto', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
        {/* Rotating promo with vertical slide */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, minHeight: 18, flex: 1, minWidth: 0, overflow: 'hidden', position: 'relative' }}>
          {PROMOS.map((p, i) => (
            <div key={i} style={{
              position: i === 0 ? 'relative' : 'absolute',
              top: 0, left: 0,
              display: 'flex', alignItems: 'center', gap: 8,
              opacity: i === idx ? 1 : 0,
              transform: i === idx ? 'translateY(0)' : i < idx ? 'translateY(-14px)' : 'translateY(14px)',
              transition: 'all .5s cubic-bezier(.2,.8,.2,1)',
              whiteSpace: 'nowrap',
            }}>
              <span style={{ width: 6, height: 6, borderRadius: '50%', background: p.dot, display: 'inline-block', flexShrink: 0, boxShadow: `0 0 0 3px ${p.dot}22` }} />
              <span style={{ color: 'rgba(255,255,255,.92)', fontWeight: 500 }}>{p.text}</span>
            </div>
          ))}
        </div>

        {/* Contact + user + lang */}
        <div className="r-topbar-right" style={{ display: 'flex', alignItems: 'center', gap: 16, fontSize: 11.5, flexShrink: 0 }}>
          <a href="tel:+5072792700" style={{ ...linkBase, color: '#fff', fontWeight: 600 }}>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.69 13a19.79 19.79 0 01-3.07-8.67A2 2 0 013.6 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L7.91 9.91a16 16 0 006.06 6.06l.92-.92a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
            </svg>
            (507) 279-2700
          </a>
          <a href="mailto:info@automarketpanama.com" className="r-topbar-email" style={linkBase}>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            info@automarketpanama.com
          </a>
          <span style={{ color: 'rgba(255,255,255,.3)' }}>·</span>
          <button
            type="button"
            onClick={() => setLoginOpen(true)}
            style={{ ...linkBase, background: 'none', border: 'none', cursor: 'pointer', padding: 0, fontFamily: 'inherit' }}
            aria-label="Iniciar sesión"
          >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            Ingresa
          </button>
          {/* Spain flag + ES */}
          <span style={{ ...linkBase, cursor: 'default' }} aria-label="Español">
            <span style={{ display: 'inline-block', width: 16, height: 11, borderRadius: 2, overflow: 'hidden', boxShadow: '0 0 0 1px rgba(255,255,255,.2)' }}>
              <span style={{ display: 'block', width: '100%', height: '33.3%', background: '#aa151b' }} />
              <span style={{ display: 'block', width: '100%', height: '33.3%', background: '#f1bf00' }} />
              <span style={{ display: 'block', width: '100%', height: '33.3%', background: '#aa151b' }} />
            </span>
            ES
          </span>
        </div>
      </div>
    </div>
    </>
  );
}
