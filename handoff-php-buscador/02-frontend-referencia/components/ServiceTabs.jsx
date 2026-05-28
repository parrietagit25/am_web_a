import { useState } from 'react';

const TABS = [
  { label: 'RENT A CAR', color: 'var(--red)' },
  { label: 'VENTA DE AUTOS', color: 'var(--tab-venta)' },
  { label: 'LEASING OPERATIVO', color: 'var(--tab-leasing)' },
  { label: 'RENTING', color: 'var(--tab-renting)' },
  { label: 'TALLER', color: 'var(--tab-taller)' },
];

export default function ServiceTabs() {
  const [active, setActive] = useState(0);

  return (
    <div style={{ background: 'var(--navy)' }}>
      <div
        className="tabs-row"
        role="tablist"
        aria-label="Servicios"
        style={{
          maxWidth: 1200,
          margin: '0 auto',
          display: 'flex',
          overflowX: 'auto',
          WebkitOverflowScrolling: 'touch',
          scrollbarWidth: 'none',
          msOverflowStyle: 'none',
        }}
      >
        {TABS.map((t, i) => (
          <button
            key={i}
            role="tab"
            aria-selected={i === active}
            id={`tab-${i}`}
            onClick={() => setActive(i)}
            style={{
              flex: '0 0 auto',
              whiteSpace: 'nowrap',
              minWidth: 'max-content',
              padding: '11px 16px',
              border: 'none',
              cursor: 'pointer',
              fontSize: 12,
              fontWeight: 700,
              letterSpacing: '.5px',
              color: '#fff',
              transition: 'all .2s',
              background: i === active ? t.color : 'transparent',
              borderBottom: i === active ? '3px solid rgba(255,255,255,.3)' : '3px solid transparent',
              opacity: i === active ? 1 : 0.6,
            }}
          >
            {t.label}
          </button>
        ))}
      </div>
    </div>
  );
}
