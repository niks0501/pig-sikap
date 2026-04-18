<?php

use App\Models\PigCycle;
use App\Models\User;
use App\Services\PigRegistry\CycleStatusTransitionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePolicyCycle(array $overrides = []): PigCycle
{
    $actor = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'CSP-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(18)->toDateString(),
        'initial_count' => 11,
        'current_count' => 11,
        'average_weight' => 7.80,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Transition policy unit test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('policy rejects backward stage transitions during regular updates', function () {
    $policy = app(CycleStatusTransitionPolicy::class);
    $cycle = makePolicyCycle([
        'stage' => 'Fattening',
        'status' => 'Under Monitoring',
    ]);

    expect(function () use ($policy, $cycle): void {
        $policy->assertAllowed($cycle, 'Weaning', 'Under Monitoring');
    })->toThrow(ValidationException::class);
});

test('policy rejects closed status without completed stage', function () {
    $policy = app(CycleStatusTransitionPolicy::class);
    $cycle = makePolicyCycle([
        'stage' => 'Growing',
        'status' => 'Active',
    ]);

    expect(function () use ($policy, $cycle): void {
        $policy->assertAllowed($cycle, 'Growing', 'Closed');
    })->toThrow(ValidationException::class);
});

test('policy allows archived transition only with explicit override', function () {
    $policy = app(CycleStatusTransitionPolicy::class);
    $archivedCycle = makePolicyCycle([
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    expect(function () use ($policy, $archivedCycle): void {
        $policy->assertAllowed($archivedCycle, 'Piglet', 'Active');
    })->toThrow(ValidationException::class);

    $policy->assertAllowed($archivedCycle, 'Piglet', 'Active', true);

    expect(true)->toBeTrue();
});
