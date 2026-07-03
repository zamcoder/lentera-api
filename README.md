# Lentera — API + Konsol Web (satu proyek Laravel)

Server **dan** konsol moderasi untuk **Lentera** dalam **satu proyek Laravel**:
API REST di `/api/*`, konsol admin React (Vite) disajikan Laravel di route web.
(Mobile Flutter dikerjakan terpisah.)

Dua bidang data yang **tidak pernah bercampur** (§05 Rencana Produk):

1. **Jurnal pribadi** — terenkripsi end-to-end. Server menyimpan **ciphertext saja**
   (`vault_backups.blob` BYTEA), tak pernah membaca/moderasi.
2. **Komunitas** — tersimpan di server (bukan E2E, agar bisa dimoderasi),
   pseudonim, wajib lewat pipa moderasi 2 lapis (§06).

## Struktur
```
lentera-api/                 ← satu proyek Laravel
├─ app/ · routes/ · database/ · config/   ← API backend (Bagian A)
├─ resources/js/console/                   ← Konsol React (Bagian B)
│  ├─ screens/ · components/ · context/ · hooks/ · ui/ · lib/
│  └─ main.jsx · tokens.css
├─ resources/views/console.blade.php       ← shell SPA (dimuat Laravel)
├─ public/build/                           ← aset Vite hasil build
└─ design/ · handover/                     ← referensi desain & token
```

## Stack
- PHP 8.2 · Laravel 11 · Sanctum (token Bearer)
- PostgreSQL 16 (BYTEA untuk ciphertext, `citext`, `gen_random_uuid`)
- Argon2id untuk hash sandi · TOTP (RFC 6238) untuk 2FA konsol
- Queue (database) untuk moderasi asinkron · Gemini API (Lapis 2)
- Konsol: React 19 · Vite (bawaan Laravel) · React Router · Newsreader/DM Sans

## Setup

```bash
# 1. Dependensi (PHP + JS dalam satu proyek)
composer install
npm install

# 2. Env & kunci
cp .env.example .env
php artisan key:generate
#   sesuaikan DB_* dan (opsional) GEMINI_API_KEY di .env

# 3. Database PostgreSQL (buat sekali)
#   createdb lentera; role lentera; lalu di dalam DB lentera:
#   CREATE EXTENSION IF NOT EXISTS pgcrypto;  CREATE EXTENSION IF NOT EXISTS citext;

# 4. Migrasi + seed (banned_terms 7 kata, admin, circles, prompt hari ini)
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # opsional: isi contoh untuk konsol

# 5. Aset konsol: build (produksi) ATAU dev (HMR)
npm run build                # → public/build (dipakai saat serve biasa)
#   ATAU untuk pengembangan konsol dengan hot-reload:
#   npm run dev              # jalankan berbarengan dengan php artisan serve

# 6. Jalankan server + worker moderasi (dua terminal)
php artisan serve            # http://127.0.0.1:8000  → API + konsol
php artisan queue:work       # memproses ClassifyPostJob (Gemini Lapis 2)
```

Buka **http://127.0.0.1:8000** → konsol moderasi. API di **/api/***.

> **Ekstensi PHP:** aktifkan `pdo_pgsql` & `pgsql` di `php.ini`.

### Akun admin awal (seed, ganti di produksi)
- email: `admin@lentera.test` · sandi: `rahasia123` · role `admin`
- Login pertama menampilkan **setup 2FA**: salin kunci ke aplikasi authenticator,
  masukkan 6 digit → masuk konsol. Wajib 2FA sebelum akses `/mod/*`.

## Konsol web (React) — §B
Konsol disajikan Laravel dari `resources/views/console.blade.php` (route web
catch-all di `routes/web.php`), dengan entry Vite `resources/js/console/main.jsx`.
API dipanggil same-origin (`/api`, lihat `resources/js/console/lib/api.js`).
Layar: Login (2FA) · Ringkasan · Antrean · Laporan · Kata terlarang · Akun —
semua daftar panjang memakai pola "Muat lebih banyak" (paginasi cursor).

