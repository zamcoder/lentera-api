<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\BannedTerm;
use App\Services\AuditLogger;
use App\Services\Moderation\GeminiModerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TermsController — pengelola kata terlarang konsol (§A5/§B5, /mod/terms).
 * Daftar pola + hitungan "× ditahan", tambah/hapus, dan saran varian dari Gemini.
 */
class TermsController extends Controller
{
    public function __construct(
        private readonly GeminiModerator $gemini,
        private readonly AuditLogger $audit,
    ) {
    }

    /** GET /mod/terms — daftar pola + hitungan hits. */
    public function index(Request $request): JsonResponse
    {
        $page = BannedTerm::query()
            ->orderByDesc('hits')
            ->orderBy('pattern')
            ->cursorPaginate((int) $request->integer('limit', 30));

        return response()->json([
            'data' => collect($page->items())->map(fn (BannedTerm $t) => [
                'id' => $t->id,
                'pattern' => $t->pattern,
                'is_regex' => (bool) $t->is_regex,
                'action' => $t->action,
                'hits' => $t->hits,
            ])->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'total' => BannedTerm::count(),
        ]);
    }

    /** POST /mod/terms — tambah pola. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pattern' => ['required', 'string', 'max:100', 'unique:banned_terms,pattern'],
            'is_regex' => ['boolean'],
            'action' => ['nullable', 'in:block,mask'],
        ]);

        // Validasi regex yang dikirim agar tak merusak pipa.
        if (($data['is_regex'] ?? false) && @preg_match('/'.$data['pattern'].'/', '') === false) {
            return response()->json(['message' => 'Pola regex tidak valid.'], 422);
        }

        $term = BannedTerm::create([
            'pattern' => $data['pattern'],
            'is_regex' => $data['is_regex'] ?? false,
            'action' => $data['action'] ?? 'block',
            'created_by' => $request->user()->id,
        ]);

        $this->audit->log($request->user()->id, 'term.create', 'term', $term->id, ['pattern' => $term->pattern], $request);

        return response()->json(['term' => [
            'id' => $term->id, 'pattern' => $term->pattern,
            'is_regex' => $term->is_regex, 'action' => $term->action, 'hits' => 0,
        ]], 201);
    }

    /** DELETE /mod/terms/{term} — hapus pola. */
    public function destroy(Request $request, BannedTerm $term): JsonResponse
    {
        $this->audit->log($request->user()->id, 'term.delete', 'term', $term->id, ['pattern' => $term->pattern], $request);
        $term->delete();

        return response()->json(['message' => 'Pola dihapus.']);
    }

    /** GET /mod/terms/suggest?term= — saran varian/salah-ketik dari Gemini. */
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate(['term' => ['required', 'string', 'max:100']]);

        $variants = $this->gemini->suggestTermVariants($data['term']);
        $existing = BannedTerm::whereIn('pattern', $variants)->pluck('pattern')->all();

        // Saring yang sudah ada agar hanya menyarankan yang baru.
        $suggestions = array_values(array_diff($variants, $existing));

        return response()->json([
            'term' => $data['term'],
            'suggestions' => $suggestions,
        ]);
    }
}
