<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Ringkasan AI (gated consent) — /ai/summarize/person & /day.
 */
class AiSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lentera.moderation.gemini_key' => 'test-key']);
    }

    private function fakeGemini(string $text): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
            ], 200),
        ]);
    }

    public function test_summarize_person(): void
    {
        $this->fakeGemini('Sumber kehangatanmu — 3 momen syukur. Tunjukkan apresiasi kecil. 💛');
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/ai/summarize/person', [
            'name' => 'Mama', 'relation' => 'Orang Tua', 'pos_count' => 3, 'neg_count' => 0,
            'interactions' => [['type' => 'positive', 'text' => 'Dibuatkan sup', 'date' => '2026-07-05']],
        ])->assertOk()->assertJsonPath('summary', 'Sumber kehangatanmu — 3 momen syukur. Tunjukkan apresiasi kecil. 💛');
    }

    public function test_summarize_day(): void
    {
        $this->fakeGemini('Hari yang campur aduk, tapi kamu bertahan dengan tenang. 🌿');
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/ai/summarize/day', [
            'date' => '2026-07-05', 'mood_index' => 3,
            'moments' => [['type' => 'positive', 'text' => 'Ngobrol enak', 'person' => 'Mama']],
        ])->assertOk()->assertJsonPath('summary', 'Hari yang campur aduk, tapi kamu bertahan dengan tenang. 🌿');
    }

    public function test_null_summary_when_no_api_key(): void
    {
        config(['lentera.moderation.gemini_key' => '']);
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/ai/summarize/person', ['name' => 'Mama'])
            ->assertOk()->assertJsonPath('summary', null);
    }

    public function test_null_summary_when_llm_fails(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('err', 500)]);
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/ai/summarize/day', ['date' => '2026-07-05'])
            ->assertOk()->assertJsonPath('summary', null);
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v1/ai/summarize/person', ['name' => 'X'])->assertStatus(401);
    }
}
