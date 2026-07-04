<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VaultBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vault sync E2E (§2) — JWT di /api/v1. Server hanya menyimpan ciphertext.
 */
class VaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_stores_ciphertext_verbatim(): void
    {
        $user = User::factory()->create();
        $ciphertext = random_bytes(512);           // anggap AES-256-GCM dari device

        $this->actingAsJwt($user)
            ->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode($ciphertext), 'version' => 1])
            ->assertOk()
            ->assertJsonPath('size_bytes', 512)
            ->assertJsonPath('version', 1);

        // Server menyimpan byte identik, tak menyentuh isi.
        $this->assertSame($ciphertext, VaultBackup::where('user_id', $user->id)->first()->ciphertext);
    }

    public function test_restore_round_trips_ciphertext(): void
    {
        $user = User::factory()->create();
        $ciphertext = random_bytes(256);
        $b64 = base64_encode($ciphertext);

        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => $b64])->assertOk();

        $res = $this->actingAsJwt($user)->getJson('/api/v1/vault/restore')->assertOk();
        $this->assertSame($b64, $res->json('ciphertext'));
        $this->assertSame($ciphertext, base64_decode($res->json('ciphertext')));
    }

    public function test_status_reports_sync_state(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->getJson('/api/v1/vault/status')
            ->assertOk()
            ->assertJsonPath('has_backup', false)
            ->assertJsonPath('sync_on', true);

        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32))])->assertOk();

        $this->actingAsJwt($user)->getJson('/api/v1/vault/status')
            ->assertOk()
            ->assertJsonPath('has_backup', true)
            ->assertJsonPath('synced', true)
            ->assertJsonPath('version', 1);
    }

    public function test_version_bumps_on_each_backup(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(16))])
            ->assertJsonPath('version', 1);
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(16))])
            ->assertJsonPath('version', 2);
    }

    public function test_sync_toggle(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->putJson('/api/v1/settings/sync', ['enabled' => false])
            ->assertOk()->assertJsonPath('sync_on', false);

        $this->actingAsJwt($user)->getJson('/api/v1/vault/status')->assertJsonPath('sync_on', false);
    }

    public function test_users_cannot_read_each_others_vault(): void
    {
        $a = User::factory()->create();
        $this->actingAsJwt($a)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32))])->assertOk();

        $b = User::factory()->create();
        $this->actingAsJwt($b)->getJson('/api/v1/vault/restore')->assertNotFound();
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/vault/status')->assertUnauthorized();
    }
}
