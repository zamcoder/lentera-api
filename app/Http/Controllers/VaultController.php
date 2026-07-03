<?php

namespace App\Http\Controllers;

use App\Models\VaultBackup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * VaultController — sinkron cadangan jurnal terenkripsi (§A3, Bidang A §05).
 *
 * PRINSIP HARGA MATI: server menerima & menyimpan CIPHERTEXT apa adanya, tak
 * pernah mendekripsi, tak pernah mencatat isinya. Tidak ada satu pun jalur di
 * sini yang membaca plaintext jurnal — kunci hanya milik device.
 */
class VaultController extends Controller
{
    /**
     * PUT /vault/backup — terima blob ciphertext, simpan apa adanya (upsert).
     *
     * Mode escrow (§05): escrow_enabled=true menitipkan kunci pemulihan
     * (tetap terenkripsi) agar bisa dibantu pulih. escrow_enabled=false =
     * mode "tanpa pemulihan, lebih privat".
     */
    public function backup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blob' => ['required', 'string'],            // base64 ciphertext
            'escrow_enabled' => ['boolean'],
            'key_escrow' => ['nullable', 'string'],      // base64, wajib bila escrow
            'checksum' => ['nullable', 'string', 'max:128'],
        ]);

        $blob = base64_decode($data['blob'], true);
        if ($blob === false) {
            return response()->json(['message' => 'blob harus base64 valid.'], 422);
        }

        $escrowEnabled = (bool) ($data['escrow_enabled'] ?? false);
        $keyEscrow = null;
        if ($escrowEnabled) {
            if (empty($data['key_escrow'])) {
                return response()->json([
                    'message' => 'key_escrow wajib saat escrow_enabled.',
                ], 422);
            }
            $keyEscrow = base64_decode($data['key_escrow'], true);
        }

        $user = $request->user();

        $vault = VaultBackup::updateOrCreate(
            ['user_id' => $user->id],
            [
                'blob' => $blob,
                'key_escrow' => $keyEscrow,
                'escrow_enabled' => $escrowEnabled,
                'size_bytes' => strlen($blob),
                'checksum' => $data['checksum'] ?? null,
            ],
        );

        // Kembalikan HANYA metadata — tak pernah isi.
        return response()->json([
            'message' => 'Cadangan tersimpan (server tidak membaca isinya).',
            'backup' => [
                'size_bytes' => $vault->size_bytes,
                'escrow_enabled' => $vault->escrow_enabled,
                'checksum' => $vault->checksum,
                'updated_at' => $vault->updated_at,
            ],
        ]);
    }

    /**
     * GET /vault/restore — kembalikan ciphertext untuk didekripsi di device.
     * Server hanya menyerahkan byte; ia sendiri tak bisa membacanya.
     */
    public function restore(Request $request): JsonResponse
    {
        $vault = VaultBackup::where('user_id', $request->user()->id)->first();

        if (! $vault) {
            return response()->json(['message' => 'Belum ada cadangan.'], 404);
        }

        return response()->json([
            'blob' => base64_encode($vault->blob),
            'key_escrow' => $vault->escrow_enabled && $vault->key_escrow
                ? base64_encode($vault->key_escrow)
                : null,
            'escrow_enabled' => $vault->escrow_enabled,
            'checksum' => $vault->checksum,
            'size_bytes' => $vault->size_bytes,
            'updated_at' => $vault->updated_at,
        ]);
    }

    /** GET /vault/status — cek ada/tidaknya cadangan tanpa mengunduh isi. */
    public function status(Request $request): JsonResponse
    {
        $vault = VaultBackup::where('user_id', $request->user()->id)->first();

        return response()->json([
            'exists' => (bool) $vault,
            'escrow_enabled' => $vault?->escrow_enabled ?? false,
            'size_bytes' => $vault?->size_bytes ?? 0,
            'updated_at' => $vault?->updated_at,
        ]);
    }

    /** DELETE /vault/backup — hapus cadangan (hak lupa). */
    public function destroy(Request $request): JsonResponse
    {
        VaultBackup::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Cadangan dihapus.']);
    }
}
