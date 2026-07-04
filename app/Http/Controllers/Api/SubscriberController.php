<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SubscriberController — waitlist email dari landing page temanlentera.id.
 * POST /api/subscribe (publik, throttle). Idempoten & tak membocorkan apakah
 * email sudah terdaftar (selalu 201 untuk email valid).
 */
class SubscriberController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email:rfc|max:190',
        ]);

        Subscriber::firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['source' => 'landing', 'ip' => $request->ip()],
        );

        return response()->json(['ok' => true], 201);
    }

    /**
     * GET /api/v1/mod/subscribers — daftar waitlist (konsol admin, di balik
     * middleware moderator). Dukung pencarian `?q=`.
     */
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Subscriber::query()
            ->when($q !== '', fn ($query) => $query->where('email', 'ilike', "%{$q}%"))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get(['id', 'email', 'source', 'ip', 'created_at']);

        return response()->json([
            'data' => $rows,
            'total' => Subscriber::count(),
        ]);
    }
}
