<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt bersama harian (§9) — seed & rotasi (create-on-read), moderasi jawaban.
 */
class PromptDailyTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_prompt_auto_created_from_pool(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->getJson('/api/v1/prompts/today')
            ->assertOk()
            ->assertJsonPath('prompt.share_count', 0)
            ->assertJsonStructure(['prompt' => ['id', 'date', 'question', 'share_count']]);

        // Idempoten — hari yang sama → satu baris prompt.
        $this->actingAsJwt($user)->getJson('/api/v1/prompts/today')->assertOk();
        $this->assertDatabaseCount('daily_prompts', 1);
    }

    public function test_approved_answer_is_visible(): void
    {
        $user = User::factory()->create();

        // Teks aman → moderasi sinkron → approved → langsung tampil.
        $this->actingAsJwt($user)->postJson('/api/v1/prompts/today/answers', [
            'text' => 'Aku bersyukur bisa istirahat cukup hari ini.', 'anon' => true,
        ])->assertCreated()
            ->assertJsonPath('answer.text', 'Aku bersyukur bisa istirahat cukup hari ini.')
            ->assertJsonPath('moderation.status', 'approved');

        $this->actingAsJwt($user)->getJson('/api/v1/prompts/today/answers')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_rejected_answer_not_listed(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        // Kata kasar → stub toxic → rejected → tak masuk daftar.
        $this->actingAsJwt($author)->postJson('/api/v1/prompts/today/answers', [
            'text' => 'dasar bodoh semua', 'anon' => true,
        ])->assertCreated();

        $this->actingAsJwt($viewer)->getJson('/api/v1/prompts/today/answers')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/prompts/today')->assertStatus(401);
    }
}
