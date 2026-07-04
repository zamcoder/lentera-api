<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\JwtTokens;
use Illuminate\Http\JsonResponse;

/**
 * AuthResponses — bentuk respons auth JWT yang konsisten (token + profil).
 */
trait AuthResponses
{
    protected function tokenResponse(User $user, string $token, int $status = 200): JsonResponse
    {
        $user->load(['identities', 'vaultBackup']);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JwtTokens::ttlSeconds(),
            'user' => new UserResource($user),
        ], $status);
    }
}
