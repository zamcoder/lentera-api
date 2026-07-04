<?php

use Illuminate\Support\Facades\Schedule;

/*
| Penjadwalan (§12) — pengingat malam dicek tiap menit; command sendiri yang
| menyaring pengguna yang jam-nya cocok. Aktifkan cron di VPS:
|   * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('lentera:reminders')
    ->everyMinute()
    ->withoutOverlapping();

// Telescope: buang entri lebih tua dari 3 hari (jalan harian → jendela bergulir 72 jam).
Schedule::command('telescope:prune --hours=72')
    ->daily()
    ->withoutOverlapping();
