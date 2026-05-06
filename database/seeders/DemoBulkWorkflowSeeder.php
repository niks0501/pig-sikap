<?php

namespace Database\Seeders;

use App\Models\AttendancePenalty;
use App\Models\DswdSubmission;
use App\Models\LiquidationReport;
use App\Models\Meeting;
use App\Models\MeetingSignatory;
use App\Models\Resolution;
use App\Models\ResolutionApproval;
use App\Models\ResolutionLineItem;
use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoBulkWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedMeetings();
            $this->seedResolutions();
            $this->seedWithdrawalsAndDswd();
            $this->seedPenalties();
        });
    }

    private function seedMeetings(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->meetings() as $row) {
            $meeting = Meeting::updateOrCreate(
                ['title' => $row['title'], 'date' => $row['date']],
                $this->onlyExistingCols('meetings', [
                    'title' => $row['title'], 'date' => $row['date'],
                    'location' => $row['location'], 'agenda' => $row['agenda'],
                    'minutes_summary' => $row['minutes_summary'], 'status' => $row['status'],
                    'meeting_type' => $row['meeting_type'],
                    'created_by' => $presidentId, 'updated_by' => $presidentId,
                ])
            );
            $absentEmails = $row['absent_emails'] ?? [];
            foreach ($this->allMemberEmails() as $email) {
                $user = User::where('email', $email)->first();
                if (! $user) continue;
                MeetingSignatory::updateOrCreate(
                    ['meeting_id' => $meeting->id, 'user_id' => $user->id],
                    ['meeting_id' => $meeting->id, 'user_id' => $user->id, 'attendance_status' => in_array($email, $absentEmails) ? 'absent' : 'present']
                );
            }
        }
    }

    private function seedResolutions(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->resolutions() as $row) {
            $meeting = Meeting::where('title', $row['meeting_title'])->where('date', $row['meeting_date'])->first();

            $resolution = Resolution::updateOrCreate(
                ['resolution_number' => $row['resolution_number']],
                $this->onlyExistingCols('resolutions', [
                    'resolution_number' => $row['resolution_number'],
                    'meeting_id' => $meeting ? $meeting->id : 1, 'title' => $row['title'],
                    'description' => $row['description'], 'status' => $row['status'],
                    'approval_deadline' => $row['approval_deadline'] ?? null,
                    'is_approval_locked' => in_array($row['status'], ['approved', 'finalized']),
                    'created_by' => $presidentId, 'updated_by' => $presidentId,
                    'focal_person_name' => $row['focal_person_name'] ?? 'Eva G. Vivas',
                ])
            );

            foreach ($row['line_items'] as $item) {
                ResolutionLineItem::updateOrCreate(
                    ['resolution_id' => $resolution->id, 'description' => $item['description']],
                    ['resolution_id' => $resolution->id, 'category' => $item['category'], 'description' => $item['description'], 'quantity' => $item['quantity'], 'unit' => $item['unit'], 'unit_cost' => $item['unit_cost'], 'total' => $item['total'], 'sort_order' => $item['sort_order']]
                );
            }

            // Approvals: 80%+ of all members approve
            $memberIds = User::whereIn('email', $this->allMemberEmails())
                ->whereNotIn('email', ['president.eva@pigsikap.local'])
                ->pluck('id')->toArray();
            $total = count($memberIds);
            $approveCount = (int) ceil($total * 0.80);
            shuffle($memberIds);
            foreach (array_slice($memberIds, 0, $approveCount) as $uid) {
                ResolutionApproval::updateOrCreate(
                    ['resolution_id' => $resolution->id, 'user_id' => $uid],
                    ['resolution_id' => $resolution->id, 'user_id' => $uid, 'is_approved' => true, 'approved_at' => now()]
                );
            }
            foreach (array_slice($memberIds, $approveCount) as $uid) {
                ResolutionApproval::updateOrCreate(
                    ['resolution_id' => $resolution->id, 'user_id' => $uid],
                    ['resolution_id' => $resolution->id, 'user_id' => $uid, 'is_approved' => false]
                );
            }
        }
    }

    private function seedWithdrawalsAndDswd(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        $treasurerId = User::where('email', 'treasurer.anaceta@pigsikap.local')->value('id');

        foreach ($this->withdrawals() as $row) {
            $resolution = Resolution::where('resolution_number', $row['resolution_number'])->first();
            if (! $resolution) continue;

            $withdrawal = Withdrawal::updateOrCreate(
                ['resolution_id' => $resolution->id, 'amount' => $row['amount']],
                $this->onlyExistingCols('withdrawals', [
                    'resolution_id' => $resolution->id, 'requested_by' => $presidentId,
                    'authorized_withdrawer_id' => $treasurerId, 'amount' => $row['amount'],
                    'currency' => 'PHP', 'bank_reference' => $row['bank_reference'] ?? null,
                    'status' => $row['status'], 'requested_at' => $row['requested_at'],
                    'completed_at' => $row['status'] === 'completed' ? now() : null,
                    'notes' => $row['notes'] ?? null,
                ])
            );

            if ($row['status'] === 'completed' && ($row['has_liquidation'] ?? false)) {
                LiquidationReport::updateOrCreate(
                    ['withdrawal_id' => $withdrawal->id],
                    $this->onlyExistingCols('liquidation_reports', [
                        'withdrawal_id' => $withdrawal->id, 'generated_by' => $treasurerId,
                        'summary' => 'All expenses accounted for and receipts submitted.',
                        'liquidation_status' => 'submitted',
                    ])
                );
            }
        }

        foreach ($this->dswdSubmissions() as $row) {
            $resolution = Resolution::where('resolution_number', $row['resolution_number'])->first();
            if (! $resolution) continue;
            DswdSubmission::updateOrCreate(
                ['resolution_id' => $resolution->id],
                $this->onlyExistingCols('dswd_submissions', [
                    'resolution_id' => $resolution->id, 'status' => $row['status'],
                    'notes' => $row['notes'], 'submitted_by' => $presidentId,
                    'submitted_at' => $row['status'] !== 'not_submitted' ? now() : null,
                    'dswd_approval_date' => $row['status'] === 'approved' ? now() : null,
                ])
            );
        }
    }

    private function seedPenalties(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        foreach ($this->penalties() as $row) {
            $user = User::where('email', $row['user_email'])->first();
            $meeting = Meeting::where('title', $row['meeting_title'])->where('date', $row['meeting_date'])->first();
            if (! $user || ! $meeting) continue;
            AttendancePenalty::updateOrCreate(
                ['user_id' => $user->id, 'meeting_id' => $meeting->id],
                $this->onlyExistingCols('attendance_penalties', [
                    'user_id' => $user->id, 'meeting_id' => $meeting->id, 'amount' => $row['amount'],
                    'status' => $row['status'], 'reason' => $row['reason'],
                    'waived_by' => $row['status'] === 'waived' ? $presidentId : null,
                    'waived_at' => $row['status'] === 'waived' ? now() : null,
                    'paid_at' => $row['status'] === 'paid' ? now() : null,
                    'created_by' => $presidentId,
                ])
            );
        }
    }

    private function allMemberEmails(): array
    {
        return [
            'president.eva@pigsikap.local', 'secretary.ronalyn@pigsikap.local', 'treasurer.anaceta@pigsikap.local',
            'officer.maricon@pigsikap.local', 'canvasser.pedro@pigsikap.local', 'auditor.juan@pigsikap.local',
            'operations.maria@pigsikap.local', 'member.leciria@pigsikap.local', 'member.antonio@pigsikap.local',
            'member.josefina@pigsikap.local', 'member.roberto@pigsikap.local', 'member.teresa@pigsikap.local',
            'member.rodrigo@pigsikap.local', 'member.elena@pigsikap.local', 'member.luis@pigsikap.local',
            'caretaker.cycle3@pigsikap.local', 'caretaker.cycle4@pigsikap.local', 'caretaker.cycle5@pigsikap.local',
            'member.corazon@pigsikap.local', 'member.danilo@pigsikap.local', 'member.erlinda@pigsikap.local',
            'member.francisco@pigsikap.local', 'member.gloria@pigsikap.local', 'member.hernando@pigsikap.local',
            'member.imelda@pigsikap.local', 'member.josephine@pigsikap.local', 'member.karen@pigsikap.local',
            'member.leonardo@pigsikap.local', 'member.myrna@pigsikap.local', 'member.nelson@pigsikap.local',
            'member.ofelia@pigsikap.local', 'member.pilar@pigsikap.local', 'member.rodelio@pigsikap.local',
        ];
    }

    private function onlyExistingCols(string $table, array $attrs): array
    {
        if (! Schema::hasTable($table)) return [];
        return collect($attrs)->filter(fn($v, string $c): bool => Schema::hasColumn($table, $c))->all();
    }

    private function meetings(): array
    {
        return [
            ['title' => 'Monthly Association Meeting - October 2025', 'date' => '2025-10-06', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. CYC-2025-004 mid-cycle review\n2. CYC-2025-005 approval\n3. Supplier evaluation", 'minutes_summary' => 'CYC-2025-004 progressing well. CYC-2025-005 approved.', 'status' => 'confirmed', 'meeting_type' => 'monthly_association', 'absent_emails' => ['member.rodrigo@pigsikap.local']],
            ['title' => 'Pig Production Review - CYC-2025-006 Startup', 'date' => '2025-09-02', 'location' => 'Association Office', 'agenda' => "1. CYC-2025-006 approval\n2. Feeds budget allocation\n3. Caretaker assignment", 'minutes_summary' => 'CYC-2025-006 approved with 10 piglets. Budget allocated.', 'status' => 'confirmed', 'meeting_type' => 'pig_production', 'absent_emails' => []],
            ['title' => 'Pig Production Review - CYC-2025-007 and CYC-2025-008', 'date' => '2025-10-12', 'location' => 'Association Office', 'agenda' => "1. CYC-2025-007 and CYC-2025-008 approval\n2. Pen expansion discussion", 'minutes_summary' => 'Both cycles approved. Pen expansion tabled for next meeting.', 'status' => 'confirmed', 'meeting_type' => 'pig_production', 'absent_emails' => ['member.luis@pigsikap.local']],
            ['title' => 'Monthly Association Meeting - November 2025', 'date' => '2025-11-03', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. Quarterly financial report\n2. CYC-2025-006 status\n3. Membership drive", 'minutes_summary' => 'Financial report presented. Three new members inducted.', 'status' => 'confirmed', 'meeting_type' => 'monthly_association', 'absent_emails' => ['member.karen@pigsikap.local']],
            ['title' => 'Pig Production Review - CYC-2025-006 Closeout', 'date' => '2026-02-10', 'location' => 'Association Office', 'agenda' => "1. CYC-2025-006 final report\n2. Profit sharing distribution\n3. CYC-2026-005 update", 'minutes_summary' => 'CYC-2025-006 closed. Net profit of P68,000 achieved.', 'status' => 'confirmed', 'meeting_type' => 'pig_production', 'absent_emails' => ['member.pilar@pigsikap.local']],
            ['title' => 'Monthly Association Meeting - January 2026', 'date' => '2026-01-05', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. 2026 operational plan\n2. CYC-2026-003 mid-review\n3. Budget review", 'minutes_summary' => '2026 plan approved. Budget for 5 new cycles allocated.', 'status' => 'confirmed', 'meeting_type' => 'monthly_association', 'absent_emails' => []],
            ['title' => 'Pig Production Review - CYC-2025-007 and CYC-2025-008 Closeout', 'date' => '2026-02-20', 'location' => 'Association Office', 'agenda' => "1. CYC-2025-007 and CYC-2025-008 final reports\n2. Profit sharing\n3. Pen maintenance fund", 'minutes_summary' => 'Both cycles closed with positive profit. Funds allocated for pen maintenance.', 'status' => 'confirmed', 'meeting_type' => 'pig_production', 'absent_emails' => ['member.rodelio@pigsikap.local']],
            ['title' => 'Monthly Association Meeting - February 2026', 'date' => '2026-02-02', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. CYC-2026-004 progress\n2. CYC-2025-005 closeout prep\n3. Feeds price update", 'minutes_summary' => 'Feeds supplier notified of price increase. Association to canvass alternatives.', 'status' => 'confirmed', 'meeting_type' => 'monthly_association', 'absent_emails' => ['member.hernando@pigsikap.local']],
            ['title' => 'Monthly Association Meeting - March 2026', 'date' => '2026-03-01', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. CYC-2026-005 review\n2. CYC-2026-006 through 010 approval\n3. DSWD update", 'minutes_summary' => 'Five new cycles approved for 2026. DSWD submissions on track.', 'status' => 'confirmed', 'meeting_type' => 'monthly_association', 'absent_emails' => ['member.imelda@pigsikap.local']],
            ['title' => 'Pig Production Review - CYC-2026-006 Closeout', 'date' => '2026-04-10', 'location' => 'Association Office', 'agenda' => "1. CYC-2026-006 final report\n2. CYC-2026-007 closeout preview\n3. Feeds supplier update", 'minutes_summary' => 'CYC-2026-006 achieved P32,000 net profit. New supplier onboarded.', 'status' => 'confirmed', 'meeting_type' => 'pig_production', 'absent_emails' => []],
            ['title' => 'Pig Production Review - CYC-2026-007 and CYC-2026-008 Closeout', 'date' => '2026-04-20', 'location' => 'Association Office', 'agenda' => "1. CYC-2026-007 and CYC-2026-008 final reports\n2. Emergency fund discussion", 'minutes_summary' => 'Both cycles profitable. Emergency fund increased to P5,000.', 'status' => 'confirmed', 'meeting_type' => 'pig_production', 'absent_emails' => ['member.ofelia@pigsikap.local']],
            ['title' => 'Monthly Association Meeting - April 2026', 'date' => '2026-04-06', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. CYC-2026-009 and CYC-2026-010 closeout\n2. CYC-2026-011 through 015 approval\n3. Quarterly financials", 'minutes_summary' => 'Association in strong financial position. Six new cycles approved.', 'status' => 'confirmed', 'meeting_type' => 'monthly_association', 'absent_emails' => ['member.nelson@pigsikap.local']],
            ['title' => 'General Assembly - Mid-Year Review 2026', 'date' => '2026-04-30', 'location' => 'Humayingan Barangay Hall', 'agenda' => "1. Mid-year financial report\n2. Operational highlights\n3. Election of new officers", 'minutes_summary' => 'Strong H1 performance. All cycles profitable. Officers re-elected.', 'status' => 'confirmed', 'meeting_type' => 'general', 'absent_emails' => ['member.myrna@pigsikap.local']],
        ];
    }

    private function resolutions(): array
    {
        return [
            [
                'resolution_number' => 'RES-2026-008', 'meeting_title' => 'Pig Production Review - CYC-2025-006 Closeout', 'meeting_date' => '2026-02-10',
                'title' => 'Approval of CYC-2025-006 Profit Sharing',
                'description' => 'Resolution approving profit sharing distribution for CYC-2025-006.',
                'status' => 'finalized', 'approval_deadline' => '2026-02-25', 'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Share (50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 34000, 'total' => 34000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 17000, 'total' => 17000, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 17000, 'total' => 17000, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-009', 'meeting_title' => 'Pig Production Review - CYC-2025-007 and CYC-2025-008 Closeout', 'meeting_date' => '2026-02-20',
                'title' => 'Approval of CYC-2025-007 and CYC-2025-008 Profit Sharing',
                'description' => 'Combined profit sharing for CYC-2025-007 and CYC-2025-008.',
                'status' => 'finalized', 'approval_deadline' => '2026-03-10', 'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Shares (2 cycles, 50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 55000, 'total' => 55000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 27500, 'total' => 27500, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 27500, 'total' => 27500, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-010', 'meeting_title' => 'Pig Production Review - CYC-2026-006 Closeout', 'meeting_date' => '2026-04-10',
                'title' => 'Approval of CYC-2026-006 Profit Sharing',
                'description' => 'Profit sharing for CYC-2026-006.',
                'status' => 'finalized', 'approval_deadline' => '2026-04-25', 'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Share (50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 16000, 'total' => 16000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 8000, 'total' => 8000, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 8000, 'total' => 8000, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-011', 'meeting_title' => 'Pig Production Review - CYC-2026-007 and CYC-2026-008 Closeout', 'meeting_date' => '2026-04-20',
                'title' => 'Approval of CYC-2026-007 and CYC-2026-008 Profit Sharing',
                'description' => 'Combined profit sharing for CYC-2026-007 and CYC-2026-008.',
                'status' => 'approved', 'approval_deadline' => '2026-05-10', 'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Shares (2 cycles, 50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 45000, 'total' => 45000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 22500, 'total' => 22500, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 22500, 'total' => 22500, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-012', 'meeting_title' => 'Monthly Association Meeting - March 2026', 'meeting_date' => '2026-03-01',
                'title' => 'Approval of 2026-Q2 Cycle Expansion (CYC-2026-011 through CYC-2026-015)',
                'description' => 'Resolution to approve five new production cycles for Q2 2026.',
                'status' => 'finalized', 'approval_deadline' => '2026-03-20', 'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'acquisition', 'description' => 'Piglets for 5 new cycles (~45 heads)', 'quantity' => 45, 'unit' => 'head', 'unit_cost' => 7000, 'total' => 315000, 'sort_order' => 1],
                    ['category' => 'feed', 'description' => 'Initial feeds for 5 cycles', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 25000, 'total' => 25000, 'sort_order' => 2],
                    ['category' => 'medicine', 'description' => 'Initial medicines for 5 cycles', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 3000, 'total' => 3000, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-013', 'meeting_title' => 'General Assembly - Mid-Year Review 2026', 'meeting_date' => '2026-04-30',
                'title' => 'Approval of Pen Expansion and Maintenance Budget',
                'description' => 'Resolution for pen expansion to accommodate growing cycle count.',
                'status' => 'approved', 'approval_deadline' => '2026-05-15', 'focal_person_name' => 'Pedro S. Santos',
                'line_items' => [
                    ['category' => 'supplies', 'description' => 'Construction Materials (cement, hollow blocks, roofing)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 15000, 'total' => 15000, 'sort_order' => 1],
                    ['category' => 'supplies', 'description' => 'Labor Cost', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 5000, 'total' => 5000, 'sort_order' => 2],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-014', 'meeting_title' => 'Pig Production Review - CYC-2026-009 and CYC-2026-010 Closeout', 'meeting_date' => '2026-04-06',
                'title' => 'Approval of CYC-2026-009 and CYC-2026-010 Profit Sharing',
                'description' => 'Combined profit sharing for CYC-2026-009 and CYC-2026-010.',
                'status' => 'finalized', 'approval_deadline' => '2026-04-20', 'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Shares (2 cycles, 50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 38000, 'total' => 38000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 19000, 'total' => 19000, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 19000, 'total' => 19000, 'sort_order' => 3],
                ],
            ],
        ];
    }

    private function withdrawals(): array
    {
        return [
            ['resolution_number' => 'RES-2026-008', 'amount' => 68000, 'status' => 'completed', 'requested_at' => '2026-02-15', 'bank_reference' => 'BDO-TRF-B01', 'notes' => 'CYC-2025-006 profit sharing.', 'has_liquidation' => true],
            ['resolution_number' => 'RES-2026-009', 'amount' => 110000, 'status' => 'completed', 'requested_at' => '2026-02-25', 'bank_reference' => 'BDO-TRF-B02', 'notes' => 'CYC-2025-007 and 008 combined profit sharing.', 'has_liquidation' => true],
            ['resolution_number' => 'RES-2026-010', 'amount' => 32000, 'status' => 'completed', 'requested_at' => '2026-04-15', 'bank_reference' => 'BDO-TRF-B03', 'notes' => 'CYC-2026-006 profit sharing.', 'has_liquidation' => true],
            ['resolution_number' => 'RES-2026-012', 'amount' => 343000, 'status' => 'completed', 'requested_at' => '2026-03-05', 'bank_reference' => 'BDO-TRF-B04', 'notes' => 'Q2 cycle expansion fund.', 'has_liquidation' => true],
            ['resolution_number' => 'RES-2026-014', 'amount' => 76000, 'status' => 'completed', 'requested_at' => '2026-04-10', 'bank_reference' => 'BDO-TRF-B05', 'notes' => 'CYC-2026-009 and 010 profit sharing.', 'has_liquidation' => true],
            ['resolution_number' => 'RES-2026-011', 'amount' => 90000, 'status' => 'pending', 'requested_at' => '2026-04-25', 'bank_reference' => null, 'notes' => 'Pending bank processing.', 'has_liquidation' => false],
            ['resolution_number' => 'RES-2026-013', 'amount' => 20000, 'status' => 'pending', 'requested_at' => '2026-05-02', 'bank_reference' => null, 'notes' => 'Awaiting DSWD clearance.', 'has_liquidation' => false],
        ];
    }

    private function dswdSubmissions(): array
    {
        return [
            ['resolution_number' => 'RES-2026-008', 'status' => 'approved', 'notes' => 'DSWD approved profit sharing distribution.'],
            ['resolution_number' => 'RES-2026-009', 'status' => 'approved', 'notes' => 'DSWD approved combined profit sharing.'],
            ['resolution_number' => 'RES-2026-012', 'status' => 'approved', 'notes' => 'DSWD approved cycle expansion budget.'],
            ['resolution_number' => 'RES-2026-014', 'status' => 'approved', 'notes' => 'DSWD approved profit sharing.'],
            ['resolution_number' => 'RES-2026-011', 'status' => 'submitted', 'notes' => 'Documents submitted to DSWD. Awaiting review.'],
        ];
    }

    private function penalties(): array
    {
        return [
            ['user_email' => 'member.rodelio@pigsikap.local', 'meeting_title' => 'Pig Production Review - CYC-2025-007 and CYC-2025-008 Closeout', 'meeting_date' => '2026-02-20', 'amount' => 100, 'status' => 'paid', 'reason' => 'Unexcused absence at production review.'],
            ['user_email' => 'member.nelson@pigsikap.local', 'meeting_title' => 'Monthly Association Meeting - April 2026', 'meeting_date' => '2026-04-06', 'amount' => 100, 'status' => 'pending', 'reason' => 'Unexcused absence at April meeting.'],
            ['user_email' => 'member.myrna@pigsikap.local', 'meeting_title' => 'General Assembly - Mid-Year Review 2026', 'meeting_date' => '2026-04-30', 'amount' => 150, 'status' => 'pending', 'reason' => 'Unexcused absence at general assembly.'],
        ];
    }
}
