<?php

namespace Tests\Feature;

use App\Models\Interaction;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Momen/Interaksi (§4) — E2E di /api/v1.
 */
class InteractionTest extends TestCase
{
    use RefreshDatabase;

    private function moment(array $over = []): array
    {
        return array_merge([
            'type' => 'positive',
            'text_enc' => base64_encode(random_bytes(64)),
            'text_nonce' => base64_encode(random_bytes(12)),
            'topic' => 'Keluarga',
        ], $over);
    }

    public function test_store_persists_ciphertext_and_returns_type_string(): void
    {
        $user = User::factory()->create();
        $body = $this->moment();

        $res = $this->actingAsJwt($user)->postJson('/api/v1/interactions', $body)
            ->assertCreated()
            ->assertJsonPath('data.type', 'positive')
            ->assertJsonPath('data.text_enc', $body['text_enc'])
            ->assertJsonPath('data.topic', 'Keluarga');

        $stored = Interaction::find($res->json('data.id'));
        $this->assertSame(base64_decode($body['text_enc']), $stored->text_enc);
        $this->assertSame(Interaction::TYPE_POSITIVE, $stored->type);
    }

    public function test_filter_by_person_and_type(): void
    {
        $user = User::factory()->create();
        $ana = Person::factory()->create(['user_id' => $user->id]);
        $budi = Person::factory()->create(['user_id' => $user->id]);

        $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'positive', 'person_ids' => [$ana->id]]));
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'negative', 'person_ids' => [$ana->id]]));
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'positive', 'person_ids' => [$budi->id]]));

        $this->actingAsJwt($user)->getJson("/api/v1/interactions?person_id={$ana->id}")->assertOk()->assertJsonCount(2, 'data');
        $this->actingAsJwt($user)->getJson('/api/v1/interactions?type=positive')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAsJwt($user)->getJson("/api/v1/interactions?person_id={$ana->id}&type=negative")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_person_counters_maintained_from_interactions(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'positive', 'person_ids' => [$person->id]]));
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'positive', 'person_ids' => [$person->id]]));
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'negative', 'person_ids' => [$person->id]]));

        $person->refresh();
        $this->assertSame(2, $person->pos_count);
        $this->assertSame(1, $person->neg_count);
        $this->assertSame(Interaction::TYPE_NEGATIVE, $person->last_type);
        $this->assertNotNull($person->last_at);
    }

    public function test_delete_recounts_person(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        $id = $this->actingAsJwt($user)->postJson('/api/v1/interactions', $this->moment(['type' => 'positive', 'person_ids' => [$person->id]]))->json('data.id');
        $this->assertSame(1, $person->fresh()->pos_count);

        $this->actingAsJwt($user)->deleteJson("/api/v1/interactions/{$id}")->assertOk();
        $this->assertSame(0, $person->fresh()->pos_count);
    }

    public function test_cannot_touch_others_interaction(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $id = $this->actingAsJwt($a)->postJson('/api/v1/interactions', $this->moment())->json('data.id');

        $this->actingAsJwt($b)->getJson('/api/v1/interactions')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAsJwt($b)->deleteJson("/api/v1/interactions/{$id}")->assertNotFound();
    }

    public function test_cannot_link_person_of_another_user(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $bPerson = Person::factory()->create(['user_id' => $b->id]);

        // A mencoba menautkan orang milik B → diabaikan (tak tertaut).
        $id = $this->actingAsJwt($a)->postJson('/api/v1/interactions', $this->moment(['person_ids' => [$bPerson->id]]))->json('data.id');
        $this->assertDatabaseMissing('interaction_people', ['interaction_id' => $id, 'person_id' => $bPerson->id]);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/interactions')->assertUnauthorized();
    }
}
