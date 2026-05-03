<?php

/**
 * WorkflowServicesTest – Unit tests for the workflow service layer
 * covering ResolutionService, ApprovalService, EligibilityService,
 * WithdrawalService, and ReportService.
 */

use App\Models\DswdSubmission;
use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\ResolutionApproval;
use App\Models\User;
use App\Services\Workflow\ApprovalService;
use App\Services\Workflow\EligibilityService;
use App\Services\Workflow\ReportService;
use App\Services\Workflow\ResolutionService;
use App\Services\Workflow\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ── Helpers ──

function makeTestRole(string $slug): int
{
    return \DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeTestUser(string $roleSlug = 'secretary'): User
{
    $role = \DB::table('roles')->where('slug', $roleSlug)->first();

    if (! $role) {
        $roleId = makeTestRole($roleSlug);
    } else {
        $roleId = $role->id;
    }

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

function makeTestMeeting(User $user): Meeting
{
    return Meeting::create([
        'title' => 'Test Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $user->id,
    ]);
}

function makeTestResolution(Meeting $meeting, User $user): Resolution
{
    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Test Resolution',
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $resolution->lineItems()->create([
        'category' => 'Feed',
        'description' => 'Pig grower',
        'quantity' => 10,
        'unit' => 'sack',
        'unit_cost' => 1000,
        'total' => 10000,
        'sort_order' => 0,
    ]);

    return $resolution;
}


// ╔══════════════════════════════════════════════════════════════╗
// ║  ResolutionService                                          ║
// ╚══════════════════════════════════════════════════════════════╝

test('ResolutionService::create builds line items with computed totals', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);

    $service = app(ResolutionService::class);

    $resolution = $service->create([
        'meeting_id' => $meeting->id,
        'title' => 'Service test resolution',
        'line_items' => [
            ['category' => 'Feed', 'description' => 'Grower', 'quantity' => 5, 'unit' => 'sack', 'unit_cost' => 1200],
            ['category' => 'Meds', 'description' => 'Vitamins', 'quantity' => 2, 'unit' => 'bottle', 'unit_cost' => 500],
        ],
    ], $user);

    expect($resolution->lineItems)->toHaveCount(2);
    expect((float) $resolution->lineItems[0]->total)->toBe(6000.00);
    expect((float) $resolution->lineItems[1]->total)->toBe(1000.00);
    expect((float) $resolution->grand_total)->toBe(7000.00);
});

test('ResolutionService::changeStatus transitions status correctly', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);

    $service = app(ResolutionService::class);
    $result = $service->changeStatus($resolution, 'pending_approval', $user);

    expect($result->status)->toBe('pending_approval');
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  ApprovalService                                            ║
// ╚══════════════════════════════════════════════════════════════╝

test('ApprovalService::recordBatch stores multiple approvals', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'pending_approval']);

    $member1 = makeTestUser('treasurer');
    $member2 = makeTestUser('president');

    $service = app(ApprovalService::class);

    $result = $service->recordBatch($resolution, [
        ['user_id' => $member1->id, 'is_approved' => true],
        ['user_id' => $member2->id, 'is_approved' => false, 'rejection_reason' => 'Need more info'],
    ]);

    expect(ResolutionApproval::where('resolution_id', $resolution->id)->count())->toBe(2);

    $approved = ResolutionApproval::where('resolution_id', $resolution->id)
        ->where('user_id', $member1->id)->first();
    expect($approved->is_approved)->toBeTrue();
    expect($approved->approved_at)->not->toBeNull();

    $rejected = ResolutionApproval::where('resolution_id', $resolution->id)
        ->where('user_id', $member2->id)->first();
    expect($rejected->is_approved)->toBeFalse();
    expect($rejected->rejection_reason)->toBe('Need more info');
});

test('ApprovalService auto-advances resolution when threshold met', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'pending_approval']);

    // Only 1 active user → approving them = 100%
    $service = app(ApprovalService::class);
    $service->record($resolution, $user, true);

    $resolution->refresh();
    expect($resolution->status)->toBe('approved');
});

test('ApprovalService does not advance if below threshold', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'pending_approval']);

    // Create more users to dilute the percentage
    User::factory()->count(5)->create(['is_active' => true]);

    // Approve only 1 out of 6+ total = well below 75%
    $service = app(ApprovalService::class);
    $service->record($resolution, $user, true);

    $resolution->refresh();
    expect($resolution->status)->toBe('pending_approval');
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  EligibilityService                                         ║
// ╚══════════════════════════════════════════════════════════════╝

test('EligibilityService::canWithdraw blocks when no DSWD approval', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'dswd_submitted']);

    // Add approval so threshold is met (1/1 = 100%)
    $resolution->approvals()->create([
        'user_id' => $user->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    $service = app(EligibilityService::class);
    $result = $service->canWithdraw($resolution);

    expect($result['eligible'])->toBeFalse();
    expect($result['reasons'])->toContain('DSWD approval has not been received yet.');
});

test('EligibilityService::canWithdraw blocks when approval below threshold', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'dswd_submitted']);

    // DSWD approved
    $resolution->dswdSubmissions()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    // But create many users so approval is below threshold
    User::factory()->count(10)->create(['is_active' => true]);

    $service = app(EligibilityService::class);
    $result = $service->canWithdraw($resolution);

    expect($result['eligible'])->toBeFalse();
    // Should mention the threshold
    expect(implode(' ', $result['reasons']))->toContain('threshold');
});

test('EligibilityService::canWithdraw blocks when no remaining balance', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'dswd_submitted']);

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

    // Withdraw all the balance
    $resolution->withdrawals()->create([
        'requested_by' => $user->id,
        'amount' => $resolution->grand_total,
        'status' => 'completed',
        'requested_at' => now(),
    ]);

    $service = app(EligibilityService::class);
    $result = $service->canWithdraw($resolution);

    expect($result['eligible'])->toBeFalse();
    expect($result['reasons'])->toContain('No remaining balance available for withdrawal.');
});

test('EligibilityService::canWithdraw allows when all conditions met', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);
    $resolution->update(['status' => 'dswd_submitted']);

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

    $service = app(EligibilityService::class);
    $result = $service->canWithdraw($resolution);

    expect($result['eligible'])->toBeTrue();
    expect($result['reasons'])->toBeEmpty();
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  ReportService                                              ║
// ╚══════════════════════════════════════════════════════════════╝

test('ReportService::generate creates a liquidation report', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);

    $withdrawal = $resolution->withdrawals()->create([
        'requested_by' => $user->id,
        'amount' => 5000,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $service = app(ReportService::class);
    $report = $service->generate($withdrawal, $user);

    expect($report->withdrawal_id)->toBe($withdrawal->id);
    expect($report->generated_by)->toBe($user->id);
    expect($report->summary)->toContain('Test Resolution');
    expect($report->finalized_at)->not->toBeNull();
});

test('ReportService::generate uses custom summary when provided', function () {
    $user = makeTestUser();
    $meeting = makeTestMeeting($user);
    $resolution = makeTestResolution($meeting, $user);

    $withdrawal = $resolution->withdrawals()->create([
        'requested_by' => $user->id,
        'amount' => 3000,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $service = app(ReportService::class);
    $report = $service->generate($withdrawal, $user, 'Custom summary text for the report.');

    expect($report->summary)->toBe('Custom summary text for the report.');
});
