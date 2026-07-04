<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mood, Statistik & Rekap (§6) — agregat non-sensitif di /api/v1.
 */
class StatsTest extends TestCase
{
    use RefreshDatabase;

    private function logMoment(User $user, string $type, ?string $personId = null): void
    {
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', [
            'type' => $type,
            'text_enc' => base64_encode(random_bytes(16)),
            'text_nonce' => base64_encode(random_bytes(12)),
            'person_ids' => $personId ? [$personId] : [],
        ])->assertCreated();
    }

    public function test_mood_upsert_one_per_day(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/mood', ['mood_index' => 3])
            ->assertOk()->assertJsonPath('mood_index', 3);
        $this->actingAsJwt($user)->postJson('/api/v1/mood', ['mood_index' => 1])
            ->assertOk()->assertJsonPath('mood_index', 1);

        $this->assertDatabaseCount('moods', 1);
    }

    public function test_summary_aggregates_distribution_and_recap(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        $this->logMoment($user, 'positive', $person->id);
        $this->logMoment($user, 'positive', $person->id);
        $this->logMoment($user, 'negative', $person->id);
        $this->logMoment($user, 'neutral');

        $res = $this->actingAsJwt($user)->getJson('/api/v1/stats/summary?range=week')->assertOk();

        $res->assertJsonPath('distribution.positive', 2)
            ->assertJsonPath('distribution.negative', 1)
            ->assertJsonPath('distribution.neutral', 1)
            ->assertJsonPath('recap.person_id', $person->id)
            ->assertJsonPath('recap.pos_count', 2);

        $this->assertCount(7, $res->json('week'));
        $this->assertGreaterThanOrEqual(1, $res->json('streak'));

        // Hari terakhir seri = hari ini, dengan pos=2 neg=1.
        $today = $res->json('week.6');
        $this->assertSame(2, $today['pos']);
        $this->assertSame(1, $today['neg']);
    }

    public function test_today_reports_mood_counts_and_social_energy(): void
    {
        $user = User::factory()->create();
        $filler = Person::factory()->create(['user_id' => $user->id]);
        $drainer = Person::factory()->create(['user_id' => $user->id]);

        $this->actingAsJwt($user)->postJson('/api/v1/mood', ['mood_index' => 4]);
        $this->logMoment($user, 'positive', $filler->id);
        $this->logMoment($user, 'negative', $drainer->id);

        $res = $this->actingAsJwt($user)->getJson('/api/v1/today')->assertOk();

        $res->assertJsonPath('mood_index', 4)
            ->assertJsonPath('counts.positive', 1)
            ->assertJsonPath('counts.negative', 1);
        $this->assertContains($filler->id, $res->json('social_energy.filled'));
        $this->assertContains($drainer->id, $res->json('social_energy.drained'));
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/stats/summary')->assertUnauthorized();
        $this->getJson('/api/v1/today')->assertUnauthorized();
    }
}