## Grup API

| Grup | Endpoint |
|---|---|
| **Auth** | `POST /api/auth/register · /login · /otp/{request,verify} · /oauth · /recover · /recover/confirm` · `/2fa/{setup,enable,verify,disable}` · `GET /me` · `POST /logout` |
| **Sync/Vault** | `PUT /api/vault/backup` · `GET /api/vault/restore` · `GET /api/vault/status` · `DELETE /api/vault/backup` |
| **Community** | `GET /api/feed` · `POST /api/posts` · `POST\|DELETE /api/posts/{id}/react` · `GET /api/circles`, `/circles/{id}`, `/circles/{id}/feed` · `POST /circles/{id}/join` · `GET /api/prompt/today` |
| **Moderation** | `POST /api/reports` · `GET /api/mod/queue` · `POST /api/mod/action` · `GET /api/mod/reports` · `POST /api/mod/reports/action` · `GET\|POST /api/mod/terms` · `GET /api/mod/terms/suggest` · `DELETE /api/mod/terms/{id}` · `GET /api/mod/accounts` · `POST /api/mod/accounts/{id}/action` · `GET /api/mod/metrics` |

Daftar lengkap: `php artisan route:list`.

## Alur autentikasi & gerbang admin (§A2/§A6)
1. `POST /auth/login` (admin) → bila 2FA belum aktif: `two_factor_setup_required`, token app.
2. `POST /auth/2fa/setup` → `secret` + `otpauth_uri` (QR authenticator).
3. `POST /auth/2fa/enable` `{code}` → **token konsol** (ability `app` + `mod`).
4. Login berikutnya (2FA aktif) → `pending_token` → `POST /auth/2fa/verify` `{code}` → token konsol.
5. Semua `/mod/*` dijaga berlapis: `auth:sanctum` + `abilities:mod` + middleware `moderator`
   (role admin + 2FA aktif). Tiap tindakan tercatat di `audit_logs`.

## Pipa moderasi (§06)
`POST /posts` →
- **Lapis 0** isyarat menyakiti diri → `held` + `self_harm` + sinyal `safe_space`
  ke klien (tawarkan Ruang Tenang) — **bukan blokir dingin**.
- **Lapis 1** regex `banned_terms` → blok (`rejected`) / mask instan.
- **Kirim kekuatan** (pesan siap-pakai) → `approved` instan, tanpa antrean.
- **Lapis 2** `ClassifyPostJob` (Queue) → Gemini menilai toxic/pelecehan/spam/self-harm
  → `approved` / `held` / `rejected`. Tanpa `GEMINI_API_KEY`, memakai stub heuristik.

## Privasi jurnal (§A3)
`vault/backup` menerima & menyimpan **ciphertext apa adanya** (BYTEA). Tidak ada
satu pun jalur yang mendekripsi. Mode escrow (`escrow_enabled`) opsional untuk
pemulihan dibantu server; tanpa escrow = "lebih privat". Lihat `VaultTest`.

## Anti-cemas (§03)
Tanpa jumlah pengikut/like publik. Reaksi hangat (peluk/kekuatan/paham) tak
mengembalikan hitungan. Rate limit per akun. Metrik konsol berfokus pada
kehangatan, bukan pertumbuhan.

## Test
```bash
php artisan test          # 24 test: Auth, Vault, Community, Moderation Console
```
DB test: `lentera_test` (lihat `phpunit.xml`).

## Deploy ke produksi (VPS)
Panduan lengkap + **checklist yang perlu disiapkan** (VPS, PostgreSQL, Gemini
key, SMTP, IP allowlist, HTTPS, Supervisor worker) ada di **[DEPLOYMENT.md](DEPLOYMENT.md)**.
