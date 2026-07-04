# Lentera — Prompt Backend: Endpoint Waitlist Email

Kirim ke Claude Code / developer backend. Ini penambahan kecil pada API Laravel yang sudah ada di **console.temanlentera.id** — dipakai oleh landing page statis di **temanlentera.id** untuk mengumpulkan email calon pengguna.

---

## Prompt

> Tambahkan endpoint **kumpulkan email waitlist** ke API Laravel ini (domain: `console.temanlentera.id`). Landing page statis di `https://temanlentera.id` akan mengirim POST JSON ke sini. Buat lengkap: migration, model, route, controller, dan konfigurasi CORS.
>
> **Kontrak API**
> - **Endpoint:** `POST /api/subscribe`
> - **Request body (JSON):** `{ "email": "nama@email.com" }`
> - **Sukses:** `201` → `{ "ok": true }`
> - **Email tidak valid:** `422` (format error standar Laravel)
> - **Kena rate limit:** `429`
> - Idempoten: email yang sama dikirim dua kali TIDAK error dan TIDAK menduplikat.
>
> **Detail implementasi**
> 1. **Migration** tabel `subscribers`: `id`, `email` (string, unique, simpan lowercase), `source` (string nullable, default 'landing'), `ip` (string nullable), `created_at`.
> 2. **Model** `Subscriber` (`$fillable = ['email','source','ip']`, `$timestamps = false` atau pakai created_at saja).
> 3. **Route** di `routes/api.php`: `POST /subscribe` → `SubscriberController@store`, middleware `throttle:10,1` (maks 10 permintaan/menit per IP, anti-spam).
> 4. **Controller** `store()`:
>    - Validasi: `email => 'required|email:rfc|max:190'`.
>    - `Subscriber::firstOrCreate(['email' => strtolower($request->email)], ['source' => 'landing', 'ip' => $request->ip()])`.
>    - Kembalikan `response()->json(['ok' => true], 201)`.
> 5. **CORS** (`config/cors.php`): izinkan hanya origin landing —
>    ```php
>    'paths' => ['api/*'],
>    'allowed_methods' => ['POST'],
>    'allowed_origins' => ['https://temanlentera.id', 'https://www.temanlentera.id'],
>    'allowed_headers' => ['Content-Type', 'Accept'],
>    ```
>    (Untuk testing lokal, tambahkan origin dev-mu sementara.)
>
> **Keamanan & kualitas**
> - Rate limit sudah lewat middleware `throttle`.
> - Validasi email wajib; tolak yang tidak valid dengan 422.
> - Jangan bocorkan apakah email sudah terdaftar (selalu balas 201 untuk email valid).
> - (Opsional) endpoint admin `GET /api/subscribers` di belakang auth untuk melihat daftar; atau cukup query tabel langsung dari konsol.
>
> **Uji**
> - `curl -X POST https://console.temanlentera.id/api/subscribe -H "Content-Type: application/json" -d '{"email":"tes@lentera.id"}'` → `201 {"ok":true}`.
> - Kirim ulang email yang sama → tetap `201`, baris tidak bertambah.
> - Email ngawur → `422`.

---

## Sisi frontend (sudah beres)

Landing page sudah menembak endpoint ini. Di file HTML, variabel endpoint ada di:

```js
this.API = 'https://console.temanlentera.id/api/subscribe';
```

Alur di tombol **"Beri tahu saya"**: validasi format email → `fetch` POST JSON `{ email }` → status "Mengirim…" → sukses menampilkan "Terima kasih 🌿", gagal menampilkan pesan error. Tidak perlu perubahan frontend selama kontrak API di atas dipenuhi.

> Catatan: kalau nanti kamu memilih menyajikan landing dari Laravel yang sama (same-origin), endpoint frontend bisa diganti jadi relatif `'/api/subscribe'` dan CORS tak diperlukan.
