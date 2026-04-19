<?php

use App\Models\PigCycle;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function transitionPresident(array $overrides = []): User
{
    $role = Role::query()->where('slug', 'president')->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
        ...$overrides,
    ]);
}

function makeTransitionCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'CSTP-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(20)->toDateString(),
        'initial_count' => 9,
        'current_count' => 9,
        'average_weight' => 8.00,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Transition policy feature test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('status endpoint rejects backward stage transitions', function () {
    $president = transitionPresident();
    $cycle = makeTransitionCycle($president, [
        'stage' => 'Growing',
        'status' => 'Active',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $cycle), [
            'new_stage' => 'Piglet',
            'remarks' => 'Accidental regression',
        ])
        ->assertSessionHasErrors(['new_stage']);

    $cycle->refresh();
    expect($cycle->stage)->toBe('Growing');
    expect($cycle->status)->toBe('Active');
});

test('status endpoint rejects closed status unless stage is completed', function () {
    $president = transitionPresident();
    $cycle = makeTransitionCycle($president, [
        'stage' => 'Growing',
        'status' => 'Active',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $cycle), [
            'new_status' => 'Closed',
            'remarks' => 'Should fail',
        ])
        ->assertSessionHasErrors(['new_status']);
});

test('archived cycles are terminal and cannot be updated', function () {
    $president = transitionPresident();
    $cycle = makeTransitionCycle($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    actingAs($president)
        ->post(route('cycles.status.store', $cycle), [
            'new_stage' => 'Piglet',
            'new_status' => 'Active',
            'remarks' => 'Attempt status change on archived cycle',
        ])
        ->assertSessionHasErrors(['cycle']);

    $cycle->refresh();

    expect($cycle->stage)->toBe('Completed');
    expect($cycle->status)->toBe('Closed');
});
