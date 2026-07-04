<?php

namespace Database\Seeders;

use App\Models\BannedTerm;
use App\Models\Circle;
use App\Models\DailyPrompt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBannedTerms();
        $this->seedAdmin();
        $this->seedCircles();
        $this->seedTodayPrompt();
    }

    /**
     * §06 Lapis 1 — tujuh kata awal daftar terlarang (dari PROMPTS A1).
     * Disimpan sebagai kata literal (is_regex=false); pencocokan pakai
     * batas-kata word-boundary di BannedWordFilter.
     */
    private function seedBannedTerms(): void
    {
        $seed = ['bodoh', 'tolol', 'goblok', 'sialan', 'brengsek', 'bangsat', 'benci kamu'];

        foreach ($seed as $pattern) {
            BannedTerm::firstOrCreate(
                ['pattern' => $pattern],
                ['is_regex' => false, 'action' => 'block', 'hits' => 0],
            );
        }
    }

    /**
     * Satu moderator awal (§11 "hanya kamu, sementara").
     * Sandi dev: "rahasia123" — ganti di produksi. TOTP di-setup lewat endpoint.
     */
    private function seedAdmin(): void
    {
        User::firstOrCreate(
            ['handle' => 'moderator'],
            [
                'email' => 'admin@lentera.test',
                'password_hash' => Hash::make('rahasia123'),
                'role' => 'admin',
                'status' => 'active',
            ],
        );
    }

    /** Empat lingkaran identik `LData.circles` (lib/data/dummy_data.dart). */
    private function seedCircles(): void
    {
        $circles = [
            ['theme' => 'Menjaga batas', 'emoji' => '🛡️', 'pal' => 'peach', 'members' => 1200,
                'description' => 'Tempat aman untuk belajar berkata "tidak" dan menjaga energimu.'],
            ['theme' => 'Berdamai dengan keluarga', 'emoji' => '🏡', 'pal' => 'mint', 'members' => 890,
                'description' => 'Ruang pelan untuk memperbaiki & menerima hubungan keluarga.'],
            ['theme' => 'Pulih pelan-pelan', 'emoji' => '🌱', 'pal' => 'lav', 'members' => 2100,
                'description' => 'Langkah-langkah kecil menuju pulih — tanpa terburu-buru.'],
            ['theme' => 'Syukur harian', 'emoji' => '💛', 'pal' => 'mint', 'members' => 3400,
                'description' => 'Merayakan kebaikan-kebaikan kecil setiap hari, bersama.'],
        ];

        foreach ($circles as $c) {
            // updateOrCreate: memperbaiki baris lama (mis. emoji NULL) sekaligus
            // menambah yang belum ada — idempoten & aman untuk reseed di prod.
            Circle::updateOrCreate(
                ['slug' => Str::slug($c['theme'])],
                [
                    'theme' => $c['theme'], 'emoji' => $c['emoji'], 'pal' => $c['pal'],
                    'description' => $c['description'], 'member_count' => $c['members'],
                ],
            );
        }
    }

    private function seedTodayPrompt(): void
    {
        DailyPrompt::firstOrCreate(
            ['prompt_date' => now()->toDateString()],
            ['body' => 'Kebaikan kecil apa yang kamu terima hari ini?'],
        );
    }
}
