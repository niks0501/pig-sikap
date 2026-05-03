<?php

/**
 * ResolutionWorkflowTest – End-to-end feature tests for the
 * Resolution-to-Withdrawal workflow covering meeting creation,
 * resolution creation, approvals, DSWD, withdrawal, and reports.
 *
 * Uses Pest with RefreshDatabase (auto-applied via Pest.php).
 */

use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ── Helpers ──────────────────────────────────────────────

/**
 * Create a Role row and return its id.
 */
function createRole(string $name, string $slug): int
{
    return \DB::table('roles')->insertGetId([
        'name' => $name,
        'slug' => $slug,
        'description' => "{$name} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Create an active user with the given role slug.
 * No static caching – RefreshDatabase wipes roles between tests.
 */
function makeOfficer(string $roleSlug = 'secretary'): User
{
    $role = \DB::table('roles')->where('slug', $roleSlug)->first();

    if (! $role) {
        $roleId = createRole(ucfirst($roleSlug), $roleSlug);
    } else {
        $roleId = $role->id;
    }

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

/**
 * Create multiple active members so the approval % can be computed.
 */
function createActiveMembers(int $count = 10): \Illuminate\Support\Collection
{
    $role = \DB::table('roles')->where('slug', 'member')->first();
    $roleId = $role ? $role->id : createRole('Member', 'member');

    return User::factory()->count($count)->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

/**
 * Seed a confirmed meeting via the API and return it.
 */
function seedMeeting(User $officer): Meeting
{
    return Meeting::create([
        'title' => 'Monthly Meeting – ' . fake()->monthName(),
        'date' => now()->subDay()->toDateString(),
        'location' => 'Barangay Hall',
        'agenda' => 'Discuss feed procurement',
        'minutes_summary' => 'All agreed to buy 50 sacks of feed.',
        'status' => 'confirmed',
        'created_by' => $officer->id,
    ]);
}

/**
 * Seed a resolution with line-items.
 */
function seedResolution(Meeting $meeting, User $officer): Resolution
{
    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Buy 50 sacks of feeds',
        'description' => 'For the current pig cycle',
        'status' => 'draft',
        'created_by' => $officer->id,
    ]);

    $resolution->lineItems()->create([
        'category' => 'Feed',
        'description' => 'Pig grower pellets',
        'quantity' => 50,
        'unit' => 'sack',
        'unit_cost' => 1200,
        'total' => 60000,
        'sort_order' => 0,
    ]);

    return $resolution;
}


// ╔══════════════════════════════════════════════════════════════╗
// ║  MEETING TESTS                                              ║
// ╚══════════════════════════════════════════════════════════════╝

test('secretary can create a meeting with attendees', function () {
    $secretary = makeOfficer('secretary');
    $member = makeOfficer('treasurer'); // another user to attach as attendee

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.meetings.store'),
        [
            'title' => 'Monthly Meeting – May 2026',
            'date' => now()->subDay()->toDateString(),
            'location' => 'Barangay Hall',
            'agenda' => 'Feed procurement discussion',
            'status' => 'confirmed',
            'attendees' => [
                ['user_id' => $secretary->id, 'attendance_status' => 'present'],
                ['user_id' => $member->id, 'attendance_status' => 'present'],
            ],
        ]
    );

    $response->assertStatus(201);
    $response->assertJsonPath('message', 'Meeting created successfully.');

    $this->assertDatabaseHas('meetings', ['title' => 'Monthly Meeting – May 2026']);
    $this->assertDatabaseCount('meeting_signatories', 2);
});

test('secretary can upload a minutes file when creating a meeting', function () {
    Storage::fake('public');
    $secretary = makeOfficer('secretary');

    $file = UploadedFile::fake()->create('minutes.pdf', 500, 'application/pdf');

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.meetings.store'),
        [
            'title' => 'Meeting with file upload',
            'date' => now()->subDay()->toDateString(),
            'status' => 'draft',
            'minutes_file' => $file,
        ]
    );

    $response->assertStatus(201);

    $meeting = Meeting::latest()->first();
    expect($meeting->minutes_file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($meeting->minutes_file_path);
});

test('meeting index page loads and shows meetings', function () {
    $secretary = makeOfficer('secretary');
    seedMeeting($secretary);

    $response = $this->actingAs($secretary)->get(route('workflow.meetings.index'));
    $response->assertStatus(200);
    $response->assertViewHas('meetings');
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  RESOLUTION TESTS                                           ║
// ╚══════════════════════════════════════════════════════════════╝

test('resolution can be created from a meeting with line items', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.store'),
        [
            'meeting_id' => $meeting->id,
            'title' => 'Buy feeds for pig cycle',
            'description' => 'Bulk purchase of grower pellets',
            'line_items' => [
                [
                    'category' => 'Feed',
                    'description' => 'Grower pellets',
                    'quantity' => 50,
                    'unit' => 'sack',
                    'unit_cost' => 1200,
                ],
                [
                    'category' => 'Vitamins',
                    'description' => 'Multi-vitamin supplement',
                    'quantity' => 10,
                    'unit' => 'bottle',
                    'unit_cost' => 350,
                ],
            ],
        ]
    );

    $response->assertStatus(201);

    $resolution = Resolution::latest()->first();
    expect($resolution->title)->toBe('Buy feeds for pig cycle');
    expect($resolution->lineItems)->toHaveCount(2);
    expect((float) $resolution->grand_total)->toBe(63500.00); // 50*1200 + 10*350
});

test('resolution auto-fills title from meeting when created via meeting link', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);

    $resolution = app(\App\Services\Workflow\ResolutionService::class)
        ->createFromMeeting($meeting, $secretary);

    expect($resolution->title)->toContain($meeting->title);
    expect($resolution->meeting_id)->toBe($meeting->id);
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  APPROVAL TESTS (75% threshold)                             ║
// ╚══════════════════════════════════════════════════════════════╝

test('recording approvals updates percentage correctly', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'pending_approval']);

    // Create 10 active members (+ the secretary = 11 total active)
    $members = createActiveMembers(10);

    // Approve 8 out of 11 = ~72.7% (still below 75%)
    $approvals = $members->take(8)->map(fn ($m) => [
        'user_id' => $m->id,
        'is_approved' => true,
    ])->toArray();

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.approvals.store', $resolution),
        ['approvals' => $approvals]
    );

    $response->assertOk();

    $resolution->refresh();
    // 8 approved out of 11 active = 72.7%, status should still be pending_approval
    expect($resolution->status)->toBe('pending_approval');
});

