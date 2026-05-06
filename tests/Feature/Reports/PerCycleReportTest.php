<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\Role;
use App\Models\User;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use App\Services\PigRegistry\Reports\PerCycleReportService;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RoleSeeder::class);
});

test('per cycle report generates with cycle data', function () {
    $role = Role::query()->where('slug', 'president')->firstOrFail();
    $user = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);

    $cycle = PigCycle::factory()->create([
        'batch_code' => 'CYCLE-001',
        'stage' => 'Growing',
        'status' => 'Active',
        'initial_count' => 10,
        'current_count' => 8,
        'caretaker_user_id' => $user->id,
    ]);

    PigCycleExpense::factory()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 1500.00,
        'expense_date' => now(),
    ]);

    PigCycleSale::factory()->create([
        'batch_id' => $cycle->id,
        'amount' => 5000.00,
        'amount_paid' => 3000.00,
        'pigs_sold' => 2,
        'sale_date' => now(),
    ]);

    $service = app(PerCycleReportService::class);
    $report = $service->generate(['cycle_id' => $cycle->id]);

    expect($report['summary']['cycle_code'])->toBe('CYCLE-001');
    expect($report['summary']['initial_count'])->toBe(10);
    expect($report['summary']['current_count'])->toBe(8);
    expect(count($report['expense_rows']))->toBe(1);
    expect(count($report['sales_rows']))->toBe(1);
    expect($report['empty'])->toBeFalse();
});

test('per cycle report requires valid cycle id', function () {
    $service = app(PerCycleReportService::class);

    expect(fn () => $service->generate(['cycle_id' => 99999]))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
