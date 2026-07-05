<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Otp\WhatsAppChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lengkapi profil (§1) — tambah/ganti/hapus email & nomor WA, dgn verifikasi OTP.
 * Prinsip kritikal: kdf_salt tak boleh berubah (cadangan E2E lama tetap terbaca).
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_email_flow_sets_email_and_identity_without_touching_kdf_salt(): void
    {
        Mail::fake();
        $salt = random_bytes(16);
        $user = User::factory()->create(['email' => null, 'kdf_salt' => $salt]);

        $req = $this->actingAsJwt($user)->postJson('/api/v1/profile/email', ['email' => 'Baru@Contoh.id'])
            ->assertOk();
        $code = $req->json('dev_code');
        $this->assertNotNull($code);

        $res = $this->actingAsJwt($user)->postJson('/api/v1/profile/email/confirm', ['email' => 'Baru@Contoh.id', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('user.email', 'baru@contoh.id');            // dinormalkan lowercase
        $this->assertContains('email', $res->json('user.providers'));

        $this->assertDatabaseHas('auth_identities', ['user_id' => $user->id, 'provider' => 'email', 'identifier' => 'baru@contoh.id']);

        // kdf_salt WAJIB tetap sama.
        $this->assertSame($salt, $user->fresh()->kdf_salt);
    }

    public function test_add_phone_flow_attaches_identity(): void
    {
        // WA channel di-stub agar tak benar-benar kirim.
        $this->mock(WhatsAppChannel::class)->shouldReceive('send')->andReturnNull();
        $user = User::factory()->create();

        $req = $this->actingAsJwt($user)->postJson('/api/v1/profile/phone', ['phone' => '+6281299998888'])->assertOk();
        $code = $req->json('dev_code');

        $this->actingAsJwt($user)->postJson('/api/v1/profile/phone/confirm', ['phone' => '+6281299998888', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.phone', '+6281299998888');            // nomor mentah ikut di payload

        $this->assertDatabaseHas('auth_identities', ['user_id' => $user->id, 'provider' => 'phone', 'identifier' => '+6281299998888']);

        // /me juga menyertakan phone.
        $this->actingAsJwt($user)->getJson('/api/v1/me')->assertOk()
            ->assertJsonPath('user.phone', '+6281299998888');
    }

    public function test_wrong_code_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => null]);
        $this->actingAsJwt($user)->postJson('/api/v1/profile/email', ['email' => 'x@contoh.id'])->assertOk();

        $this->actingAsJwt($user)->postJson('/api/v1/profile/email/confirm', ['email' => 'x@contoh.id', 'code' => '000000'])
            ->assertStatus(422)->assertJsonPath('errors.code.0', 'Kode salah atau kedaluwarsa.');
    }

    public function test_email_taken_by_another_user_is_rejected(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'taken@contoh.id']);
        $user = User::factory()->create(['email' => null]);

        // Ditolak sudah di tahap request (tak buang kode ke email yg tak bisa dipasang).
        $this->actingAsJwt($user)->postJson('/api/v1/profile/email', ['email' => 'taken@contoh.id'])
            ->assertStatus(422)->assertJsonPath('errors.email.0', 'Email ini sudah dipakai akun lain.');
    }

    public function test_phone_taken_by_another_user_is_rejected(): void
    {
        $this->mock(WhatsAppChannel::class)->shouldReceive('send')->andReturnNull();
        $other = User::factory()->create();
        $other->identities()->create(['provider' => 'phone', 'identifier' => '+6281200000000', 'verified_at' => now()]);
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/profile/phone', ['phone' => '+6281200000000'])
            ->assertStatus(422)->assertJsonPath('errors.phone.0', 'Nomor ini sudah dipakai akun lain.');
    }

    public function test_cannot_remove_last_login_method(): void
    {
        $user = User::factory()->create(['email' => 'solo@contoh.id']);
        $user->identities()->create(['provider' => 'email', 'identifier' => 'solo@contoh.id', 'verified_at' => now()]);

        $this->actingAsJwt($user)->deleteJson('/api/v1/profile/identity', ['provider' => 'email'])
            ->assertStatus(422)->assertJsonPath('errors.provider.0', 'Tidak bisa dihapus — sisakan minimal satu cara masuk.');
    }

    public function test_can_remove_identity_when_another_remains(): void
    {
        $user = User::factory()->create(['email' => 'dua@contoh.id']);
        $user->identities()->create(['provider' => 'email', 'identifier' => 'dua@contoh.id', 'verified_at' => now()]);
        $user->identities()->create(['provider' => 'google', 'identifier' => 'sub-123', 'verified_at' => now()]);

        $this->actingAsJwt($user)->deleteJson('/api/v1/profile/identity', ['provider' => 'email'])
            ->assertOk()
            ->assertJsonPath('user.email', null);

        $this->assertDatabaseMissing('auth_identities', ['user_id' => $user->id, 'provider' => 'email']);
        $this->assertNull($user->fresh()->email);
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v1/profile/email', ['email' => 'a@b.id'])->assertUnauthorized();
    }
}
