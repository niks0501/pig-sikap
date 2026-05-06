<?php

namespace Database\Seeders;

use App\Models\AuditTrail;
use App\Models\GeneratedReport;
use App\Models\PigCycle;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoReportsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedGeneratedReports();
            $this->seedReportSchedules();
            $this->seedAuditTrails();
        });
    }

    private function seedGeneratedReports(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->generatedReports() as $row) {
            $cycle = null;
            if ($row['cycle_code']) {
                $cycle = PigCycle::where('batch_code', $row['cycle_code'])->first();
            }

            GeneratedReport::updateOrCreate(
                [
                    'report_type' => $row['report_type'],
                    'generated_at' => $row['generated_at'],
                ],
                $this->onlyExistingColumns('generated_reports', [
                    'report_type' => $row['report_type'],
                    'format' => $row['format'],
                    'cycle_id' => $cycle ? $cycle->id : null,
                    'filters_json' => $row['filters_json'] ?? null,
                    'generated_by' => $presidentId,
                    'status' => 'generated',
                    'generated_at' => $row['generated_at'],
                    'notes' => $row['notes'] ?? null,
                ])
            );
        }
    }

    private function seedReportSchedules(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->reportSchedules() as $row) {
            ReportSchedule::updateOrCreate(
                [
                    'report_type' => $row['report_type'],
                    'frequency' => $row['frequency'],
                ],
                $this->onlyExistingColumns('report_schedules', [
                    'report_type' => $row['report_type'],
                    'format' => $row['format'],
                    'frequency' => $row['frequency'],
                    'day_of_month' => $row['day_of_month'],
                    'run_at' => $row['run_at'],
                    'status' => 'active',
                    'last_run_at' => $row['last_run_at'],
                    'next_run_at' => $row['next_run_at'],
                    'created_by' => $presidentId,
                ])
            );
        }
    }

    private function seedAuditTrails(): void
    {
        foreach ($this->auditTrails() as $row) {
            $user = User::where('email', $row['user_email'])->first();

            AuditTrail::updateOrCreate(
                [
                    'user_id' => $user ? $user->id : null,
                    'action' => $row['action'],
                    'module' => $row['module'],
                    'created_at' => $row['created_at'],
                ],
                [
                    'user_id' => $user ? $user->id : null,
                    'action' => $row['action'],
                    'module' => $row['module'],
                    'description' => $row['description'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['created_at'],
                ]
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
    private function generatedReports(): array
    {
        return [
            [
                'report_type' => 'cycle_profitability', 'format' => 'pdf',
                'cycle_code' => 'CYC-2025-001', 'generated_at' => '2026-01-16 10:00:00',
                'notes' => 'CYC-2025-001 profitability report.',
            ],
            [
                'report_type' => 'cycle_profitability', 'format' => 'pdf',
                'cycle_code' => 'CYC-2025-002', 'generated_at' => '2026-03-01 10:00:00',
                'notes' => 'CYC-2025-002 profitability report.',
            ],
            [
                'report_type' => 'expense_breakdown', 'format' => 'csv',
                'cycle_code' => 'CYC-2026-003', 'generated_at' => '2026-04-01 14:00:00',
                'notes' => 'Monthly expense breakdown for CYC-2026-003.',
            ],
            [
                'report_type' => 'expense_breakdown', 'format' => 'pdf',
                'cycle_code' => null, 'generated_at' => '2026-04-01 15:00:00',
                'notes' => 'Association-wide expense report covering all active cycles.',
                'filters_json' => json_encode(['date_from' => '2025-08-01', 'date_to' => '2026-04-01']),
            ],
            [
                'report_type' => 'sales_summary', 'format' => 'pdf',
                'cycle_code' => null, 'generated_at' => '2026-04-05 09:00:00',
                'notes' => 'Quarterly sales summary report.',
                'filters_json' => json_encode(['date_from' => '2026-01-01', 'date_to' => '2026-04-05']),
            ],
            [
                'report_type' => 'health_summary', 'format' => 'pdf',
                'cycle_code' => null, 'generated_at' => '2026-04-10 11:00:00',
                'notes' => 'Health status report across all active cycles.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reportSchedules(): array
    {
        return [
            [
                'report_type' => 'expense_breakdown', 'format' => 'pdf',
                'frequency' => 'monthly', 'day_of_month' => 1, 'run_at' => '08:00:00',
                'last_run_at' => '2026-04-01 08:00:00', 'next_run_at' => '2026-05-01 08:00:00',
            ],
            [
                'report_type' => 'sales_summary', 'format' => 'csv',
                'frequency' => 'monthly', 'day_of_month' => 5, 'run_at' => '09:00:00',
                'last_run_at' => '2026-04-05 09:00:00', 'next_run_at' => '2026-05-05 09:00:00',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditTrails(): array
    {
        return [
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-001 with 9 pigs.', 'created_at' => '2025-08-15 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-002 with 5 pigs.', 'created_at' => '2025-08-30 08:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-003 with 5 pigs.', 'created_at' => '2025-10-09 09:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-004 with 8 pigs.', 'created_at' => '2025-11-10 07:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-005 with 7 pigs.', 'created_at' => '2025-12-01 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-001 with 12 pigs.', 'created_at' => '2026-01-26 07:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-002 with 5 pigs.', 'created_at' => '2026-02-15 08:15:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-003 with 10 pigs.', 'created_at' => '2026-01-12 08:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-004 with 12 pigs.', 'created_at' => '2026-03-01 09:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-005 with 9 pigs.', 'created_at' => '2026-04-01 07:00:00'],

            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded acquisition expense for CYC-2025-001: 9 piglets.', 'created_at' => '2025-08-15 09:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded feed expense for CYC-2025-001: Hog Starter.', 'created_at' => '2025-08-16 10:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded medicine expense for CYC-2025-001: Vetracin.', 'created_at' => '2025-08-17 11:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded feed expense for CYC-2025-001: Hog Grower.', 'created_at' => '2025-09-24 09:30:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded transport expense for CYC-2025-001: Pamasahe.', 'created_at' => '2025-09-24 10:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded transport expense for CYC-2025-001: Gastos Katay.', 'created_at' => '2025-12-12 14:00:00'],

            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'sale.create', 'module' => 'PigCycleSale', 'description' => 'Recorded sale for CYC-2025-001: 1 pig sold per-head.', 'created_at' => '2025-12-04 13:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'sale.create', 'module' => 'PigCycleSale', 'description' => 'Recorded sale for CYC-2025-001: 1 pig sold per-head.', 'created_at' => '2025-12-12 15:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'sale.create', 'module' => 'PigCycleSale', 'description' => 'Recorded sale for CYC-2025-001: 1 pig sold live-weight.', 'created_at' => '2026-01-12 11:00:00'],

            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2025-001 (Completed/Closed).', 'created_at' => '2026-01-16 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2025-002 (Completed/Closed).', 'created_at' => '2026-03-01 16:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2025-004 (Completed/Closed).', 'created_at' => '2026-03-21 17:00:00'],

            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'health.incident.create', 'module' => 'CycleHealthIncident', 'description' => 'Reported sick pig incident in CYC-2026-001: 2 pigs with respiratory infection.', 'created_at' => '2026-03-15 09:00:00'],
            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'health.task.complete', 'module' => 'CycleHealthTask', 'description' => 'Completed oral medication period for CYC-2026-003.', 'created_at' => '2026-02-26 10:00:00'],
            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'health.incident.create', 'module' => 'CycleHealthIncident', 'description' => 'Reported deceased pig in CYC-2026-004: Pig #5 failure to thrive.', 'created_at' => '2026-03-15 08:00:00'],

            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.create', 'module' => 'Meeting', 'description' => 'Created meeting: Monthly Association Meeting - September 2025.', 'created_at' => '2025-09-06 10:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.confirm', 'module' => 'Meeting', 'description' => 'Confirmed meeting: Monthly Association Meeting - September 2025.', 'created_at' => '2025-09-08 12:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.create', 'module' => 'Meeting', 'description' => 'Created meeting: Pig Production Review - CYC-2025-001 Closeout.', 'created_at' => '2026-01-14 09:00:00'],

            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.create', 'module' => 'Resolution', 'description' => 'Created resolution RES-2026-001: CYC-2025-001 Profit Sharing.', 'created_at' => '2026-01-15 14:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.approve', 'module' => 'Resolution', 'description' => 'RES-2026-001 approved with 80% member approval.', 'created_at' => '2026-01-25 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.create', 'module' => 'Resolution', 'description' => 'Created resolution RES-2026-005: CYC-2026-003 startup fund.', 'created_at' => '2026-01-04 15:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.create', 'module' => 'Resolution', 'description' => 'Created resolution RES-2026-007: CYC-2026-005 startup fund.', 'created_at' => '2026-04-05 15:30:00'],

            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'withdrawal.create', 'module' => 'Withdrawal', 'description' => 'Created withdrawal for RES-2026-001: P90,000 profit sharing.', 'created_at' => '2026-01-20 11:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'withdrawal.complete', 'module' => 'Withdrawal', 'description' => 'Completed withdrawal for RES-2026-001. Funds transferred.', 'created_at' => '2026-01-25 14:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'withdrawal.create', 'module' => 'Withdrawal', 'description' => 'Created withdrawal for RES-2026-007: P66,360 startup fund.', 'created_at' => '2026-04-10 10:00:00'],

            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'dswd.submit', 'module' => 'DswdSubmission', 'description' => 'Submitted RES-2026-001 documents to DSWD.', 'created_at' => '2026-01-20 13:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'dswd.approve', 'module' => 'DswdSubmission', 'description' => 'DSWD approved RES-2026-001 submission.', 'created_at' => '2026-02-10 10:00:00'],

            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'canvass.create', 'module' => 'Canvass', 'description' => 'Created feeds price canvass for CYC-2026-003.', 'created_at' => '2026-01-05 08:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'canvass.award', 'module' => 'Canvass', 'description' => 'Awarded feeds canvass to Batangas Feeds Center.', 'created_at' => '2026-01-07 11:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'supplier.create', 'module' => 'Supplier', 'description' => 'Added supplier: Lian Agri-Supply.', 'created_at' => '2025-10-10 09:00:00'],

            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'penalty.create', 'module' => 'AttendancePenalty', 'description' => 'Applied penalty for unexcused absence at December meeting.', 'created_at' => '2025-12-10 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'penalty.waive', 'module' => 'AttendancePenalty', 'description' => 'Waived penalty for member with valid excuse.', 'created_at' => '2025-12-15 11:00:00'],

            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated CYC-2025-001 profitability report (PDF).', 'created_at' => '2026-01-16 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated association-wide expense report (PDF).', 'created_at' => '2026-04-01 15:00:00'],

            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'login', 'module' => 'User', 'description' => 'President Eva Vivas logged in.', 'created_at' => '2026-04-25 08:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'login', 'module' => 'User', 'description' => 'Treasurer Anaceta Guevarra logged in.', 'created_at' => '2026-04-25 08:15:00'],
        ];
    }
}
