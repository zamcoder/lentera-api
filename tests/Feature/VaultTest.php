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

    public function test_stale_version_is_rejected_with_conflict(): void
    {
        $user = User::factory()->create();

        // Device A backup ke versi 3.
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32)), 'version' => 3])
            ->assertOk()->assertJsonPath('version', 3);

        // Device B (basi) coba backup versi 2 → 409 + versi terkini.
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32)), 'version' => 2])
            ->assertStatus(409)
            ->assertJsonPath('code', 'version_conflict')
            ->assertJsonPath('current_version', 3);

        // Versi sama juga ditolak (idempoten-safe: harus naik).
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32)), 'version' => 3])
            ->assertStatus(409);

        // Versi lebih baru diterima.
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32)), 'version' => 4])
            ->assertOk()->assertJsonPath('version', 4);
    }

    public function test_sync_off_blocks_writes_but_allows_reads_and_delete(): void
    {
        $user = User::factory()->create();
        // Ada cadangan lama saat sinkron masih nyala.
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32))])->assertOk();

        // Matikan sinkron.
        $this->actingAsJwt($user)->putJson('/api/v1/settings/sync', ['enabled' => false])->assertOk();

        // Tulis (PUT backup) ditolak 409 sync_disabled.
        $this->actingAsJwt($user)->putJson('/api/v1/vault/backup', ['ciphertext' => base64_encode(random_bytes(32))])
            ->assertStatus(409)->assertJsonPath('code', 'sync_disabled');

        // Baca (restore/status) tetap boleh.
        $this->actingAsJwt($user)->getJson('/api/v1/vault/restore')->assertOk();
        $this->actingAsJwt($user)->getJson('/api/v1/vault/status')->assertOk();

        // Hapus (hak lupa) tetap boleh meski sinkron mati.
        $this->actingAsJwt($user)->deleteJson('/api/v1/vault/backup')->assertOk();
    }

    public function test_sync_off_blocks_e2e_entity_writes(): void
    {
        $user = User::factory()->create();
        $this->actingAsJwt($user)->putJson('/api/v1/settings/sync', ['enabled' => false])->assertOk();

        // People / interaksi / refleksi = data E2E → tulis ditolak saat sinkron mati.
        $this->actingAsJwt($user)->postJson('/api/v1/people', [])->assertStatus(409)->assertJsonPath('code', 'sync_disabled');
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', [])->assertStatus(409)->assertJsonPath('code', 'sync_disabled');
        $this->actingAsJwt($user)->putJson('/api/v1/reflections/2026-07-05', [])->assertStatus(409)->assertJsonPath('code', 'sync_disabled');

        // Baca tetap boleh.
        $this->actingAsJwt($user)->getJson('/api/v1/people')->assertOk();
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
