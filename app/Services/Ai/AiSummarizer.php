<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiSummarizer — ringkasan hangat via Gemini untuk fitur "Ringkasan AI"
 * (gated consent). Menerima PLAINTEXT (menembus E2E atas izin user).
 *
 * Privasi: konten TIDAK disimpan/di-log. Proses transien (kirim → hasil → buang).
 * Hanya HASIL ringkasan yang boleh di-cache, berkunci hash prompt (bukan konten
 * mentah yang bisa dibalik). Log hanya pesan error, tak pernah konten.
 */
class AiSummarizer
{
    private const STYLE = 'Kamu asisten jurnal syukur berbahasa Indonesia yang hangat, tenang, '
        .'dan menjaga batas. Balas HANYA ringkasan 1–2 kalimat pendek, sapaan "kamu", '
        .'boleh 1 emoji lembut. Jangan menggurui, jangan mendiagnosa. Tanpa awalan/tanda kutip.';

    /** Ringkasan catatan seseorang. Null bila kosong / AI tak tersedia. */
    public function person(array $d): ?string
    {
        $name = trim((string) ($d['name'] ?? '')) ?: 'orang ini';
        $rel = trim((string) ($d['relation'] ?? ''));
        $pos = (int) ($d['pos_count'] ?? 0);
        $neg = (int) ($d['neg_count'] ?? 0);
        $lines = $this->lines($d['interactions'] ?? [], 15);

        $prompt = self::STYLE."\n\n"
            ."Ringkas hubungan pengguna dengan \"{$name}\"".($rel !== '' ? " ({$rel})" : '').'. '
            ."Jumlah momen positif: {$pos}, negatif: {$neg}.\n"
            .($lines !== '' ? "Catatan terbaru:\n{$lines}\n" : '')
            .'Tulis ringkasan hangat + 1 saran kecil menjaga hubungan/batas.';

        return $this->cached($prompt);
    }

    /** Ringkasan satu hari. Null bila kosong / AI tak tersedia. */
    public function day(array $d): ?string
    {
        $date = trim((string) ($d['date'] ?? ''));
        $mood = $d['mood_index'] ?? null;
        $lines = $this->lines($d['moments'] ?? [], 15);

        $prompt = self::STYLE."\n\n"
            .'Ringkas hari pengguna'.($date !== '' ? " ({$date})" : '').'. '
            .($mood !== null ? "Indeks mood 0–4: {$mood}.\n" : '')
            .($lines !== '' ? "Momen hari ini:\n{$lines}\n" : '')
            .'Akui perasaannya dengan lembut, tanpa menghakimi.';

        return $this->cached($prompt);
    }

    /** Ringkas item interaksi/momen jadi baris pendek (ambil N terbaru). */
    private function lines(array $items, int $limit): string
    {
        $out = [];
        foreach (array_slice(array_values($items), -$limit) as $it) {
            $text = trim((string) ($it['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $type = (string) ($it['type'] ?? 'netral');
            $meta = $it['date'] ?? ($it['person'] ?? null);
            $out[] = "- [{$type}] ".mb_substr($text, 0, 200).($meta ? " ({$meta})" : '');
        }

        return implode("\n", $out);
    }

    /** Cache hasil (bukan konten) by hash prompt; hanya simpan yang sukses. */
    private function cached(string $prompt): ?string
    {
        $k = 'ai_sum:'.hash('sha256', $prompt);
        $hit = Cache::get($k);
        if ($hit !== null) {
            return $hit;
        }
        $val = $this->generate($prompt);
        if ($val !== null) {
            Cache::put($k, $val, now()->addHours(24));
        }

        return $val;
    }

    private function generate(string $prompt): ?string
    {
        $key = (string) config('lentera.moderation.gemini_key');
        if ($key === '') {
            return null; // app fallback ke template lokal
        }

        try {
            $model = config('lentera.moderation.gemini_model');
            $endpoint = rtrim((string) config('lentera.moderation.gemini_endpoint'), '/');
            $url = "{$endpoint}/models/{$model}:generateContent?key={$key}";

            $resp = Http::timeout(20)->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 160],
            ]);
            $resp->throw();

            $text = trim((string) data_get($resp->json(), 'candidates.0.content.parts.0.text', ''));

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('AI summarize gagal: '.$e->getMessage()); // pesan error saja, TANPA konten

            return null;
        }
    }
}
