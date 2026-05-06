<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardDataController extends Controller
{
    /**
     * Return role-specific dashboard data as JSON.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleSlug = $user->role?->slug;

        return match ($roleSlug) {
            'president', 'system_admin' => $this->presidentData($request),
            'secretary' => $this->secretaryData($request),
            'treasurer' => $this->treasurerData($request),
            'canvasser' => $this->canvasserData($request),
            'caretaker' => $this->caretakerData($request),
            'member' => $this->memberData($request),
            default => $this->memberData($request),
        };
    }

    /**
     * President sees full overview with all KPIs and charts.
     */
    private function presidentData(Request $request): JsonResponse
    {
        // Delegate to the existing OverallDashboardService
        $filters = [
            'cycle_id' => $request->integer('cycle_id') ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
            'pig_status' => $request->string('pig_status')->toString() ?: null,
            'pig_sex' => $request->string('pig_sex')->toString() ?: null,
        ];

        $data = app(\App\Services\Dashboard\OverallDashboardService::class)->getOverview($filters);

        return response()->json($data);
    }

    /**
     * Secretary sees meetings, resolutions, and document status.
     */
    private function secretaryData(Request $request): JsonResponse
    {
        $pendingMeetings = \App\Models\Meeting::whereHas('resolutions', function ($q) {
            $q->whereNotIn('workflow_status', ['dswd_approved']);
        })->count();

        $draftResolutions = \App\Models\Resolution::where('workflow_status', 'draft')->count();
        $pendingSignature = \App\Models\Resolution::whereIn('workflow_status', ['generated', 'printed'])->count();
        $submittedToDswd = \App\Models\Resolution::whereIn('workflow_status', ['dswd_pending', 'dswd_approved'])->count();

        $recentResolutions = \App\Models\Resolution::with('meeting')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'meeting_title' => $r->meeting?->title,
                'workflow_status' => $r->workflow_status,
                'created_at' => $r->created_at?->toISOString(),
            ]);

        $recentMeetings = \App\Models\Meeting::withCount('resolutions')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'resolutions_count' => $m->resolutions_count,
                'created_at' => $m->created_at?->toISOString(),
            ]);

        return response()->json([
            'last_updated' => now()->toISOString(),
            'kpis' => [
                'pending_meetings' => $pendingMeetings,
                'draft_resolutions' => $draftResolutions,
                'pending_signature' => $pendingSignature,
                'submitted_to_dswd' => $submittedToDswd,
            ],
            'recent_resolutions' => $recentResolutions,
            'recent_meetings' => $recentMeetings,
            'user_name' => $request->user()->name,
        ]);
    }

    /**
     * Treasurer sees financial KPIs, expenses, sales, withdrawals.
     */
    private function treasurerData(Request $request): JsonResponse
    {
        $cycleId = $request->integer('cycle_id') ?: null;

        $expenseQuery = \App\Models\PigCycleExpense::query();
        $saleQuery = \App\Models\PigCycleSale::query();

        if ($cycleId) {
            $expenseQuery->where('pig_cycle_id', $cycleId);
            $saleQuery->where('pig_cycle_id', $cycleId);
        }

        $totalExpenses = (clone $expenseQuery)->sum('amount')
            + \App\Models\AssociationExpense::sum('amount');

        $totalSales = (clone $saleQuery)->sum('amount');
        $collectedRevenue = (clone $saleQuery)->where('payment_received', true)->sum('amount');

        $pendingWithdrawals = \App\Models\Withdrawal::whereHas('resolution', function ($q) {
            $q->where('workflow_status', 'dswd_approved');
        })->count();

        $recentExpenses = (clone $expenseQuery)->with('pigCycle:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'amount' => $e->amount,
                'category' => $e->category,
                'cycle_name' => $e->pigCycle?->name,
                'created_at' => $e->created_at?->toISOString(),
            ]);

        $recentSales = (clone $saleQuery)->with('pigCycle:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'amount' => $s->amount,
                'cycle_name' => $s->pigCycle?->name,
                'created_at' => $s->created_at?->toISOString(),
            ]);

        return response()->json([
            'last_updated' => now()->toISOString(),
            'kpis' => [
                'total_expenses' => $totalExpenses,
                'total_sales' => $totalSales,
                'collected_revenue' => $collectedRevenue,
                'net_profit' => $totalSales - $totalExpenses,
                'pending_withdrawals' => $pendingWithdrawals,
            ],
            'recent_expenses' => $recentExpenses,
            'recent_sales' => $recentSales,
            'user_name' => $request->user()->name,
        ]);
    }

    /**
     * Canvasser sees canvass records and supplier info.
     */
    private function canvasserData(Request $request): JsonResponse
    {
        $openCanvasses = \App\Models\Canvass::whereNull('selected_item_id')->count();
        $totalCanvasses = \App\Models\Canvass::count();
        $totalSuppliers = \App\Models\Supplier::count();

        $recentCanvasses = \App\Models\Canvass::with(['resolution:id,title', 'items.supplier:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'resolution_title' => $c->resolution?->title,
                'items_count' => $c->items->count(),
                'has_selected' => $c->selected_item_id !== null,
                'created_at' => $c->created_at?->toISOString(),
            ]);

        return response()->json([
            'last_updated' => now()->toISOString(),
            'kpis' => [
                'open_canvasses' => $openCanvasses,
                'total_canvasses' => $totalCanvasses,
                'total_suppliers' => $totalSuppliers,
            ],
            'recent_canvasses' => $recentCanvasses,
            'user_name' => $request->user()->name,
        ]);
    }

    /**
     * Caretaker sees health data and assigned batch/cycle info.
     */
    private function caretakerData(Request $request): JsonResponse
    {
        $totalPigs = \App\Models\Pig::count();
        $sickPigs = \App\Models\Pig::where('status', 'sick')->count();
        $deceasedPigs = \App\Models\Pig::where('status', 'deceased')->count();
        $healthyPigs = \App\Models\Pig::where('status', 'healthy')->count();

        $upcomingTreatments = \App\Models\CycleHealthTask::where('completed', false)
            ->with('pigCycle:id,name')
            ->latest('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'description' => $t->description,
                'cycle_name' => $t->pigCycle?->name,
                'scheduled_at' => $t->scheduled_at?->toISOString(),
            ]);

        $activeCycles = \App\Models\PigCycle::where('is_archived', false)
            ->withCount('pigs')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'pigs_count' => $c->pigs_count,
                'status' => $c->status,
            ]);

        return response()->json([
            'last_updated' => now()->toISOString(),
            'kpis' => [
                'total_pigs' => $totalPigs,
                'healthy_pigs' => $healthyPigs,
                'sick_pigs' => $sickPigs,
                'deceased_pigs' => $deceasedPigs,
            ],
            'upcoming_treatments' => $upcomingTreatments,
            'active_cycles' => $activeCycles,
            'user_name' => $request->user()->name,
        ]);
    }

    /**
     * Member sees limited view-only association overview.
     */
    private function memberData(Request $request): JsonResponse
    {
        $activeCycles = \App\Models\PigCycle::where('is_archived', false)->count();
        $totalPigs = \App\Models\Pig::count();
        $totalMembers = \App\Models\User::where('is_active', true)->count();

        return response()->json([
            'last_updated' => now()->toISOString(),
            'kpis' => [
                'active_cycles' => $activeCycles,
                'total_pigs' => $totalPigs,
                'total_members' => $totalMembers,
            ],
            'user_name' => $request->user()->name,
        ]);
    }
}
