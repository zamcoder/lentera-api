<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DailyPrompt extends Model
{
    use HasUuids;

    protected $table = 'daily_prompts';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['prompt_date', 'body'];

    protected function casts(): array
    {
        return ['prompt_date' => 'date'];
    }

    /**
     * Prompt hari ini (WIB), dibuat-on-read dari pool secara deterministik &
     * dirotasi harian — tanpa cron. Idempoten (firstOrCreate per tanggal).
     */
    public static function todayFor(string $tz): self
    {
        $today = Carbon::now($tz)->startOfDay();
        $pool = array_values(array_filter((array) config('lentera.prompt_pool')));
        if ($pool === []) {
            $pool = ['Kebaikan kecil apa yang kamu terima hari ini?'];
        }

        $index = intdiv($today->getTimestamp(), 86400) % count($pool);

        return static::firstOrCreate(
            ['prompt_date' => $today->toDateString()],
            ['body' => $pool[$index]],
        );
    }
}
