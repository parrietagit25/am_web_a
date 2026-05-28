// Compact URL param encoder/decoder for the booking flow.
//
// Old URL (124 chars):
//   /seleccion?locationCode=PTY&pickupDate=2026-05-15&pickupTime=10:00&returnDate=2026-05-18&returnTime=10:00&age=25
//
// New URL (50 chars):
//   /seleccion?l=PTY&d1=2026-05-15&d2=2026-05-18
//
// Defaults that get omitted: pickupTime/returnTime=10:00, age=25.
// Times are combined with their date (ISO format) only when not the default:
//   /seleccion?l=PTY&d1=2026-05-15T14:30&d2=2026-05-18&a=23
//
// parseSearchUrl reads BOTH the new short keys and the legacy long keys, so
// bookmarks from earlier deploys keep working — there's no breaking change
// for users who saved a search URL.

const DEFAULT_TIME = '10:00';
const DEFAULT_AGE  = '25';

export function buildSearchUrl({
  locationCode, returnLocationCode,
  pickupDate, pickupTime,
  returnDate, returnTime,
  age, promoCode,
}) {
  const p = new URLSearchParams();
  if (locationCode) p.set('l', locationCode);
  if (returnLocationCode && returnLocationCode !== locationCode) {
    p.set('rl', returnLocationCode);
  }
  // Pack date+time. Drop the time half when it's the default 10:00.
  const packDT = (date, time) =>
    !date ? '' : (time && time !== DEFAULT_TIME ? `${date}T${time}` : date);
  const d1 = packDT(pickupDate, pickupTime);
  const d2 = packDT(returnDate, returnTime);
  if (d1) p.set('d1', d1);
  if (d2) p.set('d2', d2);
  if (age && age !== DEFAULT_AGE) p.set('a', age);
  if (promoCode) p.set('pr', promoCode);
  return p.toString();
}

export function parseSearchUrl(searchParams) {
  // Accept either URLSearchParams or a plain { key: value } object
  const get = key => typeof searchParams.get === 'function'
    ? searchParams.get(key)
    : (searchParams?.[key] ?? null);

  // New short keys take precedence; legacy long keys are read as fallback.
  const locationCode       = get('l')  || get('locationCode')       || '';
  const returnLocationCode = get('rl') || get('returnLocationCode') || locationCode;

  // d1 / d2 may be "YYYY-MM-DD" or "YYYY-MM-DDTHH:MM" — split into date + time
  function unpack(raw, legacyDate, legacyTime) {
    if (raw) {
      const [date, time] = raw.split('T');
      return { date, time: time || DEFAULT_TIME };
    }
    return { date: get(legacyDate) || '', time: get(legacyTime) || DEFAULT_TIME };
  }
  const { date: pickupDate, time: pickupTime } = unpack(get('d1'), 'pickupDate', 'pickupTime');
  const { date: returnDate, time: returnTime } = unpack(get('d2'), 'returnDate', 'returnTime');

  const age       = get('a')  || get('age')       || DEFAULT_AGE;
  const promoCode = get('pr') || get('promoCode') || '';

  return {
    locationCode, returnLocationCode,
    pickupDate, pickupTime,
    returnDate, returnTime,
    age, promoCode,
  };
}
