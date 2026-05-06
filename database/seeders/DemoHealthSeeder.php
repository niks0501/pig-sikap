<?php

namespace Database\Seeders;

use App\Models\CycleHealthIncident;
use App\Models\CycleHealthTask;
use App\Models\HealthTemplate;
use App\Models\HealthTemplateItem;
use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoHealthSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedPigProfiles();
            $this->seedHealthTasks();
            $this->seedHealthIncidents();
        });
    }

    private function seedPigProfiles(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->pigProfiles() as $row) {
            $cycle = PigCycle::where('batch_code', $row['batch_code'])->first();

            if (! $cycle) {
                continue;
            }

            Pig::updateOrCreate(
                [
                    'batch_id' => $cycle->id,
                    'pig_no' => $row['pig_no'],
                ],
                $this->onlyExistingColumns('pigs', [
                    'batch_id' => $cycle->id,
                    'pig_no' => $row['pig_no'],
                    'ear_mark_type' => $row['ear_mark_type'],
                    'ear_mark_value' => $row['ear_mark_value'],
                    'sex' => $row['sex'],
                    'status' => $row['status'],
                    'remarks' => $row['remarks'] ?? null,
                    'created_by' => $presidentId,
                ])
            );

            if (Schema::hasColumn('pig_cycles', 'has_pig_profiles') && ! $cycle->has_pig_profiles) {
                $cycle->update(['has_pig_profiles' => true]);
            }
        }
    }

    private function seedHealthTasks(): void
    {
        $template = HealthTemplate::where('code', HealthTemplate::DEFAULT_TEMPLATE_CODE)->first();

        if (! $template) {
            return;
        }

        $templateItems = HealthTemplateItem::where('health_template_id', $template->id)
            ->orderBy('sort_order')
            ->get();

        $caretakerId = User::where('email', 'officer.maricon@pigsikap.local')->value('id');

        foreach ($this->healthTaskCycles() as $row) {
            $cycle = PigCycle::where('batch_code', $row['batch_code'])->first();

            if (! $cycle) {
                continue;
            }

            $purchaseDate = $cycle->date_of_purchase;

            foreach ($templateItems as $item) {
                $plannedStart = date('Y-m-d', strtotime($purchaseDate.' + '.$item->day_offset_start.' days'));
                $plannedEnd = $item->day_offset_end
                    ? date('Y-m-d', strtotime($purchaseDate.' + '.$item->day_offset_end.' days'))
                    : null;

                CycleHealthTask::updateOrCreate(
                    [
                        'batch_id' => $cycle->id,
                        'health_template_item_id' => $item->id,
                    ],
                    $this->onlyExistingColumns('cycle_health_tasks', [
                        'batch_id' => $cycle->id,
                        'health_template_item_id' => $item->id,
                        'task_name' => $item->task_name,
                        'task_type' => $item->task_type,
                        'planned_start_date' => $plannedStart,
                        'planned_end_date' => $plannedEnd,
                        'actual_date' => $row['status'] === 'completed' ? $plannedStart : null,
                        'status' => $row['status'] ?? 'pending',
                        'target_count' => $cycle->initial_count,
                        'completed_count' => $row['status'] === 'completed' ? $cycle->initial_count : 0,
                        'remaining_count' => $row['status'] === 'completed' ? 0 : $cycle->initial_count,
                        'is_optional' => $item->is_optional,
                        'remarks' => $row['status'] === 'completed' ? 'Completed as scheduled.' : null,
                        'completed_by' => $row['status'] === 'completed' ? $caretakerId : null,
                    ])
                );
            }
        }
    }

    private function seedHealthIncidents(): void
    {
        $caretakerId = User::where('email', 'officer.maricon@pigsikap.local')->value('id');

        foreach ($this->healthIncidents() as $row) {
            $cycle = PigCycle::where('batch_code', $row['batch_code'])->first();

            if (! $cycle) {
                continue;
            }

            $pigId = null;
            if ($row['pig_no'] && Schema::hasTable('pigs')) {
                $pigId = Pig::where('batch_id', $cycle->id)
                    ->where('pig_no', $row['pig_no'])
                    ->value('id');
            }

            CycleHealthIncident::updateOrCreate(
                [
                    'batch_id' => $cycle->id,
                    'incident_type' => $row['incident_type'],
                    'date_reported' => $row['date_reported'],
                ],
                $this->onlyExistingColumns('cycle_health_incidents', [
                    'batch_id' => $cycle->id,
                    'pig_id' => $pigId,
                    'incident_type' => $row['incident_type'],
                    'date_reported' => $row['date_reported'],
                    'affected_count' => $row['affected_count'],
                    'suspected_cause' => $row['suspected_cause'],
                    'treatment_given' => $row['treatment_given'],
                    'remarks' => $row['remarks'] ?? null,
                    'reported_by' => $caretakerId,
                ])
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function onlyExistingColumns(string $table, array $attributes): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return collect($attributes)
            ->filter(fn ($value, string $column): bool => Schema::hasColumn($table, $column))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pigProfiles(): array
    {
        $profiles = [];

        // CYC-2026-003 (10 pigs)
        for ($i = 1; $i <= 10; $i++) {
            $profiles[] = [
                'batch_code' => 'CYC-2026-003',
                'pig_no' => $i,
                'ear_mark_type' => $i % 3 === 0 ? 'tag' : 'notch',
                'ear_mark_value' => 'E3-'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'sex' => $i % 3 === 0 ? 'Female' : 'Male',
                'status' => 'Active',
                'remarks' => null,
            ];
        }

        // CYC-2026-004 (12 pigs, 1 mortality)
        for ($i = 1; $i <= 12; $i++) {
            $profiles[] = [
                'batch_code' => 'CYC-2026-004',
                'pig_no' => $i,
                'ear_mark_type' => $i % 2 === 0 ? 'notch' : 'tag',
                'ear_mark_value' => 'E4-'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'sex' => $i % 3 === 0 ? 'Female' : 'Male',
                'status' => $i === 5 ? 'Deceased' : 'Active',
                'remarks' => $i === 5 ? 'Mortality: week 2, suspected weak piglet.' : null,
            ];
        }

        // CYC-2026-005 (9 pigs)
        for ($i = 1; $i <= 9; $i++) {
            $profiles[] = [
                'batch_code' => 'CYC-2026-005',
                'pig_no' => $i,
                'ear_mark_type' => $i % 2 === 0 ? 'tag' : 'notch',
                'ear_mark_value' => 'E5-'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'sex' => $i % 4 === 0 ? 'Female' : 'Male',
                'status' => 'Active',
                'remarks' => null,
            ];
        }

        return $profiles;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function healthTaskCycles(): array
    {
        return [
            ['batch_code' => 'CYC-2026-003', 'status' => 'completed'],
            ['batch_code' => 'CYC-2026-004', 'status' => 'in_progress'],
            ['batch_code' => 'CYC-2026-005', 'status' => 'in_progress'],
            ['batch_code' => 'CYC-2025-005', 'status' => 'completed'],
            ['batch_code' => 'CYC-2026-001', 'status' => 'completed'],
            ['batch_code' => 'CYC-2026-002', 'status' => 'in_progress'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function healthIncidents(): array
    {
        return [
            [
                'batch_code' => 'CYC-2026-001',
                'pig_no' => null,
                'incident_type' => 'sick',
                'date_reported' => '2026-03-15',
                'affected_count' => 2,
                'suspected_cause' => 'Mild respiratory infection due to weather change.',
                'treatment_given' => 'Vetracin oral spray for 5 days. Isolated for observation.',
                'remarks' => 'Both pigs recovered within one week.',
            ],
            [
                'batch_code' => 'CYC-2026-001',
                'pig_no' => null,
                'incident_type' => 'sick',
                'date_reported' => '2026-04-05',
                'affected_count' => 1,
                'suspected_cause' => 'Possible parasitic infection. Mild diarrhea observed.',
                'treatment_given' => 'Deworming dose administered. Probiotics mixed with feed.',
                'remarks' => 'Recovered after 3 days.',
            ],
            [
                'batch_code' => 'CYC-2026-002',
                'pig_no' => null,
                'incident_type' => 'sick',
                'date_reported' => '2026-03-28',
                'affected_count' => 1,
                'suspected_cause' => 'Skin irritation from pen flooring.',
                'treatment_given' => 'Topical ointment applied. Pen area cleaned and disinfected.',
                'remarks' => 'Condition improved within 4 days.',
            ],
            [
                'batch_code' => 'CYC-2026-003',
                'pig_no' => 3,
                'incident_type' => 'sick',
                'date_reported' => '2026-03-10',
                'affected_count' => 1,
                'suspected_cause' => 'Reduced appetite. Possible stress from recent feed change.',
                'treatment_given' => 'Vitamin B-complex injection. Appetite stimulant mixed with feed.',
                'remarks' => 'Resumed normal feeding after 2 days.',
            ],
            [
                'batch_code' => 'CYC-2026-004',
                'pig_no' => 5,
                'incident_type' => 'deceased',
                'date_reported' => '2026-03-15',
                'affected_count' => 1,
                'suspected_cause' => 'Weak piglet. Failure to thrive since acquisition.',
                'treatment_given' => 'Oral medication and force-feeding attempted. Did not respond.',
                'remarks' => 'Piglet #5 deceased. Reported to association for documentation.',
            ],
        ];
    }
}
