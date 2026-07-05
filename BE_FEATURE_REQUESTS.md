# Permintaan Fitur / Endpoint Backend — Lentera

**Env:** `https://console.temanlentera.id/api/v1`. Bug (bukan fitur baru) ada di [BE_BUGS.md](docs/BE_BUGS.md).

> **Status per 2026-07-05:** semua permintaan sudah **shipped**. **Lengkapi Profil** baru saja dikirim & diverifikasi live (lihat di bawah). Tak ada item terbuka.

---

## ✅ SHIPPED (2026-07-05) — Lengkapi profil: tambah/ganti/hapus email & nomor HP

**Live & terverifikasi di produksi.** Endpoint (semua butuh JWT):
```
# Email (OTP dikirim via email — Brevo SMTP)
POST   /profile/email          { "email": "user@contoh.id" }        → 200 { message, dev_code:null }
POST   /profile/email/confirm  { "email": "user@contoh.id", "code": "123456" } → 200 { user }

# Nomor (OTP dikirim via WhatsApp — gateway GOWA yang sudah ada)
POST   /profile/phone          { "phone": "+62..." }                → 200 { message, dev_code:null }
POST   /profile/phone/confirm  { "phone": "+62...", "code": "123456" }         → 200 { user }

# Hapus salah satu metode (guard: sisakan ≥1 cara masuk)
DELETE /profile/identity       { "provider": "email" | "phone" }    → 200 { user }
```
> ⚠️ **Catatan bentuk `confirm`:** kirim **email/phone yang sama** seperti saat request (stateless, tanpa pending state di server). `{ user }` = objek user terbaru, **bentuk sama persis dengan `GET /me`** (`{ user: { id, handle, email, providers[], sync, kdf_salt, ... } }`).

**Jawaban keputusan (poin 1–5):**
1. **Verifikasi wajib: YA.** Email → kode 6 digit ke email; nomor → OTP WhatsApp. Baru terpasang setelah `confirm` sukses. Kode: 6 digit, 5 menit, maks 5 percobaan.
2. **Password:** menambah email = **kanal kontak + pemulihan** (mengaktifkan email recovery). **Tidak** otomatis set password. Untuk login penuh email+password, user set sandi lewat alur **recovery** yang sudah ada (`POST /auth/recovery` → `/auth/recovery/confirm` bisa **men-set** password, bukan cuma reset). Tak ada endpoint set-password baru.
3. **Keunikan:** email/nomor yang sudah dipakai **akun lain** ditolak **422** — email: *"Email ini sudah dipakai akun lain."*, nomor: *"Nomor ini sudah dipakai akun lain."* (Sudah jadi milik sendiri → 422 *"sudah terpasang di akunmu."*)
4. **Efek:** menambah nomor → **login OTP WA otomatis aktif** (login `/auth/otp/*` mencocokkan identitas phone). Menambah email → **email recovery otomatis aktif**. ✅ **`kdf_salt` TIDAK disentuh** — cadangan E2E lama tetap terbaca.
5. **Ganti/hapus:** **Ganti** = panggil `POST /profile/email|phone` lagi dgn nilai baru → `confirm` mengganti identitas lama (satu email & satu nomor aktif per user). **Hapus** = `DELETE /profile/identity {provider}`, ditolak **422** bila itu satu-satunya cara masuk tersisa.

**`dev_code`** selalu `null` di produksi (kode asli hanya via email/WA). FE: tampilkan layar OTP 6-digit; jangan andalkan `dev_code`.

---

## ✅ ARSIP — sudah terjawab / shipped (tak perlu aksi)

### Kontrak "Data & Cadangan" (vault backup/restore/recovery/sync) — ✅ terjawab
Semua pertanyaan konfirmasi kami sudah dijawab BE lewat dokumen **`PROMPTS-flutter-auth-vault-sync.md`** (2026-07-05, "sudah diverifikasi live"). Ringkasan jawaban + status FE:
- **Konflik versi** → `PUT /vault/backup` balas **409 `version_conflict` + `current_version`**. ✅ FE menangani (restore → merge → backup ulang `current_version+1`).
- **Sync nonaktif** → endpoint tulis balas **409 `sync_disabled`**. ✅ FE menangani (jaring pengaman; app default selalu-nyala).
- **Satu blob** (bukan histori), `GET /vault/restore` selalu terbaru; **batas 20 MB** (>20 MB → 413); `checksum` opsional (`null` aman); **isolasi per-user** ✔; blob dihapus saat `DELETE`/hapus akun.
- **`last_synced_at`/`synced`** bergerak hanya saat `PUT /vault/backup` (bukan saat simpan interaksi/refleksi).
- ⚠️ **KRITIKAL — `kdf_salt` TIDAK berubah saat recovery** ✅ (dikonfirmasi BE di kode & verifikasi). Cadangan lama tetap bisa didekripsi dgn passphrase yang sama.
- **OTP WhatsApp** & **Email recovery (request + confirm)** ✅ live; FE sudah selaras (termasuk copy "reset memulihkan login, bukan passphrase", `dev_code` diabaikan).

### Buat Lingkaran (`POST /circles`) — ✅ shipped & terintegrasi
Create circle + auto-join; respons sebentuk item `GET /circles`. FE: tombol "Buat lingkaran" + form (nama, **emoji picker**, deskripsi).

### Prompt bersama harian — ✅ shipped & terintegrasi
`GET /prompts/today` (rotasi harian) + `POST /prompts/today/answers` dengan moderasi sinkron. FE membaca prompt live; tak ada lagi fallback hardcoded.

### Riwayat mood bulanan — ✅ shipped & terintegrasi
`GET /stats/mood?month=YYYY-MM` → kalender memori berwarna sebulan penuh.

### Refleksi harian "Tiga baris malam" (E2E) — ✅ live
`PUT/GET /reflections/{date}` (+ range), field terenkripsi `*_enc`/`*_nonce`. Sync antar-device.

### Ringkasan AI (consent-gated, plaintext transien) — ✅ live
`POST /ai/summarize/person` & `/ai/summarize/day` → `{summary}`. Hanya saat consent nyala. **Privasi:** plaintext → jangan simpan/log, proses transien; boleh cache by hash konten.
