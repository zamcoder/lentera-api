<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonRequest;
use App\Http\Requests\People\UpdatePersonRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * PersonController (v1) — CRUD Orang (§3). Selalu terlingkup ke user pemilik.
 * Field terenkripsi diterima base64 dari device & disimpan BYTEA apa adanya.
 */
class PersonController extends Controller
{
    // Field terenkripsi yang dikirim device sebagai base64.
    private const ENC_FIELDS = [
        'name_enc', 'name_nonce', 'rel_enc', 'rel_nonce', 'recall_enc', 'recall_nonce',
    ];

    /** GET /api/v1/people — daftar orang (urut interaksi terakhir). */
    public function index(): AnonymousResourceCollection
    {
        $people = Person::where('user_id', auth('api')->id())
            ->orderByRaw('last_at DESC NULLS LAST')
            ->orderByDesc('created_at')
            ->get();

        return PersonResource::collection($people);
    }

    /** POST /api/v1/people — tambah orang (terenkripsi). */
    public function store(StorePersonRequest $request): JsonResponse
    {
        $attrs = $this->decodeEnc($request->validated());
        $attrs['user_id'] = auth('api')->id();

        $person = Person::create($attrs);

        return (new PersonResource($person->fresh()))->response()->setStatusCode(201);
    }

    /** PUT /api/v1/people/{person} — edit. */
    public function update(UpdatePersonRequest $request, string $person): PersonResource
    {
        $model = $this->owned($person);
        $model->fill($this->decodeEnc($request->validated()));
        $model->save();

        return new PersonResource($model->fresh());
    }

    /** DELETE /api/v1/people/{person} — hapus. */
    public function destroy(string $person): JsonResponse
    {
        $this->owned($person)->delete();

        return response()->json(['message' => 'Orang dihapus.']);
    }

    /** Ambil person milik user, atau 404 (jangan bocorkan keberadaan). */
    private function owned(string $id): Person
    {
        return Person::where('user_id', auth('api')->id())->findOrFail($id);
    }

    /** Decode field base64 → biner untuk kolom BYTEA; sisanya apa adanya. */
    private function decodeEnc(array $data): array
    {
        foreach (self::ENC_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] === null ? null : base64_decode($data[$field]);
            }
        }

        return $data;
    }
}
