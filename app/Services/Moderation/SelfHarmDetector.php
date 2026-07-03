<?php

namespace App\Services\Moderation;

/**
 * SelfHarmDetector — deteksi isyarat menyakiti diri (§06 penanganan khusus).
 * Lapisan cepat berbasis frasa; Gemini (Lapis 2) memberi konfirmasi kontekstual.
 * Keselamatan di atas moderasi: hasil positif = tahan lembut + tawarkan bantuan,
 * BUKAN blokir dingin.
 */
class SelfHarmDetector
{
    public function detect(string $text): bool
    {
        $lower = mb_strtolower($text);

        foreach ((array) config('lentera.self_harm_signals') as $signal) {
            if (str_contains($lower, mb_strtolower($signal))) {
                return true;
            }
        }

        return false;
    }
}
