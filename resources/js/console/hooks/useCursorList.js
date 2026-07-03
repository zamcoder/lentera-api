import { useCallback, useEffect, useState } from 'react';
import { api } from '../lib/api';

/**
 * useCursorList — memuat daftar berpaginasi cursor dari API dan mendukung
 * pola "Muat lebih banyak" (§ aturan styling konsol). Mengembalikan item,
 * status, dan helper untuk memuat lagi, refresh, serta membuang item
 * secara optimistik setelah tindakan moderasi.
 *
 * @param {string} path        endpoint dasar (mis. "/mod/queue")
 * @param {object} extraMeta   fungsi opsional memetakan respons → meta tambahan
 */
export function useCursorList(path, { mapMeta } = {}) {
  const [items, setItems] = useState([]);
  const [cursor, setCursor] = useState(null);
  const [meta, setMeta] = useState({});
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState(null);

  const load = useCallback(
    async (nextCursor, append) => {
      const sep = path.includes('?') ? '&' : '?';
      const url = nextCursor ? `${path}${sep}cursor=${encodeURIComponent(nextCursor)}` : path;
      try {
        const res = await api.get(url);
        setItems((prev) => (append ? [...prev, ...(res.data || [])] : res.data || []));
        setCursor(res.next_cursor || null);
        setMeta(mapMeta ? mapMeta(res) : res);
        setError(null);
      } catch (e) {
        setError(e);
      }
    },
    [path, mapMeta],
  );

  useEffect(() => {
    setLoading(true);
    load(null, false).finally(() => setLoading(false));
  }, [load]);

  const loadMore = useCallback(async () => {
    if (!cursor) return;
    setLoadingMore(true);
    await load(cursor, true);
    setLoadingMore(false);
  }, [cursor, load]);

  const refresh = useCallback(async () => {
    setLoading(true);
    await load(null, false);
    setLoading(false);
  }, [load]);

  // Buang item dari daftar (optimistik) setelah tindakan.
  const removeById = useCallback((id) => {
    setItems((prev) => prev.filter((x) => x.id !== id));
  }, []);

  const patchById = useCallback((id, patch) => {
    setItems((prev) => prev.map((x) => (x.id === id ? { ...x, ...patch } : x)));
  }, []);

  return {
    items,
    setItems,
    meta,
    hasMore: !!cursor,
    loading,
    loadingMore,
    error,
    loadMore,
    refresh,
    removeById,
    patchById,
  };
}
