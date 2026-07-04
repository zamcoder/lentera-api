<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Waitlist email landing page — POST /api/subscribe.
 */
class SubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_creates_and_is_idempotent(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'Tes@Lentera.ID'])
            ->assertStatus(201)
            ->assertExactJson(['ok' => true]);

        // Email sama (beda kapitalisasi) → tetap 201, tak menduplikat.
        $this->postJson('/api/subscribe', ['email' => 'tes@lentera.id'])
            ->assertStatus(201);

        $this->assertDatabaseCount('subscribers', 1);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'tes@lentera.id', 'source' => 'landing',
        ]);
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'bukan-email'])
            ->assertStatus(422);
        $this->assertDatabaseCount('subscribers', 0);
    }

    public function test_missing_email_returns_422(): void
    {
        $this->postJson('/api/subscribe', [])
            ->assertStatus(422);
    }
}
