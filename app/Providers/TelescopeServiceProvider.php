<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Rekam semua entri (visibilitas penuh untuk debugging integrasi mobile).
        // Volume dikendalikan lewat `telescope:prune` terjadwal (3 hari).
        Telescope::filter(fn (IncomingEntry $entry) => true);
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token', 'password', 'passphrase']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',   // jangan simpan JWT Bearer di Telescope
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        // Akses dashboard /telescope dilindungi di level nginx (HTTP Basic Auth),
        // karena konsol memakai JWT (bukan sesi web) sehingga $user selalu null.
        Gate::define('viewTelescope', fn ($user = null) => true);
    }
}
