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

    /** GET /api/subscribers — daftar waitlist (admin saja). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return response()->json(
            Subscriber::orderByDesc('created_at')->paginate(50)
        );
    }
}
