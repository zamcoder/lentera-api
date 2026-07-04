<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengaturan/Notifikasi §12 + Keselamatan §11 (JWT, /api/v1).
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_defaults(): void
    {
        $res = $this->actingAsJwt(User::factory()->create())->getJson('/api/v1/settings')->assertOk();
        $res->assertJsonPath('settings.sync_on', true)
            ->assertJsonPath('settings.reminder_on', false)
            ->assertJsonPath('settings.accent', 'sage')
            ->assertJsonPath('settings.theme', 'system');
    }

    public function test_update_settings_partial(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->putJson('/api/v1/settings', ['theme' => 'dark', 'accent' => 'clay'])
            ->assertOk()
            ->assertJsonPath('settings.theme', 'dark')
            ->assertJsonPath('settings.accent', 'clay');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'theme' => 'dark', 'accent' => 'clay']);
    }

    public function test_reminder_enable_defaults_to_2100(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->putJson('/api/v1/settings/reminder', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('reminder_on', true)
            ->assertJsonPath('reminder_at', '21:00');

        $this->actingAsJwt($user)->putJson('/api/v1/settings/reminder', ['enabled' => true, 'at' => '22:30'])
            ->assertJsonPath('reminder_at', '22:30');
    }

    public function test_register_device_token(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/notifications/token', ['token' => 'fcm-abc-123', 'platform' => 'fcm'])
            ->assertCreated()->assertJsonStructure(['device_id']);

        $this->assertDatabaseHas('devices', ['token' => 'fcm-abc-123', 'user_id' => $user->id, 'platform' => 'fcm']);
    }

    public function test_device_token_moves_to_new_user(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAsJwt($a)->postJson('/api/v1/notifications/token', ['token' => 'same-token', 'platform' => 'apns']);
        $this->actingAsJwt($b)->postJson('/api/v1/notifications/token', ['token' => 'same-token', 'platform' => 'apns']);

        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseHas('devices', ['token' => 'same-token', 'user_id' => $b->id]);
    }

    public function test_hotlines_segera_hadir(): void
    {
        $res = $this->actingAsJwt(User::factory()->create())->getJson('/api/v1/safety/hotlines?region=ID')->assertOk();
        $res->assertJsonPath('region', 'ID')
            ->assertJsonPath('available', false)
            ->assertJsonPath('message', 'Segera hadir')
            ->assertJsonCount(0, 'hotlines');
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/settings')->assertUnauthorized();
        $this->getJson('/api/v1/safety/hotlines')->assertUnauthorized();
    }
}
