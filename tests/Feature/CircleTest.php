<?php

namespace Tests\Feature;

use App\Models\Circle;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lingkaran §8 — list/detail/join/leave + feed (JWT, /api/v1).
 */
class CircleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // 4 lingkaran identik dummy
    }

    public function test_index_lists_seeded_circles_with_shape(): void
    {
        $res = $this->actingAsJwt(User::factory()->create())->getJson('/api/v1/circles')->assertOk();

        $this->assertCount(4, $res->json('data'));
        $res->assertJsonStructure(['data' => [['id', 'name', 'emoji', 'desc', 'pal', 'members', 'member_count', 'joined']]]);
        // "Syukur harian" 3400 → "3,4rb".
        $syukur = collect($res->json('data'))->firstWhere('name', 'Syukur harian');
        $this->assertSame('3,4rb', $syukur['members']);
        $this->assertFalse($syukur['joined']);
    }

    public function test_join_and_leave_updates_membership_and_count(): void
    {
        $user = User::factory()->create();
        $circle = Circle::where('theme', 'Menjaga batas')->first();
        $before = $circle->member_count;

        $this->actingAsJwt($user)->postJson("/api/v1/circles/{$circle->id}/join")
            ->assertOk()->assertJsonPath('joined', true)->assertJsonPath('member_count', $before + 1);
        $this->assertDatabaseHas('circle_members', ['circle_id' => $circle->id, 'user_id' => $user->id]);

        // joined tercermin di index.
        $joined = collect($this->actingAsJwt($user)->getJson('/api/v1/circles')->json('data'))
            ->firstWhere('id', $circle->id);
        $this->assertTrue($joined['joined']);

        $this->actingAsJwt($user)->deleteJson("/api/v1/circles/{$circle->id}/join")
            ->assertOk()->assertJsonPath('joined', false)->assertJsonPath('member_count', $before);
        $this->assertDatabaseMissing('circle_members', ['circle_id' => $circle->id, 'user_id' => $user->id]);
    }

    public function test_circle_feed_returns_approved_posts(): void
    {
        $user = User::factory()->create();
        $circle = Circle::first();
        Post::factory()->approved()->create(['circle_id' => $circle->id, 'surface' => 'circle']);

        $res = $this->actingAsJwt($user)->getJson("/api/v1/circles/{$circle->id}/feed")->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertSame($circle->id, $res->json('circle.id'));
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v1/circles')->assertUnauthorized();
    }
}
