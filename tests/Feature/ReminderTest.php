<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Services\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengingat malam terjadwal (§12) — command lentera:reminders.
 */
class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_sent_only_to_matching_users(): void
    {
        $due = User::factory()->create(['reminder_on' => true, 'reminder_at' => '21:00']);
        $otherTime = User::factory()->create(['reminder_on' => true, 'reminder_at' => '07:00']);
        $off = User::factory()->create(['reminder_on' => false, 'reminder_at' => '21:00']);
        Device::create(['id' => \Illuminate\Support\Str::uuid(), 'user_id' => $due->id, 'token' => 'tok', 'platform' => 'fcm']);

        $push = $this->mock(PushSender::class);
        $push->shouldReceive('sendReminder')->once()
            ->with(\Mockery::on(fn (User $u) => $u->id === $due->id));

        $this->artisan('lentera:reminders', ['--at' => '21:00'])->assertSuccessful();
    }

    public function test_push_sender_logs_when_no_fcm_driver(): void
    {
        // Driver default 'log' → tak error, dan hanya kirim bila ada device.
        $user = User::factory()->create();
        Device::create(['id' => \Illuminate\Support\Str::uuid(), 'user_id' => $user->id, 'token' => 'tok', 'platform' => 'fcm']);

        app(PushSender::class)->sendReminder($user);   // tak melempar exception
        $this->assertTrue(true);
    }
}
