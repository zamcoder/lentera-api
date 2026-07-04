# Laporan Bug Backend — dari E2E test Flutter

**Tanggal:** 2026-07-04 · **Env:** `https://console.temanlentera.id/api/v1` · **Metode:** uji integrasi app Android lawan server produksi.

Ringkas: mayoritas endpoint **sudah jalan** (auth email+Google, /me, /interactions, /media, /notifications/token, stats, settings GET, moderation, hotlines, community feed, circles, prompts). Ada **2 endpoint 500** dan **1 gap desain kdf_salt** yang perlu diperbaiki BE.

---

## 🔴 BUG 1 — `POST /community/posts` → 500 (core feature: kirim post)

Request valid tetap **500 Server Error**. Bukan masalah validasi (kalau `text` dihilangkan, server benar mengembalikan 422). Jadi crash terjadi **setelah** validasi, di logika pembuatan post.

**Request app:**
```
POST /community/posts
Authorization: Bearer <jwt>
{ "text": "Halo dari uji coba", "anon": true, "surface": "gratitude" }
```
**Respons:** `500 { "message": "Server Error" }`

**Bukti (curl):**
```
# text ADA  -> 500
curl -X POST .../community/posts -H "Authorization: Bearer <jwt>" \
  -d '{"text":"probe A","anon":true,"surface":"gratitude"}'
# -> {"message":"Server Error"}

# text KOSONG -> 422 (validasi jalan normal)
curl -X POST .../community/posts -H "Authorization: Bearer <jwt>" -d '{"surface":"gratitude"}'
# -> {"message":"The text field is required.","errors":{"text":["The text field is required."]}}
```
**Tolong cek:** log/stacktrace server saat membuat post (kemungkinan null pointer / kolom DB / relasi moderasi). **Severity: TINGGI** — fitur komunitas tidak bisa dipakai.

---

## 🔴 BUG 2 — `PUT /settings/sync` → 500 (toggle sinkron)

Body valid `{enabled: bool}` selalu **500** untuk `true` maupun `false`. `GET /settings` sendiri jalan normal.

**Request app:**
```
PUT /settings/sync
Authorization: Bearer <jwt>
{ "enabled": true }
```
**Respons:** `500 { "message": "Server Error" }`

**Bukti (curl):**
```
curl -X PUT .../settings/sync -H "Authorization: Bearer <jwt>" -d '{"enabled":true}'   # -> 500
curl -X PUT .../settings/sync -H "Authorization: Bearer <jwt>" -d '{"enabled":false}'  # -> 500
curl     .../settings         -H "Authorization: Bearer <jwt>"
# -> {"settings":{"sync_on":false,"reminder_on":false,"reminder_at":null,"accent":"sage","theme":"system"}}
```
**Tolong cek:** handler `PUT /settings/sync`. **Severity: SEDANG.**

---

## 🟠 BUG/GAP 3 — `kdf_salt` null untuk akun OAuth (Google) → vault E2E tak bisa dibuka

Akun yang dibuat lewat **Google Sign-In** punya `kdf_salt: null` di `/auth/oauth` dan `/me`. Akibatnya app tidak bisa menurunkan kunci enkripsi → jurnal E2E (interactions/media/backup) **tidak bisa dipakai** untuk user Google.

**Bukti — akun Google:**
```
GET /me -> { "user": { ..., "email":"...@gmail.com", "kdf_salt": null, "providers":["google"] } }
```
**Bukti — akun Email (benar):**
```
POST /auth/register -> 201 { "user": { ..., "kdf_salt":"03DTtcyQmvFK92SrFHcLjw==", "providers":["email"] } }
```

**Diskusi desain (perlu keputusan bareng):** kdf_salt adalah 16 byte acak per akun, dibuat device saat register email. Untuk user Google, device tidak pernah membuatnya. Opsi:
- **(A)** BE generate `kdf_salt` acak saat OAuth signup pertama & simpan (paling simpel; app tinggal baca dari `/me`). Tapi user tetap butuh **passphrase** untuk turunkan kunci — app perlu langkah "set passphrase" setelah first Google login.
- **(B)** App yang generate `kdf_salt` saat first OAuth login lalu kirim ke endpoint (mis. `POST /me/kdf-salt`) — butuh endpoint baru dari BE.

Untuk sekarang **E2E hanya jalan untuk akun email**. Mohon diskusi opsi A/B. **Severity: TINGGI untuk fitur E2E user Google.**

---

## 🟡 KONFIRMASI 4 — verifikasi `id_token` di `/auth/oauth` (keamanan)

App mengirim `POST /auth/oauth { provider, sub, email, id_token }`. **Server WAJIB memverifikasi `id_token`** (cek tanda tangan Google + `aud` = Web Client ID `288749223818-h2agl9p19hv1v8kifop5h1bls2lo54pi.apps.googleusercontent.com`) lalu ambil `sub`/`email` dari token — **jangan percaya `sub` mentah** dari klien (bisa dipalsukan). Mohon konfirmasi ini sudah dilakukan.

---

## ✅ Yang sudah TERVALIDASI jalan (tidak perlu aksi)
`POST /auth/register` (+kdf_salt) · `POST /auth/login` · `POST /auth/oauth` · `GET /me` · `GET /vault/status` · `GET/POST /interactions` · `POST /media` (kind `audio`|`photo`) · `POST /notifications/token` · push FCM diterima · `GET /stats/summary` · `GET /settings` · `GET /moderation/banned-terms` · `GET /safety/hotlines` · `GET /community/feed` · `GET /circles` · `GET /prompts/today`.

> Catatan format: semua respons profil dibungkus `{"user":{...}}` / `{"settings":{...}}` — app sudah menyesuaikan; sekadar FYI kalau mau konsistenkan (mis. selalu `data`).
