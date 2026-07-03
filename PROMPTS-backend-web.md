# Lentera — Prompts untuk Claude Code (BACKEND + KONSOL WEB)

Membangun **API backend (Laravel)** lalu **konsol web admin (React)** dalam satu repo. Mobile Flutter dikerjakan terpisah.

Kerjakan **backend dulu** (Bagian A), baru **konsol web** (Bagian B) — konsol mengonsumsi API-nya.

```
lentera-server/
├─ design/
│  ├─ Lentera - Konsol Admin.dc.html      ← sumber kebenaran VISUAL konsol web
│  ├─ Lentera - Rencana Produk.dc.html    ← skema DB (§08) + endpoint + model privasi (§05) + moderasi (§06)
│  └─ Lentera - Handoff Doc.dc.html        ← alur enkripsi E2E + skema SQL
├─ handover/
│  ├─ tokens.css                           ← token warna/spacing untuk React
│  └─ README.md
├─ api/                                     ← Laravel (Bagian A)
└─ web/                                     ← React + Vite (Bagian B)
```

**Aturan emas:**
- Skema DB, kolom, relasi, daftar endpoint → baca dari `Lentera - Rencana Produk.dc.html` §08 & alur enkripsi dari `Lentera - Handoff Doc.dc.html`. Jangan menebak.
- Tampilan konsol web → baca style elemen dari `Lentera - Konsol Admin.dc.html` (CSS asli, bukan screenshot) agar slicing 100% sama.

---

## 0 — Kickoff (jalankan sekali)

> Baca `handover/README.md`, lalu `design/Lentera - Rencana Produk.dc.html` (fokus §05 Model Privasi, §06 Moderasi, §08 Arsitektur & Data) dan `design/Lentera - Handoff Doc.dc.html` (enkripsi E2E + skema SQL). Ini server untuk "Lentera" — app jurnal tenang + komunitas dimoderasi. Kita mengerjakan **API backend (Laravel)** lalu **konsol web admin (React)** di repo ini; mobile Flutter terpisah.
>
> **Prinsip arsitektur non-negosiasi (§05):** dua bidang data yang **tidak pernah bercampur** — (1) **jurnal pribadi** terenkripsi end-to-end, server simpan **ciphertext saja**, tak pernah baca/moderasi; (2) **komunitas** tersimpan di server (bukan E2E, agar bisa dimoderasi), pseudonim, wajib lewat moderasi. Bagi momen ke komunitas = tindakan sadar (disalin & dilepas), bukan otomatis.
>
> Tugasmu sekarang hanya: setup **Laravel 11 + PostgreSQL 16** di `api/`, koneksi DB, install **Sanctum**. Belum bikin migration/endpoint. Tunjukkan rencana struktur (Models, Migrations, Controllers per grup Auth/Sync/Community/Moderation, Jobs untuk moderasi async) dan konfirmasi sebelum lanjut.

---

# BAGIAN A — API BACKEND (Laravel)

## Aturan yang harus dijaga
- **Jurnal pribadi = ciphertext only.** Server tak punya kunci, tak bisa dekripsi, tak boleh log isinya. Simpan `BYTEA`/blob.
- **Pemulihan longgar (keputusan user):** server bantu pulihkan lewat email/HP (key-escrow untuk cadangan). Jelaskan trade-off; sediakan juga mode "tanpa pemulihan, lebih privat".
- **Auth:** Argon2id, token akses singkat (Sanctum), **2FA TOTP wajib untuk konsol admin**, IP allowlist di reverse proxy.
- **Moderasi 2 lapis (§06):** (1) regex `banned_terms` (7 kata awal: bodoh, tolol, goblok, sialan, brengsek, bangsat, benci kamu) → blok/mask instan; (2) **Gemini API** via **Queue** → toxic/pelecehan/spam/self-harm. Hasil masuk antrean konsol.
- **Isyarat menyakiti diri:** jangan blokir dingin — tandai "penanganan khusus", tahan dari publik, kembalikan sinyal ke klien untuk tawarkan Ruang Tenang.
- **"Kirim kekuatan" = instan** (pesan siap-pakai, tanpa teks bebas, bukan pra-tayang).
- **Anti-cemas:** tanpa jumlah pengikut/like publik. Rate limit per akun.

### A1 — Migrations & Models
> Buat migration + Eloquent model untuk semua tabel di §08: **users**, **auth_identities** (provider email/phone/google/apple), **vault_backups** (blob BYTEA, key_escrow), **posts** (author_id, body, surface, anon), **reactions** (post_id, user_id, kind), **circles** + **members**, **reports** (post_id, reporter_id, reason), **moderation** (action, target), **banned_terms** (pattern, hits). uuid PK, FK & index benar. Seed `banned_terms` 7 kata awal. Jelaskan kenapa BYTEA untuk vault.

### A2 — Auth
> Grup **Auth**: `POST /auth/register`, `/auth/login`, `/auth/otp`, `/auth/oauth`, `/auth/recover`. Empat metode masuk via `auth_identities`. Argon2id, Sanctum. TOTP untuk konsol (endpoint verifikasi 2FA terpisah). Recover = jalur bantuan server (jelaskan key-escrow). Validasi + rate limit + test dasar.

### A3 — Sync / Vault
> Grup **Sync**: `PUT /vault/backup` (terima blob ciphertext, simpan apa adanya — server TIDAK mendekripsi), `GET /vault/restore`. Server tak pernah menyentuh plaintext. Dukung mode dengan/tanpa key-escrow. Test: pastikan tak ada endpoint yang bisa membaca isi jurnal.

