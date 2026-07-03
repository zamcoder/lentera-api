import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api } from '../lib/api';

const BadgeCtx = createContext({ queue: 0, reports: 0, refresh: () => {} });
export const useBadges = () => useContext(BadgeCtx);

export function BadgeProvider({ children }) {
  const [counts, setCounts] = useState({ queue: 0, reports: 0 });

  const refresh = useCallback(async () => {
    try {
      const m = await api.get('/mod/metrics');
      setCounts({ queue: m.attention?.queue ?? 0, reports: m.attention?.reports ?? 0 });
    } catch {
      /* diamkan; badge bukan hal kritis */
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  return <BadgeCtx.Provider value={{ ...counts, refresh }}>{children}</BadgeCtx.Provider>;
}
