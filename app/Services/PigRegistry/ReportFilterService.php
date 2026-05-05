<?php

namespace App\Services\PigRegistry;

use Illuminate\Support\Carbon;
use Throwable;

class ReportFilterService
{
    public const DATE_PRESETS = [
        'this_month',
        'last_month',
        'this_quarter',
        'previous_quarter',
        'this_year',
        'custom',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        $filters = [
            'type' => (string) ($input['type'] ?? ''),
            'cycle_id' => $this->toNullableInt($input['cycle_id'] ?? null),
            'date_range' => $input['date_range'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'month' => $this->toNullableInt($input['month'] ?? null),
            'quarter' => $this->toNullableInt($input['quarter'] ?? null),
            'year' => $this->toNullableInt($input['year'] ?? null),
            'category' => $input['category'] ?? null,
            'payment_status' => $input['payment_status'] ?? null,
            'include_details' => $this->toBoolean($input['include_details'] ?? true),
            'include_charts' => $this->toBoolean($input['include_charts'] ?? false),
        ];

        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        $dateRange = $filters['date_range'] ?? null;

        if ($dateRange && in_array($dateRange, self::DATE_PRESETS, true)) {
            [$start, $end] = $this->resolvePresetRange($dateRange, $filters);
            $filters['start_date'] = $start?->toDateString();
            $filters['end_date'] = $end?->toDateString();
        }

        if (! empty($filters['start_date'])) {
            try {
                $filters['start_date'] = Carbon::parse((string) $filters['start_date'])->toDateString();
            } catch (Throwable) {
                $filters['start_date'] = null;
            }
        }

        if (! empty($filters['end_date'])) {
            try {
                $filters['end_date'] = Carbon::parse((string) $filters['end_date'])->toDateString();
            } catch (Throwable) {
                $filters['end_date'] = null;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0:?Carbon,1:?Carbon}
     */
    private function resolvePresetRange(string $preset, array $filters): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_quarter' => [$today->copy()->firstOfQuarter(), $today->copy()->lastOfQuarter()],
            'previous_quarter' => [
                $today->copy()->subQuarter()->firstOfQuarter(),
                $today->copy()->subQuarter()->lastOfQuarter(),
            ],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            default => [
                $this->safeParse($filters['start_date'] ?? null),
                $this->safeParse($filters['end_date'] ?? null),
            ],
        };
    }

    private function safeParse(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) ((int) $value);
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
