<?php

namespace Tests\Feature;

use App\Models\BannedTerm;
use App\Models\Post;
use App\Models\User;
use App\Services\TotpService;
use App\Support\TokenAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ModerationConsoleTest extends TestCase
{
    use RefreshDatabase;

    /** Admin lengkap dengan 2FA aktif + token ber-ability mod. */
    private function actingModerator(): User
    {
        // Reset guard yang mungkin men-cache user dari request sebelumnya
        // (artefak test: container dipakai ulang; di produksi tiap request baru).
        $this->app['auth']->forgetGuards();

        $admin = User::factory()->admin()->create([
            'totp_enabled' => true,
            'totp_secret_enc' => Crypt::encryptString(app(TotpService::class)->generateSecret()),
        ]);
        $token = $admin->createToken('console', [TokenAbilities::APP, TokenAbilities::MOD])->plainTextToken;
        $this->withToken($token);

        return $admin;
    }

    public function test_non_admin_cannot_access_console(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('app', [TokenAbilities::APP])->plainTextToken;

        // Token app biasa tak punya ability mod → 403.
        $this->withToken($token)->getJson('/api/mod/queue')->assertForbidden();
    }

    public function test_admin_without_mod_ability_is_blocked(): void
    {
        // Admin login tanpa 2FA: token app saja, belum mod.
        $admin = User::factory()->admin()->create(['totp_enabled' => false]);
        $token = $admin->createToken('app', [TokenAbilities::APP])->plainTextToken;

        $this->withToken($token)->getJson('/api/mod/queue')->assertForbidden();
    }

    public function test_queue_lists_held_posts_and_action_approves(): void
    {
        $this->actingModerator();

        $post = Post::factory()->held()->create(['mod_reason' => 'AI: perlu tinjauan.']);

        $queue = $this->getJson('/api/mod/queue')->assertOk();
        $this->assertCount(1, $queue->json('data'));
        $this->assertSame('AI: perlu tinjauan.', $queue->json('data.0.mod_reason'));

        $this->postJson('/api/mod/action', [
            'post_id' => $post->id,
            'action' => 'approve',
        ])->assertOk()->assertJsonPath('post.status', 'approved');

        $this->assertDatabaseHas('moderation_actions', ['post_id' => $post->id, 'action' => 'approve']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mod.approve', 'target_id' => $post->id]);
    }

    public function test_report_then_console_sees_it(): void
    {
        // Pengguna melaporkan (pakai token app, hindari campur actingAs).
        $reporter = User::factory()->create();
        $post = Post::factory()->approved()->create();
        $reporterToken = $reporter->createToken('app', [TokenAbilities::APP])->plainTextToken;
        $this->withToken($reporterToken)
            ->postJson('/api/reports', ['post_id' => $post->id, 'reason' => 'harassment'])
            ->assertCreated();

        // Moderator melihat di layar Laporan.
        $this->actingModerator();
        $reports = $this->getJson('/api/mod/reports')->assertOk();
        $this->assertSame(1, $reports->json('open_count'));

        // Sembunyikan → laporan tertutup.
        $this->postJson('/api/mod/reports/action', ['post_id' => $post->id, 'decision' => 'hide'])
            ->assertOk()->assertJsonPath('open_count', 0);
    }

    public function test_terms_crud_and_suggest(): void
    {
        $this->actingModerator();

        $this->getJson('/api/mod/terms')->assertOk()->assertJsonStructure(['data', 'total']);

        $create = $this->postJson('/api/mod/terms', ['pattern' => 'katakasar'])->assertCreated();
        $termId = $create->json('term.id');
        $this->assertDatabaseHas('banned_terms', ['pattern' => 'katakasar']);

        // Saran varian (stub heuristik tanpa API key).
        $this->getJson('/api/mod/terms/suggest?term=katakasar')
            ->assertOk()->assertJsonStructure(['suggestions']);

        $this->deleteJson("/api/mod/terms/{$termId}")->assertOk();
        $this->assertDatabaseMissing('banned_terms', ['pattern' => 'katakasar']);
    }

    public function test_account_action_blocks_user(): void
    {
        $this->actingModerator();
        $target = User::factory()->create();

        $this->postJson("/api/mod/accounts/{$target->id}/action", ['action' => 'block'])
            ->assertOk()->assertJsonPath('user.status', 'blocked');

        $this->assertDatabaseHas('audit_logs', ['action' => 'account.block', 'target_id' => $target->id]);
    }

    public function test_metrics_returns_health(): void
    {
        $this->actingModerator();
        Post::factory()->approved()->count(3)->create();

        $this->getJson('/api/mod/metrics')->assertOk()
            ->assertJsonStructure(['health' => ['score', 'label'], 'cards', 'attention']);
    }
}
