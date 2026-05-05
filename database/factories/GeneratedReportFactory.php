<?php

namespace Database\Factories;

use App\Models\GeneratedReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedReport>
 */
class GeneratedReportFactory extends Factory
{
    protected $model = GeneratedReport::class;

    public function definition(): array
    {
        return [
            'report_type' => fake()->randomElement(['inventory', 'health', 'mortality', 'expense', 'sales', 'monthly', 'quarterly', 'profitability']),
            'format' => fake()->randomElement(['pdf', 'csv']),
            'cycle_id' => null,
            'filters_json' => [],
            'generated_by' => User::factory(),
            'schedule_id' => null,
            'status' => 'generated',
            'file_path' => null,
            'file_size' => fake()->numberBetween(1024, 102400),
            'generated_at' => fake()->dateTime(),
            'notes' => null,
        ];
    }
}
