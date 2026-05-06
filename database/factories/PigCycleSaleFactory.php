<?php

namespace Database\Factories;

use App\Models\PigCycle;
use App\Models\PigCycleSale;
use Illuminate\Database\Eloquent\Factories\Factory;

class PigCycleSaleFactory extends Factory
{
    protected $model = PigCycleSale::class;

    public function definition(): array
    {
        return [
            'batch_id' => PigCycle::factory(),
            'pigs_sold' => fake()->numberBetween(1, 5),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'amount_paid' => fake()->randomFloat(2, 0, 20000),
            'sale_date' => fake()->date(),
            'payment_status' => fake()->randomElement(['paid', 'partial', 'pending']),
        ];
    }
}
