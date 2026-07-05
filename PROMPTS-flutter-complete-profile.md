# Prompt untuk Flutter — "Lengkapi Profil" (tambah/ganti/hapus email & nomor)

**Env:** `https://console.temanlentera.id/api/v1` · **Semua butuh JWT** (`Authorization: Bearer`)
**Status:** BE **live & terverifikasi di produksi** (2026-07-05). Halaman Edit Profil FE bisa berhenti menampilkan *"fitur sedang disiapkan"* dan wiring endpoint nyata di bawah.

Tujuan fitur: user yang daftar via **satu** metode (Google / WhatsApp OTP / email) bisa **melengkapi** metode lain → punya cara masuk cadangan + jalur pemulihan yang cocok.

---

## Endpoint

### Tambah / ganti EMAIL (OTP dikirim via email)
```
POST /profile/email          { "email": "user@contoh.id" }
  → 200 { "message": "...", "dev_code": null }        // dev_code SELALU null di prod — abaikan
POST /profile/email/confirm  { "email": "user@contoh.id", "code": "123456" }
  → 200 { "user": { ...bentuk sama /me... } }
```

### Tambah / ganti NOMOR (OTP dikirim via WhatsApp/GOWA)
```
POST /profile/phone          { "phone": "+6281234567890" }
  → 200 { "message": "...", "dev_code": null }
POST /profile/phone/confirm  { "phone": "+6281234567890", "code": "123456" }
  → 200 { "user": { ...bentuk sama /me... } }
```

### Hapus salah satu metode
```
DELETE /profile/identity     { "provider": "email" | "phone" }
  → 200 { "user": {...} }
```

> **PENTING (stateless confirm):** di `.../confirm`, kirim **email/phone yang SAMA** seperti saat request + `code`. Server tak menyimpan "pending" — identifier harus ikut di confirm. (Nilai ini sudah ada di layar yang sama, jadi mudah.)

---

## Perilaku & aturan yang HARUS FE tangani

1. **`dev_code` selalu `null` di produksi.** Kode asli hanya sampai via email / WhatsApp. Tampilkan layar input OTP 6-digit; jangan pernah pakai `dev_code`.
2. **Kode OTP:** 6 digit, berlaku **5 menit**, maks **5 percobaan**. Sediakan tombol "kirim ulang" (throttle **6/menit** untuk request; **10/menit** untuk confirm).
3. **Format nomor:** E.164, `^\+?[0-9]{8,15}$` (mis. `+6281234567890`). **Kirim format yang konsisten** dgn yang dipakai login OTP (sama persis) agar identitasnya cocok.
4. **Keunikan → `422`:**
   - Email dipakai akun lain: `errors.email = ["Email ini sudah dipakai akun lain."]`
   - Nomor dipakai akun lain: `errors.phone = ["Nomor ini sudah dipakai akun lain."]`
   - Sudah jadi milik sendiri: `["… sudah terpasang di akunmu."]`
   Tampilkan pesan `errors.*[0]` apa adanya (sudah Bahasa Indonesia).
5. **Kode salah/kedaluwarsa → `422`** `errors.code = ["Kode salah atau kedaluwarsa."]`.
6. **Respons `confirm` = `{ user }`** — **bentuk sama persis `GET /me`**. Langsung pakai untuk refresh state akun (jangan panggil `/me` lagi). `user.providers` akan bertambah (`email`/`phone`).
   - ✅ **`user.phone`** kini tersedia (E.164 mentah, mis. `"+6281234567890"`; `null` bila belum ada) — pakai untuk **menampilkan nomor terpasang** di kartu "Cara masuk" (bukan sekadar badge "Terhubung"). Sama untuk `user.email`. Mask di sisi FE kalau mau (mis. `+62812••••7890`).
7. **Hapus metode terakhir ditolak `422`:** `errors.provider = ["Tidak bisa dihapus — sisakan minimal satu cara masuk."]`. Di UI, disable tombol hapus kalau itu satu-satunya metode.

---

## Efek setelah berhasil (untuk copy UI)
- **Tambah nomor** → login **OTP WhatsApp** otomatis aktif untuk akun ini.
- **Tambah email** → **pemulihan via email** (`/auth/recovery`) otomatis aktif.
  - Catatan: menambah email **belum** membuat login email+password. Kalau user mau login pakai email+sandi, arahkan ke **"Lupa sandi"** (`/auth/recovery`) untuk **menetapkan** sandi pertama kali.
- ✅ **Passphrase jurnal aman:** menambah/mengganti/menghapus kontak **tidak mengubah `kdf_salt`** — cadangan E2E lama tetap bisa dibuka dengan passphrase yang sama.

## Alur UI yang disarankan
1. Kartu "Cara masuk" menampilkan `user.providers` + email/nomor saat ini.
2. Tombol **Tambah/Ganti** → sheet input (email atau nomor) → `POST /profile/{email|phone}` → layar OTP 6-kotak → `.../confirm` → update state dari `{user}` → toast "Tersimpan".
3. Tombol **Hapus** (muncul hanya kalau `providers.length > 1`) → `DELETE /profile/identity`.

## Checklist QA
- [ ] Akun **phone-only** tambah email → confirm → `providers` = `[phone, email]`, `user.email` terisi.
- [ ] Akun **Google/email** tambah nomor → bisa login ulang lewat **OTP WhatsApp**.
- [ ] Email/nomor milik akun lain → `422` pesan jelas.
- [ ] Kode salah → `422`; kirim ulang → kode baru berfungsi.
- [ ] Hapus saat cuma 1 metode → `422` (tombol ter-disable).
- [ ] Setelah tambah email → uji **round-trip vault**: backup lama tetap bisa di-restore + didekripsi (bukti `kdf_salt` tak berubah).
