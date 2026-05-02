<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\ProfitabilitySnapshot;
use App\Models\User;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function computeProfitabilityCycle(array $overrides = []): PigCycle
{
    $user = User::factory()->create();

    return PigCycle::query()->create([
        'batch_code' => 'CPROF-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $user->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(90)->toDateString(),
        'initial_count' => 10,
        'current_count' => 0,
        'average_weight' => 85.00,
        'stage' => 'Completed',
        'status' => 'Sold',
        'has_pig_profiles' => false,
        'notes' => 'Enhanced profitability unit test cycle',
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

function addProfitabilitySale(PigCycle $cycle, float $amount, array $overrides = []): PigCycleSale
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
        ...$overrides,
    ]);
}

test('it computes positive profit and the standard 50 25 25 sharing rule', function () {
    $cycle = computeProfitabilityCycle();

    addProfitabilityExpense($cycle, 'feed', 1000);
    addProfitabilityExpense($cycle, 'medicine', 500);
    addProfitabilitySale($cycle, 4000);

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);

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

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);

    expect($result['net_profit_or_loss'])->toBe(-2000.0)
        ->and($result['distributable_profit'])->toBe(0.0)
        ->and($result['caretaker_share'])->toBe(0.0)
        ->and($result['member_share'])->toBe(0.0)
        ->and($result['association_share'])->toBe(0.0)
        ->and($result['status'])->toBe('loss');
});

test('it computes receivables and total collected correctly', function () {
    $cycle = computeProfitabilityCycle();

    addProfitabilityExpense($cycle, 'feed', 1000);
    addProfitabilitySale($cycle, 4000, ['amount_paid' => 2500, 'payment_status' => 'partial']);
    addProfitabilitySale($cycle, 2000, ['amount_paid' => 0, 'payment_status' => 'pending']);

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);

    expect($result['total_sales'])->toBe(6000.0)
        ->and($result['total_collected'])->toBe(2500.0)
        ->and($result['receivables'])->toBe(3500.0)
        ->and($result['has_receivables'])->toBeTrue()
        ->and($result['has_pending_payments'])->toBeTrue();
});

test('it labels zero sales and keeps every expense category in the breakdown', function () {
    $cycle = computeProfitabilityCycle();

    addProfitabilityExpense($cycle, 'transport', 750);

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);

    expect($result['total_sales'])->toBe(0.0)
        ->and($result['net_profit_or_loss'])->toBe(-750.0)
        ->and($result['status'])->toBe('zero_sales')
        ->and(array_keys($result['expense_breakdown']))->toBe(PigCycleExpense::CATEGORIES)
        ->and($result['expense_breakdown']['transport'])->toBe(750.0)
        ->and($result['expense_breakdown']['feed'])->toBe(0.0);
});

test('it generates a deterministic source hash', function () {
    $cycle = computeProfitabilityCycle();
    addProfitabilityExpense($cycle, 'feed', 1000);
    addProfitabilitySale($cycle, 4000);

    $service = app(ComputeCycleProfitabilityService::class);

    $hash1 = $service->computeSourceHash($cycle);
    $hash2 = $service->computeSourceHash($cycle);

    expect($hash1)->toBe($hash2)
        ->and(strlen($hash1))->toBe(64);
});

test('source hash changes when an expense is modified', function () {
    $cycle = computeProfitabilityCycle();
    $expense = addProfitabilityExpense($cycle, 'feed', 1000);
    addProfitabilitySale($cycle, 4000);

    $service = app(ComputeCycleProfitabilityService::class);

    $hashBefore = $service->computeSourceHash($cycle);

    $expense->update(['amount' => 1500]);

    $hashAfter = $service->computeSourceHash($cycle);

    expect($hashBefore)->not->toBe($hashAfter);
});

test('break-even status is correctly identified', function () {
    $cycle = computeProfitabilityCycle();
    addProfitabilityExpense($cycle, 'feed', 4000);
    addProfitabilitySale($cycle, 4000);

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);

    expect($result['net_profit_or_loss'])->toBe(0.0)
        ->and($result['distributable_profit'])->toBe(0.0)
        ->and($result['status'])->toBe('break_even');
});

test('all association fund share aliases are consistent', function () {
    $cycle = computeProfitabilityCycle();
    addProfitabilityExpense($cycle, 'feed', 1000);
    addProfitabilitySale($cycle, 4000);

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);

    expect($result['association_share'])->toBe($result['association_fund_share'])
        ->and($result['association_share'])->toBeGreaterThan(0);
});

test('sales summary includes live weight and price data', function () {
    $cycle = computeProfitabilityCycle();
    addProfitabilitySale($cycle, 8500, [
        'pigs_sold' => 5,
        'sale_method' => 'live_weight',
        'live_weight_kg' => 425.00,
        'price_per_kg' => 20.00,
        'amount' => 8500,
        'amount_paid' => 8500,
    ]);

    $result = app(ComputeCycleProfitabilityService::class)->compute($cycle);
    $salesSummary = $result['sales_summary'];

    expect($salesSummary['total_live_weight_kg'])->toBe(425.00)
        ->and($salesSummary['average_price_per_kg'])->toBe(20.00)
        ->and($salesSummary['sale_count'])->toBe(1);
});