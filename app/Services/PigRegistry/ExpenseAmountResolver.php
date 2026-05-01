<?php

namespace App\Services\PigRegistry;

class ExpenseAmountResolver
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function amount(array $payload): float
    {
        if ($this->hasStructuredAmount($payload)) {
            return round((float) $payload['quantity'] * (float) $payload['unit_cost'], 2);
        }

        return round((float) ($payload['amount'] ?? 0), 2);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hasStructuredAmount(array $payload): bool
    {
        return isset($payload['quantity'], $payload['unit_cost'])
            && is_numeric($payload['quantity'])
            && is_numeric($payload['unit_cost'])
            && (float) $payload['quantity'] > 0
            && (float) $payload['unit_cost'] > 0;
    }
}
