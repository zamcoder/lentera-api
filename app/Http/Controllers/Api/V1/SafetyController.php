<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SafetyController (v1, §11) — nomor hotline krisis per wilayah untuk Ruang Tenang.
 * Kini "Segera hadir" (kosong) hingga nomor per-wilayah dilengkapi.
 */
class SafetyController extends Controller
{
    /** GET /api/v1/safety/hotlines?region= */
    public function hotlines(Request $request): JsonResponse
    {
        $region = strtoupper($request->string('region')->toString() ?: 'ID');
        $list = (array) (config('lentera.hotlines')[$region] ?? []);

        return response()->json([
            'region' => $region,
            'available' => count($list) > 0,
            'hotlines' => array_values($list),
            'message' => count($list) ? null : 'Segera hadir',
        ]);
    }
}
