<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Mood;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * StatsController (v1) — mood/statistik/rekap (§6). HANYA agregat non-sensitif
 * (jumlah per hari, indeks mood, tanggal, person_id). Isi jurnal tetap
 * terenkripsi — server tak pernah membacanya.
 */
class StatsController extends Controller
{
    // Inisial hari (Minggu..Sabtu) — selaras label WeekDay app.
    private const DAY_INITIAL = ['M', 'S', 'S', 'R', 'K', 'J', 'S'];

    /** GET /api/v1/stats/summary?range=week|month */
    public function summary(Request $request): JsonResponse
    {
        $uid = auth('api')->id();
        $tz = config('lentera.timezone');
        $days = $request->string('range')->toString() === 'month' ? 30 : 7;
        $today = Carbon::now($tz)->startOfDay();
        $start = $today->copy()->subDays($days - 1);

        // Hitungan per hari+type — dikelompokkan per tanggal LOKAL user (bukan UTC).
        // Window di-bind sebagai instant UTC (sesi DB = UTC) agar tak tergeser 7 jam.
        $byDay = [];
        Interaction::where('user_id', $uid)
            ->whereBetween('occurred_at', [$start->copy()->utc(), $today->copy()->endOfDay()->utc()])
            ->selectRaw('(occurred_at AT TIME ZONE ?)::date as d, type, count(*) as c', [$tz])
            ->groupBy('d', 'type')
            ->get()
            ->each(function ($row) use (&$byDay) {
                $byDay[(string) $row->d][(int) $row->type] = (int) $row->c;
            });

        $moods = Mood::where('user_id', $uid)
            ->whereBetween('mood_date', [$start->toDateString(), $today->toDateString()])
            ->get()
            ->keyBy(fn ($m) => $m->mood_date->toDateString());

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $series[] = [
                'date' => $key,
                'label' => self::DAY_INITIAL[$date->dayOfWeek],
                'pos' => $byDay[$key][Interaction::TYPE_POSITIVE] ?? 0,
                'neg' => $byDay[$key][Interaction::TYPE_NEGATIVE] ?? 0,
                'mood' => $moods->get($key)?->mood_index,
            ];
        }

        return response()->json([
            'range' => $days === 30 ? 'month' : 'week',
            'week' => $series,                       // seri per hari (WeekDay)
            'distribution' => $this->distribution($uid, $start, $tz),
            'streak' => $this->streak($uid, $tz),
            'recap' => $this->recap($uid),           // "Paling kamu syukuri"
        ]);
    }

    /**
     * GET /api/v1/stats/mood?month=YYYY-MM — mood harian 1 bulan penuh untuk
     * kalender "memori". Default bulan berjalan.
     */
    public function moodMonth(Request $request): JsonResponse
    {
        $month = $request->string('month')->toString() ?: Carbon::now(config('lentera.timezone'))->format('Y-m');
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month), 422, 'Format bulan harus YYYY-MM.');

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        $rows = Mood::where('user_id', auth('api')->id())
            ->whereBetween('mood_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('mood_date')
            ->get(['mood_date', 'mood_index']);

        return response()->json([
            'data' => $rows->map(fn (Mood $m) => [
                'date' => $m->mood_date->format('Y-m-d'),
                'mood_index' => $m->mood_index,
            ]),
        ]);
    }

    /** GET /api/v1/today — rekap Hari Ini + energi sosial. */
    public function today(): JsonResponse
    {
        $uid = auth('api')->id();
        $now = Carbon::now(config('lentera.timezone'));
        // Batas hari LOKAL, tapi di-bind sebagai instant UTC (sesi DB = UTC).
        [$start, $end] = [$now->copy()->startOfDay()->utc(), $now->copy()->endOfDay()->utc()];

        $counts = Interaction::where('user_id', $uid)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type');

        return response()->json([
            'date' => $now->toDateString(),
            'mood_index' => Mood::where('user_id', $uid)->where('mood_date', $now->toDateString())->value('mood_index'),
            'counts' => [
                'positive' => (int) ($counts[Interaction::TYPE_POSITIVE] ?? 0),
                'negative' => (int) ($counts[Interaction::TYPE_NEGATIVE] ?? 0),
                'neutral' => (int) ($counts[0] ?? 0),
            ],
            // Energi sosial: siapa mengisi (positif) vs menguras (negatif) hari ini.
            'social_energy' => [
                'filled' => $this->peopleTaggedToday($uid, Interaction::TYPE_POSITIVE, $start, $end),
                'drained' => $this->peopleTaggedToday($uid, Interaction::TYPE_NEGATIVE, $start, $end),
            ],
        ]);
    }

    private function distribution(string $uid, Carbon $start, string $tz): array
    {
        $d = Interaction::where('user_id', $uid)
            ->whereBetween('occurred_at', [$start->copy()->utc(), Carbon::now($tz)->endOfDay()->utc()])
            ->selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type');

        return [
            'positive' => (int) ($d[Interaction::TYPE_POSITIVE] ?? 0),
            'negative' => (int) ($d[Interaction::TYPE_NEGATIVE] ?? 0),
            'neutral' => (int) ($d[0] ?? 0),
        ];
    }

    /** Rentetan hari berturut (mundur dari hari ini) yang ada momennya. */
    private function streak(string $uid, string $tz): int
    {
        $dates = Interaction::where('user_id', $uid)
            ->selectRaw('distinct (occurred_at AT TIME ZONE ?)::date as d', [$tz])
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $streak = 0;
        $cur = Carbon::now($tz)->startOfDay();
        while ($dates->has($cur->toDateString())) {
            $streak++;
            $cur = $cur->subDay();
        }

        return $streak;
    }

    private function recap(string $uid): ?array
    {
        $top = Person::where('user_id', $uid)->where('pos_count', '>', 0)
            ->orderByDesc('pos_count')->first();

        return $top ? ['person_id' => $top->id, 'pos_count' => $top->pos_count] : null;
    }

    /** @return array<int,string> distinct person_id yang ditandai hari ini pada type tertentu. */
    private function peopleTaggedToday(string $uid, int $type, Carbon $start, Carbon $end): array
    {
        return DB::table('interaction_people as ip')
            ->join('interactions as i', 'i.id', '=', 'ip.interaction_id')
            ->where('i.user_id', $uid)
            ->where('i.type', $type)
            ->whereBetween('i.occurred_at', [$start, $end])
            ->distinct()
            ->pluck('ip.person_id')
            ->all();
    }
}
