<?php

use App\Models\CycleHealthIncident;
use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use App\Models\Role;
use App\Models\AuditTrail;
use App\Models\User;
use App\Services\PigRegistry\CycleHealthPlanGenerator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function healthUserByRole(string $roleSlug, array $overrides = []): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
        ...$overrides,
    ]);
}

function healthPresident(array $overrides = []): User
{
    return healthUserByRole('president', $overrides);
}

function healthSecretary(array $overrides = []): User
{
    return healthUserByRole('secretary', $overrides);
}

function healthSystemAdmin(array $overrides = []): User
{
    return healthUserByRole('system_admin', $overrides);
}

function makeHealthCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'HC-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(20)->toDateString(),
        'initial_count' => 12,
        'current_count' => 12,
        'average_weight' => 9.50,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Health module test cycle',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('president can view dynamic health pages', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    actingAs($president)->get(route('health.index'))->assertOk();
    actingAs($president)->get(route('health.schedule'))->assertOk();
    actingAs($president)->get(route('health.create'))->assertOk();
    actingAs($president)->get(route('health.sick'))->assertOk();
    actingAs($president)->get(route('health.cycles.show', $cycle))->assertOk();
});

test('health index supports sick card filtering for incident related cycles', function () {
    $president = healthPresident();
    $cycleWithIncident = makeHealthCycle($president, ['batch_code' => 'HC-SICK-101']);
    $cycleWithoutIncident = makeHealthCycle($president, ['batch_code' => 'HC-OK-102']);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycleWithIncident);
    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycleWithoutIncident);

    $cycleWithIncident->healthIncidents()->create([
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'remarks' => 'Cycle has active sick case',
        'reported_by' => $president->id,
    ]);

    $response = actingAs($president)->get(route('health.index', ['tab' => 'sick']));

    $response->assertOk();
    $response->assertSee('HC-SICK-101');
    $response->assertDontSee('HC-OK-102');
});

test('health index exposes needs action groups', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    $tasks = $cycle->healthTasks()
        ->where('task_type', '!=', 'oral_medication_period')
        ->orderBy('id')
        ->take(3)
        ->get()
        ->values();

    expect($tasks)->toHaveCount(3);

    $tasks[0]->update([
        'planned_start_date' => today()->subDay()->toDateString(),
        'status' => 'pending',
    ]);

    $tasks[1]->update([
        'planned_start_date' => today()->toDateString(),
        'status' => 'pending',
    ]);

    $tasks[2]->update([
        'planned_start_date' => today()->addDays(3)->toDateString(),
        'status' => 'pending',
    ]);

    $response = actingAs($president)->get(route('health.index'));

    $response->assertOk();
    $response->assertSee('Needs Action');
    $response->assertViewHas('needsAction.overdue', fn ($items) => $items->isNotEmpty());
    $response->assertViewHas('needsAction.due_today', fn ($items) => $items->isNotEmpty());
    $response->assertViewHas('needsAction.upcoming_soon', fn ($items) => $items->isNotEmpty());
});

test('task update and undo from health index redirects back to list page', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $indexUrl = route('health.index', ['tab' => 'overdue']);

    $updateResponse = actingAs($president)
        ->from($indexUrl)
        ->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
            'action' => 'complete_all',
            'actual_date' => now()->toDateString(),
        ]);

    $updateResponse->assertRedirect($indexUrl);
    $updateResponse->assertSessionHas('undo_task');

    $undoTask = session('undo_task');
    $undoToken = is_array($undoTask) ? ($undoTask['token'] ?? null) : null;

    expect($undoToken)->not->toBeNull();

    $undoResponse = actingAs($president)
        ->from($indexUrl)
        ->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
            'undo_token' => $undoToken,
        ]);

    $undoResponse->assertRedirect($indexUrl);
});

test('task update ignores referers that only match by host and not by configured app url', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $appUrl = parse_url((string) config('app.url')) ?: [];
    $refererScheme = (($appUrl['scheme'] ?? 'http') === 'https') ? 'http' : 'https';
    $refererHost = (string) ($appUrl['host'] ?? 'localhost');
    $referer = $refererScheme.'://'.$refererHost.'/health/cycles';

    $response = actingAs($president)
        ->from($referer)
        ->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
            'action' => 'complete_all',
            'actual_date' => now()->toDateString(),
        ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));
    $response->assertSessionHas('undo_task');
});

