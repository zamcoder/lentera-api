<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceJson — pastikan semua permintaan /api/* diperlakukan sebagai JSON,
 * sehingga request tak terautentikasi menghasilkan 401 JSON (bukan redirect
 * ke route 'login' yang tak ada → 500), apa pun header Accept klien.
 */
class ForceJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