test('resolution auto-advances to approved when 75% threshold is met', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'pending_approval']);

    // Create 9 other active members (total = 10 active users including secretary)
    $members = createActiveMembers(9);

    // Approve 8 out of 10 = 80% (above 75%)
    $approvals = $members->take(7)->map(fn ($m) => [
        'user_id' => $m->id,
        'is_approved' => true,
    ])->toArray();

    // Also approve the secretary
    $approvals[] = ['user_id' => $secretary->id, 'is_approved' => true];

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.approvals.store', $resolution),
        ['approvals' => $approvals]
    );

    $response->assertOk();

    $resolution->refresh();
    expect($resolution->status)->toBe('approved');
    expect($resolution->hasMetApprovalThreshold())->toBeTrue();
});

test('approval data API returns all members with approval status', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);

    $response = $this->actingAs($secretary)->getJson(
        route('workflow.resolutions.approvals.data', $resolution)
    );

    $response->assertOk();
    $response->assertJsonStructure([
        'members',
        'approvals',
        'total_members',
        'approved_count',
        'approval_percentage',
        'threshold',
        'has_met_threshold',
    ]);
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  DSWD SUBMISSION TESTS                                      ║
// ╚══════════════════════════════════════════════════════════════╝

test('DSWD status can be updated with file upload', function () {
    Storage::fake('public');
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'approved']);

    $file = UploadedFile::fake()->create('dswd-letter.pdf', 300, 'application/pdf');

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.dswd.store', $resolution),
        [
            'status' => 'submitted',
            'submission_file' => $file,
            'notes' => 'Submitted to DSWD Region XI',
        ]
    );

    $response->assertOk();

    $this->assertDatabaseHas('dswd_submissions', [
        'resolution_id' => $resolution->id,
        'status' => 'submitted',
    ]);
});

