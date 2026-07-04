<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Models\Media;
use Illuminate\Http\JsonResponse;

/**
 * MediaController (v1) — blob suara/foto terenkripsi (§5). Server buta.
 */
class MediaController extends Controller
{
    /** POST /api/v1/media — unggah blob terenkripsi → media_id. */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $data = $request->validated();

        $blob = base64_decode($data['blob'], true);
        if ($blob === false) {
            return response()->json(['message' => 'blob harus base64 valid.'], 422);
        }

        $media = Media::create([
            'user_id' => auth('api')->id(),
            'kind' => $data['kind'],
            'blob_enc' => $blob,
            'nonce' => isset($data['nonce']) ? base64_decode($data['nonce']) : null,
            'mime' => $data['mime'] ?? null,
            'size_bytes' => strlen($blob),
        ]);

        return response()->json([
            'media_id' => $media->id,
            'kind' => $media->kind,
            'size_bytes' => $media->size_bytes,
        ], 201);
    }

    /** GET /api/v1/media/{media} — ambil blob ciphertext untuk diputar/tampil. */
    public function show(string $media): JsonResponse
    {
        $item = Media::where('user_id', auth('api')->id())->findOrFail($media);

        return response()->json([
            'id' => $item->id,
            'kind' => $item->kind,
            'mime' => $item->mime,
            'size_bytes' => $item->size_bytes,
            'blob' => base64_encode($item->blob_enc),
            'nonce' => $item->nonce ? base64_encode($item->nonce) : null,
        ]);
    }
}
