<?php

namespace Tests;

use App\Models\User;
use App\Support\JwtTokens;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bersihkan state auth agar request berikutnya mem-parse token dari header
     * yang baru (artefak test: container & singleton JWT dipakai ulang; di
     * produksi tiap request proses baru). Guard JWT memakai binding
     * `tymon.jwt` — di-forget agar dibuat ulang tanpa token ter-cache.
     */
    private function resetAuthState(): void
    {
        $this->app['auth']->forgetGuards();
        foreach (['tymon.jwt', 'tymon.jwt.auth'] as $binding) {
            if ($this->app->bound($binding)) {
                $this->app->forgetInstance($binding);
            }
        }
    }

    /** Set header Authorization dengan token JWT mentah (reset guard dulu). */
    protected function withJwt(string $token): static
    {
        $this->resetAuthState();
        $this->withToken($token);

        return $this;
    }

    /** Set header Authorization dengan JWT app untuk $user. */
    protected function actingAsJwt(User $user): static
    {
        $this->resetAuthState();
        $this->withToken(JwtTokens::forApp($user));

        return $this;
    }

    /** JWT konsol (scope mod) untuk admin ber-2FA. */
    protected function actingAsModerator(User $user): static
    {
        $this->resetAuthState();
        $this->withToken(JwtTokens::forConsole($user));

        return $this;
    }
}
