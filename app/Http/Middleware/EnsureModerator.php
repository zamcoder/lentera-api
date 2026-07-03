<?php

namespace App\Http\Middleware;

use App\Support\TokenAbilities;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModerator — gerbang konsol (§A6). Pertahanan berlapis di atas
 * abilities:mod: memastikan pengguna admin, 2FA aktif, dan token memang
 * ber-ability 'mod' (hanya terbit setelah verifikasi 2FA).
 */
class EnsureModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isAdmin(), 403, 'Butuh peran admin.');
        abort_unless($user->totp_enabled, 403, '2FA wajib aktif untuk konsol.');

        $token = $user->currentAccessToken();
        abort_unless($token && $token->can(TokenAbilities::MOD), 403, 'Token belum terverifikasi 2FA.');

        return $next($request);
    }
}
