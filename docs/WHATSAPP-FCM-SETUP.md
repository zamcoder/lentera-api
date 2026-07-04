# Lentera — Setup WhatsApp OTP (self-host) & FCM Push

Panduan menyalakan **OTP WhatsApp** lewat `go-whatsapp-web-multidevice`
(self-host di VPS) dan **push notification FCM** (server + Flutter).

- OTP login HP → WhatsApp. OTP pemulihan → email. (SMS tidak dipakai.)
- Push (pengingat malam §12) → FCM.

---

# BAGIAN A — WhatsApp OTP via go-whatsapp-web-multidevice

Repo: <https://github.com/aldinokemal/go-whatsapp-web-multidevice>
Ini REST wrapper untuk **whatsmeow** — kamu "link device" satu nomor WA
(seperti WhatsApp Web), lalu backend Lentera kirim OTP via `POST /send/message`.

## ⚠️ Baca dulu — keamanan & risiko
1. **Jangan expose port gateway ke publik.** Bind ke `127.0.0.1` saja; backend
   Lentera memanggilnya lewat localhost. Siapa pun yang bisa akses gateway =
   bisa kirim WA atas nama nomormu.
2. **Pakai nomor WA khusus** (bukan nomor pribadimu). Akun WhatsApp yang dipakai
   mengirim OTP massal **berisiko diblokir** WhatsApp. Untuk volume kecil OTP ini
   umumnya aman, tapi siapkan nomor cadangan.
3. **Selalu pakai Basic Auth** (username:password) pada gateway.
4. Session tersimpan di folder `storages/` (SQLite) — **wajib di-mount volume**
   agar tidak perlu scan QR ulang tiap restart.

## A.1 — Deploy dengan Docker (disarankan)

Prasyarat: Docker + Docker Compose terpasang di VPS.
```bash
# cek; kalau belum ada:
docker --version && docker compose version
# install cepat (Debian/Ubuntu):
# curl -fsSL https://get.docker.com | sh
```

Buat folder khusus (di luar web-root Laravel):
```bash
mkdir -p /opt/lentera-wa && cd /opt/lentera-wa
mkdir -p storages
```

Buat `docker-compose.yml`:
```yaml
services:
  gowa:
    image: aldinokemal2104/go-whatsapp-web-multidevice:latest
    container_name: lentera-wa
    restart: unless-stopped
    # HANYA localhost — jangan "3000:3000" (itu buka ke publik)
    ports:
      - "127.0.0.1:3000:3000"
    volumes:
      - ./storages:/app/storages
    command:
      - rest
      - --port=3000
      - --basic-auth=lentera:GANTI_PASSWORD_KUAT
      - --os=Lentera
      - --account-validation=false
```
> Ganti `GANTI_PASSWORD_KUAT` dengan password acak yang kuat. Catat
> `lentera` (user) + password itu — nanti dipakai di `.env` Lentera.

Nyalakan:
```bash
docker compose up -d
docker compose logs -f    # pantau; Ctrl-C untuk berhenti memantau
```

## A.2 — Link device (scan QR) via SSH tunnel

Karena gateway hanya di `127.0.0.1`, buka dashboard-nya lewat **SSH tunnel**
dari laptopmu (bukan diekspos publik):
```bash
# jalankan DI LAPTOP-mu (bukan di VPS):
ssh -L 3000:127.0.0.1:3000 root@IP_VPS
```
Biarkan sesi SSH itu terbuka, lalu di browser laptop buka:
```
http://127.0.0.1:3000
```
- Masukkan Basic Auth (`lentera` / password tadi).
- Klik **Login / App → Login**, akan muncul **QR code**.
- Di HP: WhatsApp → **Perangkat tertaut → Tautkan perangkat** → scan QR.
- Setelah tertaut, status jadi *connected*. Tutup tunnel SSH (gateway tetap jalan
  di VPS). Session tersimpan di `storages/`.

## A.3 — Hubungkan ke backend Lentera

