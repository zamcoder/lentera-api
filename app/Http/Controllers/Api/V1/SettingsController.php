<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\SyncToggleRequest;
use Illuminate\Http\JsonResponse;

/**
 * SettingsController (v1) — untuk sekarang hanya toggle sinkron awan (§2).
 * Pengaturan lain (reminder/accent/theme + device token) menyusul di Fase 3.
 */
class SettingsController extends Controller
{
    /** PUT /api/v1/settings/sync — nyalakan/matikan sinkron awan. */
    public function sync(SyncToggleRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $user->sync_on = $request->validated()['enabled'];
        $user->save();

        return response()->json(['sync_on' => (bool) $user->sync_on]);
    }
}