test('DSWD approval advances resolution status', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'approved']);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.resolutions.dswd.store', $resolution),
        ['status' => 'approved', 'notes' => 'DSWD approved the resolution']
    );

    $response->assertOk();

    $resolution->refresh();
    expect($resolution->status)->toBe('dswd_submitted');
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  WITHDRAWAL TESTS                                           ║
// ╚══════════════════════════════════════════════════════════════╝

test('withdrawal is prevented when resolution is not DSWD approved', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'approved']); // not dswd_submitted

    // Try to access the withdrawal form – should redirect back with errors
    $response = $this->actingAs($secretary)->get(
        route('workflow.withdrawals.create', $resolution)
    );

    $response->assertRedirect(route('workflow.resolutions.show', $resolution));
});

test('withdrawal is prevented when approval threshold is not met', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'dswd_submitted']);

    // No approvals at all → 0%
    $response = $this->actingAs($secretary)->postJson(
        route('workflow.withdrawals.store', $resolution),
        ['amount' => 5000]
    );

    $response->assertStatus(422);
});

test('successful withdrawal changes resolution status to withdrawn', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'dswd_submitted']);

    // Create DSWD approved record
    $resolution->dswdSubmissions()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'submitted_by' => $secretary->id,
    ]);

    // Create enough approvals for 75% threshold
    // Only secretary is active, so 1/1 = 100%
    $resolution->approvals()->create([
        'user_id' => $secretary->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.withdrawals.store', $resolution),
        [
            'amount' => 30000,
            'bank_account' => 'LBP-12345',
            'notes' => 'First withdrawal',
        ]
    );

    $response->assertStatus(201);

    $resolution->refresh();
    expect($resolution->status)->toBe('withdrawn');
    expect((float) $resolution->total_withdrawn)->toBe(30000.00);
    expect((float) $resolution->remaining_balance)->toBe(30000.00); // 60000 - 30000
});

test('withdrawal amount cannot exceed remaining balance', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'dswd_submitted']);

    $resolution->dswdSubmissions()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'submitted_by' => $secretary->id,
    ]);

    $resolution->approvals()->create([
        'user_id' => $secretary->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    // Try to withdraw more than the budget total (60,000)
    $response = $this->actingAs($secretary)->postJson(
        route('workflow.withdrawals.store', $resolution),
        ['amount' => 99999]
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('amount');
});


// ╔══════════════════════════════════════════════════════════════╗
// ║  LIQUIDATION REPORT TESTS                                   ║
// ╚══════════════════════════════════════════════════════════════╝

test('liquidation report finalizes the resolution', function () {
    $secretary = makeOfficer('secretary');
    $meeting = seedMeeting($secretary);
    $resolution = seedResolution($meeting, $secretary);
    $resolution->update(['status' => 'withdrawn']);

    $withdrawal = $resolution->withdrawals()->create([
        'requested_by' => $secretary->id,
        'amount' => 60000,
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.withdrawals.report', $withdrawal),
        ['summary' => 'All funds used for feed procurement']
    );

    $response->assertOk();

    $resolution->refresh();
    $withdrawal->refresh();
    expect($resolution->status)->toBe('finalized');
    expect($withdrawal->status)->toBe('completed');
    expect($withdrawal->completed_at)->not->toBeNull();

    $this->assertDatabaseHas('liquidation_reports', [
        'withdrawal_id' => $withdrawal->id,
        'generated_by' => $secretary->id,
    ]);
});
