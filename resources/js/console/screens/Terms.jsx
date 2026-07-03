import { useCallback, useEffect, useState } from 'react';
import { useToast } from '../context/ToastContext';
import { api } from '../lib/api';
import { useCursorList } from '../hooks/useCursorList';
import { C, FONT } from '../lib/theme';
import { Icon } from '../lib/icons';
import { CenterState, ErrorState, LoadMore, Spinner } from '../ui/primitives';

/** Kata terlarang (§B5). /mod/terms CRUD + saran varian Gemini. */
export default function Terms() {
  const list = useCursorList('/mod/terms');
  const toast = useToast();
  const [input, setInput] = useState('');
  const [suggestSeed, setSuggestSeed] = useState('');
  const [suggestions, setSuggestions] = useState([]);

  // Ambil saran varian untuk pola paling sering menahan (atau input).
  const fetchSuggestions = useCallback(async (term) => {
    if (!term) {
      setSuggestions([]);
      return;
    }
    try {
      const res = await api.get(`/mod/terms/suggest?term=${encodeURIComponent(term)}`);
      setSuggestions(res.suggestions || []);
      setSuggestSeed(term);
    } catch {
      setSuggestions([]);
    }
  }, []);

  useEffect(() => {
    // Seed awal: pola pertama (terurut hits terbanyak dari API).
    if (!list.loading && list.items.length && !suggestSeed) {
      fetchSuggestions(list.items[0].pattern);
    }
  }, [list.loading, list.items, suggestSeed, fetchSuggestions]);

  async function addTerm(pattern) {
    const w = pattern.trim().toLowerCase();
    if (!w) {
      toast('Ketik kata atau pola dulu');
      return;
    }
    if (list.items.some((t) => t.pattern === w)) {
      toast('Pola itu sudah ada');
      return;
    }
    try {
      const res = await api.post('/mod/terms', { pattern: w });
      list.setItems((prev) => [{ ...res.term }, ...prev]);
      setInput('');
      setSuggestions((s) => s.filter((x) => x !== w));
      toast('Pola ditambahkan ke filter ✓');
    } catch (e) {
      toast(e.message || 'Gagal menambah pola.');
    }
  }

  async function removeTerm(t) {
    list.removeById(t.id);
    try {
      await api.del(`/mod/terms/${t.id}`);
      toast('Pola dihapus');
    } catch (e) {
      toast(e.message || 'Gagal menghapus.');
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
    <div style={{ display: 'grid', gridTemplateColumns: '1.4fr 1fr', gap: 16, alignItems: 'start' }}>
      {/* daftar */}
      <div style={{ borderRadius: 18, background: C.card, border: `1px solid ${C.line}`, overflow: 'hidden' }}>
        <div style={{ padding: '16px 20px', borderBottom: `1px solid rgba(40,45,35,.07)` }}>
          <div style={{ fontSize: 15, fontWeight: 600, marginBottom: 12 }}>Daftar kata &amp; pola terlarang</div>
          <div style={{ display: 'flex', gap: 9 }}>
            <input
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && addTerm(input)}
              placeholder="Tambah kata atau pola regex…"
              style={{ flex: 1, minWidth: 0, border: `1px solid ${C.border}`, outline: 'none', background: C.field, borderRadius: 11, padding: '10px 13px', fontFamily: FONT.body, fontSize: 13.5, color: C.text }}
            />
            <div onClick={() => addTerm(input)} className="lt-act" style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, padding: '10px 16px', borderRadius: 11, background: C.sage, color: '#F4F8F2', fontSize: 13, fontWeight: 700 }}>
              + Tambah
            </div>
          </div>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: '1fr 110px 60px', gap: 12, padding: '10px 20px', background: 'rgba(40,45,35,.03)', fontSize: 11.5, fontWeight: 600, color: C.muted, textTransform: 'uppercase', letterSpacing: 0.4 }}>
          <span>Pola</span>
          <span>Ditahan</span>
          <span />
        </div>

        {list.items.map((t) => (
          <div key={t.id} className="lt-row" style={{ display: 'grid', gridTemplateColumns: '1fr 110px 60px', gap: 12, padding: '13px 20px', alignItems: 'center', borderTop: `1px solid rgba(40,45,35,.06)`, transition: 'background .12s' }}>
            <span style={{ fontFamily: FONT.mono, fontSize: 13.5, fontWeight: 600, color: C.text }}>
              {t.pattern}
              {t.is_regex && <span style={{ marginLeft: 6, fontSize: 10, color: C.lavender, fontFamily: FONT.body }}>regex</span>}
            </span>
            <span style={{ fontSize: 12.5, color: C.muted }}>{t.hits}× ditahan</span>
            <span onClick={() => removeTerm(t)} className="lt-act" style={{ cursor: 'pointer', fontSize: 12.5, fontWeight: 600, color: C.clay, textAlign: 'right' }}>
              Hapus
            </span>
          </div>
        ))}

        <LoadMore show={list.hasMore} onClick={list.loadMore} loading={list.loadingMore} bordered />
      </div>

      {/* saran AI */}
      <div style={{ borderRadius: 18, background: 'rgba(126,114,184,.08)', border: '1px solid rgba(126,114,184,.2)', padding: 20 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginBottom: 10 }}>
          {Icon.sparkle(C.lavender, 18)}
          <span style={{ fontSize: 14.5, fontWeight: 700, color: C.lavDeep }}>Saran AI</span>
        </div>
        <div style={{ fontSize: 12.5, color: '#5A5275', lineHeight: 1.55, marginBottom: 14 }}>
          Gemini mengusulkan varian &amp; salah-ketik{suggestSeed ? ` dari "${suggestSeed}"` : ''} agar penyaringan lebih tahan.
        </div>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
          {suggestions.map((w) => (
            <div key={w} onClick={() => addTerm(w)} className="lt-act" style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, padding: '7px 12px', borderRadius: 10, background: C.field, border: '1px solid rgba(126,114,184,.25)', fontSize: 12.5, fontWeight: 600, color: C.lavDeep }}>
              <span style={{ fontFamily: FONT.mono }}>{w}</span>
              <span style={{ color: C.lavender, fontWeight: 800 }}>+</span>
            </div>
          ))}
          {suggestions.length === 0 && <div style={{ fontSize: 12.5, color: '#9088A8', fontStyle: 'italic' }}>Semua saran sudah ditambahkan. 🌿</div>}
        </div>
      </div>
    </div>
  );
}
