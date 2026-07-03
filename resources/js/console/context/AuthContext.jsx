import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { api, getToken, setToken } from '../lib/api';

const AuthCtx = createContext(null);
export const useAuth = () => useContext(AuthCtx);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [ready, setReady] = useState(false);

  // Validasi token yang tersimpan saat aplikasi dibuka.
  useEffect(() => {
    let alive = true;
    (async () => {
      const t = getToken();
      if (!t) {
        setReady(true);
        return;
      }
      try {
        const { user } = await api.get('/auth/me');
        if (alive) setUser(user);
      } catch {
        setToken(null); // token kedaluwarsa/cabut
      } finally {
        if (alive) setReady(true);
      }
    })();
    return () => {
      alive = false;
    };
  }, []);

  // Dipanggil setelah 2FA lolos: token konsol (ability mod) + profil.
  const establish = useCallback((token, u) => {
    setToken(token);
    setUser(u);
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      /* abaikan */
    }
    setToken(null);
    setUser(null);
  }, []);

  const isModerator = !!user && user.role === 'admin';

  return (
    <AuthCtx.Provider value={{ user, ready, isModerator, establish, logout, setUser }}>
      {children}
    </AuthCtx.Provider>
  );
}
