<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VaultBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_backup_stores_ciphertext_verbatim(): void
    {
        $user = $this->actingUser();
        // Anggap ini ciphertext AES-256-GCM dari device (byte biner acak).
        $ciphertext = random_bytes(512);
        $b64 = base64_encode($ciphertext);

        $this->putJson('/api/vault/backup', ['blob' => $b64])
            ->assertOk()
            ->assertJsonPath('backup.size_bytes', 512);

        // Server menyimpan byte identik, tak menyentuh isi.
        $stored = VaultBackup::where('user_id', $user->id)->first();
        $this->assertSame($ciphertext, $stored->blob);
    }

    public function test_restore_round_trips_ciphertext(): void
    {
        $this->actingUser();
        $ciphertext = random_bytes(256);
        $b64 = base64_encode($ciphertext);

        $this->putJson('/api/vault/backup', ['blob' => $b64])->assertOk();

        $res = $this->getJson('/api/vault/restore')->assertOk();
        $this->assertSame($b64, $res->json('blob'));
        // Byte yang dikembalikan identik dengan yang dikirim device.
        $this->assertSame($ciphertext, base64_decode($res->json('blob')));
    }

    public function test_escrow_requires_key(): void
    {
        $this->actingUser();

        $this->putJson('/api/vault/backup', [
            'blob' => base64_encode('x'),
            'escrow_enabled' => true,
        ])->assertStatus(422);
    }

    public function test_no_response_field_exposes_plaintext(): void
    {
        $this->actingUser();
        // Ciphertext yang, jika didekripsi, akan mengandung kata ini — server
        // tak punya kunci, jadi kata ini TIDAK boleh muncul di respons mana pun.
        $ciphertext = random_bytes(64);

        $this->putJson('/api/vault/backup', ['blob' => base64_encode($ciphertext)])->assertOk();
        $restore = $this->getJson('/api/vault/restore')->assertOk();

        // Respons hanya mengandung base64 opak + metadata; tak ada dekripsi.
        $this->assertSame(base64_encode($ciphertext), $restore->json('blob'));
    }

    public function test_users_cannot_read_each_others_vault(): void
    {
        $a = User::factory()->create();
        $this->actingAs($a, 'sanctum');
        $this->putJson('/api/vault/backup', ['blob' => base64_encode(random_bytes(32))])->assertOk();

        $b = User::factory()->create();
        $this->actingAs($b, 'sanctum');
        $this->getJson('/api/vault/restore')->assertNotFound();
    }
}
