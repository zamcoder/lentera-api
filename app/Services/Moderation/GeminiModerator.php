<?php

namespace App\Services\Moderation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiModerator — Lapis 2 (§06): klasifikasi nada & niat via Google Gemini.
 * Menilai toxic / pelecehan / spam / isyarat menyakiti diri.
 *
 * Bila GEMINI_API_KEY kosong (dev/tanpa kuota), memakai STUB heuristik lokal
 * agar pipa tetap berfungsi tanpa memanggil jaringan.
 */
class GeminiModerator
{
    /**
     * @return array{label: string, score: float, categories: array<int,string>, reason: string, self_harm: bool}
     */
    public function classify(string $text): array
    {
        $key = (string) config('lentera.moderation.gemini_key');

        if ($key === '') {
            return $this->stub($text);
        }

        try {
            return $this->callGemini($text, $key);
        } catch (\Throwable $e) {
            Log::warning('Gemini gagal, fallback ke stub: '.$e->getMessage());

            return $this->stub($text);
        }
    }

    /**
     * Saran varian/salah-ketik untuk memperkaya daftar kata terlarang (§B5).
     * Tanpa API key → varian heuristik sederhana (leetspeak & spasi).
     *
     * @return array<int, string>
     */
    public function suggestTermVariants(string $term): array
    {
        $key = (string) config('lentera.moderation.gemini_key');

        if ($key === '') {
            return $this->heuristicVariants($term);
        }

        try {
            $model = config('lentera.moderation.gemini_model');
            $endpoint = rtrim((string) config('lentera.moderation.gemini_endpoint'), '/');
            $url = "{$endpoint}/models/{$model}:generateContent?key={$key}";

            $prompt = "Berikan hingga 8 varian & salah-ketik umum (termasuk leetspeak, "
                ."sisipan spasi/simbol) dari kata terlarang berikut untuk filter moderasi. "
                ."Balas HANYA array JSON string. Kata: \"{$term}\"";

            $response = Http::timeout(20)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.2, 'responseMimeType' => 'application/json'],
            ]);
            $response->throw();

            $raw = data_get($response->json(), 'candidates.0.content.parts.0.text', '[]');
            $variants = json_decode($raw, true);

            return is_array($variants)
                ? array_values(array_unique(array_filter(array_map('strval', $variants))))
                : $this->heuristicVariants($term);
        } catch (\Throwable $e) {
            Log::warning('Gemini saran varian gagal: '.$e->getMessage());

            return $this->heuristicVariants($term);
        }
    }

    /** Varian sederhana tanpa AI: leetspeak & spasi antar huruf. */
    private function heuristicVariants(string $term): array
    {
        $leet = strtr(mb_strtolower($term), ['a' => '4', 'i' => '1', 'o' => '0', 'e' => '3', 's' => '5']);
        $spaced = trim(implode(' ', mb_str_split(str_replace(' ', '', $term))));
        $dotted = trim(implode('.', mb_str_split(str_replace(' ', '', $term))));

        return array_values(array_unique(array_filter([$leet, $spaced, $dotted], fn ($v) => $v !== '' && $v !== $term)));
    }

    private function callGemini(string $text, string $key): array
    {
        $model = config('lentera.moderation.gemini_model');
        $endpoint = rtrim((string) config('lentera.moderation.gemini_endpoint'), '/');
        $url = "{$endpoint}/models/{$model}:generateContent?key={$key}";

        $instruction = <<<'PROMPT'
        Kamu moderator komunitas dukungan emosional berbahasa Indonesia. Nilai teks
        pengguna. Balas HANYA JSON dengan bentuk:
        {"label":"ok|toxic|harassment|spam|self_harm","score":0..1,"categories":[..],"reason":"singkat"}
        - toxic/harassment: ujaran kebencian, hinaan, perundungan.
        - spam: promosi/iklan.
        - self_harm: isyarat menyakiti/mengakhiri diri (perlakukan dengan lembut).
        - score = keyakinan pelanggaran (0 aman, 1 jelas melanggar).
        Teks:
        PROMPT;

        $response = Http::timeout(20)->post($url, [
            'contents' => [[
                'parts' => [['text' => $instruction."\n".$text]],
            ]],
            'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json'],
        ]);

        $response->throw();
        $raw = data_get($response->json(), 'candidates.0.content.parts.0.text', '{}');
        $parsed = json_decode($raw, true) ?: [];

        $label = $parsed['label'] ?? 'ok';

        return [
            'label' => $label,
            'score' => (float) ($parsed['score'] ?? 0),
            'categories' => (array) ($parsed['categories'] ?? []),
            'reason' => (string) ($parsed['reason'] ?? ''),
            'self_harm' => $label === 'self_harm',
        ];
    }

    /**
     * Stub heuristik: kata kunci kasar/promosi memberi skor tinggi. Cukup untuk
     * dev; produksi memakai Gemini sungguhan.
     */
    private function stub(string $text): array
    {
        $lower = mb_strtolower($text);

        $toxic = ['bodoh', 'tolol', 'goblok', 'brengsek', 'bangsat', 'sialan', 'benci', 'mati kau', 'anjing'];
        $spam = ['promo', 'diskon', 'klik link', 'wa saya', 'jual', 'beli sekarang', 'http://', 'https://'];

        foreach ($toxic as $w) {
            if (str_contains($lower, $w)) {
                return ['label' => 'toxic', 'score' => 0.9, 'categories' => ['toxic'], 'reason' => "Terindikasi kata kasar: {$w}", 'self_harm' => false];
            }
        }
        foreach ($spam as $w) {
            if (str_contains($lower, $w)) {
                return ['label' => 'spam', 'score' => 0.7, 'categories' => ['spam'], 'reason' => 'Terindikasi promosi/spam', 'self_harm' => false];
            }
        }

        return ['label' => 'ok', 'score' => 0.05, 'categories' => [], 'reason' => 'Aman', 'self_harm' => false];
    }
}
