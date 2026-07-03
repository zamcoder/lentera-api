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

    private function seedCircles(): void
    {
        $circles = [
            ['theme' => 'Menjaga batas', 'description' => 'Belajar berkata cukup dengan lembut.'],
            ['theme' => 'Berdamai dengan keluarga', 'description' => 'Ruang untuk luka & harapan pada keluarga.'],
            ['theme' => 'Pelan-pelan pulih', 'description' => 'Menemani langkah kecil pemulihan.'],
        ];

        foreach ($circles as $c) {
            Circle::firstOrCreate(
                ['slug' => Str::slug($c['theme'])],
                ['theme' => $c['theme'], 'description' => $c['description']],
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
