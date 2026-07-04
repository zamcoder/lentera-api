# Kebutuhan API — Lentera (mobile → backend)

Dokumen untuk tim **Backend** (Laravel 11 + PostgreSQL 16). Berisi endpoint yang dibutuhkan app,
dipetakan ke fitur/layar. Saat ini app memakai **data dummy** di `lib/data/` — struktur ini yang
akan disambung.

## Prinsip penting

1. **Dua bidang data (WAJIB dipisah):**
   - **Jurnal pribadi = E2E encrypted.** Server hanya menyimpan **ciphertext** (AES‑256‑GCM,
     kunci diturunkan dari passphrase via Argon2id **di perangkat**). Server **tidak pernah**
     memegang kunci/plaintext jurnal. Hanya sedikit **metadata non‑sensitif** (jumlah, indeks mood,
     tanggal) boleh plaintext untuk agregasi/statistik & sort.
   - **Komunitas = plaintext, dimoderasi.** Bidang publik terpisah; melewati moderasi pra‑tayang.
2. **Auth:** semua endpoint (kecuali grup Auth) butuh header `Authorization: Bearer <JWT>`.
3. **Pagination:** list panjang pakai cursor (`?cursor=` / `next_cursor`) → tombol "Muat lebih banyak".
4. **Realtime (opsional):** status moderasi (`pending → approved`) & update reaksi sebaiknya via
   WebSocket/Pusher; fallback polling.
5. **Base path:** `/api` (disarankan versioned `/api/v1`). Semua JSON.

> Endpoint bertanda ⚑ sudah disebut di dokumen handover; sisanya turunan dari fitur app.

---

## 1. Auth & Akun → *Onboarding, Auth, OTP, Pemulihan, kartu akun Pengaturan*

| Method | Path | Guna |
|---|---|---|
| POST | `/api/auth/register` ⚑ | Daftar (email/HP) + simpan `kdf_salt` untuk E2E |
| POST | `/api/auth/login` ⚑ | Email+sandi → JWT (lanjut 2FA bila aktif) |
| POST | `/api/auth/oauth` | Google/Apple: kirim `id_token` → JWT (`provider`, `sub`) |
| POST | `/api/auth/otp/request` | Kirim OTP 6 digit ke nomor HP |
| POST | `/api/auth/otp/verify` | Verifikasi OTP → JWT (auto‑lanjut saat 6 digit) |
| POST | `/api/auth/2fa/verify` ⚑ | Verifikasi kode TOTP (opsional) |
| POST | `/api/auth/recovery` | Kirim tautan pemulihan via email/HP terdaftar |
| POST | `/api/auth/logout` | Cabut token/sesi |
| GET | `/api/me` | Profil akun + metode login + status sinkron (untuk Pengaturan) |

`auth_identities`: satu user bisa punya banyak identitas (google/apple/email/phone).

---

## 2. Sinkron & Cadangan Vault (E2E) → *Pengaturan: Sinkron awan / Cadangkan / Pulihkan*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/vault/status` | Status sinkron + `last_synced_at` ("Tersinkron · Baru saja") |
| PUT | `/api/vault/backup` ⚑ | Unggah **blob ciphertext** jurnal + `version` (bump tiap ubah) |
| GET | `/api/vault/restore` ⚑ | Ambil blob ciphertext terakhir untuk dipulihkan |
| PUT | `/api/settings/sync` | Nyalakan/matikan sinkron awan |

Server simpan `vault_backups.ciphertext` (BYTEA) + `version` + `updated_at`. **Tanpa** kunci.

---

## 3. Orang (People) — terenkripsi → *Orang, Tambah orang, hero Timeline*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/people` ⚑ | Daftar orang: `name_enc`, `rel_enc` + metadata plaintext untuk sort (`pos_count`, `neg_count`, `last_at`, `last_type`) |
| POST | `/api/people` ⚑ | Tambah orang (terenkripsi) |
| PUT | `/api/people/:id` | Edit |
| DELETE | `/api/people/:id` | Hapus |

Pencarian nama/catatan dilakukan **di perangkat** (data terenkripsi) — server tak perlu full‑text.

---

## 4. Momen / Interaksi (jurnal) — terenkripsi → *Logger, Beranda, Semua momen, Timeline, Hari Ini*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/interactions` ⚑ | List, filter `?person_id=&type=&cursor=` (Timeline, Semua momen) |
| POST | `/api/interactions` ⚑ | Simpan catatan terenkripsi (Logger) |
| PUT | `/api/interactions/:id` ⚑ | Edit |
| DELETE | `/api/interactions/:id` ⚑ | Hapus |

Field: `type` (positive/negative/neutral), `text_enc`, `person_ids[]`, `topic`, `mood?`,
`media_ids[]?`, `created_at`. `type` & `created_at` boleh plaintext untuk filter/urut.

---

## 5. Media suara & foto — terenkripsi → *Logger: rekam suara, lampirkan foto*

| Method | Path | Guna |
|---|---|---|
| POST | `/api/media` | Unggah blob media **terenkripsi** → `media_id` |
| GET | `/api/media/:id` | Ambil blob (untuk diputar/tampil di perangkat) |

---

## 6. Mood, Statistik & Rekap (metadata plaintext) → *Beranda chart & rekap, Hari Ini*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/stats/summary?range=week` ⚑ | Chart mingguan + distribusi + streak + rekap ("Paling kamu syukuri") |
| POST | `/api/mood` | Set mood harian (0–4) → kalender + hilangkan titik sage nav |
| GET | `/api/today` | Auto‑rekap Hari Ini + energi sosial (boleh dihitung klien dari interactions) |