Di `.env` **Laravel** (`/var/www/lentera-api/.env`):
```ini
WA_PROVIDER=gowa
WA_ENDPOINT=http://127.0.0.1:3000
WA_USERNAME=lentera
WA_PASSWORD=GANTI_PASSWORD_KUAT
```
Terapkan:
```bash
cd /var/www/lentera-api
php artisan config:cache
```

## A.4 — Uji end-to-end

Cek gateway hidup (dari VPS):
```bash
curl -s -u lentera:GANTI_PASSWORD_KUAT http://127.0.0.1:3000/app/devices
```

Uji kirim langsung (ganti nomor tujuan, format digit + kode negara):
```bash
curl -s -u lentera:GANTI_PASSWORD_KUAT \
  -H "Content-Type: application/json" \
  -d '{"phone":"628123456789","message":"Tes Lentera"}' \
  http://127.0.0.1:3000/send/message
```
Kalau WA masuk → gateway OK.

Uji lewat alur OTP Lentera:
```bash
curl -s -X POST https://console.temanlentera.id/api/v1/auth/otp/request \
  -H "Content-Type: application/json" \
  -d '{"phone":"628123456789"}'
```
OTP harusnya sampai via WhatsApp. Kalau gagal, cek:
```bash
tail -n 50 /var/www/lentera-api/storage/logs/laravel.log   # cari "WA gagal kirim"
docker compose -f /opt/lentera-wa/docker-compose.yml logs --tail=50
```

## A.5 — Operasional
```bash
cd /opt/lentera-wa
docker compose ps                 # status
docker compose restart            # restart (session tetap, tak perlu scan ulang)
docker compose pull && docker compose up -d   # update versi
```
Kalau nomor ter-*logout* (mis. WA di HP hapus perangkat tertaut), ulangi **A.2**.

## A.6 — Alternatif tanpa Docker (binary + systemd) — singkat
Unduh binary rilis dari halaman Releases repo, taruh di `/opt/lentera-wa/gowa`,
lalu buat service `/etc/systemd/system/lentera-wa.service`:
```ini
[Unit]
Description=Lentera WA Gateway
After=network.target
[Service]
WorkingDirectory=/opt/lentera-wa
ExecStart=/opt/lentera-wa/gowa rest --port=3000 --basic-auth=lentera:PASSWORD --os=Lentera
Restart=always
[Install]
WantedBy=multi-user.target
```
```bash
systemctl daemon-reload && systemctl enable --now lentera-wa
```

---

# BAGIAN B — FCM Push Notification

Server Lentera sudah punya klien **FCM HTTP v1** (OAuth2 service-account). Kamu
tinggal: buat proyek Firebase, unduh **service-account JSON**, taruh di VPS,
set `.env`. Di sisi Flutter: daftar app ke Firebase + kirim token device.

## B.1 — Buat proyek Firebase & service account
1. Buka <https://console.firebase.google.com> → **Add project** (mis. `lentera`).
2. Di project → **⚙️ Project settings → Service accounts**.
3. Klik **Generate new private key** → unduh file JSON (mis.
   `lentera-firebase-adminsdk-xxxxx.json`). **File ini rahasia** — jangan commit,
   jangan taruh di web-root.

> `project_id`, `client_email`, `private_key` dibaca otomatis dari JSON ini oleh
> server (tak perlu isi manual).

## B.2 — Taruh JSON di VPS (aman) & set .env
```bash
# taruh di luar public/, permission ketat
mkdir -p /var/www/lentera-api/storage/app/secure
# upload file JSON ke path itu (scp/sftp), lalu:
chmod 640 /var/www/lentera-api/storage/app/secure/fcm.json
chown www-data:www-data /var/www/lentera-api/storage/app/secure/fcm.json
```
`.env`:
```ini
PUSH_DRIVER=fcm
FCM_CREDENTIALS=/var/www/lentera-api/storage/app/secure/fcm.json
```
```bash
php artisan config:cache
```

## B.3 — Pastikan scheduler pengingat jalan (§12)
Pengingat malam dikirim command `lentera:reminders` via Laravel Scheduler.
Tambahkan **satu** cron di VPS:
```bash
crontab -e
```
```
* * * * * cd /var/www/lentera-api && php artisan schedule:run >> /dev/null 2>&1
```

