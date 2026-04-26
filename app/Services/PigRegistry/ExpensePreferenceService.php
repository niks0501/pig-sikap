<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleExpense;
use App\Models\User;
use App\Models\UserExpensePreference;

class ExpensePreferenceService
{
    public const LAST_CATEGORY = 'last_category';

    public const LAST_CYCLE_ID = 'last_cycle_id';

    public const LAST_EXPENSE_DATE = 'last_expense_date';

    public const PRESET_AMOUNTS = 'preset_amounts';

    /**
     * @return array<string, mixed>
     */
    public function defaultsFor(User $user): array
    {
        $preferences = UserExpensePreference::query()
            ->where('user_id', $user->id)
            ->pluck('preference_value', 'preference_key');

        return [
            'last_category' => $this->validCategory($preferences->get(self::LAST_CATEGORY)),
            'last_cycle_id' => $this->positiveInteger($preferences->get(self::LAST_CYCLE_ID)),
            'last_expense_date' => $this->validDate($preferences->get(self::LAST_EXPENSE_DATE)) ?? now()->toDateString(),
            'preset_amounts' => $this->presetAmounts($preferences->get(self::PRESET_AMOUNTS)),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(User $user, array $payload): array
    {
        if (array_key_exists('last_category', $payload)) {
            $this->put($user, self::LAST_CATEGORY, $this->validCategory($payload['last_category']));
        }

        if (array_key_exists('last_cycle_id', $payload)) {
            $cycleId = $this->positiveInteger($payload['last_cycle_id']);
            $this->put($user, self::LAST_CYCLE_ID, $cycleId > 0 ? (string) $cycleId : null);
        }

        if (array_key_exists('last_expense_date', $payload)) {
            $this->put($user, self::LAST_EXPENSE_DATE, $this->validDate($payload['last_expense_date']));
        }

        if (array_key_exists('preset_amounts', $payload)) {
            $this->put($user, self::PRESET_AMOUNTS, json_encode($this->normalizePresetAmounts($payload['preset_amounts'])));
        }

        return $this->defaultsFor($user);
    }

    public function rememberExpense(User $user, PigCycleExpense $expense): array
    {
        $this->put($user, self::LAST_CATEGORY, $expense->category);
        $this->put($user, self::LAST_CYCLE_ID, (string) $expense->batch_id);
        $this->put($user, self::LAST_EXPENSE_DATE, $expense->expense_date?->toDateString() ?? now()->toDateString());

        return $this->defaultsFor($user);
    }

    private function put(User $user, string $key, mixed $value): void
    {
        UserExpensePreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'preference_key' => $key,
            ],
            [
                'preference_value' => is_null($value) ? null : (string) $value,
            ]
        );
    }

    private function validCategory(mixed $category): ?string
    {
        if (! is_string($category)) {
            return null;
        }

        return in_array($category, PigCycleExpense::CATEGORIES, true) ? $category : null;
    }

    private function positiveInteger(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, $value);
        }

        if (is_string($value) && ctype_digit($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }

    private function validDate(mixed $date): ?string
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }

    /**
     * @return list<float>
     */
    private function presetAmounts(mixed $stored): array
    {
        if (! is_string($stored) || $stored === '') {
            return [100.0, 500.0, 1000.0, 2000.0];
        }

        $decoded = json_decode($stored, true);

        return $this->normalizePresetAmounts($decoded);
    }

    /**
     * @return list<float>
     */
    private function normalizePresetAmounts(mixed $amounts): array
    {
        if (! is_array($amounts)) {
            return [100.0, 500.0, 1000.0, 2000.0];
        }

        $normalized = collect($amounts)
            ->map(fn (mixed $amount): float => round((float) $amount, 2))
            ->filter(fn (float $amount): bool => $amount >= 1 && $amount <= 999999)
            ->unique()
            ->values()
            ->take(6)
            ->all();

        return count($normalized) > 0 ? $normalized : [100.0, 500.0, 1000.0, 2000.0];
    }
}
