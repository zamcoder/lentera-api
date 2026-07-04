<?php

namespace App\Support;

/**
 * SoftAvatar — emoji & palet lembut untuk avatar anonim komunitas (§03).
 */
final class SoftAvatar
{
    private const EMOJIS = ['🌙', '🌿', '🌅', '☁️', '🌫️', '🍂', '🌊', '🌇', '🌸', '🕊️'];
    public const PALS = ['mint', 'peach', 'lav'];

    public static function emoji(): string
    {
        return self::EMOJIS[array_rand(self::EMOJIS)];
    }

    public static function pal(): string
    {
        return self::PALS[array_rand(self::PALS)];
    }
}
