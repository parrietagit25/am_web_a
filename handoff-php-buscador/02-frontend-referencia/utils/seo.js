/**
 * SEO helpers — schema.org markup utilities.
 */

const SITE_URL = (typeof window !== 'undefined' && window.location.origin)
  || 'https://automarketrentacar.com';

const KNOWN_BRANDS = new Set([
  'Toyota', 'Hyundai', 'KIA', 'Kia', 'Suzuki', 'Geely', 'GAC',
  'Mitsubishi', 'Honda', 'Nissan', 'Mazda', 'Ford', 'Chevrolet',
]);

/**
 * Extrae marca del nombre del vehículo.
 * "Toyota RAV4" → "Toyota"
 * "Hyundai Grand I-10" → "Hyundai"
 */
export function extractBrand(name) {
  if (!name) return null;
  const first = name.trim().split(/\s+/)[0];
  return KNOWN_BRANDS.has(first) ? first : first;
}

/**
 * Extrae modelo (todo después de la marca).
 * "Toyota RAV4" → "RAV4"
 * "Hyundai Grand I-10" → "Grand I-10"
 */
export function extractModel(name) {
  if (!name) return null;
  const parts = name.trim().split(/\s+/);
  return parts.length > 1 ? parts.slice(1).join(' ') : null;
}

/**
 * Prefix relative URL with SITE_URL para absoluta.
 * "/images/vehicles/x.webp" → "https://.../images/vehicles/x.webp"
 * Si ya es absoluta o data:, devuelve tal cual.
 */
export function absoluteUrl(path) {
  if (!path) return null;
  if (/^(https?:|data:|\/\/)/.test(path)) return path;
  if (path.startsWith('/api/img?url=')) {
    // Proxy URL — extract the original URL from the query string
    try {
      const url = new URL(path, SITE_URL);
      const original = url.searchParams.get('url');
      if (original) return original;
    } catch { /* fall through */ }
  }
  return SITE_URL + (path.startsWith('/') ? path : `/${path}`);
}

/**
 * Construye objeto schema.org Car para JSON-LD.
 * Devuelve null si faltan datos esenciales (name + price).
 *
 * @param {object} vehicle - shape: { name, image, transmission, passengers, doors, priceWeb, priceTotal, sippCode, available }
 */
export function buildCarSchema(vehicle) {
  if (!vehicle || !vehicle.name) return null;
  const price = vehicle.priceWeb || vehicle.priceCounter;
  if (!price) return null;

  const brand = extractBrand(vehicle.name);
  const model = extractModel(vehicle.name);
  const validUntil = new Date(Date.now() + 90 * 86400000).toISOString().slice(0, 10);

  return {
    '@context': 'https://schema.org',
    '@type': 'Car',
    name: vehicle.name,
    ...(brand ? { brand: { '@type': 'Brand', name: brand } } : {}),
    ...(model ? { model } : {}),
    ...(vehicle.image ? { image: absoluteUrl(vehicle.image) } : {}),
    ...(vehicle.transmission ? { vehicleTransmission: vehicle.transmission } : {}),
    ...(vehicle.doors ? { numberOfDoors: vehicle.doors } : {}),
    ...(vehicle.passengers ? { vehicleSeatingCapacity: vehicle.passengers } : {}),
    offers: {
      '@type': 'Offer',
      priceCurrency: 'USD',
      price: String(price),
      priceValidUntil: validUntil,
      availability: vehicle.available === false
        ? 'https://schema.org/OutOfStock'
        : 'https://schema.org/InStock',
      url: `${SITE_URL}/rent-a-car/seleccion`,
    },
  };
}
