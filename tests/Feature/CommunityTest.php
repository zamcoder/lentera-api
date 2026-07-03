<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Butuh banned_terms + circles + prompt hari ini.
        $this->seed(DatabaseSeeder::class);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_benign_gratitude_post_is_approved_via_pipeline(): void
    {
        // Queue sync di testing → Lapis 2 (stub) jalan inline → approved.
        $this->user();

        $res = $this->postJson('/api/posts', [
            'surface' => 'gratitude',
            'body' => 'Hari ini Ibu menelepon dan itu menghangatkan hatiku.',
        ])->assertCreated();

        // Respons langsung menandai kiriman masuk antrean Lapis 2 (async).
        $this->assertTrue($res->json('moderation.queued'));

        // Queue sync di testing → Lapis 2 (stub) meng-approve → muncul di feed.
        $feed = $this->getJson('/api/feed?surface=gratitude')->assertOk();
        $this->assertCount(1, $feed->json('data'));
    }

    public function test_banned_word_is_rejected_instantly_layer1(): void
    {
        $this->user();

        $res = $this->postJson('/api/posts', [
            'surface' => 'gratitude',
            'body' => 'Kamu bodoh sekali.',
        ])->assertCreated();

        $this->assertSame('rejected', $res->json('moderation.status'));
        // Tidak tampil di feed.
        $this->getJson('/api/feed?surface=gratitude')->assertJsonCount(0, 'data');
    }

    public function test_self_harm_is_held_and_signals_safe_space(): void
    {
        $this->user();

        $res = $this->postJson('/api/posts', [
            'surface' => 'gratitude',
            'body' => 'Aku rasanya ingin mati saja, tak sanggup lagi.',
        ])->assertCreated();

        $res->assertJsonPath('moderation.status', 'held')
            ->assertJsonPath('moderation.self_harm', true);
        $this->assertNotNull($res->json('moderation.safe_space'));
        // Tertahan dari publik.
        $this->getJson('/api/feed?surface=gratitude')->assertJsonCount(0, 'data');
    }

    public function test_strength_requires_ready_made_message(): void
    {
        $this->user();

        // Teks bebas ditolak.
        $this->postJson('/api/posts', [
            'surface' => 'strength',
            'body' => 'teks bebas sembarang',
        ])->assertStatus(422);

        // Pesan siap-pakai → instan approved tanpa antrean.
        $ready = config('lentera.strength_messages')[0];
        $res = $this->postJson('/api/posts', ['surface' => 'strength', 'body' => $ready])
            ->assertCreated();
        $res->assertJsonPath('moderation.status', 'approved')
            ->assertJsonPath('moderation.queued', false);
    }

    public function test_reaction_is_idempotent_and_hides_counts(): void
    {
        $author = $this->user();
        $post = Post::factory()->create([
            'author_id' => $author->id,
            'status' => 'approved',
            'published_at' => now(),
        ]);

        $r = $this->postJson("/api/posts/{$post->id}/react", ['kind' => 'hug'])->assertOk();
        $r->assertJson(['reacted' => true, 'kind' => 'hug']);
        // Respons tidak membocorkan jumlah reaksi (anti-cemas §03).
        $this->assertArrayNotHasKey('count', $r->json());

        // Idempoten: react lagi tak menggandakan.
        $this->postJson("/api/posts/{$post->id}/react", ['kind' => 'hug'])->assertOk();
        $this->assertDatabaseCount('reactions', 1);
    }
}
