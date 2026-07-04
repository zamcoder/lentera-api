# Lentera — Prompts Integrasi Flutter ↔ Backend

Panduan menyambungkan **app Flutter** ke **API Lentera** (`/api/v1`, JWT). Polanya
sama seperti `PROMPTS-backend.md`: **satu domain per prompt**, dan selalu minta
agent **membaca file konteks** (bukan menebak).

## Konteks wajib dibaca (sumber kebenaran)
| File | Isi |
|---|---|
| `docs/API_ENDPOINTS.md` | Daftar endpoint `/api/v1` + auth + contoh body |
| `docs/lentera-api.postman_collection.json` | Koleksi Postman — import untuk lihat request/response nyata |
| `lib/data/models.dart` | **Bentuk model app** (`Person`, `Moment`, `Post`, `Reactions`, `Circle`) — response API sudah dicocokkan ke sini |
| `lib/data/dummy_data.dart` | Data & konten dummy yang akan diganti response API |

> **Aturan emas:** response backend **sudah** dibentuk agar cocok `models.dart`.
> Tujuan integrasi: ganti sumber data dari `LData` (dummy) → API, **tanpa**
> mengubah model/UI. Bila ada beda bentuk, sepakati dulu — jangan paksa.

## Prinsip yang tak boleh dilanggar
1. **Jurnal pribadi = E2E.** Enkripsi/dekripsi terjadi **di device**. Kirim ke
   server hanya **ciphertext** (`*_enc`) sebagai **base64** + nonce. Kunci
   diturunkan dari passphrase (**Argon2id**) & **tak pernah** dikirim ke server.
2. **Komunitas = plaintext, dimoderasi.** Kiriman baru berstatus `pending` →
   tampilkan "🛡️ Sedang ditinjau…" lalu "Lolos tinjauan" saat `approved`.
3. **Tenang > engagement.** Tanpa follower/like publik; reaksi tanpa komentar.
4. **Isyarat krisis** ditangani lembut (server menahan + kirim `safe_space`) →
   tawarkan **Ruang Tenang**, bukan blokir dingin.

---

## 0 — Kickoff (jalankan sekali)

> Kamu integrator Flutter menyambungkan app "Lentera" ke REST API-nya. **Baca
> dulu `docs/API_ENDPOINTS.md`, import `docs/lentera-api.postman_collection.json`,
> dan pelajari `lib/data/models.dart`.** API di `{{BASE}}/api/v1`, JSON, auth
> **JWT Bearer**, error `{message, errors}`.
>
> **Tugas kickoff (belum sentuh fitur):**
> 1. **API client** (disarankan `dio`): `baseUrl` dari config/env, header
>    `Accept: application/json`, timeout wajar.
> 2. **Token store**: simpan `access token` di `flutter_secure_storage`.
> 3. **Auth interceptor**: lampirkan `Authorization: Bearer <token>` otomatis
>    (kecuali endpoint `/auth/*` publik).
> 4. **Auto-refresh**: saat response **401**, panggil `POST /auth/refresh`
>    (token lama di header) → simpan token baru → ulang request sekali. Bila
>    refresh gagal → arahkan ke layar login.
> 5. **Error mapper**: parse `{message, errors}` → tampilkan pesan; `errors`
>    (map field→list) untuk validasi form.
> 6. **Env**: `BASE_URL` (dev `http://10.0.2.2:8000` untuk emulator Android /
>    `http://127.0.0.1:8000` iOS sim; prod `https://console.temanlentera.id`).
>
> Tunjukkan struktur (ApiClient, AuthInterceptor, TokenStore, ApiException) &
> konfirmasi sebelum lanjut. **Belum integrasi domain apa pun.**

---

## Modul E2E (prasyarat sebelum domain jurnal)

> Buat modul kripto yang menjadi jembatan jurnal E2E. **Baca §4 "Alur Enkripsi"
> di Handoff Doc** (AES-256-GCM, nonce 96-bit, key = Argon2id(passphrase, salt)).
>
> - **Derivasi kunci**: `key = Argon2id(passphrase, kdf_salt)` (paket
>   `cryptography`/`argon2` + `pointycastle`). `kdf_salt` didapat saat register
>   (dikirim balik) / dibuat di device lalu diunggah.
> - **Kunci hidup di memori** (session) + boleh di-cache di `flutter_secure_storage`
>   dgn proteksi biometrik. **Tak pernah** ke server.
> - **Helper**: `encryptField(plaintext) → {enc: base64, nonce: base64}` dan
>   `decryptField(enc, nonce) → plaintext` (AES-256-GCM).
> - Semua field `*_enc`/`*_nonce`/`blob` pada People, Interactions, Media, Vault
>   lewat helper ini. Server tak pernah melihat plaintext.

