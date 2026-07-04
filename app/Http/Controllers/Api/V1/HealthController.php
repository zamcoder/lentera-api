<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * HealthController — GET /api/v1/health (kickoff). Invokable (bukan closure)
 * agar `php artisan route:cache` berhasil di produksi.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $db = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $db = 'down';
        }

        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'db' => $db,
            'time' => now()->toIso8601String(),
        ]);
    }
}
