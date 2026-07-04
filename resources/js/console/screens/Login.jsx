import { useEffect, useRef, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useToast } from '../context/ToastContext';
import { api, ApiError } from '../lib/api';
import { C, FONT } from '../lib/theme';
import { Icon } from '../lib/icons';

/**
 * Login gate (§B1). Alur:
 *  creds → (a) 2FA verify bila admin sudah punya 2FA
 *        → (b) setup 2FA bila admin belum (agar admin awal bisa masuk).
 */
export default function Login() {
  const { establish } = useAuth();
  const toast = useToast();

  const [step, setStep] = useState('creds'); // creds | twofa | setup
  const [email, setEmail] = useState('');
  const [pwd, setPwd] = useState('');
  const [emailErr, setEmailErr] = useState(false);
  const [pwdErr, setPwdErr] = useState(false);
  const [formError, setFormError] = useState('');
  const [busy, setBusy] = useState(false);

  const [pendingToken, setPendingToken] = useState(null);
  const [setupToken, setSetupToken] = useState(null);
  const [secret, setSecret] = useState('');
  const [code, setCode] = useState('');
  const codeRef = useRef();

  const credsReady = email.trim() && pwd.trim();

  async function submitCreds() {
    const eOk = email.trim().length > 0;
    const pOk = pwd.trim().length > 0;
    setEmailErr(!eOk);
    setPwdErr(!pOk);
    setFormError('');
    if (!eOk || !pOk) return;

    setBusy(true);
    try {
      const res = await api.post('/auth/login', { email: email.trim(), password: pwd }, { auth: false });

      if (res.two_factor_required) {
        setPendingToken(res.pending_token);
        setCode('');
        setStep('twofa');
      } else if (res.two_factor_setup_required) {
        // Admin belum pasang 2FA → mulai setup dengan token app sementara.
        const s = await api.post('/auth/2fa/setup', {}, { token: res.token });
        setSetupToken(res.token);
        setSecret(s.secret);
        setCode('');
        setStep('setup');
      } else if (res.user?.role === 'admin') {
        // Admin tanpa alur 2FA (tak diharapkan) — perlakukan sebagai butuh verifikasi.
        setFormError('Akun admin perlu verifikasi 2FA.');
      } else {
        setFormError('Akun ini bukan moderator. Akses konsol ditolak.');
      }
    } catch (e) {
      setFormError(e instanceof ApiError ? e.message : 'Gagal masuk.');
    } finally {
      setBusy(false);
    }
  }

  async function verifyTwofa(finalCode) {
    setBusy(true);
    try {
      const res = await api.post('/auth/2fa/verify', { code: finalCode }, { token: pendingToken });
      const { user } = await api.get('/me', { token: res.token });
      establish(res.token, user);
      toast('Selamat datang kembali 🌿');
    } catch (e) {
      setCode('');
      toast(e instanceof ApiError ? e.message : 'Kode tidak valid.');
    } finally {
      setBusy(false);
    }
  }

  async function enableTwofa(finalCode) {
    setBusy(true);
    try {
      const res = await api.post('/auth/2fa/enable', { code: finalCode }, { token: setupToken });
      const { user } = await api.get('/me', { token: res.token });
      establish(res.token, user);
      toast('2FA aktif — selamat datang 🌿');
    } catch (e) {
      setCode('');
      toast(e instanceof ApiError ? e.message : 'Kode tidak valid.');
    } finally {
      setBusy(false);
    }
  }

  function onCode(v) {
    const digits = (v || '').replace(/\D/g, '').slice(0, 6);
    setCode(digits);
    if (digits.length === 6 && !busy) {
      step === 'setup' ? enableTwofa(digits) : verifyTwofa(digits);
    }
  }

  useEffect(() => {
    if (step !== 'creds') codeRef.current?.focus();
  }, [step]);

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        background: C.bg,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 40,
        overflow: 'hidden',
        fontFamily: FONT.body,
        color: C.text,
      }}
    >
      <Blur style={{ top: -90, right: -70, width: 340, height: 340, background: '#cdd6c4', opacity: 0.55 }} />
      <Blur style={{ bottom: -110, left: -60, width: 300, height: 300, background: '#ddd3c4', opacity: 0.5 }} />

      <div
        style={{
          position: 'relative',
          width: 404,
          background: C.card,
          border: `1px solid rgba(40,45,35,.1)`,
          borderRadius: 24,
          padding: '38px 36px',
          boxShadow: '0 24px 70px rgba(40,45,35,.16)',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: 11, marginBottom: 26 }}>
          <div style={{ width: 40, height: 40, borderRadius: 13, background: C.sageTint, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            {Icon.shield(C.sage, 22)}
          </div>
          <div>
            <div style={{ fontFamily: FONT.display, fontSize: 22, fontWeight: 500, lineHeight: 1 }}>Lentera</div>
            <div style={{ fontSize: 11.5, color: C.muted, marginTop: 2 }}>Konsol moderasi</div>
          </div>
        </div>

        {step === 'creds' && (
          <>
            <H title="Masuk sebagai admin" sub="Akses terbatas untuk moderator berwenang." />

            <Label>Email moderator</Label>
            <Field border={emailErr ? C.clay : C.border} icon={Icon.mail()}>
              <input
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value);
                  setEmailErr(false);
                  setFormError('');
                }}
                onKeyDown={(e) => e.key === 'Enter' && submitCreds()}
                placeholder="admin@lentera.test"
                style={inputStyle}
              />
            </Field>
            {emailErr && <FieldError>Email moderator wajib diisi</FieldError>}

            <Label>Kata sandi</Label>
            <Field border={pwdErr ? C.clay : C.border} icon={Icon.lock()}>
              <input
                type="password"
                value={pwd}
                onChange={(e) => {
                  setPwd(e.target.value);
                  setPwdErr(false);
                  setFormError('');
                }}
                onKeyDown={(e) => e.key === 'Enter' && submitCreds()}
                placeholder="••••••••"
                style={inputStyle}
              />
            </Field>
            {pwdErr && <FieldError>Kata sandi wajib diisi</FieldError>}

            {formError && <FormBanner>{formError}</FormBanner>}

            <Primary onClick={submitCreds} disabled={!credsReady || busy} active={!!credsReady}>
              {busy ? 'Memeriksa…' : 'Lanjut ke verifikasi'}
            </Primary>
          </>
        )}

        {(step === 'twofa' || step === 'setup') && (
          <>
            <div
              onClick={() => {
                setStep('creds');
                setCode('');
              }}
              className="lt-act"
              style={{ cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 12.5, fontWeight: 600, color: C.muted, marginBottom: 14 }}
            >
              {Icon.back()} Kembali
            </div>

            <H
              title={step === 'setup' ? 'Aktifkan 2FA' : 'Verifikasi dua langkah'}
              sub={
                step === 'setup'
                  ? 'Pindai/ketik kunci di aplikasi authenticator, lalu masukkan 6 digit.'
                  : 'Masukkan 6 digit dari aplikasi authenticator-mu.'
              }
            />

            {step === 'setup' && (
              <div
                style={{
                  marginBottom: 18,
                  padding: '12px 14px',
                  borderRadius: 12,
                  background: 'rgba(126,114,184,.08)',
                  border: '1px solid rgba(126,114,184,.2)',
                }}
              >
                <div style={{ fontSize: 11.5, color: C.lavDeep, fontWeight: 600, marginBottom: 6 }}>Kunci authenticator</div>
                <div
                  onClick={() => {
                    navigator.clipboard?.writeText(secret);
                    toast('Kunci disalin');
                  }}
                  className="lt-act"
                  style={{ cursor: 'pointer', fontFamily: FONT.mono, fontSize: 14, letterSpacing: 1, color: C.text, wordBreak: 'break-all' }}
                >
                  {secret}
                </div>
              </div>
            )}

            <div style={{ position: 'relative', marginBottom: 18 }}>
              <div style={{ display: 'flex', gap: 9 }}>
                {[0, 1, 2, 3, 4, 5].map((i) => (
                  <div
                    key={i}
                    style={{
                      flex: 1,
                      height: 54,
                      borderRadius: 13,
                      background: C.field,
                      border: `1.5px solid ${code[i] ? C.sage : C.border}`,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontFamily: FONT.display,
                      fontSize: 23,
                      fontWeight: 500,
                    }}
                  >
                    {code[i] || ''}
                  </div>
                ))}
              </div>
              <input
                ref={codeRef}
                value={code}
                onChange={(e) => onCode(e.target.value)}
                inputMode="numeric"
                maxLength={6}
                autoFocus
                style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', opacity: 0, border: 'none', cursor: 'text' }}
              />
            </div>

            {step === 'twofa' && (
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 22 }}>
                <span style={{ fontSize: 12.5, color: C.dim }}>Kode dari aplikasi authenticator</span>
                <span onClick={() => toast('Kode TOTP berganti tiap 30 detik')} className="lt-act" style={{ cursor: 'pointer', fontSize: 12.5, fontWeight: 600, color: C.sage }}>
                  Bantuan
                </span>
              </div>
            )}

            <Primary
              onClick={() => (code.length === 6 ? (step === 'setup' ? enableTwofa(code) : verifyTwofa(code)) : toast('Masukkan 6 digit kode'))}
              disabled={busy}
              active={code.length === 6}
            >
              {busy ? 'Memverifikasi…' : step === 'setup' ? 'Aktifkan & masuk' : 'Masuk ke konsol'}
            </Primary>
          </>
        )}

        <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 22, paddingTop: 18, borderTop: `1px solid ${C.line}` }}>
          {Icon.shield(C.sage, 14)}
          <span style={{ fontSize: 11.5, color: C.muted, lineHeight: 1.45 }}>2FA wajib · IP whitelist · seluruh tindakan tercatat di log audit</span>
        </div>
      </div>
    </div>
  );
}

