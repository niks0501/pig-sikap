<?php

namespace App\Services\Workflow;

use App\Models\AttendancePenalty;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PenaltyService – auto-applies attendance penalties on meeting
 * confirmation and provides management for waive/pay operations.
 */
class PenaltyService
{
    public function __construct(
        private readonly PolicyService $policyService
    ) {}

    /**
     * Auto-apply penalties for absent members when a meeting is confirmed.
     * Idempotent: checks the penalty_applied flag to prevent double-application.
     *
     * @return array{created: int, skipped: int, total_amount: float}
     */
    public function autoApplyForMeeting(Meeting $meeting, User $creator): array
    {
        $penaltyAmount = $this->policyService->getAttendancePenaltyAmount();

        // If penalty amount is 0, skip application
        if ($penaltyAmount <= 0) {
            return ['created' => 0, 'skipped' => 0, 'total_amount' => 0];
        }

        $absentSignatories = $meeting->signatories()
            ->where('attendance_status', 'absent')
            ->where('penalty_applied', false)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($absentSignatories as $signatory) {
            // Check if penalty already exists for this user + meeting (idempotency)
            $exists = AttendancePenalty::where('user_id', $signatory->user_id)
                ->where('meeting_id', $meeting->id)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            AttendancePenalty::create([
                'user_id' => $signatory->user_id,
                'meeting_id' => $meeting->id,
                'amount' => $penaltyAmount,
                'status' => 'pending',
                'created_by' => $creator->id,
            ]);

            // Mark the signatory as penalty-applied
            $signatory->update(['penalty_applied' => true]);
            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total_amount' => $created * $penaltyAmount,
        ];
    }

    /**
     * Waive a penalty (president only).
     */
    public function waive(AttendancePenalty $penalty, User $waivedBy, string $reason): AttendancePenalty
    {
        if ($penalty->status !== 'pending') {
            throw new RuntimeException('Only pending penalties can be waived.');
        }

        $penalty->update([
            'status' => 'waived',
            'reason' => $reason,
            'waived_by' => $waivedBy->id,
            'waived_at' => now(),
        ]);

        return $penalty->fresh();
    }

    /**
     * Mark a penalty as paid.
     */
    public function markPaid(AttendancePenalty $penalty): AttendancePenalty
    {
        if ($penalty->status !== 'pending') {
            throw new RuntimeException('Only pending penalties can be marked as paid.');
        }

        $penalty->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $penalty->fresh();
    }

    /**
     * Get summary statistics for penalties.
     *
     * @return array{total_pending: int, total_paid: int, total_waived: int, total_amount_pending: float}
     */
    public function getSummary(): array
    {
        return [
            'total_pending' => AttendancePenalty::where('status', 'pending')->count(),
            'total_paid' => AttendancePenalty::where('status', 'paid')->count(),
            'total_waived' => AttendancePenalty::where('status', 'waived')->count(),
            'total_amount_pending' => (float) AttendancePenalty::where('status', 'pending')->sum('amount'),
        ];
    }

    /**
     * Get penalties for a specific member.
     */
    public function getByMember(User $user): array
    {
        return AttendancePenalty::where('user_id', $user->id)
            ->with(['meeting', 'creator', 'waivered'])
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }
}