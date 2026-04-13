<?php

namespace App\Services\PigRegistry;

use App\Models\HealthTemplate;
use App\Models\HealthTemplateItem;
use App\Models\PigCycle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CycleHealthPlanGenerator
{
    public function __construct(
        private readonly CycleHealthTaskStatusResolver $statusResolver
    ) {}

    public function assignDefaultTemplateAndGenerateTasks(PigCycle $cycle): void
    {
        if ($cycle->healthTasks()->exists()) {
            return;
        }

        $template = $this->ensureDefaultTemplate();

        $this->generateForCycle($cycle, $template);
    }

    public function generateForCycle(PigCycle $cycle, ?HealthTemplate $template = null, bool $overwrite = false): void
    {
        $resolvedTemplate = $template ?? $this->ensureDefaultTemplate();

        DB::transaction(function () use ($cycle, $resolvedTemplate, $overwrite): void {
            if (! $overwrite && $cycle->healthTasks()->exists()) {
                return;
            }

            if ($overwrite) {
                $cycle->healthTasks()->delete();
            }

            $purchaseDate = $cycle->date_of_purchase instanceof Carbon
                ? $cycle->date_of_purchase->copy()->startOfDay()
                : Carbon::parse((string) $cycle->date_of_purchase)->startOfDay();

            $targetCount = max((int) $cycle->current_count, (int) $cycle->initial_count);
            $targetCount = max(0, $targetCount);

            $items = $resolvedTemplate->items()->orderBy('sort_order')->orderBy('id')->get();

            foreach ($items as $item) {
                $plannedStartDate = $purchaseDate->copy()->addDays((int) $item->day_offset_start);
                $plannedEndDate = $item->day_offset_end !== null
                    ? $purchaseDate->copy()->addDays((int) $item->day_offset_end)
                    : null;

                $task = $cycle->healthTasks()->create([
                    'health_template_item_id' => $item->id,
                    'task_name' => $item->task_name,
                    'task_type' => $item->task_type,
                    'planned_start_date' => $plannedStartDate->toDateString(),
                    'planned_end_date' => $plannedEndDate?->toDateString(),
                    'status' => 'pending',
                    'target_count' => $targetCount,
                    'completed_count' => 0,
                    'remaining_count' => $targetCount,
                    'is_optional' => (bool) $item->is_optional,
                    'remarks' => $item->default_notes,
                ]);

                $this->statusResolver->refreshTask($task);
            }

            $cycle->update([
                'health_template_id' => $resolvedTemplate->id,
            ]);
        });
    }

    private function ensureDefaultTemplate(): HealthTemplate
    {
        $template = HealthTemplate::query()->firstOrCreate(
            ['code' => HealthTemplate::DEFAULT_TEMPLATE_CODE],
            [
                'name' => 'Standard Purchased Pig Cycle Plan',
                'description' => 'Default post-purchase health plan for cycle-based monitoring.',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        if (! $template->is_default || ! $template->is_active) {
            $template->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $defaults = [
            [
                'task_name' => 'Oral Medication Period',
                'task_type' => 'oral_medication_period',
                'day_offset_start' => 0,
                'day_offset_end' => 45,
                'is_optional' => false,
                'sort_order' => 1,
                'default_notes' => 'Antibiotic powder, Vetracin, and Vitamin Pro during post-purchase period.',
            ],
            [
                'task_name' => 'Injectable Vitamins',
                'task_type' => 'injectable',
                'day_offset_start' => 45,
                'day_offset_end' => null,
                'is_optional' => false,
                'sort_order' => 2,
                'default_notes' => 'One-time injectable vitamin intervention after oral medication period.',
            ],
            [
                'task_name' => 'Deworming',
                'task_type' => 'deworming',
                'day_offset_start' => 45,
                'day_offset_end' => null,
                'is_optional' => false,
                'sort_order' => 3,
                'default_notes' => 'Initial deworming or purga intervention after 45 days.',
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

        foreach ($defaults as $default) {
            HealthTemplateItem::query()->updateOrCreate(
                [
                    'health_template_id' => $template->id,
                    'sort_order' => $default['sort_order'],
                ],
                $default,
            );
        }

        return $template;
    }
}
