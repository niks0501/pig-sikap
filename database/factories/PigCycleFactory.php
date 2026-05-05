<?php

namespace Database\Factories;

use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PigCycle>
 */
class PigCycleFactory extends Factory
{
    protected $model = PigCycle::class;

    public function definition(): array
    {
        return [
            'batch_code' => 'BP-'.fake()->unique()->numberBetween(100, 999),
            'stage' => fake()->randomElement(['Grower', 'Finisher', 'Breeder']),
            'status' => 'Active',
            'initial_count' => fake()->numberBetween(5, 30),
            'current_count' => fake()->numberBetween(2, 30),
            'caretaker_user_id' => User::factory(),
            'created_by' => User::factory(),
            'date_of_purchase' => fake()->date(),
        ];
    }
}
