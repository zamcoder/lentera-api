<?php

namespace App\Services\Push;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PushSender — kirim notifikasi ke semua device milik user (§12). Driver 'log'
 * (dev/belum dikonfigurasi) atau 'fcm'. Token yang tak valid dibersihkan.
 */
class PushSender
{
    public function __construct(private readonly FcmClient $fcm)
    {
    }

    /** Pengingat lembut malam (§12). */
    public function sendReminder(User $user): void
    {
        $this->send(
            $user,
            (string) config('lentera.reminder.title'),
            (string) config('lentera.reminder.body'),
            ['type' => 'reminder'],
        );
    }

    public function send(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = Device::where('user_id', $user->id)->pluck('token', 'id');
        if ($tokens->isEmpty()) {
            return;
        }

        $driver = config('lentera.push.driver');
        if ($driver !== 'fcm' || ! $this->fcm->isConfigured()) {
            Log::info("Push (stub) → {$user->id}: {$title} — {$body}");

            return;
        }

        foreach ($tokens as $deviceId => $token) {
            $ok = $this->fcm->sendToToken($token, $title, $body, $data);
            if (! $ok) {
                Device::whereKey($deviceId)->delete();   // token tak valid → buang
            }
        }
    }
}
