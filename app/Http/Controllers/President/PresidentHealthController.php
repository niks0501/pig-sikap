<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StoreHealthIncidentFromModuleRequest;
use App\Models\CycleHealthIncident;
use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use App\Services\PigRegistry\CycleSummaryService;
use App\Services\PigRegistry\CycleHealthSummaryService;
use App\Services\PigRegistry\CycleHealthStateProjector;
use App\Services\PigRegistry\RecordHealthIncidentWithOperationalImpactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PresidentHealthController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request, CycleSummaryService $cycleSummaryService): View
    {
        $search = trim((string) $request->query('search', ''));
        $tab = trim((string) $request->query('tab', 'all'));
        $allowedTabs = ['all', 'upcoming', 'overdue', 'completed', 'sick'];
        $terminalStatuses = CycleHealthTask::TERMINAL_STATUSES;

        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'all';
        }

        $tasksQuery = CycleHealthTask::query()->with(['cycle:id,batch_code']);

        if ($search !== '') {
            $tasksQuery->where(function ($query) use ($search): void {
                $query->where('task_name', 'like', "%{$search}%")
                    ->orWhere('task_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('cycle', function ($cycleQuery) use ($search): void {
                        $cycleQuery->where('batch_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($tab === 'upcoming') {
            $tasksQuery
                ->whereDate('planned_start_date', '>=', today())
                ->whereNotIn('status', $terminalStatuses);
        }

        if ($tab === 'completed') {
            $tasksQuery->where('status', 'completed');
        }

        if ($tab === 'overdue') {
            $tasksQuery
                ->whereDate('planned_start_date', '<', today())
                ->whereNotIn('status', $terminalStatuses);
        }

        if ($tab === 'sick') {
            $tasksQuery->whereHas('cycle.healthIncidents', function ($incidentQuery): void {
                $incidentQuery->whereIn('incident_type', ['sick', 'isolated']);
            });
        }

        $tasks = $tasksQuery
            ->orderBy('planned_start_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $needsActionBaseQuery = CycleHealthTask::query()
            ->with(['cycle:id,batch_code'])
            ->whereNotIn('status', $terminalStatuses)
            ->where('task_type', '!=', 'oral_medication_period');

        $needsAction = [
            'overdue' => (clone $needsActionBaseQuery)
                ->whereDate('planned_start_date', '<', today())
                ->orderBy('planned_start_date')
                ->orderBy('id')
                ->limit(3)
                ->get(),
            'due_today' => (clone $needsActionBaseQuery)
                ->whereDate('planned_start_date', today())
                ->orderBy('planned_start_date')
                ->orderBy('id')
                ->limit(3)
                ->get(),
            'upcoming_soon' => (clone $needsActionBaseQuery)
                ->whereDate('planned_start_date', '>', today())
                ->whereDate('planned_start_date', '<=', today()->addDays(7))
                ->orderBy('planned_start_date')
                ->orderBy('id')
                ->limit(3)
                ->get(),
        ];

        $summary = $cycleSummaryService->forHealthDashboard();

        return view('health.index', [
            'tasks' => $tasks,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'tab' => $tab,
            ],
            'needsAction' => $needsAction,
            'terminalStatuses' => $terminalStatuses,
        ]);
    }

    public function schedule(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $terminalStatuses = CycleHealthTask::TERMINAL_STATUSES;

        $baseQuery = CycleHealthTask::query()->with(['cycle:id,batch_code']);

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search): void {
                $query->where('task_name', 'like', "%{$search}%")
                    ->orWhere('task_type', 'like', "%{$search}%")
                    ->orWhereHas('cycle', function ($cycleQuery) use ($search): void {
                        $cycleQuery->where('batch_code', 'like', "%{$search}%");
                    });
            });
        }

        $overdueTasks = (clone $baseQuery)
            ->whereDate('planned_start_date', '<', today())
            ->whereNotIn('status', $terminalStatuses)
            ->orderBy('planned_start_date')
            ->limit(30)
            ->get();

        $dueTodayTasks = (clone $baseQuery)
            ->whereDate('planned_start_date', today())
            ->whereNotIn('status', $terminalStatuses)
            ->orderBy('planned_start_date')
            ->limit(30)
            ->get();

        $upcomingTasks = (clone $baseQuery)
            ->whereDate('planned_start_date', '>', today())
            ->whereDate('planned_start_date', '<=', today()->addDays(14))
            ->whereNotIn('status', $terminalStatuses)
            ->orderBy('planned_start_date')
            ->limit(30)
            ->get();

        return view('health.schedule', [
            'search' => $search,
            'overdueTasks' => $overdueTasks,
            'dueTodayTasks' => $dueTodayTasks,
            'upcomingTasks' => $upcomingTasks,
        ]);
    }

    public function create(Request $request, CycleHealthStateProjector $cycleHealthStateProjector): View
    {
        $cycles = PigCycle::query()
            ->activeRecords()
            ->withCount('pigs')
            ->with([
                'pigs' => fn ($query) => $query
                    ->select(['id', 'batch_id', 'pig_no', 'status'])
                    ->orderBy('pig_no'),
                'healthIncidents' => fn ($query) => $query
                    ->select(['id', 'batch_id', 'incident_type', 'affected_count', 'resolution_target', 'date_reported'])
                    ->orderBy('date_reported')
                    ->orderBy('id'),
            ])
            ->orderByDesc('updated_at')
            ->get(['id', 'batch_code', 'date_of_purchase', 'current_count', 'has_pig_profiles']);

        $cycles->each(function (PigCycle $cycle) use ($cycleHealthStateProjector): void {
            $projected = $cycleHealthStateProjector->projectIncidents($cycle->healthIncidents, (int) $cycle->current_count);
            $activeMetrics = $projected['active'] ?? [];

            $cycle->setAttribute('active_health', [
                'currently_sick' => (int) ($activeMetrics['currently_sick'] ?? 0),
                'currently_isolated' => (int) ($activeMetrics['currently_isolated'] ?? 0),
                'currently_affected' => (int) ($activeMetrics['currently_affected'] ?? 0),
            ]);
        });

        $selectedCycleId = (int) $request->query('cycle_id', 0);

        return view('health.create', [
            'cycles' => $cycles,
            'selectedCycleId' => $selectedCycleId,
            'incidentTypes' => CycleHealthIncident::INCIDENT_TYPES,
        ]);
    }

    public function sick(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = CycleHealthIncident::query()
            ->with(['cycle:id,batch_code'])
            ->whereIn('incident_type', ['sick', 'isolated']);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('suspected_cause', 'like', "%{$search}%")
                    ->orWhere('treatment_given', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('cycle', function ($cycleQuery) use ($search): void {
                        $cycleQuery->where('batch_code', 'like', "%{$search}%");
                    });
            });
        }

        $incidents = $query
            ->orderByDesc('date_reported')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('health.sick', [
            'incidents' => $incidents,
            'search' => $search,
        ]);
    }

    public function storeIncident(
        StoreHealthIncidentFromModuleRequest $request,
        RecordHealthIncidentWithOperationalImpactService $recordHealthIncidentWithOperationalImpactService
    ): RedirectResponse {
        $payload = $request->validated();

        $cycle = PigCycle::query()->findOrFail((int) $payload['cycle_id']);

        $incident = $recordHealthIncidentWithOperationalImpactService->handle(
            $cycle,
            [
                ...Arr::except($payload, ['cycle_id']),
                'source_channel' => 'health_module',
            ],
            $request->user()
        );

        $this->recordAudit(
            $request,
            'health_incident_created_from_module',
            "Created {$incident->incident_type} incident for cycle {$cycle->batch_code} via Health module.",
            'health_monitoring',
            [
                'cycle_id' => $cycle->id,
                'cycle_batch_code' => $cycle->batch_code,
                'incident_id' => $incident->id,
                'event_key' => $incident->event_key,
                'incident_type' => $incident->incident_type,
                'affected_count' => (int) $incident->affected_count,
                'resolution_target' => $incident->resolution_target,
                'resolved_incident_id' => $incident->resolved_incident_id,
                'pig_id' => $incident->pig_id,
                'source_channel' => $incident->source_channel,
                'media_path' => $incident->media_path,
            ]
        );

        return redirect()
            ->route('health.cycles.show', $cycle)
            ->with('status', 'Health incident recorded successfully.');
    }

    public function showCycle(PigCycle $cycle, CycleHealthSummaryService $cycleHealthSummaryService): View
    {
        $cycle->load([
            'healthTemplate:id,name,code',
            'healthTasks' => fn ($query) => $query->orderBy('planned_start_date')->orderBy('id'),
            'healthIncidents' => fn ($query) => $query->orderBy('date_reported')->orderBy('id'),
        ]);

        $timelineItems = $cycle->healthTasks
            ->map(function (CycleHealthTask $task): array {
                $timelineDate = $task->actual_date
                    ?? $task->planned_start_date
                    ?? $task->follow_up_date
                    ?? $task->planned_end_date;

                $timelineDateLabel = $task->actual_date
                    ? 'Actual Date'
                    : ($task->planned_start_date
                        ? 'Planned Start Date'
                        : ($task->follow_up_date ? 'Follow-up Date' : 'Planned End Date'));

                return [
                    'kind' => 'task',
                    'id' => $task->id,
                    'sort_weight' => 10,
                    'timeline_date' => $timelineDate,
                    'timeline_date_label' => $timelineDateLabel,
                    'task' => $task,
                ];
            })
            ->concat($cycle->healthIncidents->map(function (CycleHealthIncident $incident): array {
                return [
                    'kind' => 'incident',
                    'id' => $incident->id,
                    'sort_weight' => 20,
                    'timeline_date' => $incident->date_reported,
                    'timeline_date_label' => 'Incident Reported Date',
                    'incident' => $incident,
                ];
            }))
            ->sortBy(function (array $item): string {
                $timelineDate = $item['timeline_date'] ?? null;

                $dateValue = $timelineDate instanceof \Carbon\CarbonInterface
                    ? $timelineDate->toDateString()
                    : (is_string($timelineDate) && $timelineDate !== '' ? $timelineDate : '9999-12-31');

                return sprintf('%s-%02d-%06d', $dateValue, (int) ($item['sort_weight'] ?? 99), (int) ($item['id'] ?? 0));
            })
            ->values();

        $oralMedicationTask = $cycle->healthTasks->firstWhere('task_type', 'oral_medication_period');

        return view('health.timeline', [
            'id' => $cycle->batch_code,
            'cycle' => $cycle,
            'healthSummary' => $cycleHealthSummaryService->handle($cycle),
            'timelineItems' => $timelineItems,
            'oralMedicationTask' => $oralMedicationTask,
        ]);
    }
}
