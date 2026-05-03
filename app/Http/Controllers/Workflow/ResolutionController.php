<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreApprovalRequest;
use App\Http\Requests\Workflow\StoreResolutionRequest;
use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\User;
use App\Services\Workflow\ApprovalService;
use App\Services\Workflow\EligibilityService;
use App\Services\Workflow\ResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages resolutions and member approvals.
 */
class ResolutionController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly ResolutionService $resolutionService,
        private readonly ApprovalService $approvalService,
        private readonly EligibilityService $eligibilityService
    ) {}

    /**
     * List all resolutions.
     */
    public function index(Request $request): View|JsonResponse
    {
        $resolutions = Resolution::query()
            ->with(['meeting:id,title,date', 'creator:id,name'])
            ->withCount('approvals')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $resolutions->items(),
                'meta' => [
                    'current_page' => $resolutions->currentPage(),
                    'last_page' => $resolutions->lastPage(),
                    'per_page' => $resolutions->perPage(),
                    'total' => $resolutions->total(),
                ],
            ]);
        }

        return view('workflow.resolutions-index', [
            'resolutions' => $resolutions,
        ]);
    }

    /**
     * Show form to create a resolution from a meeting.
     */
    public function create(Request $request): View
    {
        $meetingId = $request->query('meeting_id');
        $meeting = $meetingId ? Meeting::with('signatories.user')->find($meetingId) : null;

        $meetings = Meeting::where('status', 'confirmed')
            ->latest('date')
            ->get(['id', 'title', 'date']);

        return view('workflow.resolutions-create', [
            'meeting' => $meeting,
            'meetings' => $meetings,
        ]);
    }

    /**
     * Store a new resolution.
     */
    public function store(StoreResolutionRequest $request): RedirectResponse|JsonResponse
    {
        $resolution = $this->resolutionService->create(
            $request->validated(),
            $request->user()
        );

        $this->recordAudit(
            $request,
            'resolution_created',
            "Created resolution: {$resolution->title}",
            'workflow',
            ['resolution_id' => $resolution->id, 'meeting_id' => $resolution->meeting_id]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Resolution created successfully.',
                'resolution' => $resolution,
                'redirect_url' => route('workflow.resolutions.show', $resolution),
            ], 201);
        }

        return redirect()
            ->route('workflow.resolutions.show', $resolution)
            ->with('status', 'Resolution created successfully.');
    }

    /**
     * Show resolution details with approval status.
     */
    public function show(Resolution $resolution): View
    {
        $resolution->load([
            'meeting:id,title,date',
            'creator:id,name',
            'lineItems',
            'approvals.user:id,name,role_id',
            'approvals.user.role:id,name,slug',
            'dswdSubmission',
            'withdrawals.requester:id,name',
            'withdrawals.liquidationReport',
            'documentVersions.generatedBy:id,name',
        ]);

        $eligibility = $this->eligibilityService->canWithdraw($resolution);
        $totalMembers = User::where('is_active', true)->count();

        return view('workflow.resolutions-show', [
            'resolution' => $resolution,
            'eligibility' => $eligibility,
            'totalMembers' => $totalMembers,
        ]);
    }

    /**
     * Record member approvals.
     */
    public function recordApprovals(StoreApprovalRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        // Auto-advance to pending_approval if still draft
        if ($resolution->status === 'draft') {
            $this->resolutionService->changeStatus($resolution, 'pending_approval', $request->user());
        }

        $resolution = $this->approvalService->recordBatch(
            $resolution,
            $request->validated()['approvals']
        );

        $this->recordAudit(
            $request,
            'approvals_recorded',
            "Recorded approvals for resolution #{$resolution->id}",
            'workflow',
            [
                'resolution_id' => $resolution->id,
                'approval_percentage' => $resolution->approval_percentage,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Approvals recorded successfully.',
                'resolution' => $resolution->fresh(['approvals.user']),
                'approval_percentage' => $resolution->approval_percentage,
            ]);
        }

        return redirect()
            ->route('workflow.resolutions.show', $resolution)
            ->with('status', 'Member approvals recorded successfully.');
    }

    /**
     * Get approval data for a resolution (API).
     */
    public function approvalData(Resolution $resolution): JsonResponse
    {
        $resolution->load('approvals.user:id,name,role_id');

        $members = User::where('is_active', true)
            ->with('role:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        $totalMembers = $members->count();
        $approvedCount = $resolution->approvals->where('is_approved', true)->count();

        return response()->json([
            'members' => $members,
            'approvals' => $resolution->approvals,
            'total_members' => $totalMembers,
            'approved_count' => $approvedCount,
            'approval_percentage' => $totalMembers > 0 ? round(($approvedCount / $totalMembers) * 100, 1) : 0,
            'threshold' => Resolution::APPROVAL_THRESHOLD,
            'has_met_threshold' => $resolution->hasMetApprovalThreshold(),
        ]);
    }
}