Hanya agregat non‑sensitif (jumlah per hari, indeks mood, tanggal). Teks tetap terenkripsi.

---

## 7. Komunitas — Feed & Post (plaintext, dimoderasi) → *Komunitas, Komposer, reaksi, menu ⋯*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/community/feed?cursor=` ⚑ | Dinding Syukur (pagination "Muat lebih banyak") |
| POST | `/api/community/posts` ⚑ | Kirim (`anon` bool, `circle_id?`) → **status `pending`** |
| GET | `/api/community/posts/:id` | Detail + status moderasi |
| POST | `/api/community/posts/:id/react` ⚑ | Reaksi `{kind: peluk\|kekuatan\|paham}` (toggle) |
| DELETE | `/api/community/posts/:id/react` | Batal reaksi |
| POST | `/api/community/posts/:id/hide` | Sembunyikan dari feed‑ku |

**Alur moderasi (penting):** `POST posts` → server jalankan **banned‑words regex** (identik app &
konsol) **+ klasifikasi Gemini** → `pending` → `approved` (tayang) atau `held`. App menampilkan
"🛡️ Sedang ditinjau…" lalu "Lolos tinjauan moderator". Butuh sinyal status (realtime/polling).
**Reaksi tanpa komentar** (by design — tak ada endpoint komentar).

---

## 8. Lingkaran (Circles) → *Lingkaran kecil, Jelajahi, Circle detail, Gabung*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/circles` ⚑ | Daftar lingkaran (+`joined`, `member_count`, `desc`) |
| GET | `/api/circles/:id` | Detail + feed lingkaran (pagination) |
| POST | `/api/circles/:id/join` | Gabung |
| DELETE | `/api/circles/:id/join` | Keluar |

---

## 9. Prompt bersama & Kirim kekuatan → *Prompt bersama, banner & flow Kirim kekuatan*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/prompts/today` | Pertanyaan bersama hari ini + jumlah berbagi |
| GET | `/api/prompts/today/answers?cursor=` | Jawaban orang‑orang |
| POST | `/api/prompts/today/answers` | Bagikan jawaban (lewat komposer, kena moderasi) |
| GET | `/api/strength/queue` | Orang yang sedang berat & butuh dukungan |
| POST | `/api/strength/:postId/send` | Kirim pesan kekuatan **siap‑pakai** — **instan, tanpa pra‑tayang** |

"Kirim kekuatan" = pesan template (tanpa teks bebas) → langsung terkirim (beda dari post biasa).

---

## 10. Moderasi & Laporan → *Laporkan (reason picker), moderasi pra‑tayang, deteksi krisis*

| Method | Path | Guna |
|---|---|---|
| POST | `/api/reports` ⚑ | `{post_id, reason, note?}` — laporkan ke moderator |
| GET | `/api/moderation/banned-terms` ⚑ | Sinkron daftar kata terlarang (agar identik app & konsol) |

**Deteksi krisis / self‑harm:** saat teks post/log mengandung isyarat, server **tahan** post dari
publik & rute ke "penanganan khusus" di konsol (bukan blokir dingin); app menawarkan **Ruang Tenang**.
Endpoint konsol admin (`/api/mod/queue`, `/api/mod/action`, CRUD `/api/mod/terms`) dipakai **web
console**, bukan mobile.

---

## 11. Keselamatan & Dukungan → *Ruang Tenang*

| Method | Path | Guna |
|---|---|---|
| GET | `/api/safety/hotlines?region=` | Nomor hotline krisis per wilayah (kini "Segera hadir") |

Afirmasi & grounding sudah lokal di app (boleh disajikan server bila ingin dinamis).

---

## 12. Pengaturan & Notifikasi → *Pengaturan, pengingat lembut*

| Method | Path | Guna |
|---|---|---|
| GET/PUT | `/api/settings` | `reminder_on`, `sync_on`, `accent`, `theme`, toggle keamanan |
| POST | `/api/notifications/token` | Daftar device token (FCM/APNs) |
| PUT | `/api/settings/reminder` | Jadwal pengingat malam (opt‑in, ±21:00, satu notifikasi lock‑screen) |

Panic‑lock murni klien (kunci UI + biometrik lokal) — tak butuh API.

---

## Ringkasan tabel DB (acuan handover)

`users` · `auth_identities` · `vault_backups` · `people` · `interactions` · `media` · `moods` ·
`posts` · `reactions` · `circles` · `circle_members` · `prompts` · `prompt_answers` · `reports` ·
`moderation` · `banned_terms` · `devices`.

## Urutan prioritas (saran MVP)

1. **Fase 1:** Auth → Vault sync → People → Interactions → Stats/Mood *(jurnal jalan end‑to‑end)*.
2. **Fase 2:** Community feed/posts/react → Circles → Prompt → Moderasi + banned‑terms.
3. **Fase 3:** Kirim kekuatan → deteksi krisis + Ruang Tenang/hotline → Notifikasi pengingat.

## Yang perlu dikonfirmasi ke BE

- Format error standar (`{code, message, errors}`) & rate‑limit.
- Skema JWT (exp/refresh) & mekanisme 2FA (TOTP?).
- Cara sinkron vault: satu blob besar vs per‑record ciphertext (mempengaruhi konflik/merge).
- Realtime untuk status moderasi & reaksi (WebSocket/Pusher) atau polling.
- Penyimpanan media terenkripsi (BYTEA vs object storage terenkripsi).
