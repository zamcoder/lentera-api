<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use App\Support\TokenAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_email_identity(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'email' => 'baru@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $res->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'handle']]);
        $this->assertDatabaseHas('auth_identities', [
            'provider' => 'email',
            'identifier' => 'baru@contoh.id',
        ]);
    }

    public function test_register_uses_argon2id_hash(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'argon@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertCreated();

        $user = User::where('email', 'argon@contoh.id')->first();
        $this->assertStringStartsWith('$argon2id$', $user->password_hash);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'x@contoh.id',
            'password_hash' => Hash::make('benar123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'x@contoh.id',
            'password' => 'salah000',
        ])->assertStatus(422);
    }

    public function test_admin_login_requires_two_factor_when_enabled(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $admin = User::factory()->admin()->create([
            'email' => 'admin2@contoh.id',
            'password_hash' => Hash::make('rahasia123'),
            'totp_enabled' => true,
            'totp_secret_enc' => Crypt::encryptString($secret),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin2@contoh.id',
            'password' => 'rahasia123',
        ]);

        $login->assertOk()->assertJson(['two_factor_required' => true]);
        $pending = $login->json('pending_token');

        // Token pending tidak boleh mengakses konsol.
        $code = $totp->code($secret);
        $verify = $this->withToken($pending)->postJson('/api/auth/2fa/verify', ['code' => $code]);
        $verify->assertOk()->assertJsonStructure(['token']);
    }

    public function test_otp_login_creates_account(): void
    {
        $req = $this->postJson('/api/auth/otp/request', ['phone' => '+6281200001111']);
        $req->assertOk();
        $code = $req->json('dev_code');

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '+6281200001111',
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('auth_identities', [
            'provider' => 'phone',
            'identifier' => '+6281200001111',
        ]);
    }
}
