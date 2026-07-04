<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Orang / People (§3) — E2E di /api/v1. Field terenkripsi round-trip; server buta.
 */
class PeopleTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'name_enc' => base64_encode(random_bytes(48)),
            'name_nonce' => base64_encode(random_bytes(12)),
            'rel_enc' => base64_encode(random_bytes(24)),
            'rel_nonce' => base64_encode(random_bytes(12)),
            'avatar_color' => 'mint',
        ];
    }

    public function test_store_persists_ciphertext_verbatim(): void
    {
        $user = User::factory()->create();
        $body = $this->payload();

        $res = $this->actingAsJwt($user)->postJson('/api/v1/people', $body)
            ->assertCreated()
            ->assertJsonPath('data.name_enc', $body['name_enc'])
            ->assertJsonPath('data.avatar_color', 'mint')
            ->assertJsonPath('data.pos_count', 0);

        // Server menyimpan byte identik dengan yang dikirim device.
        $person = Person::find($res->json('data.id'));
        $this->assertSame(base64_decode($body['name_enc']), $person->name_enc);
        $this->assertSame($user->id, $person->user_id);
    }

    public function test_index_lists_only_own_people(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->actingAsJwt($a)->postJson('/api/v1/people', $this->payload())->assertCreated();
        $this->actingAsJwt($a)->postJson('/api/v1/people', $this->payload())->assertCreated();
        $this->actingAsJwt($b)->postJson('/api/v1/people', $this->payload())->assertCreated();

        $this->actingAsJwt($a)->getJson('/api/v1/people')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAsJwt($b)->getJson('/api/v1/people')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_update_changes_ciphertext(): void
    {
        $user = User::factory()->create();
        $id = $this->actingAsJwt($user)->postJson('/api/v1/people', $this->payload())->json('data.id');

        $newName = base64_encode(random_bytes(48));
        $this->actingAsJwt($user)->putJson("/api/v1/people/{$id}", [
            'name_enc' => $newName,
            'name_nonce' => base64_encode(random_bytes(12)),
        ])->assertOk()->assertJsonPath('data.name_enc', $newName);
    }

    public function test_cannot_touch_others_person(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $id = $this->actingAsJwt($a)->postJson('/api/v1/people', $this->payload())->json('data.id');

        $this->actingAsJwt($b)->putJson("/api/v1/people/{$id}", ['name_enc' => base64_encode('x'), 'name_nonce' => base64_encode('y')])
            ->assertNotFound();
        $this->actingAsJwt($b)->deleteJson("/api/v1/people/{$id}")->assertNotFound();
    }

    public function test_destroy_removes_person(): void
    {
        $user = User::factory()->create();
        $id = $this->actingAsJwt($user)->postJson('/api/v1/people', $this->payload())->json('data.id');

        $this->actingAsJwt($user)->deleteJson("/api/v1/people/{$id}")->assertOk();
        $this->assertDatabaseMissing('people', ['id' => $id]);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/people')->assertUnauthorized();
    }
}
