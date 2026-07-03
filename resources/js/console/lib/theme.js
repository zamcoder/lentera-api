// Warna semantik konsol (selaras tokens.css & Lentera - Konsol Admin.dc.html).
// Dipakai untuk nilai inline yang butuh varian tint/teks per-status.
export const C = {
  sage: '#5C8166',
  sageDeep: '#3A5340',
  clay: '#AE6450',
  clayDeep: '#8C4733',
  slate: '#57708A',
  lavender: '#7E72B8',
  lavDeep: '#574E80',
  bg: '#E9E6DB',
  card: '#F6F4EC',
  field: '#FBFAF4',
  text: '#2C302A',
  text2: '#4D5347',
  muted: '#6E7567',
  dim: '#9AA093',
  line: 'rgba(40,45,35,.08)',
  border: 'rgba(40,45,35,.13)',
  sageTint: 'rgba(92,129,102,.14)',
  clayTint: 'rgba(174,100,80,.13)',
  slateTint: 'rgba(87,112,138,.13)',
  lavTint: 'rgba(126,114,184,.14)',
};

export const FONT = {
  display: "'Newsreader', serif",
  body: "'DM Sans', system-ui, sans-serif",
  mono: "'Spline Sans Mono', monospace",
};

// Label + gaya pill status akun (§B6).
export const STATUS_META = {
  active: { label: 'Aktif', bg: 'rgba(92,129,102,.14)', color: '#3A5340' },
  muted: { label: 'Dibisukan', bg: 'rgba(40,45,35,.08)', color: '#6E7567' },
  limited: { label: 'Dibatasi', bg: 'rgba(174,100,80,.13)', color: '#AE6450' },
  blocked: { label: 'Diblokir', bg: 'rgba(174,100,80,.2)', color: '#8C4733' },
};

// Gaya flag antrean per sumber/label moderasi (§B3).
export function flagStyle({ self_harm, mod_source, mod_reason }) {
  if (self_harm) {
    return { label: 'Penanganan khusus', color: C.lavender, tint: 'rgba(126,114,184,.16)', reasonBg: 'rgba(126,114,184,.09)' };
  }
  if (mod_source === 'ai') {
    return { label: 'Ditandai AI', color: C.slate, tint: 'rgba(87,112,138,.14)', reasonBg: 'rgba(87,112,138,.06)' };
  }
  return { label: 'Kata tersaring', color: C.clay, tint: 'rgba(174,100,80,.13)', reasonBg: 'rgba(174,100,80,.06)' };
}

// Avatar warna deterministik dari string (pseudonim/handle).
const AV = [
  ['rgba(174,100,80,.13)', '#AE6450'],
  ['rgba(92,129,102,.14)', '#5C8166'],
  ['rgba(126,114,184,.14)', '#7E72B8'],
  ['rgba(87,112,138,.13)', '#57708A'],
  ['rgba(40,45,35,.08)', '#6E7567'],
];
export function avatarColor(seed = '') {
  let h = 0;
  for (let i = 0; i < seed.length; i++) h = (h * 31 + seed.charCodeAt(i)) & 0xffff;
  return AV[h % AV.length];
}

export function initial(name = '?') {
  const t = (name || '?').trim();
  return t ? t[0].toUpperCase() : '?';
}

// Waktu relatif berbahasa Indonesia (mis. "3 menit lalu").
export function timeAgo(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  const s = Math.floor((Date.now() - d.getTime()) / 1000);
  if (s < 60) return 'baru saja';
  const m = Math.floor(s / 60);
  if (m < 60) return `${m} menit lalu`;
  const h = Math.floor(m / 60);
  if (h < 24) return `${h} jam lalu`;
  const dd = Math.floor(h / 24);
  return `${dd} hari lalu`;
}
