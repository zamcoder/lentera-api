# Panduan Deploy Lentera ke VPS

Panduan produksi untuk **satu proyek Laravel** (API `/api/*` + konsol moderasi
React). Target: Ubuntu 22.04/24.04 LTS. Sesuaikan bila memakai distro/panel lain.

---

## 0. Peta domain

Memakai **temanlentera.id**. Satu Laravel app (API + konsol) disajikan di **satu
subdomain**:

| Host | Untuk |
|---|---|
| **console.temanlentera.id** | Semuanya: konsol admin (SPA React) **dan** API — konsol & mobile memakai `/api/*` di host ini |
| **temanlentera.id** (root), `.site`, `.space` | Landing page — **dibuat terpisah nanti**, tidak dipasang di server ini dulu |

- Konsol memanggil API **same-origin** (`console.temanlentera.id/api`) → tak perlu CORS.
- Aplikasi **mobile** memakai `https://console.temanlentera.id/api`.
- **Perlindungan konsol admin = 2FA TOTP + role admin + ability token** (bukan IP
  allowlist — cocok bila IP Anda dinamis). Endpoint `/api/mod/*` tetap hanya bisa
  diakses token ber-ability `mod` yang terbit setelah 2FA.

---

## 1. Yang perlu Anda siapkan (checklist)

Siapkan hal berikut **sebelum** mulai:

| # | Kebutuhan | Catatan |
|---|---|---|
| ☐ | **VPS** Ubuntu 22.04+ | Min. 1 GB RAM (disarankan 2 GB), 1 vCPU, akses `sudo`/SSH |
| ☐ | **DNS temanlentera.id** | A record `console` → IP VPS. Root dibiarkan untuk landing nanti. |
| ☐ | **PostgreSQL 16** | Terpasang di VPS atau managed DB terpisah |
| ☐ | **Google Gemini API key** | Untuk moderasi Lapis 2. Ambil di <https://aistudio.google.com/apikey>. Tanpa ini, moderasi AI memakai stub heuristik. |
| ☐ | **Kredensial SMTP** | Untuk email pemulihan/OTP (mis. Mailgun/Postmark/SES/SMTP biasa). Saat ini kode OTP hanya **ditulis ke log** — produksi butuh pengirim nyata. |
| ☐ | **(Opsional) Provider SMS** | Untuk OTP nomor HP (mis. Twilio/Vonage). Saat ini OTP HP juga masih **log-only** (placeholder). |
| ☐ | ~~IP admin~~ | Tidak dipakai — konsol dijaga **2FA** (bukan IP allowlist), karena IP Anda dinamis. |
| ☐ | **Sertifikat HTTPS** | Gratis via Let's Encrypt (Certbot) — dijelaskan di bawah. |

> **Keputusan tercatat (dari Rencana Produk):** 1 moderator awal · Gemini tier
> gratis (pantau kuota) · hotline krisis menyusul (wajib aktif sebelum komunitas
> live) · tanpa batas usia.

---

## 2. Pasang paket sistem

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 + ekstensi (repo ondrej untuk versi terbaru)
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip php8.3-intl unzip git

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Web server, PostgreSQL, Supervisor, Certbot
sudo apt install -y nginx postgresql-16 supervisor certbot python3-certbot-nginx
```

> Wajib: ekstensi PHP **pdo_pgsql** & **pgsql** aktif (paket `php8.3-pgsql`),
> plus `mbstring, openssl, bcmath, ctype, fileinfo` (bawaan). Argon2id sudah
> didukung PHP 8.3.

---

## 3. Database PostgreSQL

```bash
sudo -u postgres psql <<'SQL'
CREATE ROLE lentera WITH LOGIN PASSWORD 'GANTI_PASSWORD_KUAT';
CREATE DATABASE lentera OWNER lentera;
\c lentera
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;
SQL
```

---

## 4. Ambil kode & build

```bash
sudo mkdir -p /var/www && sudo chown $USER:$USER /var/www
cd /var/www
git clone https://github.com/zamcoder/lentera-api.git
cd lentera-api

# Dependensi produksi
composer install --no-dev --optimize-autoloader
npm ci
npm run build          # → public/build (aset konsol React)
```

---

## 5. Konfigurasi `.env` produksi

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret --force   # WAJIB — mengisi JWT_SECRET (auth API mobile & konsol)
nano .env
```

> **JWT_SECRET wajib & stabil.** Tanpa ini semua auth API gagal. Set **sekali**;
> bila diganti, semua token yang beredar langsung tidak valid (semua user
> ter-logout). `key:generate` (APP_KEY) dan `jwt:secret` (JWT_SECRET) adalah dua
> kunci berbeda — keduanya harus terisi.

Isi seperti berikut (sesuaikan nilai rahasia):

