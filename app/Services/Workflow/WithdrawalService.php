<?php

namespace App\Services\Workflow;

use App\Models\Resolution;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates withdrawal requests, enforcing business rules:
 * eligibility, authorized withdrawer checks, and approval locking.
 */
class WithdrawalService
{
    public function __construct(
        private readonly EligibilityService $eligibilityService
    ) {}

    /**
     * Create a withdrawal request for a resolution.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createFromResolution(Resolution $resolution, array $data, User $user): Withdrawal
    {
        // Enforce eligibility rules
        $eligibility = $this->eligibilityService->canWithdraw($resolution);

        if (! $eligibility['eligible']) {
            throw ValidationException::withMessages([
                'resolution' => $eligibility['reasons'],
            ]);
        }

        // Enforce authorized withdrawer check
        $authCheck = $this->eligibilityService->canUserWithdraw($resolution, $user);

        if (! $authCheck['authorized']) {
            throw ValidationException::withMessages([
                'user' => [$authCheck['reason']],
            ]);
        }

        // Enforce amount does not exceed remaining balance
        if (($data['amount'] ?? 0) > $resolution->remaining_balance) {
            throw ValidationException::withMessages([
                'amount' => ["Amount cannot exceed the remaining balance of ₱" . number_format($resolution->remaining_balance, 2) . "."],
            ]);
        }

        $filePath = null;

        if (isset($data['proof_file']) && $data['proof_file'] instanceof UploadedFile) {
            $filePath = $data['proof_file']->store('withdrawals', 'public');
        }

        $withdrawal = DB::transaction(function () use ($resolution, $data, $user, $filePath) {
            $withdrawal = Withdrawal::create([
                'resolution_id' => $resolution->id,
                'requested_by' => $user->id,
                'authorized_withdrawer_id' => $user->id,
                'amount' => $data['amount'],
                'currency' => 'PHP',
                'bank_account' => $data['bank_account'] ?? null,
                'proof_file_path' => $filePath,
                'status' => 'pending',
                'requested_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Lock approval changes after first withdrawal
            if (! $resolution->is_approval_locked) {
                $resolution->update(['is_approval_locked' => true]);
            }

            // Update resolution status
            if ($resolution->status !== 'withdrawn') {
                $resolution->update(['status' => 'withdrawn']);
            }

            return $withdrawal;
        });

        event(new \App\Events\Workflow\WithdrawalCreated($withdrawal, $resolution));

        return $withdrawal;
    }
}