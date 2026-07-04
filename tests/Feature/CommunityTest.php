<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Komunitas §7 — Feed & Post, reaksi, hide (JWT, /api/v1). Moderasi pending→approved.
 */
class CommunityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // banned_terms + circles + prompt
    }

    public function test_benign_post_approved_via_pipeline_appears_in_feed(): void
    {
        // Queue sync di testing → Lapis 2 (stub) meng-approve.
        $user = User::factory()->create();

        $res = $this->actingAsJwt($user)->postJson('/api/v1/community/posts', [
            'text' => 'Bersyukur untuk teh hangat sore ini.',
        ])->assertCreated();

        $this->assertTrue($res->json('moderation.queued'));

        $feed = $this->actingAsJwt($user)->getJson('/api/v1/community/feed')->assertOk();
        $this->assertCount(1, $feed->json('data'));
        $this->assertSame('approved', $feed->json('data.0.status'));
    }

    public function test_banned_word_rejected_instantly(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAsJwt($user)->postJson('/api/v1/community/posts', ['text' => 'Kamu bodoh sekali.'])
            ->assertCreated();

        $this->assertSame('rejected', $res->json('moderation.status'));
        $this->actingAsJwt($user)->getJson('/api/v1/community/feed')->assertJsonCount(0, 'data');
    }

    public function test_self_harm_held_and_signals_safe_space(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAsJwt($user)->postJson('/api/v1/community/posts', [
            'text' => 'Aku capek hidup, rasanya ingin mati saja.',
        ])->assertCreated();

        $res->assertJsonPath('moderation.status', 'held')->assertJsonPath('moderation.self_harm', true);
        $this->assertNotNull($res->json('moderation.safe_space'));
    }

    public function test_react_returns_counts_and_toggles(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->approved()->create();

        $r = $this->actingAsJwt($author)->postJson("/api/v1/community/posts/{$post->id}/react", ['kind' => 'peluk'])
            ->assertOk();
        $r->assertJsonPath('reactions.peluk', 1);
        $this->assertContains('peluk', $r->json('my_reactions'));

        // idempoten
        $this->actingAsJwt($author)->postJson("/api/v1/community/posts/{$post->id}/react", ['kind' => 'peluk'])
            ->assertJsonPath('reactions.peluk', 1);
        $this->assertDatabaseCount('reactions', 1);

        // batal
        $this->actingAsJwt($author)->deleteJson("/api/v1/community/posts/{$post->id}/react", ['kind' => 'peluk'])
            ->assertOk()->assertJsonPath('reactions.peluk', 0);
    }

    public function test_feed_returns_reaction_counts(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->approved()->create();
        User::factory()->count(2)->create()->each(function ($u) use ($post) {
            $this->actingAsJwt($u)->postJson("/api/v1/community/posts/{$post->id}/react", ['kind' => 'kekuatan']);
        });

        $feed = $this->actingAsJwt($viewer)->getJson('/api/v1/community/feed')->assertOk();
        $this->assertSame(2, $feed->json('data.0.reactions.kekuatan'));
    }

    public function test_hide_removes_from_my_feed_only(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->approved()->create();

        $this->actingAsJwt($me)->postJson("/api/v1/community/posts/{$post->id}/hide")->assertOk();

        $this->actingAsJwt($me)->getJson('/api/v1/community/feed')->assertJsonCount(0, 'data');
        $this->actingAsJwt($other)->getJson('/api/v1/community/feed')->assertJsonCount(1, 'data');
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/community/feed')->assertUnauthorized();
    }
}
