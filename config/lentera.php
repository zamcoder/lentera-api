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
        'gemini_model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'gemini_endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),

        // Ambang skor AI (0..1) untuk menahan/menolak.
        'hold_threshold' => 0.5,
        'reject_threshold' => 0.85,
    ],

    /*
    | "Kirim kekuatan" (§03) — pesan siap-pakai, TANPA teks bebas, instan tanpa
    | antrean. Karena daftar ini sudah tervetting, kiriman yang memakainya
    | langsung disetujui (tetap "pra-tayang" secara desain).
    */
    'strength_messages' => [
        'Kamu tidak sendirian. Pelan-pelan saja.',
        'Hari ini berat, tapi kamu sudah bertahan sejauh ini. Itu luar biasa.',
        'Aku mengirim kekuatan untukmu hari ini.',
        'Napas dulu. Kamu cukup, apa adanya.',
        'Semoga ada satu hal kecil yang menghangatkanmu hari ini.',
        'Kamu layak diperlakukan dengan lembut — termasuk oleh dirimu sendiri.',
    ],

    /*
    | Rate limit komunitas (§03 pagar pengaman).
    */
    'rate' => [
        'posts_per_minute' => env('COMMUNITY_POST_RATE', 6),
        'reactions_per_minute' => 30,
    ],

    /*
    | Isyarat menyakiti diri (§06 penanganan khusus). Bila terdeteksi, kiriman
    | DITAHAN (held) + ditandai self_harm + sinyal ke klien untuk menawarkan
    | Ruang Tenang — BUKAN blokir dingin.
    */
    'self_harm_signals' => [
        'ingin mati', 'akhiri hidup', 'akhiri semua', 'mengakhiri hidup',
        'bunuh diri', 'menyakiti diri', 'melukai diri', 'tak sanggup lagi',
        'tidak sanggup lagi', 'menyerah pada hidup', 'lebih baik mati',
        'tak ada gunanya hidup', 'hilang saja',
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
