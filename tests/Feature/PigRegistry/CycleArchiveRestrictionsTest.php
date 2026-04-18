<?php

use App\Models\CycleHealthTask;
use App\Models\PigCycle;
use App\Models\Role;
use App\Models\User;
use App\Services\PigRegistry\CycleHealthPlanGenerator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

function archivePresident(array $overrides = []): User
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

function makeArchivedCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'CAR-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(25)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 8.50,
        'stage' => 'Completed',
        'status' => 'Closed',
        'has_pig_profiles' => false,
        'notes' => 'Archived restrictions test cycle',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('archived cycles block manual count adjustments', function () {
    $president = archivePresident();
    $cycle = makeArchivedCycle($president);

    actingAs($president)
        ->from(route('cycles.show', $cycle))
        ->post(route('cycles.adjustments.store', $cycle), [
            'adjustment_type' => 'decrease',
            'quantity_change' => 1,
            'reason' => 'mortality',
        ])
        ->assertRedirect(route('cycles.show', $cycle))
        ->assertSessionHasErrors(['cycle']);
});

test('archived cycles block pig profile writes', function () {
    $president = archivePresident();
    $cycle = makeArchivedCycle($president);

    actingAs($president)
        ->post(route('cycles.profiles.store', $cycle), [
            'pig_no' => 1,
            'status' => 'Active',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['cycle']);
});

test('archived cycles block health incident writes', function () {
    $president = archivePresident();
    $cycle = makeArchivedCycle($president);

    actingAs($president)
        ->post(route('health.cycles.incidents.store', $cycle), [
            'event_key' => fake()->uuid(),
            'incident_type' => 'sick',
            'date_reported' => now()->toDateString(),
            'affected_count' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['cycle']);
});

test('archived cycles block health task updates', function () {
    $president = archivePresident();
    $cycle = makeArchivedCycle($president);

    app(CycleHealthPlanGenerator::class)->assignDefaultTemplateAndGenerateTasks($cycle);

    /** @var CycleHealthTask $task */
    $task = $cycle->healthTasks()->firstOrFail();

    actingAs($president)
        ->patch(route('health.cycles.tasks.update', [$cycle, $task]), [
            'action' => 'complete_all',
            'actual_date' => now()->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['cycle']);
});
