<?php

namespace Database\Seeders;

use App\Models\PigCycle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoCycleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCycles();
        });
    }

    private function seedCycles(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->cycles() as $row) {
            $caretakerId = User::where('email', $row['caretaker_email'])->value('id');

            PigCycle::updateOrCreate(
                ['batch_code' => $row['batch_code']],
                $this->onlyExistingColumns('pig_cycles', [
                    'batch_code' => $row['batch_code'],
                    'caretaker_user_id' => $caretakerId,
                    'cycle_number' => $row['cycle_number'],
                    'date_of_purchase' => $row['date_of_purchase'],
                    'initial_count' => $row['initial_count'],
                    'current_count' => $row['current_count'],
                    'average_weight' => $row['average_weight'],
                    'stage' => $row['stage'],
                    'status' => $row['status'],
                    'has_pig_profiles' => $row['has_pig_profiles'],
                    'notes' => $row['notes'],
                    'last_reviewed_at' => now(),
                    'archived_at' => in_array($row['status'], ['Sold', 'Closed'], true) ? now() : null,
                    'archived_by' => in_array($row['status'], ['Sold', 'Closed'], true) ? $presidentId : null,
                    'created_by' => $presidentId,
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
    private function cycles(): array
    {
        return [
            [
                'batch_code' => 'CYC-2025-004',
                'caretaker_email' => 'member.antonio@pigsikap.local',
                'cycle_number' => 4,
                'date_of_purchase' => '2025-11-10',
                'initial_count' => 8,
                'current_count' => 0,
                'average_weight' => null,
                'stage' => 'Completed',
                'status' => 'Closed',
                'has_pig_profiles' => false,
                'notes' => 'Batch of 8 piglets from Batangas supplier. Sold by March 2026. Net profit: positive.',
            ],
            [
                'batch_code' => 'CYC-2025-005',
                'caretaker_email' => 'member.josefina@pigsikap.local',
                'cycle_number' => 5,
                'date_of_purchase' => '2025-12-01',
                'initial_count' => 7,
                'current_count' => 7,
                'average_weight' => 82.5,
                'stage' => 'For Sale',
                'status' => 'Ready for Sale',
                'has_pig_profiles' => false,
                'notes' => 'Batch of 7 pigs nearing harvest weight. Expected sale by end of April 2026.',
            ],
            [
                'batch_code' => 'CYC-2026-003',
                'caretaker_email' => 'member.roberto@pigsikap.local',
                'cycle_number' => 3,
                'date_of_purchase' => '2026-01-12',
                'initial_count' => 10,
                'current_count' => 10,
                'average_weight' => 68.0,
                'stage' => 'Fattening',
                'status' => 'Under Monitoring',
                'has_pig_profiles' => true,
                'notes' => 'Good weight gain. On track for harvest by June 2026.',
            ],
            [
                'batch_code' => 'CYC-2026-004',
                'caretaker_email' => 'member.teresa@pigsikap.local',
                'cycle_number' => 4,
                'date_of_purchase' => '2026-03-01',
                'initial_count' => 12,
                'current_count' => 11,
                'average_weight' => 38.0,
                'stage' => 'Growing',
                'status' => 'Active',
                'has_pig_profiles' => true,
                'notes' => 'One piglet mortality in week 2. Remaining 11 are healthy and gaining weight steadily.',
            ],
            [
                'batch_code' => 'CYC-2026-005',
                'caretaker_email' => 'officer.maricon@pigsikap.local',
                'cycle_number' => 5,
                'date_of_purchase' => '2026-04-01',
                'initial_count' => 9,
                'current_count' => 9,
                'average_weight' => 12.5,
                'stage' => 'Piglet',
                'status' => 'Active',
                'has_pig_profiles' => true,
                'notes' => 'New batch. Oral medication period in progress. All piglets are active and feeding well.',
            ],
        ];
    }
}
