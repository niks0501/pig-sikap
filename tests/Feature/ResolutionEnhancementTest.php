<?php

/**
 * ResolutionEnhancementTest – Tests for the Meeting Resolutions &
 * Withdrawal workflow enhancements:
 * - 75% denominator uses meeting present attendees
 * - Meeting type default agenda auto-fill
 * - focal_person_name, bank_reference, evidence_file, dswd_approval_date
 * - Liquidation status tracking
 */

use App\Models\Meeting;
use App\Models\MeetingSignatory;
use App\Models\Resolution;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\LiquidationReport;
use App\Services\Workflow\MemberSnapshotService;
use App\Services\Workflow\WorkflowTransitionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ── Helpers (shared conventions from ResolutionWorkflowTest) ──

function erCreateRole(string $name, string $slug): int
{
    return DB::table('roles')->insertGetId([
        'name' => $name,
        'slug' => $slug,
        'description' => "{$name} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function erMakeOfficer(string $roleSlug = 'secretary'): User
{
    $role = DB::table('roles')->where('slug', $roleSlug)->first();
    $roleId = $role ? $role->id : erCreateRole(ucfirst($roleSlug), $roleSlug);

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

function erCreateActiveMember(string $slug = 'member'): User
{
    $role = DB::table('roles')->where('slug', $slug)->first();
    $roleId = $role ? $role->id : erCreateRole(ucfirst($slug), $slug);

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

// ╔══════════════════════════════════════════════════════════════╗
// ║  75% APPROVAL DENOMINATOR = MEETING PRESENT ATTENDEES      ║
// ╚══════════════════════════════════════════════════════════════╝

test('snapshot uses meeting present attendees not all active users', function () {
    $secretary = erMakeOfficer('secretary');
    $president = erMakeOfficer('president');

    // Create 3 additional active members who will NOT attend the meeting
    User::factory()->count(3)->create(['is_active' => true]);

    // Create meeting with only 2 present attendees
    $meeting = Meeting::create([
        'title' => 'Test Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    MeetingSignatory::create(['meeting_id' => $meeting->id, 'user_id' => $secretary->id, 'attendance_status' => 'present']);
    MeetingSignatory::create(['meeting_id' => $meeting->id, 'user_id' => $president->id, 'attendance_status' => 'present']);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Test Resolution',
        'status' => 'draft',
        'created_by' => $secretary->id,
    ]);

    // Take snapshot
    $service = app(MemberSnapshotService::class);
    $snapshot = $service->takeSnapshot($resolution);

    // Denominator should be 2 (present attendees), not 5 (all active users)
    expect($snapshot->eligible_count)->toBe(2);
    // 75% of 2 = ceil(1.5) = 2
    expect($snapshot->required_approvals)->toBe(2);
});

test('snapshot throws when no present attendees', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Empty Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    // No signatories created

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Empty Resolution',
        'status' => 'draft',
        'created_by' => $secretary->id,
    ]);

    $service = app(MemberSnapshotService::class);

    expect(fn () => $service->takeSnapshot($resolution))
        ->toThrow(RuntimeException::class, 'no present attendees');
});

test('approval percentage uses meeting present count as denominator', function () {
    $secretary = erMakeOfficer('secretary');
    $president = erMakeOfficer('president');

    // Create 8 more active members (10 total active) but only 2 present
    User::factory()->count(8)->create(['is_active' => true]);

    $meeting = Meeting::create([
        'title' => 'Approval Test Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    MeetingSignatory::create(['meeting_id' => $meeting->id, 'user_id' => $secretary->id, 'attendance_status' => 'present']);
    MeetingSignatory::create(['meeting_id' => $meeting->id, 'user_id' => $president->id, 'attendance_status' => 'present']);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Approval Test Resolution',
        'status' => 'draft',
        'created_by' => $secretary->id,
    ]);

    // Approve 1 out of 2 present members
    $resolution->approvals()->create([
        'user_id' => $secretary->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    // Fresh load to compute
    $resolution = $resolution->fresh(['meeting.signatories']);

    // 1/2 = 50%, NOT 1/10 = 10%
    expect($resolution->approval_percentage)->toBe(50.0);
    expect($resolution->hasMetApprovalThreshold())->toBeFalse();
    expect($resolution->getMeetingPresentCount())->toBe(2);
});

// ╔══════════════════════════════════════════════════════════════╗
// ║  MEETING TYPE & DEFAULT AGENDA                             ║
// ╚══════════════════════════════════════════════════════════════╝

test('pig production meeting has correct default agenda', function () {
    $secretary = erMakeOfficer('secretary');

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.meetings.store'),
        [
            'title' => 'Pig Production Meeting',
            'date' => now()->subDay()->toDateString(),
            'meeting_type' => 'pig_production',
            'status' => 'draft',
        ]
    );

    $response->assertStatus(201);

    $meeting = Meeting::latest()->first();
    expect($meeting->meeting_type)->toBe('pig_production');
    expect($meeting->agenda_json)->toBeArray();
    expect($meeting->agenda_json)->toContain('Canvassing Assign Person');
    expect($meeting->agenda_json)->toContain('Number of Piglets to Buy');
    expect($meeting->agenda_json)->toContain('Group Policy');
});

test('monthly association meeting has different default agenda', function () {
    $secretary = erMakeOfficer('secretary');

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.meetings.store'),
        [
            'title' => 'Monthly Association Meeting',
            'date' => now()->subDay()->toDateString(),
            'meeting_type' => 'monthly_association',
            'status' => 'draft',
        ]
    );

    $response->assertStatus(201);

    $meeting = Meeting::latest()->first();
    expect($meeting->meeting_type)->toBe('monthly_association');
    expect($meeting->agenda_json)->toContain('Call to Order');
    expect($meeting->agenda_json)->toContain("Treasurer's Report");
    expect($meeting->agenda_json)->toContain('Attendance Review & Penalties');
});

test('custom agenda_json overrides default', function () {
    $secretary = erMakeOfficer('secretary');

    $customAgenda = ['Custom Item 1', 'Custom Item 2'];

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.meetings.store'),
        [
            'title' => 'Custom Agenda Meeting',
            'date' => now()->subDay()->toDateString(),
            'meeting_type' => 'pig_production',
            'agenda_json' => json_encode($customAgenda),
            'status' => 'draft',
        ]
    );

    $response->assertStatus(201);

    $meeting = Meeting::latest()->first();
    expect($meeting->agenda_json)->toBe($customAgenda);
});

test('meeting has structured_agenda accessor', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Accessor Test',
        'date' => now()->subDay()->toDateString(),
        'meeting_type' => 'pig_production',
        'agenda_json' => ['Item A', 'Item B'],
        'status' => 'draft',
        'created_by' => $secretary->id,
    ]);

    expect($meeting->structured_agenda)->toBe(['Item A', 'Item B']);
    expect($meeting->default_agenda)->toContain('Canvassing Assign Person');
});

