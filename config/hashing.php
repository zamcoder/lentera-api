<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Lentera memakai Argon2id (§A2) untuk hash sandi login. Argon2id tahan
    | GPU/side-channel dan direkomendasikan OWASP. PHP 8.2 mendukung native
    | (PASSWORD_ARGON2ID).
    |
    */

    'driver' => 'argon2id',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    'argon' => [
        'memory' => 65536,   // 64 MB
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

];