```ini
APP_NAME=Lentera
APP_ENV=production
APP_KEY=            # sudah diisi key:generate
JWT_SECRET=         # sudah diisi jwt:secret
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://console.temanlentera.id

# Masa berlaku token JWT (menit). Default config 60. Untuk mobile, umumnya
# dinaikkan agar user tak sering login ulang (belum ada refresh-token endpoint).
JWT_TTL=20160       # 14 hari (contoh)

# Origin browser lintas-domain yang boleh memanggil API. Konsol same-origin
# (tak butuh CORS). Isi nanti dengan origin landing page bila ia memanggil API
# dari browser, mis. https://temanlentera.id. Kosong = izinkan semua origin.
# Aplikasi mobile native TIDAK terpengaruh CORS.
CORS_ALLOWED_ORIGINS=

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=lentera
DB_USERNAME=lentera
DB_PASSWORD=GANTI_PASSWORD_KUAT

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

# Moderasi AI (Lapis 2). Kosong = stub heuristik (tanpa AI nyata).
GEMINI_API_KEY=isi_key_gemini_anda
GEMINI_MODEL=gemini-1.5-flash

# Rate limit komunitas (kiriman/menit/akun)
COMMUNITY_POST_RATE=6

# Email (untuk OTP pemulihan). Ganti ke SMTP nyata.
MAIL_MAILER=smtp
MAIL_HOST=smtp.provider.com
MAIL_PORT=587
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
MAIL_FROM_ADDRESS=no-reply@temanlentera.id
MAIL_FROM_NAME=Lentera
```

> **PENTING:** `APP_DEBUG=false` di produksi (jangan bocorkan stack trace).

---

## 6. Migrasi, seed, dan hardening awal

```bash
php artisan migrate --force               # --force wajib di produksi
php artisan db:seed --force               # banned_terms 7 kata + admin + circles + prompt hari ini
# (JANGAN jalankan DemoSeeder di produksi — itu data contoh)

# GANTI SANDI ADMIN default segera:
php artisan tinker --execute="\App\Models\User::where('handle','moderator')->update(['password_hash'=>\Illuminate\Support\Facades\Hash::make('SANDI_ADMIN_BARU')]);"
```

Login pertama admin akan meminta **setup 2FA** (salin kunci ke Google
Authenticator/Authy). 2FA wajib sebelum akses `/mod/*`.

---

## 7. Izin folder & cache produksi

