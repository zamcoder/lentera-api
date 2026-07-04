<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\Person;

/**
 * PersonCounters — memelihara metadata plaintext Person (§3) dari interactions:
 * pos_count, neg_count, last_at, last_type. Dihitung ulang (bukan increment)
 * agar selalu konsisten setelah create/update/delete momen.
 */
class PersonCounters
{
    public function recount(string $userId, string $personId): void
    {
        $person = Person::where('user_id', $userId)->find($personId);
        if (! $person) {
            return;
        }

        $base = Interaction::query()
            ->where('user_id', $userId)
            ->whereHas('people', fn ($q) => $q->whereKey($personId));

        $latest = (clone $base)->orderByDesc('occurred_at')->first();

        $person->update([
            'pos_count' => (clone $base)->where('type', Interaction::TYPE_POSITIVE)->count(),
            'neg_count' => (clone $base)->where('type', Interaction::TYPE_NEGATIVE)->count(),
            'last_at' => $latest?->occurred_at,
            'last_type' => $latest?->type,
        ]);
    }

    /** Hitung ulang untuk beberapa orang sekaligus. */
    public function recountMany(string $userId, array $personIds): void
    {
        foreach (array_unique($personIds) as $personId) {
            $this->recount($userId, $personId);
        }
    }
}
