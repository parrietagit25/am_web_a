const ALL_CATEGORIES = ['Todos', 'Económico', 'Compacto', 'Intermedio', 'Estándar', 'Full Size', 'Premium', 'Lujo', 'SUV', 'Van'];

export default function FilterBar({ activeFilter, onFilter, vehicles = [] }) {
  // Only count vehicles that are available or still loading (not unavailable)
  const countable = vehicles.filter(v => v.available !== false);

  const countByCategory = countable.reduce((acc, v) => {
    acc[v.category] = (acc[v.category] || 0) + 1;
    return acc;
  }, {});

  // Only surface categories that have at least one countable vehicle
  const availableSet = new Set(countable.map((v) => v.category));
  const visibleCategories = ALL_CATEGORIES.filter((c) => c === 'Todos' || availableSet.has(c));

  const totalCount = countable.length;
  const displayCount = activeFilter === 'Todos' ? totalCount : (countByCategory[activeFilter] || 0);

  return (
    <div
      style={{
        position: 'sticky',
        top: 0,
        zIndex: 10,
        background: 'var(--gray-100)',
        paddingTop: 12,
        paddingBottom: 16,
        marginBottom: 8,
        marginLeft: -2,
        marginRight: -2,
        paddingLeft: 2,
        paddingRight: 2,
      }}
    >
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          gap: 12,
          flexWrap: 'wrap',
        }}
      >
        {/* Category chips */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap' }}>
        {visibleCategories.map((c) => {
          const isActive = activeFilter === c;
          const chipCount = c === 'Todos' ? totalCount : (countByCategory[c] || 0);

          return (
            <button
              key={c}
              onClick={() => onFilter(c)}
              aria-pressed={isActive}
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 6,
                padding: '9px 14px',
                borderRadius: 20,
                border: isActive ? '2px solid var(--red)' : '2px solid var(--gray-300)',
                fontSize: 13,
                fontWeight: isActive ? 700 : 500,
                cursor: 'pointer',
                background: isActive ? 'var(--red)' : 'var(--surface-base)',
                color: isActive ? '#fff' : 'var(--gray-600)',
                transition: 'background .18s, color .18s, border-color .18s, box-shadow .18s',
                boxShadow: isActive ? '0 2px 8px var(--red-25)' : 'none',
                whiteSpace: 'nowrap',
                minHeight: 44,
              }}
              onMouseEnter={(e) => {
                if (!isActive) {
                  e.currentTarget.style.borderColor = 'var(--red)';
                  e.currentTarget.style.color = 'var(--red)';
                }
              }}
              onMouseLeave={(e) => {
                if (!isActive) {
                  e.currentTarget.style.borderColor = 'var(--gray-300)';
                  e.currentTarget.style.color = 'var(--gray-600)';
                }
              }}
            >
              {c}
              {/* Count badge */}
              <span
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  minWidth: 20,
                  height: 20,
                  borderRadius: 10,
                  fontSize: 11,
                  fontWeight: 700,
                  lineHeight: 1,
                  padding: '0 5px',
                  background: isActive ? 'rgba(255,255,255,.25)' : 'var(--gray-200)',
                  color: isActive ? '#fff' : 'var(--gray-500)',
                  transition: 'background .18s, color .18s',
                }}
              >
                {chipCount}
              </span>
            </button>
          );
        })}
        </div>

        {/* "X autos disponibles" counter — inspired by Dollar Panamá */}
        <div style={{
          fontSize: 13,
          fontWeight: 500,
          color: 'var(--gray-600)',
          whiteSpace: 'nowrap',
          flexShrink: 0,
        }}>
          <span style={{ fontWeight: 700, color: 'var(--navy)' }}>{displayCount}</span>
          {' '}auto{displayCount !== 1 ? 's' : ''} disponible{displayCount !== 1 ? 's' : ''}
        </div>
      </div>
    </div>
  );
}
