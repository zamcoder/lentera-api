import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { BadgeProvider, useBadges } from '../context/BadgeContext';
import { C, FONT } from '../lib/theme';
import { Icon } from '../lib/icons';

const NAV = [
  { to: '/ringkasan', label: 'Ringkasan', icon: Icon.overview },
  { to: '/antrean', label: 'Antrean moderasi', icon: Icon.queue, badge: 'queue', kind: 'lav' },
  { to: '/laporan', label: 'Laporan', icon: Icon.flag, badge: 'reports', kind: 'clay' },
  { to: '/kata-terlarang', label: 'Kata terlarang', icon: Icon.terms },
  { to: '/akun', label: 'Akun', icon: Icon.users },
];

const TITLES = {
  '/ringkasan': { t: 'Kesehatan komunitas', s: 'Ringkasan ruang bersama' },
  '/antrean': { t: 'Antrean moderasi', s: 'Kiriman menunggu ditinjau' },
  '/laporan': { t: 'Laporan pengguna', s: 'Konten yang dilaporkan anggota' },
  '/kata-terlarang': { t: 'Kata terlarang', s: 'Filter lapis pertama' },
  '/akun': { t: 'Tindakan akun', s: 'Kelola anggota komunitas' },
};

export default function Shell() {
  return (
    <BadgeProvider>
      <ShellInner />
    </BadgeProvider>
  );
}

function ShellInner() {
  const { user, logout } = useAuth();
  const badges = useBadges();
  const loc = useLocation();
  const meta = TITLES[loc.pathname] || TITLES['/ringkasan'];

  return (
    <div style={{ display: 'flex', height: '100vh', background: C.bg, color: C.text, fontFamily: FONT.body }}>
      {/* ===== Sidebar ===== */}
      <aside
        style={{
          width: 248,
          flexShrink: 0,
          background: C.card,
          borderRight: `1px solid rgba(40,45,35,.10)`,
          display: 'flex',
          flexDirection: 'column',
          padding: '22px 16px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '4px 8px 24px' }}>
          <div style={{ width: 38, height: 38, borderRadius: 12, background: C.sageTint, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            {Icon.shield(C.sage, 20)}
          </div>
          <div>
            <div style={{ fontFamily: FONT.display, fontSize: 21, fontWeight: 500, lineHeight: 1 }}>Lentera</div>
            <div style={{ fontSize: 11, color: C.muted, marginTop: 2 }}>Konsol moderasi</div>
          </div>
        </div>

        <nav style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          {NAV.map((n) => (
            <NavLink key={n.to} to={n.to} style={{ textDecoration: 'none' }}>
              {({ isActive }) => (
                <div
                  className="lt-navitem"
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 12,
                    padding: '11px 12px',
                    borderRadius: 11,
                    cursor: 'pointer',
                    background: isActive ? C.sageTint : 'transparent',
                  }}
                >
                  <span style={{ width: 19, display: 'flex' }}>{n.icon(isActive ? C.sage : C.muted)}</span>
                  <span style={{ flex: 1, fontSize: 14, fontWeight: isActive ? 600 : 500, color: isActive ? C.sageDeep : C.text2 }}>
                    {n.label}
                  </span>
                  {n.badge && badges[n.badge] > 0 && (
                    <span
                      style={{
                        minWidth: 20,
                        height: 20,
                        padding: '0 6px',
                        borderRadius: 10,
                        background: n.kind === 'lav' ? 'rgba(126,114,184,.16)' : 'rgba(174,100,80,.14)',
                        color: n.kind === 'lav' ? C.lavender : C.clay,
                        fontSize: 11,
                        fontWeight: 700,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                      }}
                    >
                      {badges[n.badge]}
                    </span>
                  )}
                </div>
              )}
            </NavLink>
          ))}
        </nav>

        <div style={{ flex: 1 }} />

        <div style={{ padding: 14, borderRadius: 14, background: 'rgba(92,129,102,.10)', border: '1px solid rgba(92,129,102,.22)', marginBottom: 14 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
            {Icon.shield(C.sage, 15)}
            <span style={{ fontSize: 12.5, fontWeight: 600, color: C.sage }}>Sesi aman</span>
          </div>
          <div style={{ fontSize: 11.5, color: C.muted, lineHeight: 1.5 }}>2FA aktif · 1 moderator · log audit</div>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '6px 4px' }}>
          <div style={{ width: 32, height: 32, borderRadius: 10, background: C.sage, color: '#F4F8F2', display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: FONT.display, fontSize: 15 }}>
            {(user?.handle || 'A')[0].toUpperCase()}
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: 13, fontWeight: 600 }}>{user?.handle || 'Admin'}</div>
            <div style={{ fontSize: 11, color: C.muted }}>Moderator tunggal</div>
          </div>
          <span onClick={logout} className="lt-act" title="Keluar" style={{ cursor: 'pointer', fontSize: 11.5, fontWeight: 600, color: C.muted }}>
            Keluar
          </span>
        </div>
      </aside>

      {/* ===== Main ===== */}
      <main style={{ flex: 1, minWidth: 0, display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
        <header style={{ flexShrink: 0, display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '22px 32px', borderBottom: `1px solid ${C.line}` }}>
          <div>
            <div style={{ fontFamily: FONT.display, fontSize: 26, fontWeight: 500, lineHeight: 1.1 }}>{meta.t}</div>
            <div style={{ fontSize: 13, color: C.muted, marginTop: 3 }}>{meta.s}</div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 7, padding: '8px 13px', borderRadius: 10, background: 'rgba(92,129,102,.12)' }}>
              <span style={{ width: 7, height: 7, borderRadius: '50%', background: C.sage }} />
              <span style={{ fontSize: 12.5, fontWeight: 600, color: C.sage }}>2FA terverifikasi</span>
            </div>
            <div style={{ width: 38, height: 38, borderRadius: 11, background: C.card, border: `1px solid rgba(40,45,35,.10)`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              {Icon.search(C.muted)}
            </div>
          </div>
        </header>

        <div className="lt-scroll" style={{ flex: 1, overflow: 'auto', padding: '26px 32px 40px' }}>
          <Outlet />
        </div>
      </main>
    </div>
  );
}
