<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Media suara/foto terenkripsi (§5) — E2E di /api/v1.
 */
class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_and_fetch_round_trips_ciphertext(): void
    {
        $user = User::factory()->create();
        $blob = random_bytes(300);

        $up = $this->actingAsJwt($user)->postJson('/api/v1/media', [
            'kind' => 'photo',
            'blob' => base64_encode($blob),
            'mime' => 'image/jpeg',
        ])->assertCreated()->assertJsonPath('size_bytes', 300);

        $res = $this->actingAsJwt($user)->getJson("/api/v1/media/{$up->json('media_id')}")->assertOk();
        $this->assertSame(base64_encode($blob), $res->json('blob'));
        $this->assertSame($blob, base64_decode($res->json('blob')));
    }

    public function test_media_attaches_to_interaction(): void
    {
        $user = User::factory()->create();
        $mediaId = $this->actingAsJwt($user)->postJson('/api/v1/media', [
            'kind' => 'audio', 'blob' => base64_encode(random_bytes(50)),
        ])->json('media_id');

        $res = $this->actingAsJwt($user)->postJson('/api/v1/interactions', [
            'type' => 'neutral',
            'text_enc' => base64_encode(random_bytes(16)),
            'text_nonce' => base64_encode(random_bytes(12)),
            'media_ids' => [$mediaId],
        ])->assertCreated();

        $this->assertContains($mediaId, $res->json('data.media_ids'));
        $this->assertDatabaseHas('media', ['id' => $mediaId, 'interaction_id' => $res->json('data.id')]);
    }

    public function test_cannot_fetch_others_media(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $mediaId = $this->actingAsJwt($a)->postJson('/api/v1/media', [
            'kind' => 'photo', 'blob' => base64_encode(random_bytes(20)),
        ])->json('media_id');

        $this->actingAsJwt($b)->getJson("/api/v1/media/{$mediaId}")->assertNotFound();
    }
}
