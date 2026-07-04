<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RegisterDeviceRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

/**
 * NotificationController (v1, §12) — daftar device token (FCM/APNs) untuk
 * pengingat lembut. Token unik → dipindahkan ke user saat ini bila berpindah.
 */
class NotificationController extends Controller
{
    /** POST /api/v1/notifications/token — daftar/segarkan device token. */
    public function registerToken(RegisterDeviceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $device = Device::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => auth('api')->id(), 'platform' => $data['platform']],
        );

        return response()->json(['device_id' => $device->id], 201);
    }
}
