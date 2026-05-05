<?php

use App\Services\PigRegistry\ReportFilterService;

beforeEach(function () {
    $this->service = new ReportFilterService();
});

test('normalizes default filters to expected structure', function () {
    $result = $this->service->normalize(['type' => 'expense']);

    expect($result)->toHaveKey('type', 'expense');
    expect($result)->not->toHaveKey('cycle_id');
});

test('resolves this_month preset range', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'this_month',
    ]);

    expect($result['start_date'])->toBe(now()->startOfMonth()->toDateString());
    expect($result['end_date'])->toBe(now()->endOfMonth()->toDateString());
});

test('resolves last_month preset range', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'last_month',
    ]);

    $expectedStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
    $expectedEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

    expect($result['start_date'])->toBe($expectedStart);
    expect($result['end_date'])->toBe($expectedEnd);
});

test('resolves this_quarter preset range', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'this_quarter',
    ]);

    expect($result['start_date'])->toBe(now()->firstOfQuarter()->toDateString());
    expect($result['end_date'])->toBe(now()->lastOfQuarter()->toDateString());
});

test('resolves this_year preset range', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'this_year',
    ]);

    expect($result['start_date'])->toBe(now()->startOfYear()->toDateString());
    expect($result['end_date'])->toBe(now()->endOfYear()->toDateString());
});

test('custom date range with explicit dates', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'custom',
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-31',
    ]);

    expect($result['start_date'])->toBe('2025-01-01');
    expect($result['end_date'])->toBe('2025-01-31');
});

test('include_details and include_charts booleans are normalized', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'include_details' => '1',
        'include_charts' => 'true',
    ]);

    expect($result['include_details'])->toBeTrue();
    expect($result['include_charts'])->toBeTrue();
});
