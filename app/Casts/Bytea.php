<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Bytea — cast untuk kolom PostgreSQL BYTEA yang menyimpan byte biner mentah
 * (ciphertext, salt, secret terenkripsi).
 *
 * Masalah yang dipecahkan: PDO pgsql secara default mengikat parameter string
 * sebagai teks UTF-8; byte biner (mis. 0xfb) memicu error 22021. Dengan
 * membungkus nilai sebagai STREAM RESOURCE saat set, Laravel mengikatnya
 * sebagai PDO::PARAM_LOB → Postgres menulis ke bytea dengan benar. Saat get,
 * kita baca stream atau dekode format hex "\x..." kembali ke string biner.
 */
class Bytea implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_resource($value)) {
            // Stream bytea dari pdo_pgsql bisa sudah tergeser posisinya saat
            // hidrasi model di SAPI web → rewind dulu bila memungkinkan.
            if (stream_get_meta_data($value)['seekable'] ?? false) {
                rewind($value);
            }

            return stream_get_contents($value);
        }

        // pgsql dapat mengembalikan bytea sebagai string hex berawalan "\x".
        if (is_string($value) && str_starts_with($value, '\\x')) {
            return hex2bin(substr($value, 2));
        }

        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return [$key => null];
        }

        // Bungkus dalam stream agar diikat sebagai LOB (bukan teks UTF-8).
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $value);
        rewind($stream);

        return [$key => $stream];
    }
}