### A4 — Community
> Grup **Community**: `GET /feed` (paginated per surface), `POST /posts` (masuk pipeline moderasi — langkah A5), `POST /posts/:id/react` (peluk/kekuatan/paham; TANPA komentar), `/circles` (list/join/detail + feed), `/prompt/today`. Anon/pseudonim per post. Rate limit. Paginasi cursor/offset. "Kirim kekuatan" instan tanpa antrean.

### A5 — Moderation pipeline + Gemini (Queue)
> Pipa 2 lapis (§06). Saat `POST /posts`: (1) cek regex `banned_terms` → blok/mask instan; (2) dispatch **Job** → **Gemini API** klasifikasi. Status: `pending`→`approved`/`held`/`rejected`/`escalated`. Self-harm → `held` + flag "penanganan khusus" + sinyal ke klien (tawarkan Ruang Tenang). Endpoint konsol: `POST /reports`, `GET /mod/queue`, `POST /mod/action`, `/mod/terms` (CRUD banned_terms + saran varian via Gemini). Test alur pending→approved.

### A6 — Admin gate + audit
> Amankan semua `/mod/*` di belakang **2FA TOTP + role admin**. Catat tiap tindakan moderator ke **log audit** (siapa/aksi/target/waktu). Endpoint metrik kesehatan komunitas (rasio positif, antrean, kecepatan moderasi) untuk Ringkasan — hindari metrik pertumbuhan yang bikin cemas.

### A — Penutup
> Rapikan: route list ringkas, `.env.example` (DB, Sanctum, Gemini key), README (run + migrate + seed + `queue:work`). CORS untuk klien Flutter & konsol React.

---

# BAGIAN B — KONSOL WEB ADMIN (React + Vite)

Baru mulai setelah API jalan. **Sumber kebenaran visual = `design/Lentera - Konsol Admin.dc.html`** — baca style elemennya untuk warna/px/radius persis. Import `handover/tokens.css`.

### Aturan styling konsol
- Pakai token dari `tokens.css` (sage `#5C8166`, clay `#AE6450`, slate, lavender, krem `#F6F4EC`, dll) — jangan hardcode.
- Font: Newsreader (judul) + DM Sans (teks) + Spline Sans Mono (label/mono), lihat desain.
- Konsumsi REST Laravel dari Bagian A (dummy dulu bila endpoint belum siap).
- Daftar panjang pakai pola **"Muat lebih banyak"** (seperti di desain).

### B1 — Shell + Login gate
> Setup React + Vite di `web/`, import `tokens.css`. Bangun **login gate** dari desain: langkah 1 kredensial (email + kata sandi) dengan **validasi wajib isi** (border merah + pesan "wajib diisi", tombol meredup saat kosong, error hilang saat mengetik), langkah 2 **2FA 6 digit** (auto-masuk saat 6 digit, ada "Kembali" + "Kirim ulang"). Setelah lolos → shell konsol: sidebar (Ringkasan · Antrean · Laporan · Kata terlarang · Akun) dengan badge angka, topbar "2FA terverifikasi", toast. Konsumsi `/auth/login` + verifikasi TOTP.

### B2 — Ringkasan
> Layar **Ringkasan** (baca blok OVERVIEW di desain): hero "Kesehatan komunitas" (skor + label Hangat), 4 kartu metrik (rasio positif, antrean menunggu, kecepatan moderasi, anggota aktif), daftar "Perlu perhatianmu" yang melompat ke Antrean/Laporan. Data dari endpoint metrik (A6). Slicing persis dari desain.

### B3 — Antrean moderasi
> Layar **Antrean** (blok QUEUE): kartu kiriman tertahan dengan avatar/surface/waktu, **alasan AI**, dan aksi **Setujui / Haluskan / Tolak**. **Isyarat menyakiti diri** = kartu border ungu, aksi "Tawarkan dukungan"/"Eskalasi" (bukan blokir dingin). Aksi memanggil `POST /mod/action`, hapus item dari antrean, kurangi badge sidebar, tampilkan toast. Pola "Muat lebih banyak". Konsumsi `GET /mod/queue`.

### B4 — Laporan
> Layar **Laporan** (blok REPORTS): kartu konten dilaporkan + alasan pelapor + hitungan, aksi Biarkan tampil / Sembunyikan / Hapus & tindak akun. Konsumsi `POST /reports` (list) + `POST /mod/action`. "Muat lebih banyak".

### B5 — Kata terlarang
> Layar **Kata terlarang** (blok TERMS): daftar pola + hitungan "× ditahan", tambah/hapus pola, panel **Saran AI** (varian/salah-ketik dari Gemini) yang bisa diketuk untuk ditambahkan. Konsumsi `/mod/terms`. "Muat lebih banyak".

### B6 — Akun
> Layar **Akun** (blok ACCOUNTS): tabel anggota (status, jumlah laporan), aksi Bisukan / Batasi / Blokir yang mengubah status + toast. Konsumsi endpoint akun/`/mod/action`. "Muat lebih banyak".

### B — Penutup
> Rapikan: routing antar-view, state fetch/loading, error handling, `.env` (base URL API). Pastikan tinggi window & layout konsisten dengan desain. Jalan lokal dengan `npm run dev` menunjuk ke API lokal.

---

### Referensi cepat (dari dokumen desain)
- **Tabel:** users · auth_identities · vault_backups · posts · reactions · circles/members · reports · moderation · banned_terms
- **Grup API:** Auth (`/auth/*`) · Sync (`/vault/*`) · Community (`/feed`, `/posts`, `/circles`, `/prompt/today`) · Moderation (`/reports`, `/mod/*`)
- **View konsol:** Login (2FA) · Ringkasan · Antrean · Laporan · Kata terlarang · Akun
- **Keputusan tercatat:** 1 moderator awal · Gemini (cloud) · pra-tayang hanya untuk yang perlu · tanpa batas usia · pemulihan dibantu server.
