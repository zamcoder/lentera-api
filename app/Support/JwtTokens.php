<?php

namespace App\Support;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * JwtTokens — penerbitan JWT (tymon/jwt-auth) untuk API mobile & konsol.
 *
 * Gerbang konsol admin (§A6) memakai klaim `mod` yang HANYA diberikan setelah
 * admin + 2FA terverifikasi. Token "pending 2FA" tak memiliki klaim itu.
 */
final class JwtTokens
{
    /** Klaim scope moderasi pada token JWT. */
    public const SCOPE_MOD = 'mod';

    /** Token aplikasi biasa (mobile / user komunitas). */
    public static function forApp(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /** Token konsol penuh (admin ber-2FA) — berisi klaim `mod`. */
    public static function forConsole(User $user): string
    {
        return JWTAuth::customClaims([self::SCOPE_MOD => true])->fromUser($user);
    }

    /** Token sementara: hanya untuk memverifikasi 2FA (tanpa `mod`). */
    public static function pending(User $user): string
    {
        return JWTAuth::customClaims(['twofa' => 'pending'])->fromUser($user);
    }

    /** TTL token dalam detik (untuk field expires_in respons). */
    public static function ttlSeconds(): int
    {
        return (int) (auth('api')->factory()->getTTL() * 60);
    }
}