```bash
sudo chown -R www-data:www-data /var/www/lentera-api/storage /var/www/lentera-api/bootstrap/cache
sudo chmod -R ug+rwx /var/www/lentera-api/storage /var/www/lentera-api/bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Setiap kali `.env` berubah, jalankan ulang `php artisan config:cache`.

---

## 8. Nginx + HTTPS

Satu server block untuk `console.temanlentera.id` (menyajikan konsol + API).

`/etc/nginx/sites-available/temanlentera`:

```nginx
server {
    listen 80;
    server_name console.temanlentera.id;
    root /var/www/lentera-api/public;
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/temanlentera /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# HTTPS otomatis (Let's Encrypt)
sudo certbot --nginx -d console.temanlentera.id
```

> **Keamanan konsol:** tanpa IP allowlist (IP Anda dinamis). Gerbang admin
> mengandalkan **2FA TOTP + role admin + ability token** — `/api/mod/*` hanya
> bisa diakses token ber-ability `mod` yang baru terbit setelah verifikasi 2FA.
> Konsol memuat SPA dari `console.temanlentera.id` lalu memanggil
> `console.temanlentera.id/api/*` (same-origin). Aplikasi mobile juga memakai
> `https://console.temanlentera.id/api`.

---

## 9. Worker antrean (moderasi Lapis 2) via Supervisor

Moderasi Gemini berjalan asinkron lewat Queue — butuh worker berjalan terus.

`/etc/supervisor/conf.d/lentera-worker.conf`:

```ini
[program:lentera-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lentera-api/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/lentera-api/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start lentera-worker:*
```

> Setelah tiap deploy kode, restart worker: `php artisan queue:restart`.

---

## 10. (Opsional) Penjadwal

Jika nanti menambah tugas terjadwal (mis. rotasi prompt harian), aktifkan cron:

```bash
sudo crontab -u www-data -e
# tambahkan:
* * * * * cd /var/www/lentera-api && php artisan schedule:run >> /dev/null 2>&1
```

---

## 11. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## 12. Checklist keamanan produksi

- ☐ `APP_DEBUG=false`, `APP_ENV=production`
- ☐ `APP_KEY` **dan** `JWT_SECRET` terisi (key:generate + jwt:secret) & tak pernah diganti sembarangan
- ☐ HTTPS **wajib** (mobile menolak HTTP) — Certbot aktif
- ☐ Worker antrean (`queue:work` via Supervisor) berjalan — jika tidak, kiriman komunitas macet `pending`
- ☐ Sandi admin default **diganti**
- ☐ 2FA admin aktif (dipaksa sistem sebelum `/mod`) — **gerbang utama konsol**
- ☐ HTTPS aktif (redirect 80→443 oleh Certbot)
- ☐ `DB_PASSWORD` kuat & unik
- ☐ `GEMINI_API_KEY` diisi (moderasi AI nyata) — pantau kuota tier gratis
- ☐ SMTP nyata untuk OTP/pemulihan
- ☐ Hotline krisis diisi di `config/lentera.php` (`safe_space.hotlines`) **sebelum** komunitas dibuka (§06 penanganan khusus)
- ☐ Backup DB terjadwal (mis. `pg_dump` harian ke storage terpisah)
- ☐ Jurnal E2E tetap ciphertext — jangan pernah menambah endpoint yang mendekripsi (§A3)

---

## 13. Deploy ulang (update)

```bash
cd /var/www/lentera-api
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

---

## 14. Verifikasi cepat pasca-deploy

```bash
curl -s https://console.temanlentera.id/up -o /dev/null -w "up:       %{http_code}\n"                    # 200
curl -s https://console.temanlentera.id/api/v1/health                                                    # {"status":"ok","db":"ok"}
curl -s https://console.temanlentera.id/ -o /dev/null -w "konsol:   %{http_code}\n"                      # 200 (SPA)
curl -s https://console.temanlentera.id/api/v1/me -o /dev/null -w "api auth: %{http_code}\n"             # 401 (butuh JWT)
```

Buka `https://console.temanlentera.id` → login `admin@lentera.test` (sandi baru
Anda) → setup 2FA → konsol moderasi siap.

---

## 15. Integrasi aplikasi mobile (Flutter)

- **Base URL:** `https://console.temanlentera.id/api/v1` — **wajib HTTPS**. iOS
  (App Transport Security) & Android (cleartext diblokir default) menolak HTTP.
  Jadi Certbot (§8) bukan opsional untuk mobile.
- **Auth:** kirim `Authorization: Bearer <JWT>` di semua request kecuali grup
  `/auth/*` publik. Token didapat dari register/login/otp/oauth. Simpan aman
  (`flutter_secure_storage`).
- **CORS:** aplikasi Flutter **native** tak terpengaruh CORS (hanya browser).
  Tak perlu setting apa pun di server untuk itu.
- **E2E (harga mati):** enkripsi jurnal terjadi **di device**. Server hanya
  menerima/menyimpan `*_enc` (base64 ciphertext) — ia tak punya kunci. Flutter
  yang mengimplementasikan **AES-256-GCM** + derivasi kunci **Argon2id** dari
  passphrase; kirim `text_enc`, `name_enc`, `blob`, dst. sebagai base64.
- **Kontrak data:** bentuk JSON sudah dicocokkan ke `lib/data/models.dart`
  (`Person`, `Moment`, `Post`, `Reactions`, `Circle`). Referensi & contoh:
  `docs/API_ENDPOINTS.md` + koleksi Postman di `docs/`.
- **`reminder_at`** dikirim/diterima format `HH:MM` (mis. `"21:00"`).

---

## 16. Yang BELUM otomatis (perlu dilengkapi sebelum fitur terkait dipakai)

| Fitur | Status saat ini | Yang perlu disiapkan |
|---|---|---|
| **OTP HP** (`/auth/otp/*`) | Kode **ditulis ke log** (`storage/logs`), belum dikirim | Provider SMS (Twilio/Vonage) — implementasi kirim di `OtpService` |
| **OTP email / pemulihan** | Kode ke log | Set **SMTP** (§5) — begitu MAIL_* diisi, kirim email nyata perlu ditambah di `OtpService` (kini masih `Log::info`) |
| **Push notification / pengingat malam** | Device token **disimpan** (`/notifications/token`), tapi **belum ada pengirim** | Kredensial **FCM** (Android) / **APNs** (iOS) + command terjadwal yang mengirim push pada `reminder_at` (cron `schedule:run`, §10) |
| **Hotline krisis** (`/safety/hotlines`) | "Segera hadir" (kosong) | Isi `config/lentera.php` → `hotlines` per wilayah **sebelum komunitas dibuka** |
| **Moderasi AI** | Stub heuristik bila `GEMINI_API_KEY` kosong | Isi `GEMINI_API_KEY` untuk klasifikasi Gemini nyata |
| **Refresh token JWT** | Belum ada endpoint refresh | Naikkan `JWT_TTL` (§5) atau tambah endpoint refresh bila perlu sesi panjang |

> **Catatan keamanan:** selama OTP masih lewat log, kode verifikasi tersimpan di
> `storage/logs/laravel.log`. Batasi akses file log, dan prioritaskan pasang
> SMS/SMTP nyata sebelum rilis publik.

> **Worker antrean wajib jalan** (§9): tanpa `queue:work`, kiriman komunitas
> **berhenti di status `pending`** (tak pernah tayang) karena klasifikasi Lapis 2
> tak diproses.
