import { useBadges } from '../context/BadgeContext';
import { useToast } from '../context/ToastContext';
import { api } from '../lib/api';
import { useCursorList } from '../hooks/useCursorList';
import { avatarColor, C, FONT, flagStyle, initial, timeAgo } from '../lib/theme';
import { Icon } from '../lib/icons';
import { ActBtn, CenterState, EmptyState, ErrorState, LoadMore, Spinner } from '../ui/primitives';

const SURFACE_LABEL = {
  gratitude: 'Dinding Syukur',
  strength: 'Kirim kekuatan',
  prompt: 'Prompt bersama',
  circle: 'Lingkaran',
};

/** Antrean moderasi (§B3). GET /mod/queue · aksi POST /mod/action. */
export default function Queue() {
  const list = useCursorList('/mod/queue');
  const toast = useToast();
  const badges = useBadges();

  async function act(post, action, msg) {
    // Optimistik: hapus dari daftar dulu agar terasa responsif.
    list.removeById(post.id);
    try {
      await api.post('/mod/action', { post_id: post.id, action });
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

  return (
    <>
      {/* Banner konteks moderasi */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 18, padding: '13px 16px', borderRadius: 13, background: 'rgba(126,114,184,.10)', border: '1px solid rgba(126,114,184,.22)' }}>
        {Icon.sparkle(C.lavender, 17)}
        <span style={{ fontSize: 12.5, color: C.lavDeep, lineHeight: 1.5 }}>
          Disaring otomatis oleh AI (Gemini) + filter kata. Kiriman ditahan sampai kamu tinjau. <b>Isyarat menyakiti diri</b> diberi penanganan khusus.
        </span>
      </div>

      {list.items.length === 0 ? (
        <EmptyState emoji="🌿" title="Antrean bersih" sub="Tidak ada kiriman menunggu. Ruang sedang tenang." />
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          {list.items.map((q) => (
            <QueueCard key={q.id} q={q} onAct={act} />
          ))}
          <LoadMore show={list.hasMore} onClick={list.loadMore} loading={list.loadingMore} />
        </div>
      )}
    </>
  );
}

function QueueCard({ q, onAct }) {
  const selfHarm = q.self_harm;
  const f = flagStyle(q);
  const [avBg, avFg] = avatarColor(q.author || 'anon');
  const border = selfHarm ? 'rgba(126,114,184,.4)' : C.line;

  return (
    <div style={{ borderRadius: 18, background: C.card, border: `1px solid ${border}`, overflow: 'hidden' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 11, padding: '15px 20px 12px' }}>
        <span style={{ width: 34, height: 34, borderRadius: 10, background: avBg, color: avFg, display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: FONT.display, fontSize: 16 }}>
          {initial(q.author)}
        </span>
        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <span style={{ fontSize: 14, fontWeight: 600 }}>{q.author || 'Anonim'}</span>
            <span style={{ fontSize: 11, color: C.muted, background: 'rgba(40,45,35,.06)', padding: '2px 8px', borderRadius: 7 }}>{SURFACE_LABEL[q.surface] || q.surface}</span>
          </div>
          <div style={{ fontSize: 12, color: C.dim, marginTop: 2 }}>{timeAgo(q.created_at)}</div>
        </div>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '5px 11px', borderRadius: 9, fontSize: 12, fontWeight: 700, background: f.tint, color: f.color }}>
          {f.label}
        </span>
      </div>

      <div style={{ padding: '0 20px 14px', fontSize: 14.5, lineHeight: 1.6, color: C.text }}>{q.body}</div>

      {q.mod_reason && (
        <div style={{ padding: '12px 20px', background: f.reasonBg, borderTop: `1px solid rgba(40,45,35,.06)`, display: 'flex', alignItems: 'center', gap: 10 }}>
          {Icon.warn(f.color, 15)}
          <span style={{ flex: 1, fontSize: 12.5, color: C.text2, lineHeight: 1.45 }}>{q.mod_reason}</span>
        </div>
      )}

      <div style={{ display: 'flex', gap: 9, padding: '13px 20px', borderTop: `1px solid rgba(40,45,35,.06)`, alignItems: 'center' }}>
        {selfHarm ? (
          <>
            <ActBtn variant="lav" onClick={() => onAct(q, 'offer_support', 'Dukungan ditawarkan 💜')}>
              Tawarkan dukungan
            </ActBtn>
            <ActBtn variant="soft" onClick={() => onAct(q, 'hold', 'Disembunyikan dengan lembut')}>
              Sembunyikan lembut
            </ActBtn>
            <div style={{ flex: 1 }} />
            <ActBtn variant="ghost" onClick={() => onAct(q, 'escalate', 'Dieskalasi ke penanganan krisis')}>
              Eskalasi
            </ActBtn>
          </>
        ) : (
          <>
            <ActBtn variant="primary" onClick={() => onAct(q, 'approve', 'Disetujui & tayang ✓')}>
              Setujui & tayangkan
            </ActBtn>
            <ActBtn variant="soft" onClick={() => onAct(q, 'soften', 'Dihaluskan & tayang ✓')}>
              Haluskan
            </ActBtn>
            <div style={{ flex: 1 }} />
            <ActBtn variant="ghost" onClick={() => onAct(q, 'reject', 'Kiriman ditolak')}>
              Tolak
            </ActBtn>
          </>
        )}
      </div>
    </div>
  );
}