test('president can update cycle health task and action is logged for health module', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $response = actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'partial',
        'completed_count' => 5,
        'actual_date' => now()->toDateString(),
        'follow_up_date' => now()->addDays(2)->toDateString(),
        'remarks' => 'Partial treatment done.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));

    assertDatabaseHas('cycle_health_tasks', [
        'id' => $task->id,
        'status' => 'partially_completed',
        'completed_count' => 5,
        'remaining_count' => 7,
    ]);

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_task_updated',
        'module' => 'health_monitoring',
    ]);

    $audit = AuditTrail::query()
        ->where('action', 'cycle_health_task_updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect((int) ($audit?->context_json['cycle_id'] ?? 0))->toBe($cycle->id);
    expect((int) ($audit?->context_json['task_id'] ?? 0))->toBe($task->id);
    expect((string) ($audit?->context_json['requested_action'] ?? ''))->toBe('partial');
    expect((string) ($audit?->context_json['after_status'] ?? ''))->toBe('partially_completed');
});

test('president can undo accidental complete all task action', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $beforeStatus = (string) $task->status;
    $beforeCompletedCount = (int) $task->completed_count;
    $beforeRemainingCount = (int) $task->remaining_count;
    $beforeActualDate = $task->actual_date;

    $response = actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'complete_all',
        'actual_date' => now()->toDateString(),
        'remarks' => 'Accidental complete all action.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));
    $response->assertSessionHas('undo_task');

    $task->refresh();

    expect($task->status)->toBe('completed');

    $undoTask = session('undo_task');
    $undoToken = is_array($undoTask) ? ($undoTask['token'] ?? null) : null;

    expect($undoToken)->not->toBeNull();

    $undoResponse = actingAs($president)->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
        'undo_token' => $undoToken,
    ]);

    $undoResponse->assertRedirect(route('health.cycles.show', $cycle));

    $task->refresh();

    expect((string) $task->status)->toBe($beforeStatus);
    expect((int) $task->completed_count)->toBe($beforeCompletedCount);
    expect((int) $task->remaining_count)->toBe($beforeRemainingCount);
    expect((string) $task->actual_date)->toBe((string) $beforeActualDate);

    $repeatUndoResponse = actingAs($president)->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
        'undo_token' => $undoToken,
    ]);

    $repeatUndoResponse->assertRedirect(route('health.cycles.show', $cycle));
    $repeatUndoResponse->assertSessionHasErrors('undo');
    expect((string) session('errors')->first('undo'))->toContain('already been used');

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_task_update_undone',
        'module' => 'health_monitoring',
    ]);
});

test('president can undo partial task action', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $beforeStatus = (string) $task->status;
    $beforeCompletedCount = (int) $task->completed_count;
    $beforeRemainingCount = (int) $task->remaining_count;
    $beforeActualDate = $task->actual_date;
    $beforeFollowUpDate = $task->follow_up_date;

    $response = actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'partial',
        'completed_count' => 4,
        'actual_date' => now()->toDateString(),
        'follow_up_date' => now()->addDays(2)->toDateString(),
        'remarks' => 'Partial treatment before correction.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));
    $response->assertSessionHas('undo_task');
    $response->assertSessionHas('undo_task.message', 'Task marked as Partially Completed. Undo?');

    $task->refresh();

    expect($task->status)->toBe('partially_completed');

    $undoTask = session('undo_task');
    $undoToken = is_array($undoTask) ? ($undoTask['token'] ?? null) : null;

    expect($undoToken)->not->toBeNull();

    $undoResponse = actingAs($president)->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
        'undo_token' => $undoToken,
    ]);

    $undoResponse->assertRedirect(route('health.cycles.show', $cycle));

    $task->refresh();

    expect((string) $task->status)->toBe($beforeStatus);
    expect((int) $task->completed_count)->toBe($beforeCompletedCount);
    expect((int) $task->remaining_count)->toBe($beforeRemainingCount);
    expect((string) $task->actual_date)->toBe((string) $beforeActualDate);
    expect((string) $task->follow_up_date)->toBe((string) $beforeFollowUpDate);

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_task_update_undone',
        'module' => 'health_monitoring',
    ]);
});

