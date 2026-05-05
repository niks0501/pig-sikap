<?php

use App\Services\PigRegistry\ReportFilterService;

beforeEach(function () {
    $this->service = new ReportFilterService();
});

test('malformed start_date string is quietly set to null instead of throwing', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'custom',
        'start_date' => 'not-a-date-at-all',
        'end_date' => '2025-12-31',
    ]);

    expect($result['start_date'] ?? null)->toBeNull();
    expect($result['end_date'])->toBe('2025-12-31');
});

test('malformed end_date string is quietly set to null instead of throwing', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'custom',
        'start_date' => '2025-01-01',
        'end_date' => 'garbage',
    ]);

    expect($result['start_date'])->toBe('2025-01-01');
    expect($result['end_date'] ?? null)->toBeNull();
});

test('malformed date in custom preset fallback is handled gracefully', function () {
    $result = $this->service->normalize([
        'type' => 'expense',
        'date_range' => 'custom',
        'start_date' => 'abcdef',
        'end_date' => 'xyz',
    ]);

    expect($result['start_date'] ?? null)->toBeNull();
    expect($result['end_date'] ?? null)->toBeNull();
});