/* ---- bagian kecil ---- */
const inputStyle = { flex: 1, minWidth: 0, border: 'none', outline: 'none', background: 'transparent', fontFamily: FONT.body, fontSize: 14, color: C.text };

function Blur({ style }) {
  return <div style={{ position: 'absolute', borderRadius: '50%', filter: 'blur(74px)', pointerEvents: 'none', ...style }} />;
}
function H({ title, sub }) {
  return (
    <>
      <div style={{ fontFamily: FONT.display, fontSize: 25, fontWeight: 500, lineHeight: 1.15, marginBottom: 6 }}>{title}</div>
      <div style={{ fontSize: 13, color: C.muted, lineHeight: 1.5, marginBottom: 22 }}>{sub}</div>
    </>
  );
}
function Label({ children }) {
  return <div style={{ fontSize: 12, fontWeight: 600, color: C.text2, marginBottom: 7 }}>{children}</div>;
}
function Field({ children, icon, border }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '12px 14px', borderRadius: 12, background: C.field, border: `1.5px solid ${border}`, marginBottom: 16 }}>
      {icon}
      {children}
    </div>
  );
}
function FieldError({ children }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 6, margin: '-10px 0 14px', fontSize: 11.5, fontWeight: 600, color: C.clay }}>
      {Icon.warn()} {children}
    </div>
  );
}
function FormBanner({ children }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 7, margin: '0 0 16px', padding: '10px 12px', borderRadius: 10, background: 'rgba(174,100,80,.09)', fontSize: 12.5, fontWeight: 600, color: C.clay }}>
      {Icon.warn()} {children}
    </div>
  );
}
function Primary({ children, onClick, disabled, active }) {
  return (
    <div
      onClick={disabled ? undefined : onClick}
      className="lt-act"
      style={{
        cursor: disabled ? 'default' : 'pointer',
        textAlign: 'center',
        padding: 14,
        borderRadius: 13,
        background: active ? C.sage : 'rgba(92,129,102,.45)',
        color: '#F4F8F2',
        fontSize: 14.5,
        fontWeight: 700,
      }}
    >
      {children}
    </div>
  );
}
