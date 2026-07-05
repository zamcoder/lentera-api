# Lentera — Prompt Update FE (batch terbaru)

Kirim ke agent Flutter. Backend menambah/mengubah beberapa endpoint. **Base:**
`https://console.temanlentera.id/api/v1` · JSON · JWT Bearer · error `{message, errors}`.
Sumber kebenaran: `docs/API_ENDPOINTS.md`.

---

## Prompt (paste ke agent Flutter)

> Ada update backend Lentera. **Baca dulu `docs/API_ENDPOINTS.md`**, lalu
> integrasikan perubahan berikut. Semua di `{{BASE}}/api/v1`, JWT.

### 1. 🆕 Buat Lingkaran — `POST /circles`
> User sekarang bisa **membuat** lingkaran sendiri (sebelumnya cuma lihat/gabung).
>
> ```
> POST /circles
> body: { "name": "Pejuang tidur", "emoji": "🌙", "description": "..." }
> → 201 { id, name, emoji, desc, pal, member_count: 1, members: "1", joined: true }
> ```
> - Respons **flat, persis bentuk item `GET /circles`** → tinggal `insert(0, circle)` ke daftar (auto-join, `joined:true`).
> - **Tugas FE:** tombol **"Buat lingkaran"** (di Komunitas/Explore) → sheet form (nama wajib, emoji opsional, deskripsi opsional) → `POST /circles` → tambahkan ke daftar & buka.
> - **Validasi & error yang harus ditangani:**
>   - `name` 2–60 char, `description` ≤ 280, `emoji` opsional (server default 🌱)
>   - **422** bila nama/deskripsi kena kata terlarang → tampilkan pesan `message`/`errors.name`
>   - **422** "batas 5 lingkaran" bila user sudah punya 5 circle buatan sendiri → tampilkan pesan
> - Lingkaran **langsung publik** (tanpa tinjauan admin) — tak perlu state "menunggu tinjauan".
> - Nama boleh sama dengan circle lain (server bikin slug unik) — jangan blok di FE.

### 2. 💬 Prompt bersama harian — sekarang AKTIF
> `GET /prompts/today` **selalu mengembalikan prompt** (dirotasi harian otomatis):
> ```
> GET /prompts/today → { "prompt": { id, date, question, share_count } }
> ```
> - **Tugas FE:** hapus fallback "Belum ada pertanyaan hari ini" untuk kasus normal (tetap sediakan untuk error jaringan). Tampilkan `question` + `share_count`.
> - **Kirim jawaban** — moderasi **SINKRON** (bukan antre): status akhir langsung ada di respons.
>   ```
>   POST /prompts/today/answers { "text": "...", "anon": true }
>   → 201 { answer: {...}, moderation: { status, self_harm, safe_space } }
>   ```
>   Tangani `moderation.status`:
>   - **`approved`** → jawaban langsung tampil di daftar (`GET /prompts/today/answers`).
>   - **`held`** + `self_harm:true` → **jangan** tampilkan sebagai penolakan; arahkan ke **Ruang Tenang** pakai `moderation.safe_space`.
>   - **`rejected`** → beri tahu lembut ("jawaban tak bisa dibagikan"), jangan kasar.
>   - **Tidak ada status `pending`** untuk prompt — tak perlu spinner "menunggu tinjauan".
> - `GET /prompts/today/answers?cursor=` → hanya jawaban **approved** (pagination cursor).

### 3. Sudah tersedia juga (integrasikan bila belum)
> - **Kalender mood sebulan:** `GET /stats/mood?month=YYYY-MM` → `{ data: [ {date, mood_index} ] }`. Pakai untuk mewarnai "Kalender memori" sebulan penuh (gabung dgn `/reflections?from=&to=`).
> - **Refleksi "Tiga baris malam" (E2E):** `PUT/GET /reflections/{date}` (field `*_enc`/`*_nonce` via modul E2E). Ganti penyimpanan lokal → sync antar-device.
> - **Ringkasan AI (consent-gated):** `POST /ai/summarize/person` & `/day` → `{summary}` (bisa `null` → fallback template lokal). **Hanya panggil saat toggle "Ringkasan AI" ON.**

### Catatan penting
> - **Google Sign-In:** kirim **`id_token`** ke `POST /auth/oauth` (server verifikasi tanda tangan + `aud`; `sub` mentah diabaikan). Setelah login Google pertama, jalankan langkah **set passphrase** lalu turunkan kunci E2E dari `user.kdf_salt` (`/me`).
> - **Statistik harian** sudah di-bucket per **tanggal lokal (WIB)**. FE tetap kirim `occurred_at` ber-offset lokal (mis. `2026-07-05T00:30:00+07:00`) di `POST /interactions`.
> - Respons profil dibungkus `{user}` / `{settings}`; circle create **flat** (tanpa wrapper).

---

## Ringkas perubahan (untuk changelog FE)
| Endpoint | Perubahan |
|---|---|
| `POST /circles` | **BARU** — buat lingkaran (public, filter, maks 5/user) |
| `GET /prompts/today` | Sekarang selalu ada prompt (rotasi harian WIB) |
| `POST /prompts/today/answers` | Moderasi sinkron — baca `moderation.status` |
| `GET /stats/mood?month=` | **BARU** — kalender mood bulanan |
| `PUT/GET /reflections/{date}` | E2E refleksi (sync antar-device) |
| `POST /ai/summarize/*` | Ringkasan AI (consent ON) |