---

## Urutan integrasi (satu domain per prompt) — cermin fase backend

### Fase 1 — Jurnal jalan end-to-end
1. **Auth & Akun** — register/login/oauth/otp(WA)/2fa/recovery(email)/logout +
   `GET /me`. Simpan token; alur 2FA admin (opsional untuk user). (`API §1`)
2. **Vault sync (E2E)** — `GET /vault/status`, `PUT /vault/backup` (kirim
   `ciphertext` base64 + `version`), `GET /vault/restore`. Toggle di Pengaturan
   (`PUT /settings/sync`). (`§2`)
3. **Orang (People)** — CRUD `/people`; kirim `name_enc/rel_enc/recall_enc`
   (+nonce) via modul E2E; tampilkan `pos_count/neg_count/last_*` dari metadata.
   `initial` diturunkan device dari nama terdekripsi. (`§3`)
4. **Momen/Interaksi + Media** — `/interactions` (filter `?person_id=&type=&cursor=`),
   kirim `text_enc`; `person_ids[]`; unggah `/media` (blob base64) → lampirkan
   `media_ids[]`. (`§4-5`)
5. **Mood & Statistik** — `POST /mood`, `GET /stats/summary?range=week`
   (chart `week[]` → `WeekDay`, `distribution`, `streak`, `recap`), `GET /today`
   (mood + `social_energy`). (`§6`)

### Fase 2 — Komunitas
6. **Feed & Post** — `GET /community/feed`, `POST /community/posts` (→ `pending`,
   tampilkan status), react `POST/DELETE /community/posts/:id/react`
   `{kind: peluk|kekuatan|paham}` (dengan jumlah), `hide`. (`§7`)
7. **Lingkaran** — `/circles` (+`joined`, `members`), `join`/`leave`,
   `/circles/:id/feed`. (`§8`)
8. **Prompt bersama & Kirim kekuatan** — `/prompts/today` + `/answers`;
   `/strength/queue` + `POST /strength/:postId/send` (pesan **siap-pakai**,
   instan). (`§9`)
9. **Moderasi & Laporan** — `POST /reports` (reason = label `reportReasons`),
   `GET /moderation/banned-terms` (sinkron kata → filter lokal **identik**
   server; `crisisSignals` juga disediakan). Saat post/log kena isyarat krisis,
   respons memberi `safe_space` → tawarkan **Ruang Tenang**. (`§10`)

### Fase 3 — Keselamatan & Notifikasi
10. **Ruang Tenang** — `GET /safety/hotlines?region=` ("Segera hadir" bila kosong).
11. **Pengaturan & Notifikasi** — `GET/PUT /settings` (reminder/accent/theme),
    `PUT /settings/reminder` (`{enabled, at:"21:00"}`), `POST /notifications/token`
    (daftar token FCM saat izin push diberikan). (`§11-12`)

---

## Catatan penting per domain
- **Pagination**: list panjang pakai `?cursor=` → respons `{data, next_cursor}`.
  Tombol "Muat lebih banyak" kirim `cursor=next_cursor`.
- **Reaksi**: respons `react` mengembalikan `{reactions:{peluk,kekuatan,paham}, my_reactions[]}`.
- **Waktu**: post punya `time` (string singkat, mis. "5 mnt") siap pakai;
  `occurred_at`/`created_at` ISO untuk perhitungan.
- **`reminder_at`** format `"HH:MM"`.
- **Status moderasi**: `pending|approved|held|rejected`. `held` + `self_harm`
  → jangan tampilkan sebagai penolakan; arahkan ke Ruang Tenang.
- **OTP**: login HP dikirim via **WhatsApp**, pemulihan via **email** (tak ada SMS).

## Penutup
> Rapikan: state management (Riverpod/Bloc) untuk token & data, loading/empty/error
> per layar, retry, offline-first untuk jurnal (tulis lokal → sinkron E2E).
> Pastikan konten sama dengan `dummy_data.dart` saat menguji end-to-end (khusus
> komunitas yang di-seed server: banned-terms, circles, prompt).
