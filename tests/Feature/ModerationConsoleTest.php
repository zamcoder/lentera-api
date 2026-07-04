<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Konsol moderasi (§A5/§A6) — JWT di /api/v1/mod.
 */
class ModerationConsoleTest extends TestCase
{
    use RefreshDatabase;

    /** Admin ber-2FA aktif (syarat gerbang konsol). */
    private function makeModerator(): User
    {
        return User::factory()->admin()->create([
            'totp_enabled' => true,
            'totp_secret_enc' => Crypt::encryptString('DUMMYSECRET234567'),
        ]);
    }

    public function test_non_admin_cannot_access_console(): void
    {
        $user = User::factory()->create();
        $this->actingAsJwt($user)->getJson('/api/v1/mod/queue')->assertForbidden();
    }

    public function test_admin_without_2fa_is_blocked(): void
    {
        // Token app (tanpa scope mod) + belum 2FA → 403.
        $admin = User::factory()->admin()->create(['totp_enabled' => false]);
        $this->actingAsJwt($admin)->getJson('/api/v1/mod/queue')->assertForbidden();
    }

    public function test_queue_lists_held_posts_and_action_approves(): void
    {
        $this->actingAsModerator($this->makeModerator());
        $post = Post::factory()->held()->create(['mod_reason' => 'AI: perlu tinjauan.']);

        $queue = $this->getJson('/api/v1/mod/queue')->assertOk();
        $this->assertCount(1, $queue->json('data'));
        $this->assertSame('AI: perlu tinjauan.', $queue->json('data.0.mod_reason'));

        $this->postJson('/api/v1/mod/action', ['post_id' => $post->id, 'action' => 'approve'])
            ->assertOk()->assertJsonPath('post.status', 'approved');

        $this->assertDatabaseHas('moderation_actions', ['post_id' => $post->id, 'action' => 'approve']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mod.approve', 'target_id' => $post->id]);
    }

    public function test_report_then_console_sees_it(): void
    {
        // Pengguna melaporkan (JWT app).
        $reporter = User::factory()->create();
        $post = Post::factory()->approved()->create();
        $this->actingAsJwt($reporter)
            ->postJson('/api/v1/reports', ['post_id' => $post->id, 'reason' => 'Pelecehan / perundungan'])
            ->assertCreated();

        // Moderator melihat & menindak.
        $this->actingAsModerator($this->makeModerator());
        $this->getJson('/api/v1/mod/reports')->assertOk()->assertJsonPath('open_count', 1);
        $this->postJson('/api/v1/mod/reports/action', ['post_id' => $post->id, 'decision' => 'hide'])
            ->assertOk()->assertJsonPath('open_count', 0);
    }

    public function test_terms_crud_and_suggest(): void
    {
        $this->actingAsModerator($this->makeModerator());

        $this->getJson('/api/v1/mod/terms')->assertOk()->assertJsonStructure(['data', 'total']);

        $create = $this->postJson('/api/v1/mod/terms', ['pattern' => 'katakasar'])->assertCreated();
        $this->assertDatabaseHas('banned_terms', ['pattern' => 'katakasar']);

        $this->getJson('/api/v1/mod/terms/suggest?term=katakasar')->assertOk()->assertJsonStructure(['suggestions']);

        $this->deleteJson("/api/v1/mod/terms/{$create->json('term.id')}")->assertOk();
        $this->assertDatabaseMissing('banned_terms', ['pattern' => 'katakasar']);
    }

    public function test_account_action_blocks_user(): void
    {
        $this->actingAsModerator($this->makeModerator());
        $target = User::factory()->create();

        $this->postJson("/api/v1/mod/accounts/{$target->id}/action", ['action' => 'block'])
            ->assertOk()->assertJsonPath('user.status', 'blocked');

        $this->assertDatabaseHas('audit_logs', ['action' => 'account.block', 'target_id' => $target->id]);
    }

    public function test_metrics_returns_health(): void
    {
        $this->actingAsModerator($this->makeModerator());
        Post::factory()->approved()->count(3)->create();

        $this->getJson('/api/v1/mod/metrics')->assertOk()
            ->assertJsonStructure(['health' => ['score', 'label'], 'cards', 'attention']);
    }
}
