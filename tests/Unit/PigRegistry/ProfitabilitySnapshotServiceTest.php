<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\User;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use App\Services\PigRegistry\ProfitabilitySnapshotService;
use App\Services\PigRegistry\ProfitabilityValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function snapshotCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'SNAP-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(120)->toDateString(),
        'initial_count' => 10,
        'current_count' => 0,
        'average_weight' => 85.00,
        'stage' => 'Completed',
        'status' => 'Sold',
        'has_pig_profiles' => false,
        'notes' => 'Snapshot unit test',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

function snapshotExpense(PigCycle $cycle, float $amount, string $category = 'feed'): PigCycleExpense
{
    return PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => $category,
        'amount' => $amount,
        'expense_date' => now()->toDateString(),
        'created_by' => $cycle->created_by,
    ]);
}

function snapshotSale(PigCycle $cycle, float $amount, array $overrides = []): PigCycleSale
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

test('validation blocks finalization when cycle is not archived', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user, [
        'stage' => 'For Sale',
        'status' => 'Ready for Sale',
        'current_count' => 5,
    ]);
    snapshotExpense($cycle, 1000);
    snapshotSale($cycle, 4000);

    $computed = app(ComputeCycleProfitabilityService::class)->compute($cycle);
    $result = app(ProfitabilityValidationService::class)->validate($cycle, $computed, null);

    expect($result['can_finalize'])->toBeFalse()
        ->and($result['blocking_errors'])->not->toBeEmpty();
});

test('validation blocks finalization with no sales', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);

    $computed = app(ComputeCycleProfitabilityService::class)->compute($cycle);
    $result = app(ProfitabilityValidationService::class)->validate($cycle, $computed, null);

    expect($result['can_finalize'])->toBeFalse()
        ->and($result['blocking_errors'])->toHaveCount(1);
});

test('validation passes for a ready cycle', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    snapshotSale($cycle, 4000);

    $computed = app(ComputeCycleProfitabilityService::class)->compute($cycle);
    $result = app(ProfitabilityValidationService::class)->validate($cycle, $computed, null);

    expect($result['can_finalize'])->toBeTrue()
        ->and($result['blocking_errors'])->toHaveCount(0);
});

test('validation warns about receivables', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    snapshotSale($cycle, 4000, ['amount_paid' => 2500, 'payment_status' => 'partial']);

    $computed = app(ComputeCycleProfitabilityService::class)->compute($cycle);
    $result = app(ProfitabilityValidationService::class)->validate($cycle, $computed, null);

    expect($result['warnings'])->not->toBeEmpty();
});

test('validation warns about net loss', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 5000);
    snapshotSale($cycle, 3000);

    $computed = app(ComputeCycleProfitabilityService::class)->compute($cycle);
    $result = app(ProfitabilityValidationService::class)->validate($cycle, $computed, null);

    expect($result['can_finalize'])->toBeTrue()
        ->and($result['warnings'])->not->toBeEmpty();
});

test('finalization creates snapshot with correct values', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    snapshotSale($cycle, 4000);

    $snapshot = app(ProfitabilitySnapshotService::class)->finalize($cycle, $user, 'Test finalization');

    expect($snapshot->pig_cycle_id)->toBe($cycle->id)
        ->and($snapshot->version_number)->toBe(1)
        ->and($snapshot->is_current)->toBeTrue()
        ->and($snapshot->notes)->toBe('Test finalization')
        ->and((float) $snapshot->gross_income)->toBe(4000.00)
        ->and((float) $snapshot->net_profit_or_loss)->toBe(3000.00)
        ->and((float) $snapshot->caretaker_share)->toBe(1500.00)
        ->and($snapshot->source_hash)->not->toBeNull();
});

test('re-finalization creates version 2 and marks old as not current', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    $sale = snapshotSale($cycle, 4000);

    $snapshot1 = app(ProfitabilitySnapshotService::class)->finalize($cycle, $user);

    $sale->update(['amount' => 5000, 'amount_paid' => 5000]);

    $snapshot2 = app(ProfitabilitySnapshotService::class)->finalize(
        $cycle, $user, null,
        force: true,
        reasonCode: 'corrected_sale',
        reasonNotes: 'Sale amount was corrected from 4000 to 5000 after buyer confirmation'
    );

    expect($snapshot2->version_number)->toBe(2)
        ->and($snapshot2->is_current)->toBeTrue()
        ->and($snapshot2->supersedes_snapshot_id)->toBe($snapshot1->id)
        ->and($snapshot2->re_finalize_reason_code)->toBe('corrected_sale');

    $snapshot1->refresh();

    expect($snapshot1->is_current)->toBeFalse();
});

test('snapshot history returns all versions in descending order', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    $sale = snapshotSale($cycle, 4000);

    app(ProfitabilitySnapshotService::class)->finalize($cycle, $user);

    $sale->update(['amount' => 5000, 'amount_paid' => 5000]);

    app(ProfitabilitySnapshotService::class)->finalize(
        $cycle, $user, null,
        force: true,
        reasonCode: 'corrected_sale',
        reasonNotes: 'Corrected sale amount to reflect actual amount'
    );

    $history = app(ProfitabilitySnapshotService::class)->snapshotHistory($cycle);

    expect($history)->toHaveCount(2)
        ->and($history[0]->version_number)->toBe(2)
        ->and($history[1]->version_number)->toBe(1);
});

test('latestCurrentSnapshot returns only the current version', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    $sale = snapshotSale($cycle, 4000);

    app(ProfitabilitySnapshotService::class)->finalize($cycle, $user);

    $sale->update(['amount' => 5000, 'amount_paid' => 5000]);

    app(ProfitabilitySnapshotService::class)->finalize(
        $cycle, $user, null,
        force: true,
        reasonCode: 'corrected_sale',
        reasonNotes: 'Corrected sale amount'
    );

    $latest = app(ProfitabilitySnapshotService::class)->latestCurrentSnapshot($cycle);

    expect($latest->version_number)->toBe(2)
        ->and($latest->is_current)->toBeTrue();
});

test('detectDataChanges returns true when source data differs', function () {
    $user = User::factory()->create();
    $cycle = snapshotCycle($user);
    snapshotExpense($cycle, 1000);
    snapshotSale($cycle, 4000);

    $originalSnapshot = app(ProfitabilitySnapshotService::class)->finalize($cycle, $user);

    $changed = app(ProfitabilitySnapshotService::class)->detectDataChanges($cycle, $originalSnapshot);

    expect($changed)->toBeFalse();

    $cycle->expenses()->first()->update(['amount' => 1500]);

    $changedAfter = app(ProfitabilitySnapshotService::class)->detectDataChanges($cycle, $originalSnapshot);

    expect($changedAfter)->toBeTrue();
});