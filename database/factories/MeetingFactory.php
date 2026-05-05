<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'date' => fake()->date(),
            'location' => fake()->address(),
            'agenda' => fake()->paragraph(),
            'minutes_summary' => fake()->paragraph(),
            'minutes_file_path' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
            'status' => 'draft',
        ];
    }
}
