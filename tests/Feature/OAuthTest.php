<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sign in with Google (§1) — ID token WAJIB diverifikasi (aud + issuer).
 */
class OAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_ids' => 'client-A.apps.googleusercontent.com,client-B.apps.googleusercontent.com']);
    }

    public function test_valid_google_token_logs_in(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'https://accounts.google.com',
                'aud' => 'client-A.apps.googleusercontent.com',
                'sub' => '1234567890',
                'email' => 'user@gmail.com',
                'email_verified' => 'true',
            ], 200),
        ]);

        $res = $this->postJson('/api/v1/auth/oauth', ['provider' => 'google', 'id_token' => 'dummy'])
            ->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'kdf_salt']]);

        // Akun Google WAJIB dapat kdf_salt (opsi A) agar E2E bisa jalan.
        $this->assertNotNull($res->json('user.kdf_salt'));

        $this->assertDatabaseHas('auth_identities', [
            'provider' => 'google', 'identifier' => '1234567890',
        ]);
    }

    public function test_rejects_wrong_audience(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'https://accounts.google.com',
                'aud' => 'penyerang.apps.googleusercontent.com',
                'sub' => '999',
                'email' => 'evil@gmail.com',
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/oauth', ['provider' => 'google', 'id_token' => 'dummy'])
            ->assertStatus(401);
        $this->assertDatabaseCount('auth_identities', 0);
    }

    public function test_rejects_invalid_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response(['error' => 'invalid_token'], 400),
        ]);

        $this->postJson('/api/v1/auth/oauth', ['provider' => 'google', 'id_token' => 'bad'])
            ->assertStatus(401);
    }

    public function test_apple_not_supported_yet(): void
    {
        $this->postJson('/api/v1/auth/oauth', ['provider' => 'apple', 'id_token' => 'x'])
            ->assertStatus(422);
    }
}
