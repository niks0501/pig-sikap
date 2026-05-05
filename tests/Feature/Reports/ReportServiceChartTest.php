<?php

use App\Services\PigRegistry\Reports\ExpenseReportService;
use App\Services\PigRegistry\Reports\HealthReportService;
use App\Services\PigRegistry\Reports\InventoryReportService;
use App\Services\PigRegistry\Reports\MonthlyReportService;
use App\Services\PigRegistry\Reports\MortalityReportService;
use App\Services\PigRegistry\Reports\ProfitabilityReportService;
use App\Services\PigRegistry\Reports\QuarterlyReportService;
use App\Services\PigRegistry\Reports\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('all eight report services return a charts key', function (string $serviceClass) {
    $service = app($serviceClass);

    $result = $service->generate([
        'type' => 'inventory',
        'year' => now()->year,
        'month' => now()->month,
        'quarter' => now()->quarter,
    ]);

    expect($result)->toHaveKey('charts');
    expect($result['charts'])->toBeArray();
})->with([
    InventoryReportService::class,
    HealthReportService::class,
    MortalityReportService::class,
    ExpenseReportService::class,
    SalesReportService::class,
    MonthlyReportService::class,
    QuarterlyReportService::class,
    ProfitabilityReportService::class,
]);
