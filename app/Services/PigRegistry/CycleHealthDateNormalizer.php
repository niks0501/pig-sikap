<?php

namespace App\Services\PigRegistry;

use Illuminate\Support\Carbon;

class CycleHealthDateNormalizer
{
    public function toCarbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        return Carbon::parse((string) $value)->startOfDay();
    }

    public function toDateString(mixed $value): ?string
    {
        return $this->toCarbon($value)?->toDateString();
    }
}