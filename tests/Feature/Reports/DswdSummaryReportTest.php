<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\Role;
use App\Models\User;
use App\Services\PigRegistry\Reports\DswdSummaryReportService;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RoleSeeder::class);
});

test('dswd summary report generates with association overview', function () {
    $role = Role::query()->where('slug', 'president')->firstOrFail();
    $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

    $memberRole = Role::query()->where('slug', 'member')->firstOrFail();
    User::factory()->count(5)->create(['role_id' => $memberRole->id, 'is_active' => true]);

    $cycle = PigCycle::factory()->create([
        'batch_code' => 'CYCLE-DSWD-001',
        'stage' => 'Growing',
        'status' => 'Active',
        'initial_count' => 10,
        'current_count' => 8,
    ]);

    PigCycleSale::factory()->create([
        'batch_id' => $cycle->id,
        'amount' => 10000.00,
        'amount_paid' => 8000.00,
        'pigs_sold' => 3,
        'sale_date' => now(),
    ]);

    PigCycleExpense::factory()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 3000.00,
        'expense_date' => now(),
    ]);

    $service = app(DswdSummaryReportService::class);
    $report = $service->generate([]);

    expect($report['summary']['total_cycles'])->toBeGreaterThanOrEqual(1);
    expect($report['summary']['active_cycles'])->toBeGreaterThanOrEqual(1);
    expect($report['summary']['total_members'])->toBe(5);
    expect((float) $report['summary']['total_sales'])->toBeGreaterThan(0);
    expect((float) $report['summary']['total_expenses'])->toBeGreaterThan(0);
    expect((float) $report['summary']['net_overall'])->not->toBeNull();
    expect(count($report['sales_by_cycle']))->toBeGreaterThanOrEqual(1);
    expect(count($report['expense_by_category']))->toBeGreaterThanOrEqual(1);
    expect($report['empty'])->toBeFalse();
});

test('dswd summary report handles empty data gracefully', function () {
    $service = app(DswdSummaryReportService::class);
    $report = $service->generate([]);

    expect($report['summary']['total_cycles'])->toBe(0);
    expect($report['summary']['total_members'])->toBe(0);
    expect($report['empty'])->toBeTrue();
});
