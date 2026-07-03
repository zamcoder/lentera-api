import { useToast } from '../context/ToastContext';
import { api } from '../lib/api';
import { useCursorList } from '../hooks/useCursorList';
import { avatarColor, C, FONT, initial, STATUS_META } from '../lib/theme';
import { CenterState, EmptyState, ErrorState, LoadMore, Spinner } from '../ui/primitives';

/** Tindakan akun (§B6). GET /mod/accounts · POST /mod/accounts/{id}/action. */
export default function Accounts() {
  const list = useCursorList('/mod/accounts');
  const toast = useToast();

  async function act(u, action, newStatus, msg) {
    const prev = u.status;
    list.patchById(u.id, { status: newStatus });
    try {
      await api.post(`/mod/accounts/${u.id}/action`, { action });
      toast(msg);
    } catch (e) {
      list.patchById(u.id, { status: prev });
      toast(e.message || 'Gagal menindak akun.');
    }
  }

  if (list.loading)
    return (
      <CenterState>
        <Spinner size={24} />
      </CenterState>
    );
  if (list.error) return <ErrorState error={list.error} onRetry={list.refresh} />;

  if (list.items.length === 0) return <EmptyState emoji="🌿" title="Belum ada anggota" sub="Akun akan muncul saat komunitas tumbuh." />;

  return (
    <div style={{ borderRadius: 18, background: C.card, border: `1px solid ${C.line}`, overflow: 'hidden' }}>
      <div style={{ display: 'grid', gridTemplateColumns: '1.4fr 110px 90px 1fr', gap: 14, padding: '12px 22px', background: 'rgba(40,45,35,.03)', fontSize: 11.5, fontWeight: 600, color: C.muted, textTransform: 'uppercase', letterSpacing: 0.4 }}>
        <span>Akun</span>
        <span>Status</span>
        <span>Laporan</span>
        <span style={{ textAlign: 'right' }}>Tindakan</span>
      </div>

      {list.items.map((u) => {
        const [avBg, avFg] = avatarColor(u.handle);
        const sm = STATUS_META[u.status] || STATUS_META.active;
        const reported = u.reports_count > 0;
        return (
          <div key={u.id} className="lt-row" style={{ display: 'grid', gridTemplateColumns: '1.4fr 110px 90px 1fr', gap: 14, padding: '14px 22px', alignItems: 'center', borderTop: `1px solid rgba(40,45,35,.06)`, transition: 'background .12s' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <span style={{ width: 30, height: 30, borderRadius: 9, background: avBg, color: avFg, display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: FONT.display, fontSize: 15 }}>
                {initial(u.handle)}
              </span>
              <div>
                <div style={{ fontSize: 13.5, fontWeight: 600 }}>{u.handle}</div>
                <div style={{ fontSize: 11.5, color: C.dim }}>anggota</div>
              </div>
            </div>

            <span style={{ display: 'inline-flex', alignItems: 'center', padding: '4px 10px', borderRadius: 8, fontSize: 12, fontWeight: 600, background: sm.bg, color: sm.color, width: 'fit-content' }}>{sm.label}</span>

            <span style={{ fontSize: 13, fontWeight: 600, color: reported ? C.clay : C.dim }}>{reported ? `${u.reports_count}×` : '—'}</span>

            <div style={{ display: 'flex', gap: 7, justifyContent: 'flex-end' }}>
              <Btn bg="rgba(40,45,35,.05)" color={C.text2} onClick={() => act(u, 'mute', 'muted', `${u.handle} dibisukan`)}>
                Bisukan
              </Btn>
              <Btn bg="rgba(174,100,80,.12)" color={C.clay} onClick={() => act(u, 'limit', 'limited', `${u.handle} dibatasi`)}>
                Batasi
              </Btn>
              <Btn bg={C.clay} color="#F8EEEA" bold onClick={() => act(u, 'block', 'blocked', `${u.handle} diblokir`)}>
                Blokir
              </Btn>
            </div>
          </div>
        );
      })}

      <LoadMore show={list.hasMore} onClick={list.loadMore} loading={list.loadingMore} bordered />
    </div>
  );
}

function Btn({ children, bg, color, bold, onClick }) {
  return (
    <div onClick={onClick} className="lt-act" style={{ cursor: 'pointer', padding: '7px 12px', borderRadius: 9, background: bg, color, fontSize: 12, fontWeight: bold ? 700 : 600 }}>
      {children}
    </div>
  );
}
