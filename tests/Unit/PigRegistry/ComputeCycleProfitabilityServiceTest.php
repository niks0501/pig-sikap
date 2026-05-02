<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\User;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function computeProfitabilityCycle(array $overrides = []): PigCycle
{
    $user = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'PROFIT-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $user->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(90)->toDateString(),
        'initial_count' => 10,
        'current_count' => 0,
        'average_weight' => 85.00,
        'stage' => 'Completed',
        'status' => 'Sold',
        'has_pig_profiles' => false,
        'notes' => 'Profitability unit test cycle',
        'last_reviewed_at' => now(),
        'created_by' => $user->id,
        ...$overrides,
    ]);
}

function addProfitabilityExpense(PigCycle $cycle, string $category, float $amount): PigCycleExpense
{
    return PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => $category,
        'amount' => $amount,
        'expense_date' => now()->toDateString(),
        'created_by' => $cycle->created_by,
    ]);
}

function addProfitabilitySale(PigCycle $cycle, float $amount): PigCycleSale
{
    return PigCycleSale::query()->create([
        'batch_id' => $cycle->id,
        'pigs_sold' => 2,
        'amount' => $amount,
        'sale_date' => now()->toDateString(),
        'sale_method' => 'per_head',
        'price_per_head' => $amount / 2,
        'payment_status' => 'paid',
        'amount_paid' => $amount,
        'created_by' => $cycle->created_by,
    ]);
}

test('it computes positive profit and the standard 50 25 25 sharing rule', function () {
    $cycle = computeProfitabilityCycle();

    addProfitabilityExpense($cycle, 'feed', 1000);
    addProfitabilityExpense($cycle, 'medicine', 500);
    addProfitabilitySale($cycle, 4000);

    $result = app(ComputeCycleProfitabilityService::class)->handle($cycle);

    expect($result['total_expenses'])->toBe(1500.0)
        ->and($result['total_sales'])->toBe(4000.0)
        ->and($result['net_profit_or_loss'])->toBe(2500.0)
        ->and($result['distributable_profit'])->toBe(2500.0)
        ->and($result['caretaker_share'])->toBe(1250.0)
        ->and($result['member_share'])->toBe(625.0)
        ->and($result['association_share'])->toBe(625.0)
        ->and($result['status'])->toBe('profit');
});

test('it does not distribute negative profit', function () {
    $cycle = computeProfitabilityCycle();

    addProfitabilityExpense($cycle, 'feed', 5000);
    addProfitabilitySale($cycle, 3000);

    $result = app(ComputeCycleProfitabilityService::class)->handle($cycle);

    expect($result['net_profit_or_loss'])->toBe(-2000.0)
        ->and($result['distributable_profit'])->toBe(0.0)
        ->and($result['caretaker_share'])->toBe(0.0)
        ->and($result['member_share'])->toBe(0.0)
        ->and($result['association_share'])->toBe(0.0)
        ->and($result['status'])->toBe('loss');
});

test('it labels zero sales and keeps every expense category in the breakdown', function () {
    $cycle = computeProfitabilityCycle();

    addProfitabilityExpense($cycle, 'transport', 750);

    $result = app(ComputeCycleProfitabilityService::class)->handle($cycle);

    expect($result['total_sales'])->toBe(0.0)
        ->and($result['net_profit_or_loss'])->toBe(-750.0)
        ->and($result['status'])->toBe('zero_sales')
        ->and(array_keys($result['expense_breakdown']))->toBe(PigCycleExpense::CATEGORIES)
        ->and($result['expense_breakdown']['transport'])->toBe(750.0)
        ->and($result['expense_breakdown']['feed'])->toBe(0.0);
});