// ╔══════════════════════════════════════════════════════════════╗
// ║  FOCAL PERSON NAME                                         ║
// ╚══════════════════════════════════════════════════════════════╝

test('resolution stores focal_person_name', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Focal Person Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.store'),
        [
            'meeting_id' => $meeting->id,
            'title' => 'Focal Person Resolution',
            'focal_person_name' => 'Juan Dela Cruz',
        ]
    );

    $response->assertStatus(201);

    $resolution = Resolution::latest()->first();
    expect($resolution->focal_person_name)->toBe('Juan Dela Cruz');
});

// ╔══════════════════════════════════════════════════════════════╗
// ║  WITHDRAWAL BANK REFERENCE & EVIDENCE                      ║
// ╚══════════════════════════════════════════════════════════════╝

test('withdrawal stores bank_reference and evidence_file', function () {
    Storage::fake('public');
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Withdrawal Test Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    // Add secretary as meeting attendee (denominator for 75% threshold)
    MeetingSignatory::create([
        'meeting_id' => $meeting->id,
        'user_id' => $secretary->id,
        'attendance_status' => 'present',
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Withdrawal Test Resolution',
        'status' => 'dswd_submitted',
        'created_by' => $secretary->id,
    ]);

    // Approve the secretary (1/1 = 100%)
    $resolution->approvals()->create([
        'user_id' => $secretary->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    // Set up DSWD approved for eligibility
    $resolution->dswdSubmissions()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'submitted_by' => $secretary->id,
    ]);

    // Add line item for budget
    $resolution->lineItems()->create([
        'category' => 'Feed',
        'description' => 'Test',
        'quantity' => 1,
        'unit' => 'sack',
        'unit_cost' => 5000,
        'total' => 5000,
        'sort_order' => 0,
    ]);

    $evidenceFile = UploadedFile::fake()->create('bank_slip.pdf', 500);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.withdrawals.store', $resolution),
        [
            'amount' => 1000,
            'bank_account' => 'Land Bank 12345',
            'bank_reference' => 'TXN-2024-00123',
            'evidence_file' => $evidenceFile,
        ]
    );

    $response->assertStatus(201);

    $withdrawal = Withdrawal::latest()->first();
    expect($withdrawal->bank_account)->toBe('Land Bank 12345');
    expect($withdrawal->bank_reference)->toBe('TXN-2024-00123');
    expect($withdrawal->evidence_file_path)->not->toBeNull();
    expect($withdrawal->evidenceFileUrl())->not->toBeNull();
});

