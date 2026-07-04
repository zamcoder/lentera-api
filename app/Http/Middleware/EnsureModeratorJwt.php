<?php

namespace App\Http\Middleware;

use App\Support\JwtTokens;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModeratorJwt — gerbang konsol (§A6) untuk auth JWT. Berlapis:
 * pengguna admin + 2FA aktif + token memiliki klaim `mod` (hanya terbit
 * setelah verifikasi 2FA). Dipakai grup /api/v1/mod/*.
 */
class EnsureModeratorJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        abort_unless($user && $user->isAdmin(), 403, 'Butuh peran admin.');
        abort_unless($user->totp_enabled, 403, '2FA wajib aktif untuk konsol.');

        $mod = auth('api')->payload()->get(JwtTokens::SCOPE_MOD);
        abort_unless($mod === true, 403, 'Token belum terverifikasi 2FA.');

        return $next($request);
    }
}
