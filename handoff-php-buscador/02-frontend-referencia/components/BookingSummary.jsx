import Icon from './Icon';
import { SUCURSALES, EDADES } from '../utils/constants';

function formatDate(dateStr) {
  if (!dateStr) return '';
  const [y, m, d] = dateStr.split('-');
  return `${d}/${m}/${y}`;
}

function formatTime(time24) {
  if (!time24) return '';
  const [h, m] = time24.split(':').map(Number);
  const period = h < 12 ? 'a.m.' : 'p.m.';
  const h12 = h === 0 ? 12 : h > 12 ? h - 12 : h;
  return `${h12}:${m.toString().padStart(2, '0')} ${period}`;
}

function calcDays(pickupDate, returnDate) {
  if (!pickupDate || !returnDate) return 0;
  const d1 = new Date(pickupDate);
  const d2 = new Date(returnDate);
  const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
}

export default function BookingSummary({ params }) {
  const { locationCode, returnLocationCode, pickupDate, pickupTime, returnDate, returnTime, age } = params || {};
  const locationName = SUCURSALES.find((s) => s.code === locationCode)?.name || locationCode || '—';
  const returnLocationName = SUCURSALES.find((s) => s.code === returnLocationCode)?.name || returnLocationCode || locationName;
  const days = calcDays(pickupDate, returnDate);
  const ageLabel = EDADES.find(e => e.value === age)?.display
    ?? (parseInt(age, 10) < 25 ? '23-24 años' : '+25 años');

  return (
    <div className="r-booking-sidebar" style={{
      background: 'var(--surface-base)', borderRadius: 16, padding: 24,
      boxShadow: '0 1px 3px rgba(0,0,0,.06)',
    }}>
      <h4 style={{ fontSize: 15, fontWeight: 700, color: 'var(--navy)', marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
        <span style={{ color: 'var(--red)' }}><Icon type="calendar" size={18} /></span>
        Tu reserva
      </h4>

      <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
        {[
          { label: 'Recogida', name: locationName, date: `${formatDate(pickupDate)} ${formatTime(pickupTime)}` },
          { label: 'Devolución', name: returnLocationName, date: `${formatDate(returnDate)} ${formatTime(returnTime)}` },
        ].map((item, i) => (
          <div key={i} style={{ padding: '12px 14px', background: 'var(--gray-50)', borderRadius: 10 }}>
            <div style={{ fontSize: 11, fontWeight: 600, color: 'var(--red)', textTransform: 'uppercase', letterSpacing: '.5px', marginBottom: 4 }}>
              {item.label}
            </div>
            <div style={{ fontSize: 13, fontWeight: 500, color: 'var(--navy)', lineHeight: 1.4 }}>{item.name}</div>
            <div style={{ fontSize: 12, color: 'var(--gray-500)', marginTop: 2 }}>{item.date}</div>
          </div>
        ))}

        <div style={{ display: 'flex', justifyContent: 'space-between', padding: '10px 14px', background: 'var(--gray-50)', borderRadius: 10 }}>
          <span style={{ fontSize: 12, color: 'var(--gray-500)' }}>Edad del conductor</span>
          <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>{ageLabel}</span>
        </div>

        <div style={{ display: 'flex', justifyContent: 'space-between', padding: '10px 14px', background: 'var(--navy)', borderRadius: 10, color: '#fff' }}>
          <span style={{ fontSize: 12 }}>Días de renta</span>
          <span style={{ fontSize: 15, fontWeight: 700 }}>{days} día{days !== 1 ? 's' : ''}</span>
        </div>
      </div>
    </div>
  );
}
