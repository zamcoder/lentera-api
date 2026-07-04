// Klien REST untuk API Lentera. Token Bearer (JWT) disimpan di localStorage.
// Default same-origin '/api/v1' — konsol disajikan oleh Laravel yang sama.
const BASE = import.meta.env.VITE_API_BASE || '/api/v1';
const TOKEN_KEY = 'lentera_token';

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}
export function setToken(t) {
  if (t) localStorage.setItem(TOKEN_KEY, t);
  else localStorage.removeItem(TOKEN_KEY);
}

export class ApiError extends Error {
  constructor(message, status, data) {
    super(message);
    this.status = status;
    this.data = data;
  }
}

async function request(method, path, body, { token, auth = true } = {}) {
  const headers = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const t = token ?? (auth ? getToken() : null);
  if (t) headers.Authorization = `Bearer ${t}`;

  let res;
  try {
    res = await fetch(`${BASE}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError('Tidak bisa terhubung ke server. Pastikan API berjalan.', 0, null);
  }

  const text = await res.text();
  const data = text ? safeJson(text) : null;

  if (!res.ok) {
    const msg = data?.message || `Kesalahan ${res.status}`;
    throw new ApiError(msg, res.status, data);
  }
  return data;
}

function safeJson(text) {
  try {
    return JSON.parse(text);
  } catch {
    return null;
  }
}

export const api = {
  get: (p, opts) => request('GET', p, undefined, opts),
  post: (p, b, opts) => request('POST', p, b ?? {}, opts),
  put: (p, b, opts) => request('PUT', p, b ?? {}, opts),
  del: (p, b, opts) => request('DELETE', p, b, opts),
};
