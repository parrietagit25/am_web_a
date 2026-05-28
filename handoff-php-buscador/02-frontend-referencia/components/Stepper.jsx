import Icon from './Icon';

const STEPS = ['Sucursal y Fechas', 'Escoger Auto', 'Escoger Extras', 'Realizar Reserva', 'Pagar la Reserva'];

export default function Stepper({ current }) {
  return (
    <nav aria-label="Pasos de la reserva">
      <ol style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '20px 0', overflowX: 'auto', listStyle: 'none', margin: 0 }} className="stepper-scroll">
        {STEPS.map((s, i) => {
          const done = i < current;
          const active = i === current;
          return (
            <li
              key={i}
              aria-current={active ? 'step' : undefined}
              style={{ display: 'flex', alignItems: 'center', gap: 4 }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: 8, whiteSpace: 'nowrap' }}>
                <div style={{
                  width: 28, height: 28, borderRadius: '50%',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontSize: 12, fontWeight: 600,
                  background: done ? 'var(--red)' : active ? 'var(--navy)' : 'var(--gray-200)',
                  color: done || active ? '#fff' : 'var(--gray-400)',
                  transition: 'all .3s',
                  flexShrink: 0,
                }}>
                  {done ? <Icon type="check" size={14} /> : i + 1}
                </div>
                <span style={{ fontSize: 13, fontWeight: active ? 600 : 400, color: active ? 'var(--navy)' : 'var(--gray-500)' }}>
                  {s}
                </span>
              </div>
              {i < STEPS.length - 1 && (
                <div style={{ flex: '0 0 32px', height: 2, background: done ? 'var(--red)' : 'var(--gray-200)', borderRadius: 1, margin: '0 4px' }} />
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
