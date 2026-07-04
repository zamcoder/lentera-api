<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reflection\StoreReflectionRequest;
use App\Models\Reflection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReflectionController (v1, §6) — refleksi harian E2E "Tiga baris malam".
 * Server buta: hanya menyimpan/mengembalikan ciphertext (base64) + nonce.
 */
class ReflectionController extends Controller
{
    private const FIELDS = ['grateful', 'drained', 'tomorrow'];

    /** GET /api/v1/reflections/{date} — refleksi 1 hari (null bila belum ada). */
    public function show(string $date): JsonResponse
    {
        $date = $this->normalizeDate($date);
        $r = Reflection::where('user_id', auth('api')->id())
            ->where('reflection_date', $date)->first();

        return response()->json($this->payload($r, $date));
    }

    /** PUT /api/v1/reflections/{date} — upsert per hari. */
    public function upsert(StoreReflectionRequest $request, string $date): JsonResponse
    {
        $date = $this->normalizeDate($date);
        $data = $request->validated();

        $attrs = [];
        foreach (self::FIELDS as $f) {
            foreach (['_enc', '_nonce'] as $suffix) {
                $key = $f.$suffix;
                if (array_key_exists($key, $data)) {
                    $attrs[$key] = ($data[$key] ?? null) !== null ? base64_decode($data[$key]) : null;
                }
            }
        }

        $r = Reflection::updateOrCreate(
            ['user_id' => auth('api')->id(), 'reflection_date' => $date],
            $attrs,
        );

        return response()->json($this->payload($r->fresh(), $date));
    }

    /** GET /api/v1/reflections?from=YYYY-MM-DD&to=YYYY-MM-DD — riwayat kalender. */
    public function index(Request $request): JsonResponse
    {
        $rows = Reflection::where('user_id', auth('api')->id())
            ->when($request->filled('from'), fn ($q) => $q->where('reflection_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('reflection_date', '<=', $request->query('to')))
            ->orderBy('reflection_date')
            ->limit(400)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (Reflection $r) => $this->payload($r, $r->reflection_date->format('Y-m-d'))),
        ]);
    }

    private function payload(?Reflection $r, string $date): array
    {
        $b64 = fn (?string $v) => $v !== null ? base64_encode($v) : null;
        $out = ['date' => $date];
        foreach (self::FIELDS as $f) {
            $out["{$f}_enc"] = $r ? $b64($r->{"{$f}_enc"}) : null;
            $out["{$f}_nonce"] = $r ? $b64($r->{"{$f}_nonce"}) : null;
        }

        return $out;
    }

    private function normalizeDate(string $date): string
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 422, 'Format tanggal harus YYYY-MM-DD.');

        return $date;
    }
}
