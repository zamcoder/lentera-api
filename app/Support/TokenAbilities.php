<?php

namespace App\Support;

/**
 * Abilities token Sanctum. Gerbang admin (§A6) mengandalkan 'mod' yang HANYA
 * diberikan setelah admin + 2FA terverifikasi.
 */
final class TokenAbilities
{
    /** Akses aplikasi biasa (komunitas, vault). */
    public const APP = 'app';

    /** Akses konsol moderasi /mod/* — hanya admin ber-2FA. */
    public const MOD = 'mod';

    /** Token sementara: hanya boleh memverifikasi 2FA. */
    public const TWO_FA_PENDING = '2fa:pending';
}
