<?php

use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\User;
use App\Services\PigRegistry\AnalyzePigCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAutomationCycle(array $overrides = []): PigCycle
{
    $user = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'AUTO-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $user->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(90)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 12.50,
        'stage' => 'Weaning',
        'status' => 'Active',
        'has_pig_profiles' => true,
        'notes' => 'Automation test cycle',
        'last_reviewed_at' => now(),
        'created_by' => $user->id,
        ...$overrides,
    ]);
}

test('pig cycle computes expected ready-for-sale date and countdown fields', function () {
    $purchaseDate = now()->subDays(100)->toDateString();

    $cycle = makeAutomationCycle([
        'date_of_purchase' => $purchaseDate,
    ]);

    $expectedDate = now()->subDays(100)->startOfDay()->addMonthsNoOverflow(4)->toDateString();

    expect($cycle->expected_ready_for_sale_date)->toBe($expectedDate);
    expect($cycle->expected_harvest_month)->toBe(now()->subDays(100)->startOfDay()->addMonthsNoOverflow(4)->format('F Y'));
    expect($cycle->days_since_acquisition)->toBe(100);
    expect($cycle->days_until_ready_for_sale)->toBeInt();
});

test('analysis service returns lifecycle suggestions and warnings for overdue cycle', function () {
    $cycle = makeAutomationCycle([
        'date_of_purchase' => now()->subDays(140)->toDateString(),
        'stage' => 'Weaning',
        'status' => 'Active',
        'last_reviewed_at' => now()->subDays(20),
    ]);

    $insights = app(AnalyzePigCycleService::class)->handle($cycle);

    $suggestionCodes = collect($insights['suggestions'])->pluck('code')->all();
    $warningCodes = collect($insights['warnings'])->pluck('code')->all();

    expect($suggestionCodes)->toContain('stage_progression');
    expect($suggestionCodes)->toContain('ready_for_sale');

    expect($warningCodes)->toContain('overdue_sale_window');
    expect($warningCodes)->toContain('ready_but_no_sales');
    expect($warningCodes)->toContain('stale_cycle_updates');
});

test('analysis service computes count expense and profitability summaries', function () {
    $cycle = makeAutomationCycle([
        'date_of_purchase' => now()->subDays(120)->toDateString(),
        'initial_count' => 10,
        'current_count' => 7,
        'status' => 'Ready for Sale',
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
        'status' => 'Deceased',
        'created_by' => $cycle->created_by,
    ]);

    Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 3,
        'status' => 'Sold',
        'created_by' => $cycle->created_by,
    ]);

    Pig::query()->create([
        'batch_id' => $cycle->id,
        'pig_no' => 4,
        'status' => 'Sold',
        'created_by' => $cycle->created_by,
    ]);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'sick',
        'date_reported' => now()->subDays(8)->toDateString(),
        'affected_count' => 1,
        'reported_by' => $cycle->created_by,
    ]);

    $cycle->healthIncidents()->create([
        'event_key' => fake()->uuid(),
        'incident_type' => 'deceased',
        'date_reported' => now()->subDays(6)->toDateString(),
        'affected_count' => 1,
        'reported_by' => $cycle->created_by,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 1000,
        'expense_date' => now()->subDays(60)->toDateString(),
        'created_by' => $cycle->created_by,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'medicine',
        'amount' => 500,
        'expense_date' => now()->subDays(30)->toDateString(),
        'created_by' => $cycle->created_by,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'transport',
        'amount' => 250,
        'expense_date' => now()->subDays(10)->toDateString(),
        'created_by' => $cycle->created_by,
    ]);

    PigCycleSale::query()->create([
        'batch_id' => $cycle->id,
        'pigs_sold' => 2,
        'amount' => 4000,
        'sale_date' => now()->subDays(5)->toDateString(),
        'created_by' => $cycle->created_by,
    ]);

    $insights = app(AnalyzePigCycleService::class)->handle($cycle);

    expect($insights['counts']['sick_count'])->toBe(1);
    expect($insights['counts']['deceased_count'])->toBe(1);
    expect($insights['counts']['sold_count'])->toBe(2);
    expect($insights['counts']['remaining_count'])->toBe(7);

    expect($insights['expenses']['total_cycle_expense'])->toBe(1750.0);
    expect($insights['expenses']['total_cycle_sales'])->toBe(4000.0);

    expect($insights['profitability']['gross_income'])->toBe(4000.0);
    expect($insights['profitability']['net_profit_or_loss'])->toBe(2250.0);
    expect($insights['profitability']['caretaker_share'])->toBe(1125.0);
    expect($insights['profitability']['member_share'])->toBe(562.5);
    expect($insights['profitability']['association_share'])->toBe(562.5);
});
