<?php

namespace Tests\Feature;

use App\Models\BannedTerm;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buat Lingkaran (§8) — POST /circles: langsung publik, filter kata terlarang.
 */
class CircleCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_circle_auto_joins_and_returns_shape(): void
    {
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/circles', [
            'name' => 'Pejuang tidur', 'emoji' => '🌙', 'description' => 'Saling menyemangati soal istirahat',
        ])->assertCreated()
            ->assertJsonPath('name', 'Pejuang tidur')
            ->assertJsonPath('emoji', '🌙')
            ->assertJsonPath('member_count', 1)
            ->assertJsonPath('joined', true)
            ->assertJsonStructure(['id', 'name', 'emoji', 'desc', 'pal', 'member_count', 'members', 'joined']);

        $this->assertDatabaseHas('circles', ['theme' => 'Pejuang tidur', 'created_by' => $user->id]);
        $circle = Circle::where('created_by', $user->id)->first();
        $this->assertDatabaseHas('circle_members', ['circle_id' => $circle->id, 'user_id' => $user->id]);
    }

    public function test_duplicate_name_allowed_unique_slug(): void
    {
        $user = User::factory()->create();
        $this->actingAsJwt($user)->postJson('/api/v1/circles', ['name' => 'Pulih pelan'])->assertCreated();
        $this->actingAsJwt(User::factory()->create())->postJson('/api/v1/circles', ['name' => 'Pulih pelan'])->assertCreated();

        $this->assertSame(2, Circle::where('theme', 'Pulih pelan')->count());
        $this->assertSame(2, Circle::where('theme', 'Pulih pelan')->distinct('slug')->count('slug'));
    }

    public function test_banned_term_in_name_rejected(): void
    {
        BannedTerm::create(['pattern' => 'kasarword', 'is_regex' => false, 'action' => 'block']);
        $user = User::factory()->create();

        $this->actingAsJwt($user)->postJson('/api/v1/circles', ['name' => 'Grup kasarword'])
            ->assertStatus(422);
        $this->assertDatabaseCount('circles', 0);
    }

    public function test_max_circles_per_user(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAsJwt($user)->postJson('/api/v1/circles', ['name' => "Circle {$i}"])->assertCreated();
        }
        $this->actingAsJwt($user)->postJson('/api/v1/circles', ['name' => 'Keenam'])->assertStatus(422);
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v1/circles', ['name' => 'X'])->assertStatus(401);
    }
}
