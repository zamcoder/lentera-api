# Lentera — Prompts untuk Agent Backend (Laravel + PostgreSQL)

Panduan untuk membangun **API backend Lentera**. Polanya sama seperti `PROMPTS-flutter.md`:
**satu domain per prompt**, dan selalu minta agent membaca **file konteks** (bukan menebak).

## Sebelum mulai — siapkan konteks untuk agent

Taruh file berikut di repo backend (atau beri agent akses baca ke repo mobile) — ini **sumber kebenaran**:

| File | Isi |
|---|---|
| `API_REQUIREMENTS.md` | **Daftar endpoint per fitur/layar** (paling penting) — dari repo mobile |
| `Lentera - Rencana Produk.dc.html` | Model privasi (2 bidang data), skema DB, grup API, roadmap Fase 0–4 |
| `Lentera - Handoff Doc.dc.html` | Spesifikasi teknis: SQL schema, alur E2E encryption, endpoint |
| `lib/data/models.dart` + `lib/data/dummy_data.dart` | **Bentuk data & konten dummy** yang harus dicocokkan response API |

> Kalau agent tak bisa baca `.dc.html`, buka di browser lalu tempel bagian **skema DB** & **endpoint** ke sebuah `SPEC.md`.

**Aturan emas:** minta agent membaca `API_REQUIREMENTS.md` untuk endpoint & konteks, dan `lib/data/models.dart` untuk **bentuk JSON** yang diharapkan app — supaya frontend tinggal colok tanpa mengubah model.

---

## 0 — Kickoff (jalankan sekali)

> Kamu backend engineer membangun **REST API untuk app "Lentera"** — jurnal syukur & batas + komunitas yang dimoderasi. **Baca dulu `API_REQUIREMENTS.md` (daftar endpoint per fitur), `Lentera - Rencana Produk.dc.html` & `Lentera - Handoff Doc.dc.html` (skema DB + alur E2E), dan `lib/data/models.dart`+`lib/data/dummy_data.dart` (bentuk & konten data). Ini sumber kebenaran — jangan menebak bentuk data.**
>
> **Stack (wajib):** Laravel 11, PHP 8.2+, **PostgreSQL 16**, auth **JWT** (mis. `tymon/jwt-auth` atau Sanctum token), API Resources untuk shaping response, Form Request untuk validasi, Pest/PHPUnit untuk test. Semua route di bawah `/api/v1`, JSON, format error konsisten `{message, errors}`.
>
> **Dua prinsip yang tidak boleh dilanggar:**
> 1. **Jurnal pribadi = E2E.** Server hanya menyimpan **ciphertext** (kolom BYTEA). Kunci diturunkan di perangkat (Argon2id) & **tak pernah** dikirim ke server. Hanya metadata non‑sensitif (type, tanggal, jumlah, indeks mood) yang plaintext untuk statistik/urut.
> 2. **Komunitas = plaintext, dimoderasi.** Bidang terpisah; tiap post lewat pipeline moderasi (`pending → approved/held`).
>
> **Tugas kickoff:** setup project Laravel + koneksi PostgreSQL, konfigurasi auth JWT, buat struktur folder (Models/Http/Controllers/Requests/Resources, database/migrations, database/seeders, routes/api.php, tests). Buat **1 endpoint health‑check** `GET /api/v1/health`. Tunjukkan **rencana skema DB (daftar tabel + kolom)** dan **rencana urutan pengerjaan**, lalu **konfirmasi sebelum lanjut**. Belum bikin domain apa pun.

---

## Aturan (tempel di tiap prompt bila perlu)

- Bentuk JSON response **harus cocok** dengan model app di `lib/data/models.dart` (mis. `Person`, `Moment`, `Post`, `Reactions`, `Circle`). Kalau beda, sepakati dulu.
- Semua endpoint (kecuali grup Auth) butuh `Authorization: Bearer <JWT>`.
- List panjang **paginated** (cursor) → app pakai tombol "Muat lebih banyak".
- Tulis **migration + model + Form Request (validasi) + API Resource + controller + route + feature test** untuk tiap domain.
- **Jangan simpan plaintext jurnal.** Field jurnal (`text`, `name`, `rel`) disimpan sebagai `*_enc` (BYTEA/ciphertext yang dikirim app).
- Seed **data dummy yang sama** dengan `lib/data/dummy_data.dart` supaya app bisa diuji end‑to‑end dengan konten identik.

---

## Urutan pengerjaan (satu domain per prompt)

Ikuti prioritas MVP di `API_REQUIREMENTS.md`.

### Fase 1 — Jurnal jalan end‑to‑end
1. **Auth & Akun** — register/login/oauth/otp/2fa/recovery/logout + `GET /me`. (`API_REQUIREMENTS.md §1`)
2. **Vault sync (E2E)** — `vault/status|backup|restore`, toggle sinkron. Ciphertext saja. (§2)
3. **Orang (People)** — CRUD, field terenkripsi + metadata plaintext utk sort. (§3)
4. **Momen/Interaksi** — CRUD, filter `?person_id=&type=&cursor=`. (§4) + **Media** upload terenkripsi (§5)
5. **Mood & Statistik** — `stats/summary`, `mood`, `today`. Metadata plaintext. (§6)

### Fase 2 — Komunitas
6. **Feed & Post** — `community/feed|posts`, react, hide. **Status `pending → approved`.** (§7)
7. **Lingkaran** — list/detail/join/leave. (§8)
8. **Prompt bersama & Kirim kekuatan** — prompts + strength (kirim **instan tanpa pra‑tayang**). (§9)
9. **Moderasi** — `reports`, `moderation/banned-terms`. Pipeline pra‑tayang: **regex kata terlarang (identik `lib/data/dummy_data.dart` → `bannedWords`)** + klasifikasi Gemini + **deteksi krisis** (`crisisSignals`) → tahan + rute "penanganan khusus". (§10)

### Fase 3 — Keselamatan & Notifikasi
10. **Keselamatan** — `safety/hotlines?region=`. (§11)
11. **Pengaturan & Notifikasi** — `settings`, device token (FCM/APNs), jadwal pengingat malam. (§12)

---

## Penutup (setelah semua domain)

> Rapikan: dokumentasi API (OpenAPI/Swagger atau `routes/api.php` + koleksi Postman), format error & rate‑limit konsisten, seeder lengkap (data identik app), dan **feature test** tiap endpoint. Sediakan `.env.example` + instruksi migrate & seed. Konfirmasi bentuk response final vs `lib/data/models.dart`.

---

## Catatan penting untuk dijaga (jiwa app)

- **Privasi dua bidang:** jurnal E2E (server buta isinya) vs komunitas dimoderasi — **jangan campur**.
- **Moderasi:** post komunitas `pending` dulu; regex kata terlarang **identik** app & konsol; sinyal krisis ditangani **lembut** (tahan + Ruang Tenang), bukan blokir dingin.
- **Kirim kekuatan** = pesan siap‑pakai → **instan**, bukan pra‑tayang.
- **Tenang > engagement:** tanpa angka follower/like publik; reaksi tanpa kolom komentar.
- **Bentuk data cocok** dengan `lib/data/models.dart` supaya integrasi mobile mulus.
