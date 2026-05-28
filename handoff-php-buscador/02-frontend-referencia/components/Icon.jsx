export default function Icon({ type, size = 16, color = 'currentColor' }) {
  const s = { width: size, height: size };
  const base = { fill: 'none', stroke: color, strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' };

  const icons = {
    calendar: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <rect x="3" y="4" width="18" height="18" rx="2" />
        <line x1="16" y1="2" x2="16" y2="6" />
        <line x1="8" y1="2" x2="8" y2="6" />
        <line x1="3" y1="10" x2="21" y2="10" />
      </svg>
    ),
    map: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
        <circle cx="12" cy="10" r="3" />
      </svg>
    ),
    clock: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <circle cx="12" cy="12" r="10" />
        <polyline points="12 6 12 12 16 14" />
      </svg>
    ),
    user: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>
    ),
    search: (
      <svg {...s} viewBox="0 0 24 24" {...base} strokeWidth={2.5}>
        <circle cx="11" cy="11" r="8" />
        <line x1="21" y1="21" x2="16.65" y2="16.65" />
      </svg>
    ),
    tag: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" />
        <line x1="7" y1="7" x2="7.01" y2="7" />
      </svg>
    ),
    star: (
      <svg {...s} viewBox="0 0 24 24" fill={color} stroke={color} strokeWidth={1}>
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
      </svg>
    ),
    door: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M3 21V5a2 2 0 012-2h8l4 4v14" />
        <path d="M3 21h18" />
        <path d="M13 3v4h4" />
        <circle cx="13" cy="14" r="1" />
      </svg>
    ),
    person: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>
    ),
    snow: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <line x1="12" y1="2" x2="12" y2="22" />
        <path d="M20 16l-4-4 4-4" />
        <path d="M4 8l4 4-4 4" />
        <path d="M16 4l-4 4-4-4" />
        <path d="M8 20l4-4 4 4" />
      </svg>
    ),
    gear: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <circle cx="12" cy="12" r="3" />
        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
      </svg>
    ),
    check: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <polyline points="20 6 9 17 4 12" />
      </svg>
    ),
    menu: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <line x1="3" y1="6" x2="21" y2="6" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="18" x2="21" y2="18" />
      </svg>
    ),
    close: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    ),
    alert: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
    ),
    road: (
      <svg viewBox="0 0 24 24" fill="none" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" width={size} height={size}>
        <path d="M3 17l3-10h12l3 10" />
        <path d="M3 17h18" />
        <path d="M9 7V5" />
        <path d="M15 7V5" />
        <line x1="12" y1="3" x2="12" y2="7" />
      </svg>
    ),
    traction: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <circle cx="5" cy="5" r="2.5" />
        <circle cx="19" cy="5" r="2.5" />
        <circle cx="5" cy="19" r="2.5" />
        <circle cx="19" cy="19" r="2.5" />
        <line x1="7.5" y1="5" x2="16.5" y2="5" />
        <line x1="7.5" y1="19" x2="16.5" y2="19" />
        <line x1="5" y1="7.5" x2="5" y2="16.5" />
        <line x1="19" y1="7.5" x2="19" y2="16.5" />
        <circle cx="12" cy="12" r="1.5" />
      </svg>
    ),
    window: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <rect x="3" y="3" width="18" height="18" rx="2" />
        <line x1="12" y1="3" x2="12" y2="21" />
        <line x1="3" y1="12" x2="21" y2="12" />
      </svg>
    ),
    license: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <rect x="2" y="6" width="20" height="12" rx="2" />
        <circle cx="8" cy="12" r="2" />
        <line x1="13" y1="10" x2="18" y2="10" />
        <line x1="13" y1="14" x2="16" y2="14" />
      </svg>
    ),
    bag: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <rect x="2" y="8" width="20" height="14" rx="2" />
        <path d="M8 8V6a2 2 0 012-2h4a2 2 0 012 2v2" />
        <line x1="12" y1="13" x2="12" y2="17" />
        <line x1="10" y1="15" x2="14" y2="15" />
      </svg>
    ),
    shield: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
      </svg>
    ),
    car: (
      <svg {...s} viewBox="0 0 24 24" {...base}>
        <path d="M5 17H3a2 2 0 01-2-2V9a2 2 0 012-2h1l2-4h10l2 4h1a2 2 0 012 2v6a2 2 0 01-2 2h-2" />
        <circle cx="7.5" cy="17.5" r="1.5" />
        <circle cx="16.5" cy="17.5" r="1.5" />
      </svg>
    ),
    'chevron-left': (
      <svg {...s} viewBox="0 0 24 24" {...base}><polyline points="15 18 9 12 15 6" /></svg>
    ),
    'chevron-right': (
      <svg {...s} viewBox="0 0 24 24" {...base}><polyline points="9 18 15 12 9 6" /></svg>
    ),
    sparkle: (
      <svg {...s} viewBox="0 0 24 24" fill={color} stroke="none">
        <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z" />
      </svg>
    ),
  };

  return icons[type] || null;
}
