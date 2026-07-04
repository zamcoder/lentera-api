<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Push\PushSender;
use Illuminate\Console\Command;

/**
 * SendReminders — kirim pengingat lembut malam (§12) ke pengguna yang
 * mengaktifkannya pada jam yang cocok. Dijadwalkan tiap menit (routes/console.php).
 * Perbandingan jam memakai UTC (reminder_at disimpan tanpa timezone).
 */
class SendReminders extends Command
{
    protected $signature = 'lentera:reminders {--at= : Override jam HH:MM (untuk pengujian)}';
    protected $description = 'Kirim pengingat malam ke pengguna yang jadwalnya cocok';

    public function handle(PushSender $push): int
    {
        $minute = $this->option('at') ?: now()->format('H:i');

        $users = User::where('reminder_on', true)
            ->whereNotNull('reminder_at')
            ->whereRaw("to_char(reminder_at, 'HH24:MI') = ?", [$minute])
            ->get();

        foreach ($users as $user) {
            $push->sendReminder($user);
        }

        $this->info("Pengingat dikirim ke {$users->count()} pengguna (jam {$minute}).");

        return self::SUCCESS;
    }
}
