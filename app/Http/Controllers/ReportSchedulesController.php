<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Requests\Reports\ReportScheduleRequest;
use App\Models\PigCycle;
use App\Models\ReportSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportSchedulesController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request): View
    {
        Gate::authorize('manage-report-schedules');

        $schedules = ReportSchedule::query()
            ->with(['cycle:id,batch_code', 'creator:id,name'])
            ->latest()
            ->get();

        $cycles = PigCycle::query()
            ->activeRecords()
            ->orderByDesc('created_at')
            ->get(['id', 'batch_code', 'stage', 'status']);

        return view('reports.schedules.index', [
            'schedules' => $schedules,
            'cycles' => $cycles,
        ]);
    }

    public function store(ReportScheduleRequest $request)
    {
        Gate::authorize('manage-report-schedules');

        $schedule = ReportSchedule::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
            'next_run_at' => $this->nextRunAt($request->validated()),
        ]);

        $this->recordAudit(
            $request,
            'schedule_created',
            "Created {$schedule->report_type} report schedule",
            'reports',
            [
                'schedule_id' => $schedule->id,
                'report_type' => $schedule->report_type,
            ]
        );

        return redirect()->route('reports.schedules.index')->with('status', 'Schedule saved.');
    }

    public function update(ReportScheduleRequest $request, ReportSchedule $schedule)
    {
        Gate::authorize('manage-report-schedules');

        $schedule->update([
            ...$request->validated(),
            'next_run_at' => $this->nextRunAt($request->validated()),
        ]);

        $this->recordAudit(
            $request,
            'schedule_updated',
            "Updated {$schedule->report_type} report schedule",
            'reports',
            [
                'schedule_id' => $schedule->id,
                'report_type' => $schedule->report_type,
            ]
        );

        return redirect()->route('reports.schedules.index')->with('status', 'Schedule updated.');
    }

    public function destroy(Request $request, ReportSchedule $schedule)
    {
        Gate::authorize('manage-report-schedules');

        $schedule->delete();

        $this->recordAudit(
            $request,
            'schedule_deleted',
            "Deleted {$schedule->report_type} report schedule",
            'reports',
            [
                'schedule_id' => $schedule->id,
                'report_type' => $schedule->report_type,
            ]
        );

        return redirect()->route('reports.schedules.index')->with('status', 'Schedule removed.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nextRunAt(array $payload): ?\Illuminate\Support\Carbon
    {
        $runAt = $payload['run_at'] ?? '08:00:00';
        $day = (int) ($payload['day_of_month'] ?? 1);
        $frequency = $payload['frequency'] ?? 'monthly';

        if ($frequency === 'monthly') {
            return now()->addMonthNoOverflow()->setDay($day)->setTimeFromTimeString($runAt);
        }

        if ($frequency === 'quarterly') {
            return now()->addQuarter()->setDay($day)->setTimeFromTimeString($runAt);
        }

        return null;
    }
}
