<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Statistik harian di-bucket per tanggal LOKAL user (WIB), bukan UTC (§6 bug).
 */
class StatsTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_buckets_moment_near_midnight_on_local_day(): void
    {
        config(['lentera.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow('2026-07-05T05:00:00+07:00'); // Minggu pagi WIB

        $user = User::factory()->create();

        // Momen 00:30 Minggu WIB (= 17:30 Sabtu UTC). Harus masuk Minggu, bukan Sabtu.
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', [
            'type' => 'positive',
            'text_enc' => base64_encode('x'),
            'text_nonce' => base64_encode('nonce12bytes'),
            'occurred_at' => '2026-07-05T00:30:00+07:00',
        ])->assertCreated();

        $week = collect($this->actingAsJwt($user)->getJson('/api/v1/stats/summary?range=week')->assertOk()->json('week'));

        $this->assertSame(1, $week->firstWhere('date', '2026-07-05')['pos'], 'momen harus di Minggu (WIB)');
        $this->assertSame(0, $week->firstWhere('date', '2026-07-04')['pos'], 'jangan bocor ke Sabtu (UTC)');

        Carbon::setTestNow();
    }

    public function test_streak_counts_local_days(): void
    {
        config(['lentera.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow('2026-07-05T05:00:00+07:00');

        $user = User::factory()->create();
        // 00:30 Minggu WIB → streak hari ini (Minggu) harus terhitung.
        $this->actingAsJwt($user)->postJson('/api/v1/interactions', [
            'type' => 'neutral',
            'text_enc' => base64_encode('x'), 'text_nonce' => base64_encode('nonce12bytes'),
            'occurred_at' => '2026-07-05T00:30:00+07:00',
        ])->assertCreated();

        $this->assertSame(1, $this->actingAsJwt($user)->getJson('/api/v1/stats/summary?range=week')->json('streak'));

        Carbon::setTestNow();
    }
}
