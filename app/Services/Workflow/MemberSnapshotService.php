<?php

namespace App\Services\Workflow;

use App\Models\Resolution;
use App\Models\ResolutionMemberSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * MemberSnapshotService – creates an immutable snapshot of active
 * members when a resolution enters pending_member_approval.
 */
class MemberSnapshotService
{
    /**
     * Take an immutable snapshot of all active members.
     * Idempotent: if a snapshot already exists, it will not be overwritten.
     *
     * @throws RuntimeException
     */
    public function takeSnapshot(Resolution $resolution): ResolutionMemberSnapshot
    {
        // Idempotent – never overwrite an existing snapshot
        $existing = ResolutionMemberSnapshot::where('resolution_id', $resolution->id)->first();

        if ($existing) {
            return $existing;
        }

        $activeMembers = User::where('is_active', true)
            ->with('role')
            ->get();

        $eligibleCount = $activeMembers->count();

        if ($eligibleCount === 0) {
            throw new RuntimeException('Cannot take snapshot: no active members found.');
        }

        // Required approvals = ceil(eligible_count * 0.75)
        $requiredApprovals = (int) ceil($eligibleCount * 0.75);

        $snapshotData = $activeMembers->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->relationLoaded('role') && $user->role ? $user->role->slug : null,
            'is_active' => $user->is_active,
        ]);

        return DB::transaction(function () use ($resolution, $snapshotData, $eligibleCount, $requiredApprovals) {
            return ResolutionMemberSnapshot::create([
                'resolution_id' => $resolution->id,
                'snapshot_data' => $snapshotData->toArray(),
                'eligible_count' => $eligibleCount,
                'required_approvals' => $requiredApprovals,
                'snapshot_taken_at' => now(),
            ]);
        });
    }

    /**
     * Get the snapshot for a resolution (or null if none exists).
     */
    public function getSnapshot(Resolution $resolution): ?ResolutionMemberSnapshot
    {
        return ResolutionMemberSnapshot::where('resolution_id', $resolution->id)->first();
    }
}