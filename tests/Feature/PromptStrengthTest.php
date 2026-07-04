<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt bersama & Kirim kekuatan §9 (JWT, /api/v1).
 */
class PromptStrengthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // banned_terms + prompt hari ini
    }

    public function test_prompt_today_returns_question_and_share_count(): void
    {
        $res = $this->actingAsJwt(User::factory()->create())->getJson('/api/v1/prompts/today')->assertOk();
        $res->assertJsonStructure(['prompt' => ['id', 'date', 'question', 'share_count']]);
        $this->assertSame(0, $res->json('prompt.share_count'));
    }

    public function test_benign_answer_approved_and_counted(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/prompts/today/answers', ['text' => 'Kopi hangat pagi ini.'])
            ->assertCreated()->assertJsonPath('moderation.status', 'approved');

        $this->actingAsJwt($user)->getJson('/api/v1/prompts/today/answers')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAsJwt($user)->getJson('/api/v1/prompts/today')->assertJsonPath('prompt.share_count', 1);
    }

    public function test_self_harm_answer_held_with_safe_space(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAsJwt($user)->postJson('/api/v1/prompts/today/answers', ['text' => 'Aku ingin mati saja.'])
            ->assertCreated();

        $res->assertJsonPath('moderation.status', 'held')->assertJsonPath('moderation.self_harm', true);
        $this->assertNotNull($res->json('moderation.safe_space'));
        // Tak tampil di daftar publik.
        $this->actingAsJwt($user)->getJson('/api/v1/prompts/today/answers')->assertJsonCount(0, 'data');
    }

    public function test_banned_word_answer_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAsJwt($user)->postJson('/api/v1/prompts/today/answers', ['text' => 'dasar bodoh'])
            ->assertCreated()->assertJsonPath('moderation.status', 'rejected');
    }

    public function test_strength_queue_and_send_instant(): void
    {
        $struggler = User::factory()->create();
        $helper = User::factory()->create();
        $struggle = Post::factory()->approved()->create([
            'author_id' => $struggler->id, 'surface' => 'strength', 'strength' => true,
            'body' => 'Lagi berat banget rasanya.',
        ]);

        // Struggle muncul di antrean helper (bukan miliknya).
        $this->actingAsJwt($helper)->getJson('/api/v1/strength/queue')->assertOk()->assertJsonCount(1, 'data');
        // Tak muncul di antrean si struggler sendiri.
        $this->actingAsJwt($struggler)->getJson('/api/v1/strength/queue')->assertJsonCount(0, 'data');

        $msg = config('lentera.strength_messages')[0];
        $this->actingAsJwt($helper)->postJson("/api/v1/strength/{$struggle->id}/send", ['message' => $msg])
            ->assertCreated()->assertJsonPath('sent', true);
        $this->assertDatabaseHas('strength_sends', ['sender_id' => $helper->id, 'post_id' => $struggle->id]);

        // Sesudah kirim, hilang dari antrean helper (idempoten).
        $this->actingAsJwt($helper)->getJson('/api/v1/strength/queue')->assertJsonCount(0, 'data');
    }

    public function test_strength_rejects_free_text_message(): void
    {
        $struggler = User::factory()->create();
        $helper = User::factory()->create();
        $struggle = Post::factory()->approved()->create([
            'author_id' => $struggler->id, 'surface' => 'strength', 'strength' => true,
        ]);

        $this->actingAsJwt($helper)->postJson("/api/v1/strength/{$struggle->id}/send", ['message' => 'teks bebas'])
            ->assertStatus(422);
    }

    public function test_strength_feed_excludes_struggles_from_wall(): void
    {
        $user = User::factory()->create();
        Post::factory()->approved()->create(['surface' => 'strength', 'strength' => true]);
        Post::factory()->approved()->create(['surface' => 'gratitude']);

        // Feed utama hanya berisi kiriman non-strength.
        $this->actingAsJwt($user)->getJson('/api/v1/community/feed')->assertOk()->assertJsonCount(1, 'data');
    }
}
