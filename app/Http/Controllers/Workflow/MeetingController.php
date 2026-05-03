<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreMeetingRequest;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Workflow\MeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages meeting minutes – CRUD and JSON API for Vue components.
 */
class MeetingController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly MeetingService $meetingService
    ) {}

    /**
     * Display paginated meetings list.
     */
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $meetings = Meeting::query()
            ->with(['creator:id,name', 'signatories'])
            ->withCount('resolutions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('agenda', 'like', "%{$search}%");
                });
            })
            ->latest('date')
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $meetings->items(),
                'meta' => [
                    'current_page' => $meetings->currentPage(),
                    'last_page' => $meetings->lastPage(),
                    'per_page' => $meetings->perPage(),
                    'total' => $meetings->total(),
                ],
            ]);
        }

        return view('workflow.meetings-index', [
            'meetings' => $meetings,
            'search' => $search,
        ]);
    }

    /**
     * Show form to create a new meeting.
     */
    public function create(): View
    {
        $members = User::where('is_active', true)
            ->with('role:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        return view('workflow.meetings-create', [
            'members' => $members,
        ]);
    }

    /**
     * Store a new meeting.
     */
    public function store(StoreMeetingRequest $request): RedirectResponse|JsonResponse
    {
        $meeting = $this->meetingService->create($request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'meeting_created',
            "Created meeting: {$meeting->title}",
            'workflow',
            ['meeting_id' => $meeting->id]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Meeting created successfully.',
                'meeting' => $meeting,
                'redirect_url' => route('workflow.meetings.show', $meeting),
            ], 201);
        }

        return redirect()
            ->route('workflow.meetings.show', $meeting)
            ->with('status', 'Meeting minutes recorded successfully.');
    }

    /**
     * Show meeting details.
     */
    public function show(Meeting $meeting): View
    {
        $meeting->load([
            'creator:id,name',
            'signatories.user:id,name,role_id',
            'signatories.user.role:id,name,slug',
            'resolutions' => fn ($q) => $q->withCount('approvals'),
        ]);

        return view('workflow.meetings-show', [
            'meeting' => $meeting,
        ]);
    }

    /**
     * Update meeting details.
     */
    public function update(StoreMeetingRequest $request, Meeting $meeting): RedirectResponse|JsonResponse
    {
        $meeting = $this->meetingService->update($meeting, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'meeting_updated',
            "Updated meeting: {$meeting->title}",
            'workflow',
            ['meeting_id' => $meeting->id]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Meeting updated successfully.',
                'meeting' => $meeting,
            ]);
        }

        return redirect()
            ->route('workflow.meetings.show', $meeting)
            ->with('status', 'Meeting updated successfully.');
    }
}
