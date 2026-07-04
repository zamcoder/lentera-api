<?php

namespace Tests\Feature;

use App\Models\Mood;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Refleksi harian E2E "Tiga baris malam" (§6) + kalender mood bulanan.
 */
class ReflectionTest extends TestCase
{
    use RefreshDatabase;

    private function b64(string $s): string
    {
        return base64_encode($s);
    }

    public function test_upsert_and_get_reflection(): void
    {
        $user = User::factory()->create();
        $date = '2026-07-04';

        $this->actingAsJwt($user)->putJson("/api/v1/reflections/{$date}", [
            'grateful_enc' => $this->b64('CIPHER-g'), 'grateful_nonce' => $this->b64('NONCE12bytes'),
            'drained_enc' => $this->b64('CIPHER-d'), 'drained_nonce' => $this->b64('NONCE12bytes'),
        ])->assertOk()
            ->assertJsonPath('date', $date)
            ->assertJsonPath('grateful_enc', $this->b64('CIPHER-g'))
            ->assertJsonPath('tomorrow_enc', null);

        $this->actingAsJwt($user)->getJson("/api/v1/reflections/{$date}")
            ->assertOk()
            ->assertJsonPath('grateful_enc', $this->b64('CIPHER-g'))
            ->assertJsonPath('drained_enc', $this->b64('CIPHER-d'));
    }

    public function test_partial_update_keeps_untouched_fields(): void
    {
        $user = User::factory()->create();
        $date = '2026-07-04';

        $this->actingAsJwt($user)->putJson("/api/v1/reflections/{$date}", [
            'grateful_enc' => $this->b64('g1'), 'grateful_nonce' => $this->b64('n1'),
        ])->assertOk();

        // Kirim hanya tomorrow → grateful lama tetap ada.
        $this->actingAsJwt($user)->putJson("/api/v1/reflections/{$date}", [
            'tomorrow_enc' => $this->b64('t1'), 'tomorrow_nonce' => $this->b64('n2'),
        ])->assertOk()
            ->assertJsonPath('grateful_enc', $this->b64('g1'))
            ->assertJsonPath('tomorrow_enc', $this->b64('t1'));

        $this->assertDatabaseCount('reflections', 1);
    }

    public function test_get_missing_returns_null_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAsJwt($user)->getJson('/api/v1/reflections/2026-01-01')
            ->assertOk()
            ->assertJsonPath('date', '2026-01-01')
            ->assertJsonPath('grateful_enc', null);
    }

    public function test_index_filters_by_range(): void
    {
        $user = User::factory()->create();
        foreach (['2026-07-01', '2026-07-04', '2026-08-01'] as $d) {
            $this->actingAsJwt($user)->putJson("/api/v1/reflections/{$d}", ['grateful_enc' => $this->b64('x'), 'grateful_nonce' => $this->b64('n')]);
        }

        $this->actingAsJwt($user)->getJson('/api/v1/reflections?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_invalid_date_returns_422(): void
    {
        $user = User::factory()->create();
        $this->actingAsJwt($user)->getJson('/api/v1/reflections/2026-7-4')->assertStatus(422);
    }

    public function test_mood_month_calendar(): void
    {
        $user = User::factory()->create();
        Mood::create(['id' => Str::uuid(), 'user_id' => $user->id, 'mood_date' => '2026-07-03', 'mood_index' => 4]);
        Mood::create(['id' => Str::uuid(), 'user_id' => $user->id, 'mood_date' => '2026-07-20', 'mood_index' => 2]);
        Mood::create(['id' => Str::uuid(), 'user_id' => $user->id, 'mood_date' => '2026-08-01', 'mood_index' => 1]);

        $this->actingAsJwt($user)->getJson('/api/v1/stats/mood?month=2026-07')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.date', '2026-07-03')
            ->assertJsonPath('data.0.mood_index', 4);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/reflections/2026-07-04')->assertStatus(401);
    }
}
