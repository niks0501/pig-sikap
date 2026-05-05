<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreWithdrawalAuthorizationRequest;
use App\Models\AuditTrail;
use App\Models\Resolution;
use App\Models\ResolutionWithdrawalAuthorization;

class WithdrawalAuthorizationController extends Controller
{
    public function index(Resolution $resolution)
    {
        $authorizations = $resolution->withdrawalAuthorizations()
            ->with(['user', 'designatedBy', 'revokedBy'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['authorizations' => $authorizations]);
    }

    public function store(StoreWithdrawalAuthorizationRequest $request, Resolution $resolution)
    {
        $this->authorize('designateWithdrawers', $resolution);

        $created = [];
        $userIds = $request->input('user_ids', []);

        foreach ($userIds as $userId) {
            $auth = ResolutionWithdrawalAuthorization::firstOrCreate(
                [
                    'resolution_id' => $resolution->id,
                    'user_id' => $userId,
                ],
                [
                    'designated_by' => auth()->id(),
                    'designated_at' => now(),
                    'revoked_at' => null,
                ]
            );

            // If previously revoked, re-activate
            if ($auth->wasRecentlyCreated === false && $auth->revoked_at !== null) {
                $auth->update([
                    'revoked_at' => null,
                    'revoked_by' => null,
                ]);
            }

            $created[] = $auth->fresh()->load(['user', 'designatedBy']);
        }

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'withdrawal_authorizations_created',
            'module' => 'workflow',
            'description' => "Designated withdrawers for resolution #{$resolution->resolution_number}",
            'context_json' => [
                'resolution_id' => $resolution->id,
                'user_ids' => $userIds,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Authorizations saved.',
            'authorizations' => $created,
        ]);
    }

    public function revoke(Resolution $resolution, ResolutionWithdrawalAuthorization $authorization)
    {
        abort_if($authorization->resolution_id !== $resolution->id, 404);

        $authorization->update([
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
        ]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'withdrawal_authorization_revoked',
            'module' => 'workflow',
            'description' => "Revoked withdrawal authorization for user #{$authorization->user_id}",
            'context_json' => [
                'resolution_id' => $resolution->id,
                'user_id' => $authorization->user_id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return response()->json(['message' => 'Authorization revoked.']);
    }
}