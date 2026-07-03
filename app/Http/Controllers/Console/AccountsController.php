<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AccountsController — layar Akun konsol (§B6). Tabel anggota (status, jumlah
 * laporan); aksi Bisukan / Batasi / Blokir yang mengubah status.
 */
class AccountsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** GET /mod/accounts — daftar anggota + jumlah laporan yang mengenai kirimannya. */
    public function index(Request $request): JsonResponse
    {
        $page = User::query()
            ->where('role', 'user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('created_at')
            ->cursorPaginate((int) $request->integer('limit', 20));

        // Jumlah laporan per penulis (atas kiriman mereka).
        $ids = collect($page->items())->pluck('id');
        $reportCounts = Report::query()
            ->join('posts', 'posts.id', '=', 'reports.post_id')
            ->whereIn('posts.author_id', $ids)
            ->selectRaw('posts.author_id, count(*) as c')
            ->groupBy('posts.author_id')
            ->pluck('c', 'author_id');

        return response()->json([
            'data' => collect($page->items())->map(fn (User $u) => [
                'id' => $u->id,
                'handle' => $u->handle,
                'status' => $u->status,
                'reports_count' => (int) ($reportCounts[$u->id] ?? 0),
                'created_at' => $u->created_at,
            ])->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /**
     * POST /mod/accounts/{user}/action — ubah status akun.
     * action: mute (bisukan) | limit (batasi) | block (blokir) | reactivate (aktifkan)
     */
    public function action(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:mute,limit,block,reactivate'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        abort_if($user->isAdmin(), 422, 'Tidak dapat menindak akun admin.');

        $status = match ($data['action']) {
            'mute' => 'muted',
            'limit' => 'limited',
            'block' => 'blocked',
            'reactivate' => 'active',
        };

        $user->status = $status;
        $user->save();

        // Blokir/bisukan → cabut token aktif agar efek langsung terasa.
        if (in_array($data['action'], ['block', 'mute'], true)) {
            $user->tokens()->delete();
        }

        $this->audit->log($request->user()->id, "account.{$data['action']}", 'user', $user->id, [
            'reason' => $data['reason'] ?? null,
            'new_status' => $status,
        ], $request);

        return response()->json([
            'message' => 'Status akun diperbarui.',
            'user' => ['id' => $user->id, 'handle' => $user->handle, 'status' => $user->status],
        ]);
    }
}
