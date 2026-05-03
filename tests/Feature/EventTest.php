<?php

/**
 * EventTest – Verifies that workflow events are dispatched at the correct
 * milestones: ResolutionCreated, ResolutionApproved, DswdSubmitted,
 * WithdrawalCreated, and ReportFinalized (REQ-019).
 */

use App\Events\Workflow\DswdSubmitted;
use App\Events\Workflow\ReportFinalized;
use App\Events\Workflow\ResolutionApproved;
use App\Events\Workflow\ResolutionCreated;
use App\Events\Workflow\WithdrawalCreated;
use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\User;
use App\Services\Workflow\ApprovalService;
use App\Services\Workflow\DswdService;
use App\Services\Workflow\ReportService;
use App\Services\Workflow\ResolutionService;
use App\Services\Workflow\WithdrawalService;
use Illuminate\Support\Facades\Event;

// ── Helpers ──

function makeEventRole(string $slug): int
{
    $role = \DB::table('roles')->where('slug', $slug)->first();

    return $role ? $role->id : \DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeEventUser(string $roleSlug = 'secretary'): User
{
    return User::factory()->create([
        'role_id' => makeEventRole($roleSlug),
        'is_active' => true,
    ]);
}

function makeEventMeeting(User $user): Meeting
{
    return Meeting::create([
        'title' => 'Event Test Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $user->id,
    ]);
}

// ── Tests ──

test('ResolutionCreated event fires when a resolution is created', function () {
    Event::fake([ResolutionCreated::class]);

    $user = makeEventUser();
    $meeting = makeEventMeeting($user);

    app(ResolutionService::class)->create([
        'meeting_id' => $meeting->id,
        'title' => 'Test resolution for events',
        'line_items' => [
            ['category' => 'Feed', 'description' => 'Pellets', 'quantity' => 10, 'unit' => 'sack', 'unit_cost' => 1000],
        ],
    ], $user);

    Event::assertDispatched(ResolutionCreated::class, function ($event) {
        return $event->resolution->title === 'Test resolution for events';
    });
});

test('ResolutionApproved event fires when 75% threshold is met', function () {
    Event::fake([ResolutionApproved::class]);

    $user = makeEventUser();
    $meeting = makeEventMeeting($user);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Approval threshold test',
        'status' => 'pending_approval',
        'created_by' => $user->id,
    ]);

    // Only 1 active user → 1/1 = 100% ≥ 75%
    app(ApprovalService::class)->record($resolution, $user, true);

    Event::assertDispatched(ResolutionApproved::class, function ($event) use ($resolution) {
        return $event->resolution->id === $resolution->id
            && $event->approvalPercentage >= 75;
    });
});

test('ResolutionApproved event does NOT fire when below threshold', function () {
    Event::fake([ResolutionApproved::class]);

    $user = makeEventUser();
    $meeting = makeEventMeeting($user);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Below threshold test',
        'status' => 'pending_approval',
        'created_by' => $user->id,
    ]);

    // Create many users to dilute approval percentage
    User::factory()->count(10)->create(['is_active' => true]);

    // Only 1 approved out of 11+ = well below 75%
    app(ApprovalService::class)->record($resolution, $user, true);

    Event::assertNotDispatched(ResolutionApproved::class);
});

test('DswdSubmitted event fires when DSWD status is updated', function () {
    Event::fake([DswdSubmitted::class]);

    $user = makeEventUser();
    $meeting = makeEventMeeting($user);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'DSWD event test',
        'status' => 'approved',
        'created_by' => $user->id,
    ]);

    app(DswdService::class)->submit($resolution, [
        'status' => 'submitted',
        'notes' => 'Submitted for review',
    ], $user);

    Event::assertDispatched(DswdSubmitted::class, function ($event) use ($resolution) {
        return $event->resolution->id === $resolution->id
            && $event->submission->status === 'submitted';
    });
});

test('WithdrawalCreated event fires when a withdrawal is created', function () {
    Event::fake([WithdrawalCreated::class]);

    $user = makeEventUser();
    $meeting = makeEventMeeting($user);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Withdrawal event test',
        'status' => 'dswd_submitted',
        'created_by' => $user->id,
    ]);

    $resolution->lineItems()->create([
        'category' => 'Feed', 'description' => 'Pellets',
        'quantity' => 10, 'unit' => 'sack', 'unit_cost' => 1000, 'total' => 10000,
        'sort_order' => 0,
    ]);

    // Meet all eligibility conditions
    $resolution->dswdSubmissions()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $resolution->approvals()->create([
        'user_id' => $user->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    app(WithdrawalService::class)->createFromResolution($resolution, [
        'amount' => 5000,
    ], $user);

    Event::assertDispatched(WithdrawalCreated::class, function ($event) use ($resolution) {
        return $event->resolution->id === $resolution->id
            && (float) $event->withdrawal->amount === 5000.0;
    });
});

test('ReportFinalized event fires when a liquidation report is generated', function () {
    Event::fake([ReportFinalized::class]);

    $user = makeEventUser();
    $meeting = makeEventMeeting($user);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Report event test',
        'status' => 'withdrawn',
        'created_by' => $user->id,
    ]);

    $withdrawal = $resolution->withdrawals()->create([
        'requested_by' => $user->id,
        'amount' => 5000,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    app(ReportService::class)->generate($withdrawal, $user, 'Test summary');

    Event::assertDispatched(ReportFinalized::class, function ($event) use ($withdrawal) {
        return $event->withdrawal->id === $withdrawal->id;
    });
});
