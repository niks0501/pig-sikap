<?php

namespace Database\Factories;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

class PigCycleExpenseFactory extends Factory
{
    protected $model = PigCycleExpense::class;

    public function definition(): array
    {
        return [
            'batch_id' => PigCycle::factory(),
            'category' => fake()->randomElement(['acquisition', 'feed', 'medicine', 'transport', 'emergency']),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'expense_date' => fake()->date(),
        ];
    }
}
