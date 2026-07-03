<?php

namespace App\Services\Moderation;

use App\Models\BannedTerm;

/**
 * BannedWordFilter — Lapis 1 moderasi (§06): cepat, deterministik. Mencocokkan
 * teks dengan kamus banned_terms. Kata biasa dicocokkan dengan batas-kata
 * (word boundary, case-insensitive, sadar diakritik ringan); pola bertanda
 * is_regex diperlakukan sebagai regex.
 */
class BannedWordFilter
{
    /**
     * @return array{blocked: bool, masked: bool, text: string, matched: array<int, \App\Models\BannedTerm>}
     */
    public function scan(string $text): array
    {
        $terms = BannedTerm::all();
        $result = ['blocked' => false, 'masked' => false, 'text' => $text, 'matched' => []];

        foreach ($terms as $term) {
            $pattern = $term->is_regex
                ? '/'.$term->pattern.'/iu'
                : '/\b'.preg_quote($term->pattern, '/').'\b/iu';

            // Regex tak valid dari admin tak boleh merusak pipa.
            if (@preg_match($pattern, '') === false) {
                continue;
            }

            if (preg_match($pattern, $result['text'])) {
                $result['matched'][] = $term;

                if ($term->action === 'mask') {
                    $result['masked'] = true;
                    $result['text'] = preg_replace_callback(
                        $pattern,
                        fn ($m) => str_repeat('•', mb_strlen($m[0])),
                        $result['text'],
                    );
                } else {
                    $result['blocked'] = true;
                }
            }
        }

        return $result;
    }

    /** Naikkan penghitung "× ditahan" untuk pola yang cocok. */
    public function recordHits(array $matched): void
    {
        foreach ($matched as $term) {
            $term->increment('hits');
        }
    }
}
