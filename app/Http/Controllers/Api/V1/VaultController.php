<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\VaultBackupRequest;
use App\Models\VaultBackup;
use Illuminate\Http\JsonResponse;

/**
 * VaultController (v1) — sinkron cadangan jurnal terenkripsi (§2, Bidang A).
 *
 * PRINSIP HARGA MATI: server menerima & menyimpan CIPHERTEXT apa adanya, tak
 * pernah mendekripsi, tak pernah mencatat isinya. Tidak ada satu pun jalur di
 * sini yang membaca plaintext jurnal — kunci hanya milik device.
 */
class VaultController extends Controller
{
    /** GET /api/v1/vault/status — status sinkron + last_synced_at. */
    public function status(): JsonResponse
    {
        $user = auth('api')->user();
        $vault = VaultBackup::where('user_id', $user->id)->first();

        return response()->json([
            'sync_on' => (bool) $user->sync_on,
            'synced' => (bool) $user->sync_on && (bool) $vault,
            'has_backup' => (bool) $vault,
            'version' => $vault?->version ?? 0,
            'size_bytes' => $vault?->size_bytes ?? 0,
            'last_synced_at' => $vault?->updated_at,
        ]);
    }

    /**
     * PUT /api/v1/vault/backup — terima blob ciphertext, simpan apa adanya.
     * Versi di-bump tiap perubahan (client boleh mengirim; default naik 1).
     */
    public function backup(VaultBackupRequest $request): JsonResponse
    {
        $data = $request->validated();

        $ciphertext = base64_decode($data['ciphertext'], true);
        if ($ciphertext === false) {
            return response()->json(['message' => 'ciphertext harus base64 valid.'], 422);
        }

        $user = auth('api')->user();
        $existing = VaultBackup::where('user_id', $user->id)->first();
        $version = $data['version'] ?? (($existing->version ?? 0) + 1);

        $vault = VaultBackup::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ciphertext' => $ciphertext,
                'version' => $version,
                'size_bytes' => strlen($ciphertext),
                'checksum' => $data['checksum'] ?? null,
            ],
        );

        // Kembalikan HANYA metadata — tak pernah isi.
        return response()->json([
            'message' => 'Cadangan tersimpan (server tidak membaca isinya).',
            'version' => $vault->version,
            'size_bytes' => $vault->size_bytes,
            'last_synced_at' => $vault->updated_at,
        ]);
    }

    /** GET /api/v1/vault/restore — kembalikan ciphertext untuk didekripsi di device. */
    public function restore(): JsonResponse
    {
        $vault = VaultBackup::where('user_id', auth('api')->id())->first();

        if (! $vault) {
            return response()->json(['message' => 'Belum ada cadangan.'], 404);
        }

        return response()->json([
            'ciphertext' => base64_encode($vault->ciphertext),
            'version' => $vault->version,
            'size_bytes' => $vault->size_bytes,
            'checksum' => $vault->checksum,
            'last_synced_at' => $vault->updated_at,
        ]);
    }

    /** DELETE /api/v1/vault/backup — hapus cadangan (hak lupa). */
    public function destroy(): JsonResponse
    {
        VaultBackup::where('user_id', auth('api')->id())->delete();

        return response()->json(['message' => 'Cadangan dihapus.']);
    }
}
