import { useBadges } from '../context/BadgeContext';
import { useToast } from '../context/ToastContext';
import { api } from '../lib/api';
import { useCursorList } from '../hooks/useCursorList';
import { avatarColor, C, FONT, initial, timeAgo } from '../lib/theme';
import { ActBtn, CenterState, EmptyState, ErrorState, LoadMore, Spinner } from '../ui/primitives';

const REASON_LABEL = {
  spam: 'Spam / promosi',
  harassment: 'Pelecehan',
  self_harm: 'Isyarat menyakiti diri',
  hate: 'Ujaran kebencian',
  other: 'Tidak pantas',
};

const SURFACE_LABEL = { gratitude: 'Dinding Syukur', strength: 'Kirim kekuatan', prompt: 'Prompt bersama', circle: 'Lingkaran' };

/** Laporan pengguna (§B4). GET /mod/reports · POST /mod/reports/action. */
export default function Reports() {
  const list = useCursorList('/mod/reports');
  const toast = useToast();
  const badges = useBadges();

  async function act(post, decision, msg) {
    list.removeById(post.id);
    try {
      await api.post('/mod/reports/action', { post_id: post.id, decision });
      toast(msg);
      badges.refresh();
    } catch (e) {
      toast(e.message || 'Gagal menindak.');
      list.refresh();
    }
  }

  if (list.loading)
    return (
      <CenterState>
        <Spinner size={24} />
      </CenterState>
    );
  if (list.error) return <ErrorState error={list.error} onRetry={list.refresh} />;

  if (list.items.length === 0)
    return <EmptyState emoji="💚" title="Tidak ada laporan terbuka" sub="Semua laporan sudah ditangani." />;

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
      {list.items.map((r) => {
        const [avBg, avFg] = avatarColor(r.author || 'anon');
        const firstReason = r.reasons?.[0]?.reason;
        return (
          <div key={r.id} style={{ borderRadius: 18, background: C.card, border: `1px solid ${C.line}`, overflow: 'hidden' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '15px 20px 12px' }}>
              <span style={{ width: 34, height: 34, borderRadius: 10, background: avBg, color: avFg, display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: FONT.display, fontSize: 16 }}>
                {initial(r.author)}
              </span>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ fontSize: 14, fontWeight: 600 }}>{r.author || 'Anonim'}</span>
                  <span style={{ fontSize: 11, color: C.muted, background: 'rgba(40,45,35,.06)', padding: '2px 8px', borderRadius: 7 }}>{SURFACE_LABEL[r.surface] || r.surface}</span>
                </div>
                <div style={{ fontSize: 12, color: C.dim, marginTop: 2 }}>{r.reasons?.length ? timeAgo(r.reasons[0].created_at) : ''}</div>
              </div>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '5px 11px', borderRadius: 9, fontSize: 12, fontWeight: 700, background: 'rgba(174,100,80,.13)', color: C.clay }}>
                🚩 {r.reports_count}× dilaporkan
              </span>
            </div>

            <div style={{ padding: '0 20px 14px', fontSize: 14.5, lineHeight: 1.6, color: C.text }}>{r.body}</div>

            <div style={{ padding: '12px 20px', background: 'rgba(174,100,80,.06)', borderTop: `1px solid rgba(40,45,35,.06)`, display: 'flex', alignItems: 'center', gap: 10 }}>
              <span style={{ fontSize: 12.5, color: C.muted }}>Alasan pelapor:</span>
              <span style={{ fontSize: 12.5, fontWeight: 600, color: C.clay }}>{REASON_LABEL[firstReason] || firstReason || '—'}</span>
            </div>

            <div style={{ display: 'flex', gap: 9, padding: '13px 20px', borderTop: `1px solid rgba(40,45,35,.06)`, alignItems: 'center' }}>
              <ActBtn variant="primary" style={{ background: 'rgba(92,129,102,.14)', color: C.sageDeep }} onClick={() => act(r, 'keep', 'Dibiarkan tampil — laporan ditutup')}>
                Biarkan tampil
              </ActBtn>
              <ActBtn variant="soft" onClick={() => act(r, 'hide', 'Kiriman disembunyikan')}>
                Sembunyikan
              </ActBtn>
              <div style={{ flex: 1 }} />
              <ActBtn variant="ghost" onClick={() => act(r, 'remove', 'Dihapus & akun ditindak 🛡️')}>
                Hapus &amp; tindak akun
              </ActBtn>
            </div>
          </div>
        );
      })}
      <LoadMore show={list.hasMore} onClick={list.loadMore} loading={list.loadingMore} />
    </div>
  );
}