test('president can undo reschedule task action', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'injectable')->firstOrFail();

    $beforeStatus = (string) $task->status;
    $beforePlannedStartDate = $task->planned_start_date;
    $beforeFollowUpDate = $task->follow_up_date;

    $response = actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'reschedule',
        'planned_start_date' => now()->addDays(6)->toDateString(),
        'follow_up_date' => now()->addDays(7)->toDateString(),
        'remarks' => 'Rescheduled due to weather disruption.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));
    $response->assertSessionHas('undo_task');
    $response->assertSessionHas('undo_task.message', 'Task marked as Rescheduled. Undo?');

    $task->refresh();

    expect($task->status)->toBe('rescheduled');

    $undoTask = session('undo_task');
    $undoToken = is_array($undoTask) ? ($undoTask['token'] ?? null) : null;

    expect($undoToken)->not->toBeNull();

    $undoResponse = actingAs($president)->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
        'undo_token' => $undoToken,
    ]);

    $undoResponse->assertRedirect(route('health.cycles.show', $cycle));

    $task->refresh();

    expect((string) $task->status)->toBe($beforeStatus);
    expect((string) $task->planned_start_date)->toBe((string) $beforePlannedStartDate);
    expect((string) $task->follow_up_date)->toBe((string) $beforeFollowUpDate);

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_task_update_undone',
        'module' => 'health_monitoring',
    ]);
});

test('president can undo skip task action', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'maintenance_optional')->firstOrFail();

    $beforeStatus = (string) $task->status;
    $beforeRemarks = $task->remarks;

    $response = actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'skip',
        'remarks' => 'Skipped this optional maintenance.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));
    $response->assertSessionHas('undo_task');
    $response->assertSessionHas('undo_task.message', 'Task marked as Skipped. Undo?');

    $task->refresh();

    expect($task->status)->toBe('skipped');

    $undoTask = session('undo_task');
    $undoToken = is_array($undoTask) ? ($undoTask['token'] ?? null) : null;

    expect($undoToken)->not->toBeNull();

    $undoResponse = actingAs($president)->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
        'undo_token' => $undoToken,
    ]);

    $undoResponse->assertRedirect(route('health.cycles.show', $cycle));

    $task->refresh();

    expect((string) $task->status)->toBe($beforeStatus);
    expect((string) $task->remarks)->toBe((string) $beforeRemarks);

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_task_update_undone',
        'module' => 'health_monitoring',
    ]);
});

test('president can undo not applicable task action', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->where('task_type', 'maintenance_optional')->firstOrFail();

    $beforeStatus = (string) $task->status;
    $beforeRemarks = $task->remarks;

    $response = actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'not_applicable',
        'remarks' => 'Not applicable for this cycle week.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));
    $response->assertSessionHas('undo_task');
    $response->assertSessionHas('undo_task.message', 'Task marked as Not Applicable. Undo?');

    $task->refresh();

    expect($task->status)->toBe('not_applicable');

    $undoTask = session('undo_task');
    $undoToken = is_array($undoTask) ? ($undoTask['token'] ?? null) : null;

    expect($undoToken)->not->toBeNull();

    $undoResponse = actingAs($president)->patch(route('health.cycles.tasks.undo', [$cycle, $task]), [
        'undo_token' => $undoToken,
    ]);

    $undoResponse->assertRedirect(route('health.cycles.show', $cycle));

    $task->refresh();

    expect((string) $task->status)->toBe($beforeStatus);
    expect((string) $task->remarks)->toBe((string) $beforeRemarks);

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_task_update_undone',
        'module' => 'health_monitoring',
    ]);
});

