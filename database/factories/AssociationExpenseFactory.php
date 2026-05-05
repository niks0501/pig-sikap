<?php

namespace Database\Factories;

use App\Models\AssociationExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssociationExpense>
 */
class AssociationExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_name' => fake()->words(3, true),
            'category' => fake()->randomElement(AssociationExpense::CATEGORIES),
            'feed_subcategory' => null,
            'quantity' => null,
            'unit' => null,
            'unit_cost' => null,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'expense_date' => fake()->date(),
            'receipt_reference' => null,
            'receipt_path' => null,
            'supplier_id' => null,
            'canvass_id' => null,
            'fund_source' => null,
            'approved_resolution_id' => null,
            'withdrawal_id' => null,
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
