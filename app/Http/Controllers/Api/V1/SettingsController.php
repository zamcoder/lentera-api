<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ReminderRequest;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Http\Requests\Vault\SyncToggleRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * SettingsController (v1, §12) — pengaturan pengguna: sinkron (§2), pengingat
 * lembut malam (opt-in), aksen & tema.
 */
class SettingsController extends Controller
{
    /** GET /api/v1/settings — pengaturan saat ini. */
    public function index(): JsonResponse
    {
        return response()->json(['settings' => $this->payload(auth('api')->user())]);
    }

    /** PUT /api/v1/settings — perbarui sebagian pengaturan. */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $user->fill($request->validated())->save();

        return response()->json(['settings' => $this->payload($user)]);
    }

    /** PUT /api/v1/settings/sync — nyalakan/matikan sinkron awan (§2). */
    public function sync(SyncToggleRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $user->sync_on = $request->validated()['enabled'];
        $user->save();

        return response()->json(['sync_on' => (bool) $user->sync_on]);
    }

    /**
     * PUT /api/v1/settings/reminder — jadwal pengingat malam (opt-in, satu
     * notifikasi lock-screen ±21:00). Default 21:00 bila diaktifkan tanpa jam.
     */
    public function reminder(ReminderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth('api')->user();

        $user->reminder_on = $data['enabled'];
        if (array_key_exists('at', $data) && $data['at']) {
            $user->reminder_at = $data['at'];
        } elseif ($data['enabled'] && ! $user->reminder_at) {
            $user->reminder_at = '21:00';
        }
        $user->save();

        return response()->json([
            'reminder_on' => (bool) $user->reminder_on,
            'reminder_at' => $this->hhmm($user->reminder_at),
        ]);
    }

    private function payload(User $user): array
    {
        return [
            'sync_on' => (bool) $user->sync_on,
            'reminder_on' => (bool) $user->reminder_on,
            'reminder_at' => $this->hhmm($user->reminder_at),
            'accent' => $user->accent,
            'theme' => $user->theme,
        ];
    }

    private function hhmm(?string $t): ?string
    {
        return $t ? substr($t, 0, 5) : null;   // "21:00:00" → "21:00"
    }
}
