# Permintaan Fitur / Endpoint Backend — Lentera

**Env:** `https://console.temanlentera.id/api/v1`. Bug (bukan fitur baru) ada di [BE_BUGS.md](docs/BE_BUGS.md).

> **Status per 2026-07-06:** semua permintaan **shipped**. Tak ada item terbuka.

---

## ✅ ARSIP — sudah terjawab / shipped (tak perlu aksi)

### Field `phone` di payload user (`/me` & auth/profile) — ✅ shipped (2026-07-06)
Objek `user` sekarang menyertakan **`phone`** (E.164 mentah, mis. `"+6281234567890"`; `null` bila belum ada nomor) — di `GET /me`, `POST /profile/{phone,email}/confirm`, dan semua respons token auth (register/login/otp/oauth/refresh). Diambil dari `auth_identities` (provider=phone), bukan kolom baru. **Tidak dimask** (pemilik melihat nomornya sendiri; FE boleh memformat/mask saat menampilkan). FE kini bisa menampilkan nomor terpasang di halaman "Cara masuk", bukan sekadar "Terhubung ✓".

### Lengkapi Profil — tambah/ganti/hapus email & nomor (verifikasi OTP) — ✅ shipped & terintegrasi
Endpoint `POST /profile/{email,phone}` + `/confirm` (stateless: kirim ulang identifier + kode; confirm balas `{user}`) & `DELETE /profile/identity`. Kontrak di `PROMPTS-flutter-complete-profile.md`. FE: halaman **"Cara masuk"** (Atur → kartu akun) dengan alur input → OTP 6 digit, resend, hapus (muncul hanya kalau >1 metode). Efek: tambah nomor → login WA aktif; tambah email → recovery email aktif; `kdf_salt` tak berubah.

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
