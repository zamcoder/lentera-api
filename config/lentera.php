<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moderasi (§06)
    |--------------------------------------------------------------------------
    */
    'moderation' => [
        // Gemini (§11: cloud, tier gratis). Kosong = pakai stub heuristik lokal.
        'gemini_key' => env('GEMINI_API_KEY', ''),
        'gemini_model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'gemini_endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),

        // Ambang skor AI (0..1) untuk menahan/menolak.
        'hold_threshold' => 0.5,
        'reject_threshold' => 0.85,
    ],

    /*
    | "Kirim kekuatan" (§9) — pesan siap-pakai, TANPA teks bebas, instan tanpa
    | pra-tayang. IDENTIK dengan `strengthReplies` di lib/data/dummy_data.dart.
    */
    'strength_messages' => [
        'Kamu nggak sendirian.',
        'Pelan-pelan, kamu sudah cukup.',
        'Aku kirim pelukan untukmu.',
    ],

    /*
    | Rate limit komunitas (§03 pagar pengaman).
    */
    'rate' => [
        'posts_per_minute' => env('COMMUNITY_POST_RATE', 6),
        'reactions_per_minute' => 30,
    ],

    /*
    | Alasan laporan (§10) — IDENTIK `reportReasons` di lib/data/dummy_data.dart.
    | `self_harm_reason` menandai laporan krisis (penanganan khusus).
    */
    'report_reasons' => [
        'Ujaran menyakiti / kasar',
        'Spam / promosi',
        'Tidak pantas',
        'Pelecehan / perundungan',
        'Isyarat menyakiti diri',
        'Informasi salah',
    ],
    'self_harm_reason' => 'Isyarat menyakiti diri',

    /*
    | Isyarat menyakiti diri / krisis (§06/§10 penanganan khusus). Bila terdeteksi,
    | kiriman DITAHAN (held) + ditandai self_harm + sinyal ke klien untuk menawarkan
    | Ruang Tenang — BUKAN blokir dingin.
    |
    | WAJIB IDENTIK dengan `crisisSignals` di lib/data/dummy_data.dart agar deteksi
    | di app & server sama persis.
    */
    'self_harm_signals' => [
        'bunuh diri', 'mengakhiri hidup', 'akhiri hidup', 'tidak ingin hidup',
        'ingin mati', 'pengen mati', 'menyerah saja', 'tak sanggup lagi',
        'gak sanggup lagi', 'nggak sanggup lagi', 'menyakiti diri', 'lukai diri',
        'capek hidup', 'lelah hidup', 'putus asa', 'hampa sekali',
    ],

    /*
    | OTP (§1) — kanal pengiriman: EMAIL (pemulihan) & WHATSAPP (login HP).
    | SMS tidak dipakai. WA lewat gateway; provider 'log' = tulis ke log (dev/
    | belum dikonfigurasi). Provider didukung: fonnte (ID) atau cloud (Meta).
    */
    'whatsapp' => [
        'provider' => env('WA_PROVIDER', 'log'),   // log | gowa | fonnte | cloud
        'token' => env('WA_TOKEN', ''),
        'endpoint' => env('WA_ENDPOINT', ''),      // gowa: base URL (mis. http://127.0.0.1:3000)
        'phone_id' => env('WA_PHONE_ID', ''),      // untuk Meta Cloud API
        'username' => env('WA_USERNAME', ''),      // gowa: basic-auth user
        'password' => env('WA_PASSWORD', ''),      // gowa: basic-auth pass
    ],

    /*
    | Push notification (§12) — pengingat lembut malam. Driver 'log' (dev/belum
    | dikonfigurasi) atau 'fcm' (Firebase Cloud Messaging HTTP v1). FCM_CREDENTIALS
    | = path ke file service-account JSON dari Firebase Console.
    | iOS memakai token FCM juga (aplikasi pakai Firebase Messaging).
    */
    'push' => [
        'driver' => env('PUSH_DRIVER', 'log'),     // log | fcm
        'fcm_credentials' => env('FCM_CREDENTIALS', ''),
    ],

    // Teks pengingat malam (§12) — satu notifikasi lock-screen, lembut.
    'reminder' => [
        'title' => 'Selamat malam 🌙',
        'body' => 'Luangkan satu momen untuk hari ini — apa yang kamu syukuri?',
    ],

    /*
    | Hotline krisis per wilayah (§11). Kosong dulu → "Segera hadir".
    | Wajib diisi sebelum komunitas dibuka (§11 keputusan tercatat).
    */
    'hotlines' => [
        // 'ID' => [['label' => 'Into The Light ID', 'contact' => '...']],
    ],

    // Sumber bantuan yang ditawarkan saat penanganan khusus (§10: hotline menyusul).
    'safe_space' => [
        'title' => 'Kamu tidak sendirian',
        'message' => 'Bila kamu sedang berat, coba tarik napas sejenak di Ruang Tenang. '
            .'Kalau butuh bicara dengan seseorang, bantuan tersedia.',
        'hotlines' => [
            // Diisi per-wilayah saat rilis (§11).
            // ['label' => 'Into The Light ID', 'contact' => '...'],
        ],
    ],

];