test('president can record deceased incident and cycle count is auto adjusted', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president, [
        'initial_count' => 10,
        'current_count' => 10,
    ]);

    Storage::fake('public');

    $response = actingAs($president)->post(route('health.cycles.incidents.store', $cycle), [
        'event_key' => fake()->uuid(),
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'media' => UploadedFile::fake()->image('timeline-deceased.jpg', 1400, 1000),
        'suspected_cause' => 'Respiratory issue',
        'treatment_given' => 'Observation only',
        'remarks' => 'Marked and removed from active count.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));

    $cycle->refresh();

    expect($cycle->current_count)->toBe(8);

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'incident_type' => 'deceased',
        'affected_count' => 2,
    ]);

    $incident = CycleHealthIncident::query()
        ->where('batch_id', $cycle->id)
        ->where('incident_type', 'deceased')
        ->latest('id')
        ->firstOrFail();

    expect((string) $incident->media_path)->toStartWith('uploads/');
    expect(Storage::disk('public')->exists((string) $incident->media_path))->toBeTrue();

    assertDatabaseHas('pig_cycle_adjustments', [
        'batch_id' => $cycle->id,
        'reason' => 'mortality',
        'quantity_after' => 8,
    ]);

    assertDatabaseHas('audit_trails', [
        'action' => 'cycle_health_incident_recorded',
        'module' => 'health_monitoring',
    ]);
});

test('president can record incident from health module form route', function () {
    $president = healthPresident();
    $cycle = makeHealthCycle($president);

    Storage::fake('public');

    $response = actingAs($president)->post(route('health.incidents.store'), [
        'cycle_id' => $cycle->id,
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 3,
        'media' => UploadedFile::fake()->image('module-sick.jpg', 1200, 900),
        'suspected_cause' => 'Feed change stress',
        'treatment_given' => 'Vitamins and hydration',
        'remarks' => 'Group isolated and marked yellow.',
    ]);

    $response->assertRedirect(route('health.cycles.show', $cycle));

    assertDatabaseHas('cycle_health_incidents', [
        'batch_id' => $cycle->id,
        'incident_type' => 'sick',
        'affected_count' => 3,
    ]);

    assertDatabaseHas('audit_trails', [
        'action' => 'health_incident_created_from_module',
        'module' => 'health_monitoring',
    ]);

    $incident = CycleHealthIncident::query()
        ->where('batch_id', $cycle->id)
        ->where('incident_type', 'sick')
        ->latest('id')
        ->firstOrFail();

    expect((string) $incident->media_path)->toStartWith('uploads/');
    expect(Storage::disk('public')->exists((string) $incident->media_path))->toBeTrue();

    $audit = AuditTrail::query()
        ->where('action', 'health_incident_created_from_module')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect((int) ($audit?->context_json['cycle_id'] ?? 0))->toBe($cycle->id);
    expect((string) ($audit?->context_json['incident_type'] ?? ''))->toBe('sick');
    expect((int) ($audit?->context_json['affected_count'] ?? 0))->toBe(3);
    expect((string) ($audit?->context_json['source_channel'] ?? ''))->toBe('health_module');
    expect((string) ($audit?->context_json['media_path'] ?? ''))->toStartWith('uploads/');
});

test('non president cannot access or submit health module actions', function () {
    $president = healthPresident();
    $secretary = healthSecretary();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->firstOrFail();

    actingAs($secretary)->get(route('health.index'))->assertForbidden();
    actingAs($secretary)->get(route('health.schedule'))->assertForbidden();
    actingAs($secretary)->get(route('health.create'))->assertForbidden();
    actingAs($secretary)->get(route('health.sick'))->assertForbidden();

    actingAs($secretary)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'complete_all',
    ])->assertForbidden();

    actingAs($secretary)->post(route('health.cycles.incidents.store', $cycle), [
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
    ])->assertForbidden();
});

test('system admin dashboard shows health monitoring audit entries', function () {
    $president = healthPresident();
    $admin = healthSystemAdmin();
    $cycle = makeHealthCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->firstOrFail();

    actingAs($president)->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
        'action' => 'complete_all',
        'actual_date' => now()->toDateString(),
    ])->assertRedirect(route('health.cycles.show', $cycle));

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('health_monitoring')
        ->assertSee('cycle_health_task_updated');
});
