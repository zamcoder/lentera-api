import { createContext, useCallback, useContext, useRef, useState } from 'react';
import { C, FONT } from '../lib/theme';

const ToastCtx = createContext(null);
export const useToast = () => useContext(ToastCtx);

export function ToastProvider({ children }) {
  const [toast, setToast] = useState(null);
  const timer = useRef();

  const show = useCallback((msg) => {
    clearTimeout(timer.current);
    setToast(msg);
    timer.current = setTimeout(() => setToast(null), 2400);
  }, []);

  return (
    <ToastCtx.Provider value={show}>
      {children}
      {toast && (
        <div
          key={toast + Date.now()}
          style={{
            position: 'fixed',
            left: '50%',
            bottom: 28,
            transform: 'translateX(-50%)',
            zIndex: 200,
            display: 'flex',
            alignItems: 'center',
            gap: 9,
            padding: '12px 20px',
            borderRadius: 13,
            background: C.text,
            color: '#F4F2EA',
            fontSize: 13.5,
            fontWeight: 600,
            fontFamily: FONT.body,
            boxShadow: '0 10px 30px rgba(20,24,18,.3)',
            animation: 'lt-toast 2.4s ease both',
          }}
        >
          {toast}
        </div>
      )}
    </ToastCtx.Provider>
  );
}
