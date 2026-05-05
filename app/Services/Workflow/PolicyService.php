<?php

namespace App\Services\Workflow;

use App\Models\AssociationPolicySetting;
use Illuminate\Support\Collection;

/**
 * PolicyService – typed getter for association policy settings.
 * Caches all settings in memory per request.
 */
class PolicyService
{
    private ?Collection $settings = null;

    /**
     * Load all settings from the database once per request.
     */
    private function all(): Collection
    {
        if ($this->settings === null) {
            $this->settings = AssociationPolicySetting::pluck('value', 'key');
        }

        return $this->settings;
    }

    /**
     * Retrieve a float-typed policy value.
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->all()->get($key);

        return $value !== null ? (float) $value : $default;
    }

    /**
     * Retrieve an integer-typed policy value.
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->all()->get($key);

        return $value !== null ? (int) $value : $default;
    }

    /**
     * Retrieve a string-typed policy value.
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->all()->get($key);

        return $value !== null ? (string) $value : $default;
    }

    /**
     * Retrieve a boolean-typed policy value.
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->all()->get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the attendance penalty amount from policy settings.
     */
    public function getAttendancePenaltyAmount(): float
    {
        return $this->getFloat('attendance_penalty_amount', 0);
    }

    /**
     * Get the consecutive absent limit before suspension.
     */
    public function getConsecutiveAbsentLimit(): int
    {
        return $this->getInt('attendance_consecutive_absent_limit', 3);
    }

    /**
     * Get the meeting quorum percentage.
     */
    public function getMeetingQuorumPercentage(): float
    {
        return $this->getFloat('meeting_quorum_percentage', 50);
    }

    /**
     * Clear the cached settings (useful after updates).
     */
    public function clearCache(): void
    {
        $this->settings = null;
    }
}