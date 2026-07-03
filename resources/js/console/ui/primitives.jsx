import { C, FONT } from '../lib/theme';

export function Card({ children, style, border }) {
  return (
    <div
      style={{
        borderRadius: 18,
        background: C.card,
        border: `1px solid ${border || C.line}`,
        overflow: 'hidden',
        ...style,
      }}
    >
      {children}
    </div>
  );
}

// Tombol aksi bergaya konsol. variant: primary | soft | ghost | danger
export function ActBtn({ children, onClick, variant = 'primary', disabled, style }) {
  const V = {
    primary: { background: C.sage, color: '#F4F8F2', fontWeight: 700 },
    lav: { background: C.lavender, color: '#F4F2FB', fontWeight: 700 },
    soft: { background: 'rgba(40,45,35,.05)', color: C.text2, fontWeight: 600 },
    ghost: { background: 'transparent', color: C.clay, fontWeight: 600, border: `1px solid rgba(174,100,80,.3)` },
    danger: { background: C.clay, color: '#F8EEEA', fontWeight: 700 },
  }[variant];

  return (
    <button
      onClick={onClick}
      disabled={disabled}
      className="lt-act"
      style={{
        cursor: disabled ? 'default' : 'pointer',
        display: 'inline-flex',
        alignItems: 'center',
        gap: 7,
        padding: '9px 16px',
        borderRadius: 11,
        border: 'none',
        fontFamily: FONT.body,
        fontSize: 13,
        opacity: disabled ? 0.55 : 1,
        ...V,
        ...style,
      }}
    >
      {children}
    </button>
  );
}

export function StatusPill({ label, bg, color }) {
  return (
    <span
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: 6,
        padding: '4px 10px',
        borderRadius: 8,
        fontSize: 12,
        fontWeight: 600,
        background: bg,
        color,
        width: 'fit-content',
      }}
    >
      {label}
    </span>
  );
}

export function Avatar({ seed, bg, fg, size = 34 }) {
  const [b, f] = [bg, fg];
  return (
    <span
      style={{
        width: size,
        height: size,
        flexShrink: 0,
        borderRadius: size >= 34 ? 10 : 9,
        background: b,
        color: f,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontFamily: FONT.display,
        fontSize: size >= 34 ? 16 : 15,
      }}
    >
      {seed}
    </span>
  );
}

export function LoadMore({ show, onClick, loading, bordered }) {
  if (!show) return null;
  return (
    <div
      onClick={loading ? undefined : onClick}
      className="lt-act"
      style={{
        cursor: loading ? 'default' : 'pointer',
        textAlign: 'center',
        padding: 13,
        borderRadius: bordered ? 0 : 13,
        borderTop: bordered ? `1px solid ${C.line}` : 'none',
        background: bordered ? 'transparent' : '#F1EEE4',
        border: bordered ? undefined : `1px solid rgba(40,45,35,.1)`,
        fontSize: 13,
        fontWeight: 600,
        color: C.sage,
      }}
    >
      {loading ? 'Memuat…' : 'Muat lebih banyak'}
    </div>
  );
}

export function EmptyState({ emoji, title, sub }) {
  return (
    <div style={{ padding: '60px 20px', textAlign: 'center' }}>
      <div style={{ fontSize: 40, marginBottom: 12 }}>{emoji}</div>
      <div style={{ fontFamily: FONT.display, fontSize: 22, marginBottom: 6 }}>{title}</div>
      <div style={{ fontSize: 13.5, color: C.muted }}>{sub}</div>
    </div>
  );
}

export function Spinner({ size = 20, color = C.sage }) {
  return (
    <span
      style={{
        display: 'inline-block',
        width: size,
        height: size,
        borderRadius: '50%',
        border: `2.5px solid rgba(92,129,102,.25)`,
        borderTopColor: color,
        animation: 'lt-spin .7s linear infinite',
      }}
    />
  );
}

export function CenterState({ children }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 12, padding: '70px 20px', color: C.muted }}>
      {children}
    </div>
  );
}

export function ErrorState({ error, onRetry }) {
  return (
    <CenterState>
      <div style={{ fontFamily: FONT.display, fontSize: 20, color: C.clay }}>Gagal memuat</div>
      <div style={{ fontSize: 13.5, maxWidth: 380, textAlign: 'center' }}>
        {error?.message || 'Terjadi kesalahan.'}
      </div>
      {onRetry && (
        <ActBtn variant="soft" onClick={onRetry}>
          Coba lagi
        </ActBtn>
      )}
    </CenterState>
  );
}
