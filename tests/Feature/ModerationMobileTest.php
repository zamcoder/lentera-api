<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moderasi & Laporan mobile §10 (JWT, /api/v1).
 */
class ModerationMobileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // banned_terms (7 kata)
    }

    public function test_report_created_with_app_reason_label(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->approved()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/reports', [
            'post_id' => $post->id,
            'reason' => 'Spam / promosi',
        ])->assertCreated()->assertJsonStructure(['report_id']);

        $this->assertDatabaseHas('reports', ['post_id' => $post->id, 'reason' => 'Spam / promosi']);
    }

    public function test_report_rejects_unknown_reason(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->approved()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/reports', ['post_id' => $post->id, 'reason' => 'harassment'])
            ->assertStatus(422);
    }

    public function test_self_harm_report_holds_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->approved()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/reports', [
            'post_id' => $post->id,
            'reason' => 'Isyarat menyakiti diri',
        ])->assertCreated();

        $post->refresh();
        $this->assertSame('held', $post->status);
        $this->assertTrue((bool) $post->self_harm);
    }

    public function test_banned_terms_sync_matches_seed(): void
    {
        $res = $this->actingAsJwt(User::factory()->create())->getJson('/api/v1/moderation/banned-terms')->assertOk();

        $terms = $res->json('banned_terms');
        foreach (['bodoh', 'tolol', 'goblok', 'sialan', 'brengsek', 'bangsat', 'benci kamu'] as $w) {
            $this->assertContains($w, $terms);
        }
        // Isyarat krisis identik crisisSignals.
        $this->assertContains('bunuh diri', $res->json('crisis_signals'));
        $this->assertContains('hampa sekali', $res->json('crisis_signals'));
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/moderation/banned-terms')->assertUnauthorized();
    }
}
