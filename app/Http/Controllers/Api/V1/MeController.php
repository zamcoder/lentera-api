<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * MeController — GET /api/v1/me. Profil akun + metode login + status sinkron
 * (§1, untuk kartu akun Pengaturan).
 */
class MeController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth('api')->user()->load(['identities', 'vaultBackup']);

        return response()->json(['user' => new UserResource($user)]);
    }
}
