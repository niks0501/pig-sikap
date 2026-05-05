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
     * Take an immutable snapshot of the active members who were
     * present at the resolution's associated meeting.
     * The 75% approval denominator is meeting attendees (present),
     * not all active user accounts.
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

        $meeting = $resolution->meeting()->first();

        if (! $meeting) {
            throw new RuntimeException('Cannot take snapshot: resolution is not linked to a meeting.');
        }

        // Denominator: only members who were marked PRESENT at the meeting
        $presentSignatories = $meeting->signatories()
            ->where('attendance_status', 'present')
            ->with('user.role')
            ->get();

        $eligibleCount = $presentSignatories->count();

        if ($eligibleCount === 0) {
            throw new RuntimeException('Cannot take snapshot: no present attendees at the meeting.');
        }

        // Required approvals = ceil(eligible_count * 0.75)
        $requiredApprovals = (int) ceil($eligibleCount * (Resolution::APPROVAL_THRESHOLD / 100));

        $snapshotData = $presentSignatories->map(fn ($signatory) => [
            'id' => $signatory->user->id,
            'name' => $signatory->user->name,
            'email' => $signatory->user->email,
            'role' => $signatory->user->relationLoaded('role') && $signatory->user->role ? $signatory->user->role->slug : null,
            'is_active' => $signatory->user->is_active,
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