## B.4 — Uji kirim (butuh token device dari Flutter, lihat B.5)
Setelah app Flutter mendaftarkan token (`POST /notifications/token`), uji:
```bash
php artisan lentera:reminders --at=$(date +%H:%M)
tail -n 30 /var/www/lentera-api/storage/logs/laravel.log
```
Atau uji manual satu user via tinker:
```bash
php artisan tinker --execute="
app(App\Services\Push\PushSender::class)->sendReminder(
  App\Models\User::whereHas('devices')->first()
); echo 'sent';"
```

---

# BAGIAN C — Sisi Flutter (FCM)

Yang harus disampaikan ke agent Flutter:

## C.1 — Registrasi app ke Firebase
- **Android**: Firebase console → Add app → Android. `applicationId` = package
  app (mis. `id.temanlentera.app`). Unduh **`google-services.json`** → taruh di
  `android/app/`. Tambah plugin Google Services (Gradle).
- **iOS** (kalau perlu): Add app → iOS, unduh **`GoogleService-Info.plist`** →
  `ios/Runner/`. Aktifkan **Push Notifications** + **Background Modes → Remote
  notifications** di Xcode. Butuh **APNs key** (.p8) diunggah ke Firebase.
- Cara termudah: jalankan **FlutterFire CLI**:
  ```bash
  dart pub global activate flutterfire_cli
  flutterfire configure
  ```

## C.2 — Dependencies
```yaml
dependencies:
  firebase_core: ^3.0.0
  firebase_messaging: ^15.0.0
```

## C.3 — Alur token (prompt untuk agent Flutter)
> Integrasikan FCM. Base API `https://console.temanlentera.id/api/v1`, auth JWT.
> 1. `Firebase.initializeApp()` di `main()`.
> 2. Minta izin notifikasi: `FirebaseMessaging.instance.requestPermission()`.
> 3. Ambil token: `final token = await FirebaseMessaging.instance.getToken();`
> 4. **Setelah user login**, kirim ke server:
>    `POST /notifications/token` body `{ "token": token, "platform": "fcm" }`
>    (header `Authorization: Bearer <jwt>`).
> 5. Pantau refresh: `FirebaseMessaging.instance.onTokenRefresh.listen(...)` →
>    kirim ulang token baru ke endpoint yang sama.
> 6. Handler:
>    - Foreground: `FirebaseMessaging.onMessage.listen((m) { /* tampilkan in-app / local notification */ });`
>    - Background/terminated: daftarkan top-level
>      `@pragma('vm:entry-point') Future<void> _bgHandler(RemoteMessage m) async {}`
>      via `FirebaseMessaging.onBackgroundMessage(_bgHandler);`
> 7. (Android 13+) izin runtime `POST_NOTIFICATIONS` sudah dicakup
>    `requestPermission()` firebase_messaging, pastikan `compileSdk`/`targetSdk` ≥ 33.
> 8. Untuk local notification saat foreground, boleh pakai
>    `flutter_local_notifications` (opsional).

Payload dari server berisi `notification.title/body` + `data` (mis.
`{type: "reminder"}`) — pakai `data` untuk deep-link ke layar tertentu.

## C.4 — Endpoint terkait
| Method | Path | Body |
|---|---|---|
| POST | `/api/v1/notifications/token` | `{token, platform: "fcm"\|"apns"}` |

Token lama yang invalid otomatis dihapus server saat FCM balas
`UNREGISTERED`/`INVALID_ARGUMENT`.

---

# Ringkasan `.env` produksi (bagian OTP + Push)
```ini
# WhatsApp OTP (self-host gowa)
WA_PROVIDER=gowa
WA_ENDPOINT=http://127.0.0.1:3000
WA_USERNAME=lentera
WA_PASSWORD=<password-basic-auth-gowa>

# Push FCM
PUSH_DRIVER=fcm
FCM_CREDENTIALS=/var/www/lentera-api/storage/app/secure/fcm.json
```
Setelah ubah `.env`: `php artisan config:cache`.
