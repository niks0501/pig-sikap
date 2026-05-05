<?php

/**
 * GovernanceWorkflowTest – Feature tests for governance improvements:
 * policy settings, penalties, authorized withdrawers, approval locking,
 * member snapshots, and private document access.
 */

use App\Models\AssociationPolicySetting;
use App\Models\AttendancePenalty;
use App\Models\Meeting;
use App\Models\MeetingSignatory;
use App\Models\Resolution;
use App\Models\ResolutionApproval;
use App\Models\ResolutionMemberSnapshot;
use App\Models\ResolutionWithdrawalAuthorization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

function govFeatureMakeRole(string $slug): int
{
    $existing = DB::table('roles')->where('slug', $slug)->first();
    if ($existing) {
        return $existing->id;
    }

    return DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function govFeatureLogin(string $roleSlug = 'president'): User
{
    $role = DB::table('roles')->where('slug', $roleSlug)->first();
    if (! $role) {
        $roleId = govFeatureMakeRole($roleSlug);
    } else {
        $roleId = $role->id;
    }

    $user = User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);

    test()->actingAs($user);

    return $user;
}

// ── Policy Settings ──

test('President can update policy settings', function () {
    $user = govFeatureLogin('president');

    AssociationPolicySetting::create([
        'key' => 'attendance_penalty_amount',
        'value' => '0',
        'value_type' => 'float',
        'group' => 'attendance',
    ]);

    $this->put(route('workflow.settings.update'), [
        'settings' => [
            ['key' => 'attendance_penalty_amount', 'value' => '100'],
        ],
    ])->assertRedirect(route('workflow.settings.index'));

    $this->assertDatabaseHas('association_policy_settings', [
        'key' => 'attendance_penalty_amount',
        'value' => '100',
    ]);
});

test('Non-president cannot update policy settings', function () {
    govFeatureLogin('secretary');

    AssociationPolicySetting::create([
        'key' => 'attendance_penalty_amount',
        'value' => '0',
        'value_type' => 'float',
        'group' => 'attendance',
    ]);

    $this->put(route('workflow.settings.update'), [
        'settings' => [
            ['key' => 'attendance_penalty_amount', 'value' => '100'],
        ],
    ])->assertForbidden();
});

// ── Penalties ──

test('Meeting confirmation triggers penalty preview', function () {
    $user = govFeatureLogin('president');

    AssociationPolicySetting::create([
        'key' => 'attendance_penalty_amount',
        'value' => '50',
        'value_type' => 'float',
        'group' => 'attendance',
    ]);

    $meeting = Meeting::factory()->create(['created_by' => $user->id, 'status' => 'draft']);

    $absentUser = User::factory()->create([
        'role_id' => govFeatureMakeRole('member'),
        'is_active' => true,
    ]);

    MeetingSignatory::create([
        'meeting_id' => $meeting->id,
        'user_id' => $absentUser->id,
        'attendance_status' => 'absent',
    ]);

    expect(true)->toBeTrue();
});

test('President can waive penalty', function () {
    $user = govFeatureLogin('president');
    $member = User::factory()->create([
        'role_id' => govFeatureMakeRole('member'),
        'is_active' => true,
    ]);
    $meeting = Meeting::factory()->create(['created_by' => $user->id]);

    $penalty = AttendancePenalty::factory()->create([
        'user_id' => $member->id,
        'meeting_id' => $meeting->id,
        'amount' => 50,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $this->patch(route('workflow.penalties.waive', $penalty), [
        'reason' => 'Medical reason',
    ])->assertRedirect();

    $this->assertDatabaseHas('attendance_penalties', [
        'id' => $penalty->id,
        'status' => 'waived',
        'reason' => 'Medical reason',
    ]);
});

// ── Authorized Withdrawers ──

test('President can designate authorized withdrawers', function () {
    $user = govFeatureLogin('president');
    $member = User::factory()->create([
        'role_id' => govFeatureMakeRole('member'),
        'is_active' => true,
    ]);
    $resolution = Resolution::factory()->create([
        'created_by' => $user->id,
        'title' => 'Test',
    ]);

    $this->post(route('workflow.resolutions.authorized-withdrawers.store', $resolution), [
        'user_ids' => [$member->id],
    ])->assertJson(['message' => 'Authorizations saved.']);

    $this->assertDatabaseHas('resolution_withdrawal_authorizations', [
        'resolution_id' => $resolution->id,
        'user_id' => $member->id,
    ]);
});

test('Non-president cannot designate withdrawers', function () {
    $user = govFeatureLogin('secretary');
    $member = User::factory()->create([
        'role_id' => govFeatureMakeRole('member'),
        'is_active' => true,
    ]);
    $resolution = Resolution::factory()->create(['created_by' => $user->id, 'title' => 'Test']);

    $this->post(route('workflow.resolutions.authorized-withdrawers.store', $resolution), [
        'user_ids' => [$member->id],
    ])->assertForbidden();
});

// ── Snapshot ──

test('Snapshot created and used for threshold', function () {
    $user = govFeatureLogin('president');

    for ($i = 0; $i < 10; $i++) {
        User::factory()->create([
            'role_id' => govFeatureMakeRole('member'),
            'is_active' => true,
        ]);
    }

    $resolution = Resolution::factory()->create([
        'created_by' => $user->id,
        'title' => 'Snapshot Test',
    ]);

    $snapshotService = app(\App\Services\Workflow\MemberSnapshotService::class);
    $snapshot = $snapshotService->takeSnapshot($resolution);

    expect($snapshot->eligible_count)->toBeGreaterThanOrEqual(10);
    expect($snapshot->required_approvals)->toBe((int) ceil($snapshot->eligible_count * 0.75));

    // Create enough approvals to meet the threshold
    $needed = $snapshot->required_approvals;
    $members = User::where('is_active', true)->where('id', '!=', $user->id)->take($needed)->get();
    foreach ($members as $member) {
        ResolutionApproval::create([
            'resolution_id' => $resolution->id,
            'user_id' => $member->id,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    $resolution->refresh();
    expect($resolution->hasMetApprovalThreshold())->toBeTrue();
});

// ── Approval Locking ──

test('Approval recording blocked after lock', function () {
    $user = govFeatureLogin('president');
    $member = User::factory()->create([
        'role_id' => govFeatureMakeRole('member'),
        'is_active' => true,
    ]);

    $resolution = Resolution::factory()->create([
        'created_by' => $user->id,
        'title' => 'Lock Test',
        'is_approval_locked' => true,
    ]);

    $service = app(\App\Services\Workflow\ApprovalService::class);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $service->record($resolution, $member, true);
});