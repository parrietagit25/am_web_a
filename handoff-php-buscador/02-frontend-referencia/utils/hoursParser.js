// Parser de strings de horario de sucursal a estructura DailyHours.
//
// Formatos soportados (las 18 strings de SUCURSALES verificadas):
//   '7:00 a.m. – 8:00 p.m.'                                       → todos los días
//   '5:00 a.m. – 11:30 p.m.'                                      → todos los días
//   'Lun–Sáb: 7:00 a.m. – 8:00 p.m. | Dom: 9:00 a.m. – 6:00 p.m.' → multi-slot
//   'Mar–Dom: 2:00 p.m. – 6:00 p.m.'                              → cerrado lunes
//   'Lun–Vie: 9:00 a.m. – 5:00 p.m. | Sáb–Dom: 9:00 a.m. – 3:00 p.m.'
//
// Convención de output: claves de día en inglés (monday..sunday) para alinear con
// el uso típico en JS. Valor por día es { open: "HH:MM", close: "HH:MM" } en
// formato 24h, o null si la sucursal está cerrada ese día.

const DAY_INDEX = {
  Lun: 1, Mar: 2, Mié: 3, Mie: 3, Jue: 4, Vie: 5, Sáb: 6, Sab: 6, Dom: 0,
};

const DAY_KEYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

function emptyDailyHours() {
  return DAY_KEYS.reduce((acc, k) => (acc[k] = null, acc), {});
}

function expandDayRange(startDay, endDay) {
  const start = DAY_INDEX[startDay];
  if (start === undefined) return [];
  if (!endDay) return [start];
  const end = DAY_INDEX[endDay];
  if (end === undefined) return [];
  // Iterar circular para soportar rangos como Mar–Dom (2,3,4,5,6,0) o Sáb–Dom (6,0).
  const days = [];
  let d = start;
  while (days.length < 8) {
    days.push(d);
    if (d === end) break;
    d = (d + 1) % 7;
  }
  return days;
}

function toHHMM(hStr, mStr, period) {
  let hour = parseInt(hStr, 10);
  const min = mStr.padStart(2, '0');
  if (period === 'p.m.' && hour < 12) hour += 12;
  if (period === 'a.m.' && hour === 12) hour = 0;
  return `${hour.toString().padStart(2, '0')}:${min}`;
}

// Slot: opcional "Día[–Día]: " + "H:MM a.m./p.m. – H:MM a.m./p.m."
const SLOT_RE = /(?:([A-Za-zÁÉÍÓÚáéíóúñÑü]+)(?:–([A-Za-zÁÉÍÓÚáéíóúñÑü]+))?:\s+)?(\d{1,2}):(\d{2})\s+(a\.m\.|p\.m\.)\s+–\s+(\d{1,2}):(\d{2})\s+(a\.m\.|p\.m\.)/;

export function parseHoursString(str) {
  const result = emptyDailyHours();
  if (!str || typeof str !== 'string') return result;
  const slots = str.split(/\s*\|\s*/);

  for (const slot of slots) {
    const m = slot.match(SLOT_RE);
    if (!m) continue;
    const [, startDay, endDay, h1, mm1, p1, h2, mm2, p2] = m;
    const open  = toHHMM(h1, mm1, p1);
    const close = toHHMM(h2, mm2, p2);
    const days  = startDay ? expandDayRange(startDay, endDay) : [0, 1, 2, 3, 4, 5, 6];
    for (const d of days) {
      result[DAY_KEYS[d]] = { open, close };
    }
  }
  return result;
}

// Helper: dado un objeto dailyHours, devuelve los días cerrados (claves null)
// como array de índices weekday compatibles con Date.getDay() (0=Sun..6=Sat).
export function getClosedWeekdaysFromHours(dailyHours) {
  if (!dailyHours) return [];
  return DAY_KEYS
    .map((k, i) => (dailyHours[k] == null ? i : -1))
    .filter(i => i !== -1);
}
