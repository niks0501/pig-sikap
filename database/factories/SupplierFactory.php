<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'contact_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
