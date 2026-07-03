<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Pseudonym — nama samaran lembut ala "Pejalan Senja" (§03). Default identitas
 * komunitas adalah anonim; handle ini menjadi identitas dasar tiap akun.
 */
class Pseudonym
{
    private const ADJECTIVES = [
        'Pejalan', 'Penjaga', 'Pencari', 'Perawat', 'Penenun',
        'Pendengar', 'Peniti', 'Pemulih', 'Penyapa', 'Pelukis',
    ];

    private const NOUNS = [
        'Senja', 'Fajar', 'Embun', 'Cahaya', 'Bulan',
        'Ombak', 'Rimba', 'Angin', 'Kabut', 'Pelangi',
    ];

    /** Handle unik yang belum dipakai. */
    public static function unique(): string
    {
        do {
            $handle = self::random();
        } while (User::where('handle', $handle)->exists());

        return $handle;
    }

    public static function random(): string
    {
        $adj = self::ADJECTIVES[array_rand(self::ADJECTIVES)];
        $noun = self::NOUNS[array_rand(self::NOUNS)];

        return "{$adj} {$noun} " . Str::upper(Str::random(4));
    }
}
