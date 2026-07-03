<?php

/*
| CORS untuk klien Flutter (mobile) & konsol React. Autentikasi memakai token
| Bearer (bukan cookie stateful), jadi supports_credentials=false dan origin
| boleh diatur lewat env CORS_ALLOWED_ORIGINS (dipisah koma).
*/

$origins = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],
    'allowed_methods' => ['*'],
    // Default: izinkan semua origin (token-based). Persempit via env di produksi.
    'allowed_origins' => $origins ?: ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
