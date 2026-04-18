<?php

use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\User;
use App\Services\PigRegistry\CycleSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeSummaryCycle(array $overrides = []): PigCycle
{
    $actor = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'CSU-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(40)->toDateString(),
        'initial_count' => 12,
        'current_count' => 9,
        'average_weight' => 8.70,
        'stage' => 'Fattening',
        'status' => 'Under Monitoring',
        'has_pig_profiles' => true,
        'notes' => 'Summary service unit test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

test('cycle summary prefers profile counts but remains incident-aware for mortality', function () {
    $cycle = makeSummaryCycle([
        'has_pig_profiles' => true,
        'initial_count' => 10,
        'current_count' => 7,
    ]);

    Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 1,
        'status' => 'Sick',
        'created_by' => $cycle->created_by,
    ]);

    Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 2,
        'status' => 'Sold',
        'created_by' => $cycle->created_by,
    ]);

    Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 3,
        'status' => 'Deceased',
        'created_by' => $cycle->created_by,
    ]);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'reported_by' => $cycle->created_by,
    ]);

    $summary = app(CycleSummaryService::class)->forCycle($cycle);

    expect($summary['sick_count'])->toBe(1);
    expect($summary['sold_count'])->toBe(1);
    expect($summary['deceased_count'])->toBe(2);
    expect($summary['remaining_count'])->toBe(7);
    expect($summary['mortality_rate'])->toBe(20.0);
});

test('cycle summary uses incident counts when profile mode is disabled', function () {
    $cycle = makeSummaryCycle([
        'has_pig_profiles' => false,
        'initial_count' => 15,
        'current_count' => 13,
    ]);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->toDateString(),
        'affected_count' => 4,
        'reported_by' => $cycle->created_by,
    ]);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'isolated',
        'date_reported' => now()->toDateString(),
        'affected_count' => 2,
        'reported_by' => $cycle->created_by,
    ]);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'deceased',
        'date_reported' => now()->toDateString(),
        'affected_count' => 1,
        'reported_by' => $cycle->created_by,
    ]);

    $summary = app(CycleSummaryService::class)->forCycle($cycle);

    expect($summary['sick_count'])->toBe(4);
    expect($summary['isolated_count'])->toBe(2);
    expect($summary['deceased_count'])->toBe(1);
    expect($summary['sold_count'])->toBe(0);
    expect($summary['remaining_count'])->toBe(13);
});
