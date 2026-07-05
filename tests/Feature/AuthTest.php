<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Auth domain (§1) — JWT di /api/v1.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_jwt_and_creates_email_identity(): void
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'email' => 'baru@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $res->assertCreated()
            ->assertJsonStructure(['token', 'token_type', 'expires_in', 'user' => ['id', 'handle', 'providers', 'sync']])
            ->assertJsonPath('token_type', 'bearer');

        $this->assertDatabaseHas('auth_identities', ['provider' => 'email', 'identifier' => 'baru@contoh.id']);
    }

    public function test_register_uses_argon2id_hash(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'email' => 'argon@contoh.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertCreated();

        $user = User::where('email', 'argon@contoh.id')->first();
        $this->assertStringStartsWith('$argon2id$', $user->password_hash);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'x@contoh.id', 'password_hash' => Hash::make('benar123')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'x@contoh.id', 'password' => 'salah000'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_login_returns_jwt_for_regular_user(): void
    {
        User::factory()->create(['email' => 'ok@contoh.id', 'password_hash' => Hash::make('rahasia123')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'ok@contoh.id', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_me_requires_auth_and_returns_profile(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAsJwt($user)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.phone', null);   // belum ada nomor terpasang
    }

    public function test_admin_login_requires_two_factor_then_issues_console_token(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $admin = User::factory()->admin()->create([
            'email' => 'admin2@contoh.id',
            'password_hash' => Hash::make('rahasia123'),
            'totp_enabled' => true,
            'totp_secret_enc' => Crypt::encryptString($secret),
        ]);

        $login = $this->postJson('/api/v1/auth/login', ['email' => 'admin2@contoh.id', 'password' => 'rahasia123']);
        $login->assertOk()->assertJson(['two_factor_required' => true]);

        $pending = $login->json('pending_token');
        $this->withToken($pending)
            ->postJson('/api/v1/auth/2fa/verify', ['code' => $totp->code($secret)])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_otp_login_creates_account(): void
    {
        $req = $this->postJson('/api/v1/auth/otp/request', ['phone' => '+6281200001111'])->assertOk();

        $res = $this->postJson('/api/v1/auth/otp/verify', ['phone' => '+6281200001111', 'code' => $req->json('dev_code')])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('auth_identities', ['provider' => 'phone', 'identifier' => '+6281200001111']);

        // kdf_salt WAJIB ada (E2E) — konsisten dgn register & Google, agar device
        // bisa menurunkan kunci vault. Tanpa ini vault E2E tak jalan.
        $this->assertNotNull($res->json('user.kdf_salt'));
        $user = User::whereHas('identities', fn ($q) => $q->where('identifier', '+6281200001111'))->first();
        $this->assertNotNull($user->kdf_salt);
    }

    public function test_health_ok(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_refresh_issues_new_token_and_blacklists_old(): void
    {
        $user = User::factory()->create();
        $old = \App\Support\JwtTokens::forApp($user);

        $new = $this->withJwt($old)->postJson('/api/v1/auth/refresh')
            ->assertOk()->assertJsonStructure(['token', 'user'])->json('token');

        // Token baru berfungsi.
        $this->withJwt($new)->getJson('/api/v1/me')->assertOk()->assertJsonPath('user.id', $user->id);

        // Token lama sudah di-blacklist.
        $this->withJwt($old)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