// ╔══════════════════════════════════════════════════════════════╗
// ║  DSWD APPROVAL DATE                                        ║
// ╚══════════════════════════════════════════════════════════════╝

test('dswd approval date is recorded when approved', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'DSWD Test Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'DSWD Test Resolution',
        'status' => 'approved',
        'created_by' => $secretary->id,
    ]);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.dswd.store', $resolution),
        [
            'status' => 'approved',
            'notes' => 'Approved by DSWD officer',
        ]
    );

    $response->assertStatus(200);

    $submission = $resolution->dswdSubmission()->first();
    expect($submission->status)->toBe('approved');
    expect($submission->dswd_approval_date)->not->toBeNull();
});

// ╔══════════════════════════════════════════════════════════════╗
// ║  LIQUIDATION STATUS TRACKING                                ║
// ╚══════════════════════════════════════════════════════════════╝

test('liquidation report default status is draft', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Liquidation Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Liquidation Resolution',
        'status' => 'dswd_submitted',
        'created_by' => $secretary->id,
    ]);

    $withdrawal = Withdrawal::create([
        'resolution_id' => $resolution->id,
        'requested_by' => $secretary->id,
        'amount' => 1000,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $report = LiquidationReport::create([
        'withdrawal_id' => $withdrawal->id,
        'generated_by' => $secretary->id,
        'summary' => 'Test liquidation',
        'liquidation_status' => 'draft',
    ]);

    expect($report->liquidation_status)->toBe('draft');
    expect($report->liquidation_status_label)->toBe('Draft');
    expect($report->liquidation_status_color)->toBe('gray');
});

test('liquidation status can be updated', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Liquidation Update Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Liquidation Update Resolution',
        'status' => 'dswd_submitted',
        'created_by' => $secretary->id,
    ]);

    $withdrawal = Withdrawal::create([
        'resolution_id' => $resolution->id,
        'requested_by' => $secretary->id,
        'amount' => 1000,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $report = LiquidationReport::create([
        'withdrawal_id' => $withdrawal->id,
        'generated_by' => $secretary->id,
        'summary' => 'Test',
        'liquidation_status' => 'draft',
    ]);

    $report->update(['liquidation_status' => 'approved']);

    expect($report->liquidation_status)->toBe('approved');
    expect($report->liquidation_status_label)->toBe('Approved');
    expect($report->liquidation_status_color)->toBe('emerald');
});

test('liquidation report has valid statuses constant', function () {
    expect(LiquidationReport::STATUSES)->toBe([
        'draft', 'submitted', 'reviewed', 'approved', 'returned',
    ]);
});

// ╔══════════════════════════════════════════════════════════════╗
// ║  WORKFLOW TRANSITION SERVICE                                ║
// ╚══════════════════════════════════════════════════════════════╝

test('workflow can transition to withdrawal_ready', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Transition Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Transition Resolution',
        'status' => 'dswd_submitted',
        'workflow_status' => 'dswd_approved',
        'created_by' => $secretary->id,
    ]);

    $service = app(WorkflowTransitionService::class);
    $service->transitionToWithdrawalReady($resolution);

    $resolution = $resolution->fresh();
    expect($resolution->workflow_status)->toBe('withdrawal_ready');
});

test('workflow can transition to archived', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Archive Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Archive Resolution',
        'status' => 'finalized',
        'workflow_status' => 'withdrawn',
        'created_by' => $secretary->id,
    ]);

    $service = app(WorkflowTransitionService::class);
    $service->transitionToArchived($resolution);

    $resolution = $resolution->fresh();
    expect($resolution->workflow_status)->toBe('archived');
});

test('transition to withdrawal_ready fails if not dswd_approved', function () {
    $secretary = erMakeOfficer('secretary');

    $meeting = Meeting::create([
        'title' => 'Bad Transition Meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Bad Transition',
        'status' => 'draft',
        'workflow_status' => 'draft',
        'created_by' => $secretary->id,
    ]);

    $service = app(WorkflowTransitionService::class);

    expect(fn () => $service->transitionToWithdrawalReady($resolution))
        ->toThrow(RuntimeException::class);
});

test('workflow transition map includes full workflow order', function () {
    $service = app(WorkflowTransitionService::class);
    $transitions = $service->getTransitions();

    // Verify critical transitions exist
    expect($transitions)->toHaveKey('draft');
    expect($transitions['member_approved'])->toContain('dswd_pending');
    expect($transitions['dswd_pending'])->toContain('dswd_approved');
    expect($transitions['dswd_approved'])->toContain('withdrawal_ready');
    expect($transitions['dswd_approved'])->toContain('withdrawn');
    expect($transitions['withdrawal_ready'])->toContain('withdrawn');
    expect($transitions['withdrawn'])->toContain('archived');
});
