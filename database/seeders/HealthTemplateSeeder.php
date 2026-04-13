<?php

namespace Database\Seeders;

use App\Models\HealthTemplate;
use App\Models\HealthTemplateItem;
use Illuminate\Database\Seeder;

class HealthTemplateSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $template = HealthTemplate::query()->updateOrCreate(
            ['code' => HealthTemplate::DEFAULT_TEMPLATE_CODE],
            [
                'name' => 'Standard Purchased Pig Cycle Plan',
                'description' => 'Default cycle-based post-purchase health and treatment plan.',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $items = [
            [
                'task_name' => 'Oral Medication Period',
                'task_type' => 'oral_medication_period',
                'day_offset_start' => 0,
                'day_offset_end' => 45,
                'is_optional' => false,
                'sort_order' => 1,
                'default_notes' => 'Antibiotic powder, Vetracin, and Vitamin Pro for 30 to 45 days post-purchase.',
            ],
            [
                'task_name' => 'Injectable Vitamins',
                'task_type' => 'injectable',
                'day_offset_start' => 45,
                'day_offset_end' => null,
                'is_optional' => false,
                'sort_order' => 2,
                'default_notes' => 'One-time injectable vitamins after oral treatment period.',
            ],
            [
                'task_name' => 'Deworming',
                'task_type' => 'deworming',
                'day_offset_start' => 45,
                'day_offset_end' => null,
                'is_optional' => false,
                'sort_order' => 3,
                'default_notes' => 'One-time deworming or purga after 45 days.',
            ],
            [
                'task_name' => 'Optional Monthly Maintenance',
                'task_type' => 'maintenance_optional',
                'day_offset_start' => 75,
                'day_offset_end' => null,
                'is_optional' => true,
                'sort_order' => 4,
                'default_notes' => 'Optional monthly maintenance for vitamins and deworming.',
            ],
        ];

        foreach ($items as $item) {
            HealthTemplateItem::query()->updateOrCreate(
                [
                    'health_template_id' => $template->id,
                    'sort_order' => $item['sort_order'],
                ],
                $item,
            );
        }
    }
}
