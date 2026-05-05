<?php

/**
 * GovernanceServicesTest – Unit tests for governance service layer
 * covering PolicyService, CanvassingService, PenaltyService,
 * and MemberSnapshotService.
 */

use App\Models\AssociationPolicySetting;
use App\Models\Canvass;
use App\Models\CanvassItem;
use App\Models\Meeting;
use App\Models\MeetingSignatory;
use App\Models\Resolution;
use App\Models\ResolutionMemberSnapshot;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Workflow\CanvassingService;
use App\Services\Workflow\MemberSnapshotService;
use App\Services\Workflow\PenaltyService;
use App\Services\Workflow\PolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ── Helpers ──

function govMakeTestRole(string $slug): int
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

function govMakeTestUser(string $roleSlug = 'secretary'): User
{
    $role = DB::table('roles')->where('slug', $roleSlug)->first();
    if (! $role) {
        $roleId = govMakeTestRole($roleSlug);
    } else {
        $roleId = $role->id;
    }

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

// ── PolicyService ──

test('PolicyService returns correct typed value', function () {
    AssociationPolicySetting::create([
        'key' => 'test_float_key',
        'value' => '42.5',
        'value_type' => 'float',
        'group' => 'financial',
    ]);

    AssociationPolicySetting::create([
        'key' => 'test_int_key',
        'value' => '10',
        'value_type' => 'integer',
        'group' => 'attendance',
    ]);

    $service = app(PolicyService::class);

    expect($service->getFloat('test_float_key'))->toBe(42.5);
    expect($service->getInt('test_int_key'))->toBe(10);
});

test('PolicyService returns default when key missing', function () {
    $service = app(PolicyService::class);

    expect($service->getFloat('nonexistent', 99.9))->toBe(99.9);
    expect($service->getInt('nonexistent', 42))->toBe(42);
    expect($service->getString('nonexistent', 'fallback'))->toBe('fallback');
    expect($service->getBool('nonexistent', true))->toBe(true);
});

test('PolicyService getAttendancePenaltyAmount returns correct value', function () {
    AssociationPolicySetting::create([
        'key' => 'attendance_penalty_amount',
        'value' => '50',
        'value_type' => 'float',
        'group' => 'attendance',
    ]);

    expect(app(PolicyService::class)->getAttendancePenaltyAmount())->toBe(50.0);
});

// ── CanvassingService ──

test('CanvassingService creates canvass with items', function () {
    $user = govMakeTestUser();
    $supplier = Supplier::factory()->create(['created_by' => $user->id]);

    $canvass = app(CanvassingService::class)->create(
        [
            'title' => 'Test Canvass',
            'canvass_date' => now()->format('Y-m-d'),
        ],
        [
            [
                'description' => 'Item 1',
                'quantity' => 10,
                'unit' => 'pc',
                'unit_cost' => 100,
                'supplier_id' => $supplier->id,
            ],
        ],
        $user
    );

    expect($canvass)->toBeInstanceOf(Canvass::class);
    expect($canvass->title)->toBe('Test Canvass');
    expect($canvass->items)->toHaveCount(1);
    expect((float) $canvass->items->first()->total)->toBe(1000.0);
});

test('CanvassingService selectItem marks winner', function () {
    $user = govMakeTestUser();
    $supplier1 = Supplier::factory()->create(['created_by' => $user->id]);
    $supplier2 = Supplier::factory()->create(['created_by' => $user->id]);

    $canvass = app(CanvassingService::class)->create(
        [
            'title' => 'Comparison Canvass',
            'canvass_date' => now()->format('Y-m-d'),
        ],
        [
            [
                'description' => 'Item A',
                'quantity' => 1,
                'unit' => 'pc',
                'unit_cost' => 100,
                'supplier_id' => $supplier1->id,
            ],
            [
                'description' => 'Item A',
                'quantity' => 1,
                'unit' => 'pc',
                'unit_cost' => 90,
                'supplier_id' => $supplier2->id,
            ],
        ],
        $user
    );

    $itemToSelect = $canvass->items->last();
    $updated = app(CanvassingService::class)->selectItem($canvass, $itemToSelect);

    expect($updated->items->where('is_selected', true))->toHaveCount(1);
    expect($updated->items->where('is_selected', true)->first()->id)->toBe($itemToSelect->id);
});

// ── PenaltyService ──

test('PenaltyService auto-applies on meeting confirmation', function () {
    $policyService = app(PolicyService::class);
    $user = govMakeTestUser('president');

    // Seed penalty amount
    AssociationPolicySetting::create([
        'key' => 'attendance_penalty_amount',
        'value' => '50',
        'value_type' => 'float',
        'group' => 'attendance',
    ]);

    $meeting = Meeting::factory()->create(['created_by' => $user->id, 'status' => 'confirmed']);

    $absentUser1 = govMakeTestUser('member');
    $absentUser2 = govMakeTestUser('member');

    MeetingSignatory::create([
        'meeting_id' => $meeting->id,
        'user_id' => $absentUser1->id,
        'attendance_status' => 'absent',
        'penalty_applied' => false,
    ]);

    MeetingSignatory::create([
        'meeting_id' => $meeting->id,
        'user_id' => $absentUser2->id,
        'attendance_status' => 'absent',
        'penalty_applied' => false,
    ]);

    $service = new PenaltyService($policyService);
    $result = $service->autoApplyForMeeting($meeting, $user);

    expect($result['created'])->toBe(2);
    expect($result['total_amount'])->toBe(100.0);
});

test('PenaltyService is idempotent', function () {
    $policyService = app(PolicyService::class);
    $user = govMakeTestUser('president');

    AssociationPolicySetting::create([
        'key' => 'attendance_penalty_amount',
        'value' => '50',
        'value_type' => 'float',
        'group' => 'attendance',
    ]);

    $meeting = Meeting::factory()->create(['created_by' => $user->id, 'status' => 'confirmed']);

    $absentUser = govMakeTestUser('member');

    MeetingSignatory::create([
        'meeting_id' => $meeting->id,
        'user_id' => $absentUser->id,
        'attendance_status' => 'absent',
        'penalty_applied' => true,
    ]);

    $service = new PenaltyService($policyService);
    $result = $service->autoApplyForMeeting($meeting, $user);

    // Should skip because penalty_applied flag is already true
    expect($result['created'])->toBe(0);
});

// ── MemberSnapshotService ──

test('MemberSnapshotService takes snapshot', function () {
    $user1 = govMakeTestUser('member');
    $user2 = govMakeTestUser('member');
    $user3 = govMakeTestUser('member');

    $resolution = Resolution::factory()->create([
        'created_by' => $user1->id,
        'title' => 'Test Resolution',
    ]);

    $snapshot = app(MemberSnapshotService::class)->takeSnapshot($resolution);

    expect($snapshot)->toBeInstanceOf(ResolutionMemberSnapshot::class);
    expect($snapshot->eligible_count)->toBe(4);
    expect($snapshot->required_approvals)->toBe(3); // ceil(4 * 0.75) = 3
});

test('MemberSnapshotService is idempotent', function () {
    $user1 = govMakeTestUser('member');
    $user2 = govMakeTestUser('member');

    $resolution = Resolution::factory()->create([
        'created_by' => $user1->id,
        'title' => 'Test Resolution',
    ]);

    $snapshot1 = app(MemberSnapshotService::class)->takeSnapshot($resolution);
    $snapshot2 = app(MemberSnapshotService::class)->takeSnapshot($resolution);

    expect($snapshot1->id)->toBe($snapshot2->id); // Same record returned
});