<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureSyncEnabled — bila user mematikan sinkron awan (`sync_on = false`),
 * server BENAR-BENAR berhenti menerima tulisan sync E2E (bukan sekadar flag).
 *
 * Hanya memblokir verb tulis-baru (POST/PUT/PATCH). Baca (GET) tetap boleh
 * agar device masih bisa restore data lama, dan DELETE tetap boleh ("hak lupa"
 * — user boleh menghapus dari server kapan pun). Default `sync_on` = true, jadi
 * gerbang ini hanya berdampak pada user yang sengaja mematikan sinkron.
 */
class EnsureSyncEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            $user = auth('api')->user();

            if ($user && ! $user->sync_on) {
                return response()->json([
                    'message' => 'Sinkron awan sedang nonaktif. Nyalakan di Pengaturan untuk menyimpan ke server.',
                    'code' => 'sync_disabled',
                    'sync_on' => false,
                ], 409);
            }
        }

        return $next($request);
    }
}
