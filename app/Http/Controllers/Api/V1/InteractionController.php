<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interactions\StoreInteractionRequest;
use App\Http\Requests\Interactions\UpdateInteractionRequest;
use App\Http\Resources\InteractionResource;
use App\Models\Interaction;
use App\Models\Media;
use App\Models\Person;
use App\Services\PersonCounters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * InteractionController (v1) — momen/log jurnal (§4). Terlingkup ke user.
 * Isi terenkripsi disimpan BYTEA; person_ids[] via pivot; metadata orang
 * dihitung ulang tiap perubahan.
 */
class InteractionController extends Controller
{
    public function __construct(private readonly PersonCounters $counters)
    {
    }

    /** GET /api/v1/interactions?person_id=&type=&cursor= */
    public function index(Request $request): JsonResponse
    {
        $userId = auth('api')->id();

        $query = Interaction::query()
            ->where('user_id', $userId)
            ->with(['people:id', 'media:id,interaction_id'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($request->filled('person_id')) {
            $pid = $request->string('person_id');
            $query->whereHas('people', fn ($q) => $q->whereKey($pid));
        }

        if ($request->filled('type')) {
            $query->where('type', Interaction::typeToInt($request->string('type')));
        }

        $page = $query->cursorPaginate((int) $request->integer('limit', 20));

        return response()->json([
            'data' => InteractionResource::collection($page->items()),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    /** POST /api/v1/interactions — simpan momen terenkripsi (Logger). */
    public function store(StoreInteractionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = auth('api')->id();

        $interaction = DB::transaction(function () use ($data, $userId) {
            $interaction = Interaction::create([
                'user_id' => $userId,
                'type' => Interaction::typeToInt($data['type']),
                'text_enc' => base64_decode($data['text_enc']),
                'text_nonce' => base64_decode($data['text_nonce']),
                'topic' => $data['topic'] ?? null,
                'mood' => $data['mood'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            $personIds = $this->ownedPersonIds($userId, $data['person_ids'] ?? []);
            $interaction->people()->sync($personIds);
            $this->attachMedia($userId, $interaction->id, $data['media_ids'] ?? []);

            $this->counters->recountMany($userId, $personIds);

            return $interaction;
        });

        return (new InteractionResource($interaction->load(['people:id', 'media:id,interaction_id'])))
            ->response()->setStatusCode(201);
    }

    /** PUT /api/v1/interactions/{interaction} — edit. */
    public function update(UpdateInteractionRequest $request, string $interaction): InteractionResource
    {
        $data = $request->validated();
        $userId = auth('api')->id();
        $model = $this->owned($userId, $interaction);

        DB::transaction(function () use ($data, $userId, $model) {
            $before = $model->people()->pluck('people.id')->all();

            $model->fill(array_filter([
                'type' => isset($data['type']) ? Interaction::typeToInt($data['type']) : null,
                'text_enc' => isset($data['text_enc']) ? base64_decode($data['text_enc']) : null,
                'text_nonce' => isset($data['text_nonce']) ? base64_decode($data['text_nonce']) : null,
                'topic' => $data['topic'] ?? null,
                'mood' => $data['mood'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? null,
            ], fn ($v) => $v !== null));
            $model->save();

            $after = $before;
            if (array_key_exists('person_ids', $data)) {
                $after = $this->ownedPersonIds($userId, $data['person_ids'] ?? []);
                $model->people()->sync($after);
            }

            $this->counters->recountMany($userId, array_merge($before, $after));
        });

        return new InteractionResource($model->fresh()->load(['people:id', 'media:id,interaction_id']));
    }

    /** DELETE /api/v1/interactions/{interaction} — hapus. */
    public function destroy(string $interaction): JsonResponse
    {
        $userId = auth('api')->id();
        $model = $this->owned($userId, $interaction);

        DB::transaction(function () use ($userId, $model) {
            $affected = $model->people()->pluck('people.id')->all();
            $model->delete();
            $this->counters->recountMany($userId, $affected);
        });

        return response()->json(['message' => 'Momen dihapus.']);
    }

    private function owned(string $userId, string $id): Interaction
    {
        return Interaction::where('user_id', $userId)->findOrFail($id);
    }

    /** Saring hanya person_ids milik user (cegah menautkan orang milik user lain). */
    private function ownedPersonIds(string $userId, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Person::where('user_id', $userId)->whereIn('id', $ids)->pluck('id')->all();
    }

    /** Lampirkan media milik user ke interaction. */
    private function attachMedia(string $userId, string $interactionId, array $mediaIds): void
    {
        if (empty($mediaIds)) {
            return;
        }

        Media::where('user_id', $userId)->whereIn('id', $mediaIds)
            ->update(['interaction_id' => $interactionId]);
    }
}
