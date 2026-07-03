import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../lib/api';
import { C, FONT } from '../lib/theme';
import { Icon } from '../lib/icons';
import { CenterState, ErrorState, Spinner } from '../ui/primitives';

/** Ringkasan / Kesehatan komunitas (§B2). Data dari GET /mod/metrics (§A6). */
export default function Overview() {
  const nav = useNavigate();
  const [m, setM] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    api
      .get('/mod/metrics')
      .then((d) => {
        setM(d);
        setError(null);
      })
      .catch(setError)
      .finally(() => setLoading(false));
  };
  useEffect(load, []);

  if (loading)
    return (
      <CenterState>
        <Spinner size={24} />
      </CenterState>
    );
  if (error) return <ErrorState error={error} onRetry={load} />;

  const health = m.health;
  const cards = [
    { label: 'Rasio positif', value: `${Math.round((m.cards.positive_ratio || 0) * 100)}%`, trend: 'kehangatan ruang', trendColor: C.sage, tint: C.sageTint, icon: Icon.heart(C.sage) },
    { label: 'Antrean menunggu', value: String(m.cards.queue_waiting ?? 0), trend: 'perlu ditinjau', trendColor: C.muted, tint: C.lavTint, icon: Icon.bolt(C.lavender) },
    { label: 'Kecepatan moderasi', value: fmtSpeed(m.cards.moderation_speed_seconds), trend: 'rata-rata', trendColor: C.muted, tint: C.slateTint, icon: Icon.clock(C.slate) },
    { label: 'Anggota aktif', value: String(m.cards.active_members ?? 0), trend: '7 hari terakhir', trendColor: C.muted, tint: 'rgba(40,45,35,.07)', icon: Icon.users(C.muted) },
  ];

  const attention = [
    { title: 'Isyarat menyakiti diri', sub: 'Penanganan khusus — perlu kehangatan', count: m.attention.self_harm_held, tint: C.lavTint, color: C.lavender, icon: Icon.heart(C.lavender), to: '/antrean' },
    { title: 'Kiriman menunggu di antrean', sub: 'Disaring AI + filter kata', count: m.attention.queue, tint: C.sageTint, color: C.sage, icon: Icon.bolt(C.sage), to: '/antrean' },
    { title: 'Laporan pengguna terbuka', sub: 'Konten yang dilaporkan anggota', count: m.attention.reports, tint: C.clayTint, color: C.clay, icon: Icon.flag(C.clay), to: '/laporan' },
  ];

  return (
    <>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.55fr', gap: 16, marginBottom: 16 }}>
        {/* hero */}
        <div style={{ padding: '22px 24px', borderRadius: 18, background: 'linear-gradient(150deg, #5C8166, #4A6B54)', color: '#EAF1E8', display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
          <div>
            <div style={{ fontSize: 13, opacity: 0.85, marginBottom: 6 }}>Kesehatan komunitas</div>
            <div style={{ display: 'flex', alignItems: 'baseline', gap: 10 }}>
              <span style={{ fontFamily: FONT.display, fontSize: 52, fontWeight: 500, lineHeight: 1 }}>{health.score}</span>
              <span style={{ fontSize: 16, fontWeight: 600, padding: '4px 11px', borderRadius: 9, background: 'rgba(255,255,255,.18)' }}>{health.label}</span>
            </div>
          </div>
          <div style={{ fontSize: 12.5, lineHeight: 1.55, opacity: 0.9, marginTop: 18 }}>
            Indikator kehangatan ruang — rasio positif, kecepatan moderasi, dan sedikitnya laporan terbuka.
          </div>
        </div>

        {/* metric cards */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
          {cards.map((c) => (
            <div key={c.label} style={{ padding: '17px 18px', borderRadius: 16, background: C.card, border: `1px solid ${C.line}` }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <span style={{ fontSize: 12.5, color: C.muted }}>{c.label}</span>
                <span style={{ width: 28, height: 28, borderRadius: 9, background: c.tint, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>{c.icon}</span>
              </div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 8 }}>
                <span style={{ fontFamily: FONT.display, fontSize: 27, fontWeight: 500, lineHeight: 1 }}>{c.value}</span>
                <span style={{ fontSize: 12, fontWeight: 600, color: c.trendColor }}>{c.trend}</span>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* needs attention */}
      <div style={{ borderRadius: 18, background: C.card, border: `1px solid ${C.line}`, overflow: 'hidden' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '18px 22px', borderBottom: `1px solid rgba(40,45,35,.07)` }}>
          <div style={{ fontSize: 15, fontWeight: 600 }}>Perlu perhatianmu</div>
          <div style={{ fontSize: 12.5, color: C.muted }}>Diurutkan dari paling mendesak</div>
        </div>
        {attention.map((a) => (
          <div
            key={a.title}
            onClick={() => nav(a.to)}
            className="lt-row"
            style={{ display: 'flex', alignItems: 'center', gap: 14, padding: '15px 22px', cursor: 'pointer', borderTop: `1px solid rgba(40,45,35,.06)`, transition: 'background .12s' }}
          >
            <span style={{ width: 36, height: 36, borderRadius: 11, background: a.tint, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>{a.icon}</span>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ fontSize: 14, fontWeight: 600 }}>{a.title}</div>
              <div style={{ fontSize: 12.5, color: C.muted }}>{a.sub}</div>
            </div>
            <span style={{ fontSize: 12.5, fontWeight: 700, color: a.color, background: a.tint, padding: '5px 11px', borderRadius: 9 }}>{a.count}</span>
            {Icon.chevron()}
          </div>
        ))}
      </div>
    </>
  );
}

function fmtSpeed(seconds) {
  if (!seconds && seconds !== 0) return '—';
  if (seconds < 90) return `${Math.max(1, Math.round(seconds))} dtk`;
  return `~${Math.round(seconds / 60)} mnt`;
}
