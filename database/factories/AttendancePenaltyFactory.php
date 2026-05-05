<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendancePenaltyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'meeting_id' => Meeting::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'status' => 'pending',
            'reason' => null,
            'waived_by' => null,
            'waived_at' => null,
            'paid_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
