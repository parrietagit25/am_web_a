import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Icon from './Icon';

const ALQUILERES_ITEMS = [
  { label: 'Nuestra Flota',         sub: '15+ modelos disponibles',    icon: 'car',      action: '/rent-a-car/seleccion' },
  { label: 'Sucursales',            sub: '18 ubicaciones en Panamá',   icon: 'map',      action: '/rent-a-car/sucursales' },
  { label: 'Requisitos',            sub: 'Licencia, edad, depósito',   icon: 'license',  action: '/rent-a-car/requisitos' },
  { label: 'Términos y condiciones', sub: 'Política de alquiler',      icon: 'shield',   action: '/rent-a-car/terminos' },
];

const FLAT_LINKS = [
  { label: 'SUCURSALES',      action: '/rent-a-car/sucursales' },
  { label: 'PAGA TU RESERVA', action: '/rent-a-car/pago-seguro' },
  { label: 'CONTACTOS',       action: '/rent-a-car/contactos' },
  { label: 'MI RESERVA',      action: '/rent-a-car/mi-reserva' },
  // Featured link estilo legacy — botón con border rojo
  { label: 'Compra lo que Alquilaste', action: 'https://automarketpanama.com', external: true, featured: true },
];

export default function Nav() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [dropOpen, setDropOpen] = useState(false);
  const navigate = useNavigate();

  const linkStyle = {
    color: 'var(--gray-600)', background: 'none', border: 'none',
    textDecoration: 'none', fontSize: 12, fontWeight: 600,
    letterSpacing: '.2px', transition: 'color .15s',
    cursor: 'pointer', padding: '4px 0', fontFamily: 'inherit',
  };

  const mobileLinkStyle = {
    display: 'block', width: '100%', textAlign: 'left',
    padding: '15px 24px', color: 'var(--gray-700)', background: 'none',
    border: 'none', borderBottom: '1px solid var(--gray-100)',
    fontSize: 14, fontWeight: 600, letterSpacing: '.3px',
    transition: 'color .15s', cursor: 'pointer', fontFamily: 'inherit',
  };

  return (
    <nav style={{ background: 'var(--surface-base)', boxShadow: '0 1px 3px rgba(0,0,0,.06)', position: 'relative', zIndex: 200 }}>
      <div style={{ maxWidth: 1200, margin: '0 auto', padding: '0 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', height: 64 }}>
        <Link to="/" style={{ textDecoration: 'none', display: 'flex', alignItems: 'center' }}>
          <img src="/images/logo-am.svg" alt="Automarket Rent-A-Car" style={{ height: 38, width: 'auto', display: 'block' }} onError={e => { e.currentTarget.src = '/logo.png'; }} />
        </Link>

        {/* Desktop links */}
        <div className="r-nav-links">
          {/* ALQUILERES dropdown */}
          <div
            style={{ position: 'relative' }}
            onMouseEnter={() => setDropOpen(true)}
            onMouseLeave={() => setDropOpen(false)}
          >
            <button
              style={{ ...linkStyle, display: 'inline-flex', alignItems: 'center', gap: 4, color: dropOpen ? 'var(--red)' : 'var(--gray-600)', padding: '22px 0' }}
            >
              ALQUILERES
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{ transform: dropOpen ? 'rotate(180deg)' : 'rotate(0)', transition: 'transform .2s' }}>
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>

            {/* Dropdown panel */}
            <div style={{
              position: 'absolute', top: '100%', left: '-12px',
              background: '#fff', borderRadius: 14, padding: 8, minWidth: 300,
              boxShadow: '0 12px 40px rgba(26,35,70,.14)',
              border: '1px solid var(--gray-100)',
              opacity: dropOpen ? 1 : 0,
              transform: dropOpen ? 'translateY(0)' : 'translateY(-8px)',
              pointerEvents: dropOpen ? 'auto' : 'none',
              transition: 'all .2s cubic-bezier(.2,.8,.2,1)',
              zIndex: 60,
            }}>
              {ALQUILERES_ITEMS.map(item => (
                <button
                  key={item.label}
                  onClick={() => { setDropOpen(false); navigate(item.action); }}
                  style={{ display: 'flex', alignItems: 'center', gap: 14, padding: '11px 14px', borderRadius: 10, background: 'none', border: 'none', width: '100%', textAlign: 'left', cursor: 'pointer', fontFamily: 'inherit', transition: 'background .15s' }}
                  onMouseEnter={e => { e.currentTarget.style.background = 'var(--gray-50)'; }}
                  onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; }}
                >
                  <div style={{ width: 36, height: 36, borderRadius: 9, background: 'rgba(190,28,40,.08)', color: 'var(--red)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <Icon type={item.icon} size={17} color="var(--red)" />
                  </div>
                  <div>
                    <div style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)' }}>{item.label}</div>
                    <div style={{ fontSize: 11, color: 'var(--gray-500)', marginTop: 1 }}>{item.sub}</div>
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* Flat links */}
          {FLAT_LINKS.map(item => {
            // Estilo "featured" para "Compra lo que Alquilaste" (botón con border rojo, estilo legacy)
            if (item.featured) {
              return (
                <a
                  key={item.label}
                  href={item.action}
                  target={item.external ? '_blank' : undefined}
                  rel={item.external ? 'noopener noreferrer' : undefined}
                  style={{
                    fontSize: 12, fontWeight: 700, letterSpacing: '.2px',
                    color: 'var(--red)', textDecoration: 'none',
                    border: '1.5px solid var(--red)', borderRadius: 6,
                    padding: '7px 14px', whiteSpace: 'nowrap',
                    transition: 'all .15s', fontFamily: 'inherit',
                    display: 'inline-flex', alignItems: 'center',
                  }}
                  onMouseEnter={e => { e.currentTarget.style.background = 'var(--red)'; e.currentTarget.style.color = '#fff'; }}
                  onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; e.currentTarget.style.color = 'var(--red)'; }}
                >
                  {item.label}
                </a>
              );
            }
            return item.external ? (
              <a
                key={item.label}
                href={item.action}
                target="_blank"
                rel="noopener noreferrer"
                style={{ ...linkStyle, padding: '4px 0' }}
                onMouseEnter={e => (e.currentTarget.style.color = 'var(--red)')}
                onMouseLeave={e => (e.currentTarget.style.color = 'var(--gray-600)')}
              >
                {item.label}
              </a>
            ) : (
              <button
                key={item.label}
                type="button"
                onClick={() => navigate(item.action)}
                style={linkStyle}
                onMouseEnter={e => (e.currentTarget.style.color = 'var(--red)')}
                onMouseLeave={e => (e.currentTarget.style.color = 'var(--gray-600)')}
              >
                {item.label}
              </button>
            );
          })}
        </div>

        {/* Hamburger */}
        <button
          className="r-hamburger"
          onClick={() => setMenuOpen(o => !o)}
          aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
          aria-expanded={menuOpen}
          aria-controls="nav-mobile-menu"
        >
          <Icon type={menuOpen ? 'close' : 'menu'} size={22} />
        </button>
      </div>

      {/* Mobile drawer */}
      {menuOpen && (
        <div id="nav-mobile-menu" className="r-nav-drawer">
          {ALQUILERES_ITEMS.map(item => (
            <button key={item.label} type="button" onClick={() => { setMenuOpen(false); navigate(item.action); }} style={mobileLinkStyle} onMouseEnter={e => (e.currentTarget.style.color = 'var(--red)')} onMouseLeave={e => (e.currentTarget.style.color = 'var(--gray-700)')}>
              {item.label}
            </button>
          ))}
          <div style={{ height: 1, background: 'var(--gray-200)', margin: '4px 0' }} />
          {FLAT_LINKS.map(item => (
            item.external ? (
              <a key={item.label} href={item.action} target="_blank" rel="noopener noreferrer" onClick={() => setMenuOpen(false)} style={{ ...mobileLinkStyle, textDecoration: 'none' }} onMouseEnter={e => (e.currentTarget.style.color = 'var(--red)')} onMouseLeave={e => (e.currentTarget.style.color = 'var(--gray-700)')}>
                {item.label}
              </a>
            ) : (
              <button key={item.label} type="button" onClick={() => { setMenuOpen(false); navigate(item.action); }} style={mobileLinkStyle} onMouseEnter={e => (e.currentTarget.style.color = 'var(--red)')} onMouseLeave={e => (e.currentTarget.style.color = 'var(--gray-700)')}>
                {item.label}
              </button>
            )
          ))}
        </div>
      )}
    </nav>
  );
}
