// Sucursales verificadas contra automarketrentacar.com/es/sucursales (2026-04-24)
// 18 sucursales públicas — datos de nombre/dirección/teléfono/horario del sitio oficial
// OTA_VehLocSearchRQ no está habilitado en el tenant dolpanama — datos manuales
//
// Campos derivados (no editar a mano):
//   dailyHours       — shape estructurada generada por parseHoursString(hours)
//   closedReturnDays — leído de lib/closedReturnDays.json (regla operacional aparte)
import { parseHoursString } from './hoursParser';
import closedReturnDaysMap from '../../lib/closedReturnDays.json';

const RAW_SUCURSALES = [
  // ── Ciudad de Panamá ─────────────────────────────────────────────────────────
  {
    code: 'PTY',
    name: 'Aeropuerto Internacional de Tocumen T1',
    shortName: 'Aeropuerto PTY',
    address: 'Av. Domingo Díaz, Terminal 1, Aeropuerto Tocumen',
    city: 'Ciudad de Panamá',
    hours: '5:00 a.m. – 11:30 p.m.',
    phone: '+507 236-6785',
    lat: 9.066325,
    lng: -79.387593,
    note: null,
  },
  {
    code: 'TCP',
    name: 'Tocumen Commercial Park',
    shortName: 'Tocumen Comm. Park',
    address: 'Tocumen Commercial Park, Ciudad de Panamá',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Vie: 7:00 a.m. – 4:00 p.m. | Sáb: 7:00 a.m. – 3:00 p.m.',
    phone: '+507 279-2746',
    lat: 9.073709,
    lng: -79.408213,
    note: null,
  },
  {
    code: 'TBM',
    name: 'Tumba Muerto',
    shortName: 'Tumba Muerto',
    address: 'Vía Ricardo J. Alfaro, Villa de las Fuentes 2, Ciudad de Panamá',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Sáb: 7:00 a.m. – 8:00 p.m. | Dom: 9:00 a.m. – 6:00 p.m.',
    phone: '+507 279-2734',
    lat: 9.017653,
    lng: -79.535533,
    note: null,
  },
  {
    code: 'TDA',
    name: 'Hotel Torres de Alba',
    shortName: 'Torres de Alba',
    address: 'Av. Eusebio A. Morales, El Cangrejo, Ciudad de Panamá',
    city: 'Ciudad de Panamá',
    hours: '7:00 a.m. – 8:00 p.m.',
    phone: '+507 279-5730',
    lat: 8.984545,
    lng: -79.528875,
    note: null,
  },
  {
    code: 'ATRIOMALL',
    name: 'Atrio Mall (Costa del Este)',
    shortName: 'Atrio Mall',
    address: 'Av. Marina del Norte, Atrio Mall, Costa del Este',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Vie: 9:00 a.m. – 5:00 p.m. | Sáb: 8:00 a.m. – 3:00 p.m.',
    phone: '+507 279-5767',
    lat: 9.022001,
    lng: -79.462593,
    note: 'Disponibilidad limitada en línea. Si no encuentras vehículos, llama al +507 279-5767.',
  },
  {
    code: 'VIS',
    name: 'Vía Israel',
    shortName: 'Vía Israel',
    address: 'Vía Israel, Calle 73, San Francisco, Ciudad de Panamá',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Sáb: 8:00 a.m. – 4:00 p.m.',
    phone: '+507 279-5710',
    lat: 8.987686,
    lng: -79.505422,
    note: null,
  },
  {
    code: 'ALBROOK',
    name: 'Aeropuerto de Albrook',
    shortName: 'Albrook',
    address: 'Aeropuerto Marcos A. Gelabert, Albrook, Ciudad de Panamá',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Sáb: 7:30 a.m. – 3:00 p.m.',
    phone: '+507 279-5749',
    lat: 8.970674,
    lng: -79.559839,
    note: null,
  },
  {
    code: 'CASCO',
    name: 'Casco Antiguo',
    shortName: 'Casco Antiguo',
    address: 'Teatro Aurora, Calle B, Casco Antiguo (San Felipe)',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Sáb: 8:00 a.m. – 4:00 p.m.',
    phone: '+507 279-5769',
    lat: 8.9520,
    lng: -79.5349,
    note: 'Disponibilidad limitada en línea. Prueba fechas distintas o llama al +507 279-5769.',
  },
  {
    code: 'CHORRERA',
    name: 'La Chorrera',
    shortName: 'La Chorrera',
    address: 'Boulevard Costa Verde, La Chorrera',
    city: 'La Chorrera',
    hours: 'Lun–Vie: 7:00 a.m. – 7:00 p.m. | Sáb–Dom: 8:00 a.m. – 4:00 p.m.',
    phone: '+507 279-5762',
    lat: 8.895640,
    lng: -79.753511,
    note: null,
  },
  {
    code: 'PANPACIFIC',
    name: 'Panamá Pacífico',
    shortName: 'Panamá Pacífico',
    address: 'Aeropuerto de Panamá Pacífico BLB, Ciudad de Panamá',
    city: 'Ciudad de Panamá',
    hours: 'Lun–Sáb: 7:00 a.m. – 3:00 p.m.',
    phone: '+507 279-5733',
    lat: 8.919292,
    lng: -79.594273,
    note: null,
  },
  // ── Interior – Chiriquí ──────────────────────────────────────────────────────
  {
    code: 'MALEK',
    name: 'Aeropuerto Enrique Malek (David)',
    shortName: 'Aeropuerto David',
    address: 'Aeropuerto Internacional Enrique Malek, David, Chiriquí',
    city: 'David, Chiriquí',
    hours: 'Lun–Sáb: 7:00 a.m. – 8:00 p.m. | Dom: 8:00 a.m. – 5:00 p.m.',
    phone: '+507 279-5740',
    lat: 8.391571,
    lng: -82.431466,
    note: null,
  },
  {
    code: 'CHIRIQUI',
    name: 'David – Chiriquí',
    shortName: 'David',
    address: 'Carretera Panamericana, frente a Cochez, David, Chiriquí',
    city: 'David, Chiriquí',
    hours: 'Lun–Sáb: 8:00 a.m. – 5:00 p.m.',
    phone: '+507 279-5745',
    lat: 8.433910,
    lng: -82.441282,
    note: null,
  },
  {
    code: 'BOQUETE',
    name: 'Boquete',
    shortName: 'Boquete',
    address: 'Av. Central, Bajo Boquete, Chiriquí',
    city: 'Boquete, Chiriquí',
    hours: 'Lun–Sáb: 7:00 a.m. – 3:00 p.m.',
    phone: '+507 279-2700',
    lat: 8.770191,
    lng: -82.432994,
    note: 'Disponibilidad limitada en línea. Si no encuentras vehículos, llama al +507 279-2700.',
  },
  // ── Interior – Otras provincias ──────────────────────────────────────────────
  {
    code: 'CHITRE',
    name: 'Chitré',
    shortName: 'Chitré',
    address: 'Aeropuerto Capitán Alonso Valderrama, Chitré, Herrera',
    city: 'Chitré, Herrera',
    hours: 'Lun–Sáb: 8:00 a.m. – 3:00 p.m.',
    phone: '+507 236-6785',
    lat: 7.980989,
    lng: -80.410774,
    note: null,
  },
  {
    code: 'PENONOME',
    name: 'Penonomé',
    shortName: 'Penonomé',
    address: 'Carretera Panamericana, al lado de Super Pisos, Penonomé',
    city: 'Penonomé, Coclé',
    hours: 'Lun–Vie: 8:00 a.m. – 5:00 p.m. | Sáb: 8:00 a.m. – 4:00 p.m.',
    phone: '+507 279-5760',
    lat: 8.512313,
    lng: -80.346268,
    note: null,
  },
  {
    code: 'SANTIAGO',
    name: 'Santiago',
    shortName: 'Santiago',
    address: 'Hotel Mykonos, Santiago de Veraguas',
    city: 'Santiago, Veraguas',
    hours: 'Lun–Vie: 7:00 a.m. – 4:00 p.m. | Sáb: 7:00 a.m. – 3:00 p.m.',
    phone: '+507 279-5750',
    lat: 8.093668,
    lng: -80.946284,
    note: 'Disponibilidad limitada en línea. Si no encuentras vehículos, llama al +507 279-5750.',
  },
  {
    code: 'RIOHATO',
    name: 'Río Hato',
    shortName: 'Río Hato',
    address: 'Mareas Mall, Río Hato, Coclé',
    city: 'Río Hato, Coclé',
    hours: 'Lun–Vie: 9:00 a.m. – 4:00 p.m. | Sáb–Dom: 9:00 a.m. – 3:00 p.m.',
    phone: '+507 279-5725',
    lat: 8.373892,
    lng: -80.154426,
    note: null,
  },
  {
    code: 'VENAO',
    name: 'Playa Venao',
    shortName: 'Playa Venao',
    address: 'Playa Venao, Pedasí, Los Santos',
    city: 'Playa Venao, Los Santos',
    hours: 'Mar–Dom: 2:00 p.m. – 6:00 p.m.',
    phone: '+507 270-0355',
    lat: 7.432683,
    lng: -80.190164,
    note: null,
  },
];

export const SUCURSALES = RAW_SUCURSALES.map(s => ({
  ...s,
  dailyHours:       parseHoursString(s.hours),
  closedReturnDays: closedReturnDaysMap[s.code] || [],
}));

// 15-min slots matching legacy automarket.com.pa granularity.
export const HORAS = (() => {
  const list = [];
  for (let h = 7; h <= 18; h++) {
    for (let m = 0; m < 60; m += 15) {
      if (h === 18 && m > 0) break;
      const period = h < 12 ? 'a.m.' : 'p.m.';
      const h12 = h > 12 ? h - 12 : h === 0 ? 12 : h;
      const mm = m.toString().padStart(2, '0');
      const hh = h.toString().padStart(2, '0');
      list.push({ display: `${h12}:${mm} ${period}`, value: `${hh}:${mm}` });
    }
  }
  return list;
})();

// Two-option dropdown matching the legacy automarket.com.pa buscador.
// Drivers under 23 are not booked online — they call the sucursal.
// Sending age=23 to BARS triggers the UD (under-age) surcharge same as age=24.
export const EDADES = [
  { display: '23-24 años', value: '23' },
  { display: '+25 años',   value: '25' },
];
