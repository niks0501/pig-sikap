<?php

namespace Database\Factories;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditTrail>
 */
class AuditTrailFactory extends Factory
{
    /**
     * @var class-string<\App\Models\AuditTrail>
     */
    protected $model = AuditTrail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'created_cycle', 'updated_cycle', 'deleted_cycle',
                'added_pig', 'updated_pig', 'removed_pig',
                'recorded_expense', 'recorded_sale',
                'approved_resolution', 'generated_report',
            ]),
            'module' => fake()->randomElement([
                'pig_registry', 'workflow', 'expenses', 'sales', 'reports',
            ]),
            'description' => fake()->sentence(),
            'context_json' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
