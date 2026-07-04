import { useEffect, useMemo, useState } from 'react';
import { api } from '../lib/api';
import { C, FONT } from '../lib/theme';
import { CenterState, EmptyState, ErrorState, Spinner } from '../ui/primitives';

/** Pendaftar waitlist landing (GET /mod/subscribers) — daftar + cari + export CSV. */
export default function Subscribers() {
  const [state, setState] = useState({ loading: true, error: null, rows: [], total: 0 });
  const [q, setQ] = useState('');

  useEffect(() => {
    let alive = true;
    api
      .get('/mod/subscribers')
      .then((d) => alive && setState({ loading: false, error: null, rows: d.data || [], total: d.total ?? (d.data?.length || 0) }))
      .catch((e) => alive && setState({ loading: false, error: e, rows: [], total: 0 }));
    return () => {
      alive = false;
    };
  }, []);

  const filtered = useMemo(() => {
    const s = q.trim().toLowerCase();
    if (!s) return state.rows;
    return state.rows.filter((r) => (r.email || '').toLowerCase().includes(s));
  }, [q, state.rows]);

  function exportCsv() {
    const cols = ['email', 'source', 'ip', 'created_at'];
    const lines = [cols.join(',')].concat(filtered.map((r) => cols.map((k) => csvCell(r[k])).join(',')));
    const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `waitlist-lentera.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  if (state.loading)
    return (
      <CenterState>
        <Spinner size={24} />
      </CenterState>
    );
  if (state.error) return <ErrorState error={state.error} onRetry={() => window.location.reload()} />;
  if (state.total === 0)
    return <EmptyState emoji="🌱" title="Belum ada pendaftar" sub="Email dari landing page temanlentera.id akan muncul di sini." />;

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
        <div style={{ fontSize: 13.5, color: C.muted }}>
          <b style={{ color: C.text, fontSize: 15 }}>{state.total}</b> total pendaftar
          {q && ` · ${filtered.length} cocok`}
        </div>
        <div style={{ flex: 1 }} />
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Cari email…"
          style={{
            padding: '9px 14px',
            borderRadius: 10,
            border: `1px solid ${C.line}`,
            background: C.card,
            fontSize: 13.5,
            color: C.text,
            minWidth: 200,
            outline: 'none',
            fontFamily: FONT.body,
          }}
        />
        <div
          onClick={exportCsv}
          className="lt-act"
          style={{ cursor: 'pointer', padding: '9px 16px', borderRadius: 10, background: C.sage, color: '#F4F8F2', fontSize: 13, fontWeight: 600 }}
        >
          Export CSV
        </div>
      </div>

      <div style={{ borderRadius: 18, background: C.card, border: `1px solid ${C.line}`, overflow: 'hidden' }}>
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: '2fr 100px 1fr 130px',
            gap: 14,
            padding: '12px 22px',
            background: 'rgba(40,45,35,.03)',
            fontSize: 11.5,
            fontWeight: 600,
            color: C.muted,
            textTransform: 'uppercase',
            letterSpacing: 0.4,
          }}
        >
          <span>Email</span>
          <span>Sumber</span>
          <span>IP</span>
          <span style={{ textAlign: 'right' }}>Terdaftar</span>
        </div>

        {filtered.map((r) => (
          <div
            key={r.id}
            className="lt-row"
            style={{
              display: 'grid',
              gridTemplateColumns: '2fr 100px 1fr 130px',
              gap: 14,
              padding: '13px 22px',
              alignItems: 'center',
              borderTop: `1px solid rgba(40,45,35,.06)`,
            }}
          >
            <span style={{ fontSize: 13.5, fontWeight: 600, color: C.text, wordBreak: 'break-all' }}>{r.email}</span>
            <span style={{ fontSize: 12.5, color: C.dim }}>{r.source || '—'}</span>
            <span style={{ fontSize: 12.5, color: C.dim, fontFamily: FONT.mono }}>{r.ip || '—'}</span>
            <span style={{ fontSize: 12.5, color: C.muted, textAlign: 'right' }}>{fmtDate(r.created_at)}</span>
          </div>
        ))}

        {filtered.length === 0 && (
          <div style={{ padding: 24, textAlign: 'center', fontSize: 13, color: C.muted }}>Tak ada email cocok "{q}".</div>
        )}
      </div>
    </div>
  );
}

function csvCell(v) {
  const s = v == null ? '' : String(v);
  return /[",\n\r]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
}

function fmtDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}
