// Ikon SVG konsol (garis, mengikuti gaya Lentera - Konsol Admin.dc.html).
export const Icon = {
  overview: (c = '#6E7567', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <rect x="3" y="3" width="8" height="8" rx="2" stroke={c} strokeWidth="1.7" />
      <rect x="13" y="3" width="8" height="5" rx="2" stroke={c} strokeWidth="1.7" />
      <rect x="13" y="10" width="8" height="11" rx="2" stroke={c} strokeWidth="1.7" />
      <rect x="3" y="13" width="8" height="8" rx="2" stroke={c} strokeWidth="1.7" />
    </svg>
  ),
  queue: (c = '#6E7567', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M4 7h16M4 12h16M4 17h10" stroke={c} strokeWidth="1.7" strokeLinecap="round" />
    </svg>
  ),
  flag: (c = '#6E7567', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M6 21V5m0 0c3-1.5 6 1.5 9 0v8c-3 1.5-6-1.5-9 0" stroke={c} strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  ),
  terms: (c = '#6E7567', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M4 7V5h16v2M9 19h6M12 5v14" stroke={c} strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  ),
  users: (c = '#6E7567', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <circle cx="9" cy="8" r="3" stroke={c} strokeWidth="1.7" />
      <path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.5a3 3 0 0 1 0 5.8M17 19a5.5 5.5 0 0 0-2.5-4.6" stroke={c} strokeWidth="1.7" strokeLinecap="round" />
    </svg>
  ),
  heart: (c = '#5C8166', s = 15) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M12 20s-7-4.3-7-9.2A4 4 0 0 1 12 8a4 4 0 0 1 7-2.8c0 4.9-7 14.8-7 14.8z" fill={c} />
    </svg>
  ),
  clock: (c = '#57708A', s = 15) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="8.5" stroke={c} strokeWidth="1.6" />
      <path d="M12 8v4.5l3 1.6" stroke={c} strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  ),
  bolt: (c = '#7E72B8', s = 14) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M13 3L5 13h6l-1 8 8-10h-6l1-8z" fill={c} stroke={c} strokeWidth="1" strokeLinejoin="round" />
    </svg>
  ),
  shield: (c = '#5C8166', s = 15) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z" fill={c} opacity="0.18" stroke={c} strokeWidth="1.4" strokeLinejoin="round" />
      <path d="M9 12l2 2 4-4" stroke={c} strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  ),
  chevron: (c = '#9aa093', s = 17) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M9 6l6 6-6 6" stroke={c} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  ),
  back: (c = '#6E7567', s = 15) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M14 6l-6 6 6 6" stroke={c} strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  ),
  mail: (c = '#9aa093', s = 17) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <rect x="3" y="5" width="18" height="14" rx="3" stroke={c} strokeWidth="1.6" />
      <path d="M4 7l8 6 8-6" stroke={c} strokeWidth="1.6" strokeLinejoin="round" />
    </svg>
  ),
  lock: (c = '#9aa093', s = 17) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <rect x="5" y="10" width="14" height="10" rx="2.5" stroke={c} strokeWidth="1.6" />
      <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke={c} strokeWidth="1.6" />
    </svg>
  ),
  warn: (c = '#AE6450', s = 13) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="9" stroke={c} strokeWidth="1.6" />
      <path d="M12 8v5M12 16v.3" stroke={c} strokeWidth="1.7" strokeLinecap="round" />
    </svg>
  ),
  sparkle: (c = '#7E72B8', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <path d="M12 3l1.8 4.2 4.2 1.8-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8L12 3z" fill={c} />
      <circle cx="18" cy="17" r="1.4" fill={c} />
      <circle cx="5.5" cy="15" r="1" fill={c} />
    </svg>
  ),
  search: (c = '#6E7567', s = 18) => (
    <svg width={s} height={s} viewBox="0 0 24 24" fill="none">
      <circle cx="11" cy="11" r="6.5" stroke={c} strokeWidth="1.6" />
      <path d="M16 16l4 4" stroke={c} strokeWidth="1.6" strokeLinecap="round" />
    </svg>
  ),
};
