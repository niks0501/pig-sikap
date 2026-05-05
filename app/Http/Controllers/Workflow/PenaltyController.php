<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\WaivePenaltyRequest;
use App\Models\AttendancePenalty;
use App\Models\AuditTrail;
use App\Models\User;
use App\Services\Workflow\PenaltyService;

class PenaltyController extends Controller
{
    public function __construct(
        private readonly PenaltyService $penaltyService
    ) {}

    public function index()
    {
        $summary = $this->penaltyService->getSummary();

        $penalties = AttendancePenalty::with(['user', 'meeting', 'creator', 'waivered'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('workflow.penalties-index', compact('summary', 'penalties'));
    }

    public function byMember(User $user)
    {
        $penalties = $this->penaltyService->getByMember($user);

        $summary = [
            'total_pending' => count(array_filter($penalties, fn ($p) => $p['status'] === 'pending')),
            'total_paid' => count(array_filter($penalties, fn ($p) => $p['status'] === 'paid')),
            'total_waived' => count(array_filter($penalties, fn ($p) => $p['status'] === 'waived')),
            'total_amount' => array_sum(array_column($penalties, 'amount')),
        ];

        $member = $user;

        return view('workflow.penalties-show', compact('member', 'penalties', 'summary'));
    }

    public function waive(AttendancePenalty $penalty, WaivePenaltyRequest $request)
    {
        $this->penaltyService->waive($penalty, auth()->user(), $request->input('reason'));

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'penalty_waived',
            'module' => 'workflow',
            'description' => "Waived penalty #{$penalty->id} for amount ₱{$penalty->amount}",
            'context_json' => [
                'penalty_id' => $penalty->id,
                'user_id' => $penalty->user_id,
                'reason' => $request->input('reason'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->back()->with('status', 'Penalty waived successfully.');
    }

    public function markPaid(AttendancePenalty $penalty)
    {
        $this->penaltyService->markPaid($penalty);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'penalty_paid',
            'module' => 'workflow',
            'description' => "Marked penalty #{$penalty->id} as paid",
            'context_json' => [
                'penalty_id' => $penalty->id,
                'user_id' => $penalty->user_id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return redirect()->back()->with('status', 'Penalty marked as paid.');
    }
}