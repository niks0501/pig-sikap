<?php

namespace Database\Seeders;

use App\Models\AttendancePenalty;
use App\Models\DswdSubmission;
use App\Models\LiquidationReport;
use App\Models\Meeting;
use App\Models\MeetingSignatory;
use App\Models\PigCycle;
use App\Models\Resolution;
use App\Models\ResolutionApproval;
use App\Models\ResolutionLineItem;
use App\Models\ResolutionMemberSnapshot;
use App\Models\ResolutionWithdrawalAuthorization;
use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedMeetings();
            $this->seedResolutions();
            $this->seedWithdrawals();
            $this->seedDswdSubmissions();
            $this->seedPenalties();
        });
    }

    // ─── MEETINGS ─────────────────────────────────────────────

    private function seedMeetings(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->meetings() as $row) {
            $meeting = Meeting::updateOrCreate(
                [
                    'title' => $row['title'],
                    'date' => $row['date'],
                ],
                $this->onlyExistingColumns('meetings', [
                    'title' => $row['title'],
                    'date' => $row['date'],
                    'location' => $row['location'],
                    'agenda' => $row['agenda'],
                    'minutes_summary' => $row['minutes_summary'],
                    'status' => $row['status'],
                    'meeting_type' => $row['meeting_type'],
                    'created_by' => $presidentId,
                    'updated_by' => $presidentId,
                ])
            );

            $this->seedSignatories($meeting, $row['absent_emails'] ?? []);
        }
    }

    private function seedSignatories(Meeting $meeting, array $absentEmails): void
    {
        $memberIds = User::whereIn('email', $this->memberEmails())->pluck('id')->toArray();

        foreach ($memberIds as $userId) {
            $user = User::find($userId);
            $isAbsent = in_array($user->email, $absentEmails, true);

            MeetingSignatory::updateOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $userId,
                ],
                [
                    'meeting_id' => $meeting->id,
                    'user_id' => $userId,
                    'attendance_status' => $isAbsent ? 'absent' : 'present',
                ]
            );
        }
    }

    private function memberEmails(): array
    {
        return [
            'president.eva@pigsikap.local',
            'secretary.ronalyn@pigsikap.local',
            'treasurer.anaceta@pigsikap.local',
            'officer.maricon@pigsikap.local',
            'canvasser.pedro@pigsikap.local',
            'auditor.juan@pigsikap.local',
            'operations.maria@pigsikap.local',
            'member.leciria@pigsikap.local',
            'member.antonio@pigsikap.local',
            'member.josefina@pigsikap.local',
            'member.roberto@pigsikap.local',
            'member.teresa@pigsikap.local',
            'member.rodrigo@pigsikap.local',
            'member.elena@pigsikap.local',
            'member.luis@pigsikap.local',
        ];
    }

    private function meetings(): array
    {
        return [
            [
                'title' => 'Monthly Association Meeting - September 2025',
                'date' => '2025-09-07',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. Review of CYC-2025-001 early status\n2. Discussion of pig pen improvement\n3. Assignment of caretaker schedule",
                'minutes_summary' => 'Meeting held with 12 members present. Agreed to improve pen flooring. Caretaker schedule assigned.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => ['member.elena@pigsikap.local', 'member.luis@pigsikap.local'],
            ],
            [
                'title' => 'Pig Production Review - CYC-2025-001 Closeout',
                'date' => '2026-01-15',
                'location' => 'Association Office, Humayingan',
                'agenda' => "1. CYC-2025-001 final sale report\n2. Profit sharing computation\n3. CYC-2025-002 status update",
                'minutes_summary' => 'CYC-2025-001 closed with positive net profit. Profit sharing approved for distribution.',
                'status' => 'confirmed',
                'meeting_type' => 'pig_production',
                'absent_emails' => ['member.rodrigo@pigsikap.local'],
            ],
            [
                'title' => 'Monthly Association Meeting - October 2025',
                'date' => '2025-10-05',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. CYC-2025-003 start-up approval\n2. Feeds supplier canvassing\n3. Membership update",
                'minutes_summary' => 'CYC-2025-003 approved. Canvassing for new feeds supplier initiated.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => ['member.teresa@pigsikap.local'],
            ],
            [
                'title' => 'Monthly Association Meeting - November 2025',
                'date' => '2025-11-02',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. CYC-2025-004 approval\n2. Pen repair budget\n3. Typhoon preparedness discussion",
                'minutes_summary' => 'CYC-2025-004 approved with 8 piglets. Emergency fund for typhoon season allocated.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => ['member.josefina@pigsikap.local'],
            ],
            [
                'title' => 'Monthly Association Meeting - December 2025',
                'date' => '2025-12-07',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. Year-end financial review\n2. CYC-2025-005 approval\n3. Holiday schedule for caretakers",
                'minutes_summary' => 'Year-end report presented. CYC-2025-005 approved. Holiday feeding schedule finalized.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => ['member.luis@pigsikap.local', 'member.elena@pigsikap.local'],
            ],
            [
                'title' => 'Pig Production Review - CYC-2025-002 Closeout',
                'date' => '2026-02-28',
                'location' => 'Association Office, Humayingan',
                'agenda' => "1. CYC-2025-002 final report\n2. Profit sharing distribution\n3. Health incident review for CYC-2026-001",
                'minutes_summary' => 'CYC-2025-002 closed. Profit sharing computed. Two sick pig incidents in CYC-2026-001 noted and resolved.',
                'status' => 'confirmed',
                'meeting_type' => 'pig_production',
                'absent_emails' => ['member.rodrigo@pigsikap.local'],
            ],
            [
                'title' => 'Monthly Association Meeting - January 2026',
                'date' => '2026-01-04',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. CYC-2026-003 approval\n2. Pen disinfection schedule\n3. 2026 pig cycle plan overview",
                'minutes_summary' => 'CYC-2026-003 approved with 10 piglets. Monthly pen disinfection schedule established.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => ['member.leciria@pigsikap.local'],
            ],
            [
                'title' => 'Monthly Association Meeting - February 2026',
                'date' => '2026-02-01',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. CYC-2026-004 approval\n2. Pen roof repair update\n3. DSWD submission status for CYC-2025-001",
                'minutes_summary' => 'CYC-2026-004 approved. Pen roof repair completed. DSWD documents submitted.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => ['member.antonio@pigsikap.local'],
            ],
            [
                'title' => 'Pig Production Review - CYC-2025-003 Closeout',
                'date' => '2026-04-25',
                'location' => 'Association Office, Humayingan',
                'agenda' => "1. CYC-2025-003 final sale report\n2. Profit sharing computation\n3. CYC-2025-004 closeout preview",
                'minutes_summary' => 'CYC-2025-003 sales completed. Profit sharing to be distributed.',
                'status' => 'confirmed',
                'meeting_type' => 'pig_production',
                'absent_emails' => ['member.josefina@pigsikap.local', 'member.luis@pigsikap.local'],
            ],
            [
                'title' => 'Monthly Association Meeting - April 2026',
                'date' => '2026-04-05',
                'location' => 'Humayingan Barangay Hall',
                'agenda' => "1. CYC-2026-005 approval\n2. Quarterly financial report\n3. Preparation for CYC-2025-004 and CYC-2025-005 sales",
                'minutes_summary' => 'CYC-2026-005 approved. Quarterly report shows positive financial standing.',
                'status' => 'confirmed',
                'meeting_type' => 'monthly_association',
                'absent_emails' => [],
            ],
        ];
    }

    // ─── RESOLUTIONS ──────────────────────────────────────────

    private function seedResolutions(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        $secretaryId = User::where('email', 'secretary.ronalyn@pigsikap.local')->value('id');
        $treasurerId = User::where('email', 'treasurer.anaceta@pigsikap.local')->value('id');

        foreach ($this->resolutions() as $row) {
            $meeting = Meeting::where('title', $row['meeting_title'])
                ->where('date', $row['meeting_date'])
                ->first();

            $resolution = Resolution::updateOrCreate(
                ['resolution_number' => $row['resolution_number']],
                $this->onlyExistingColumns('resolutions', [
                    'resolution_number' => $row['resolution_number'],
                    'meeting_id' => $meeting ? $meeting->id : 1,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => $row['status'],
                    'approval_deadline' => $row['approval_deadline'] ?? null,
                    'is_approval_locked' => $row['status'] === 'approved' || $row['status'] === 'finalized',
                    'created_by' => $presidentId,
                    'updated_by' => $presidentId,
                    'focal_person_name' => $row['focal_person_name'] ?? 'Eva G. Vivas',
                ])
            );

            // Seed line items
            foreach ($row['line_items'] as $item) {
                ResolutionLineItem::updateOrCreate(
                    [
                        'resolution_id' => $resolution->id,
                        'description' => $item['description'],
                    ],
                    [
                        'resolution_id' => $resolution->id,
                        'category' => $item['category'],
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_cost' => $item['unit_cost'],
                        'total' => $item['total'],
                        'sort_order' => $item['sort_order'],
                    ]
                );
            }

            // Seed approvals (75%+ member approval)
            $this->seedResolutionApprovals($resolution);
        }
    }

    private function seedResolutionApprovals(Resolution $resolution): void
    {
        $memberIds = User::whereIn('email', $this->memberEmails())
            ->whereNotIn('email', ['president.eva@pigsikap.local'])
            ->pluck('id')
            ->toArray();

        $totalMembers = count($memberIds);
        $approveCount = (int) ceil($totalMembers * 0.80); // ~80% approval

        shuffle($memberIds);
        $approvers = array_slice($memberIds, 0, $approveCount);
        $rejectors = array_slice($memberIds, $approveCount);

        foreach ($approvers as $userId) {
            ResolutionApproval::updateOrCreate(
                [
                    'resolution_id' => $resolution->id,
                    'user_id' => $userId,
                ],
                [
                    'resolution_id' => $resolution->id,
                    'user_id' => $userId,
                    'is_approved' => true,
                    'approved_at' => now(),
                ]
            );
        }

        foreach ($rejectors as $userId) {
            ResolutionApproval::updateOrCreate(
                [
                    'resolution_id' => $resolution->id,
                    'user_id' => $userId,
                ],
                [
                    'resolution_id' => $resolution->id,
                    'user_id' => $userId,
                    'is_approved' => false,
                ]
            );
        }
    }

    private function resolutions(): array
    {
        return [
            [
                'resolution_number' => 'RES-2026-001',
                'meeting_title' => 'Pig Production Review - CYC-2025-001 Closeout',
                'meeting_date' => '2026-01-15',
                'title' => 'Approval of CYC-2025-001 Profit Sharing Distribution',
                'description' => 'Resolution to approve the distribution of net profits from CYC-2025-001 to the caretaker, association members, and association fund following the 50/25/25 sharing scheme.',
                'status' => 'finalized',
                'approval_deadline' => '2026-01-30',
                'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Share (50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 45000, 'total' => 45000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 22500, 'total' => 22500, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 22500, 'total' => 22500, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-002',
                'meeting_title' => 'Pig Production Review - CYC-2025-002 Closeout',
                'meeting_date' => '2026-02-28',
                'title' => 'Approval of CYC-2025-002 Profit Sharing and Fund Allocation',
                'description' => 'Resolution approving the net profit distribution from CYC-2025-002 and allocating remaining proceeds toward CYC-2026-004 startup costs.',
                'status' => 'approved',
                'approval_deadline' => '2026-03-15',
                'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Share (50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 18000, 'total' => 18000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 9000, 'total' => 9000, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 9000, 'total' => 9000, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-003',
                'meeting_title' => 'Pig Production Review - CYC-2025-003 Closeout',
                'meeting_date' => '2026-04-25',
                'title' => 'Approval of CYC-2025-003 Sales and Profit Distribution',
                'description' => 'Resolution to finalize the sales transactions for CYC-2025-003 and approve the computed profit sharing distribution.',
                'status' => 'approved',
                'approval_deadline' => '2026-05-10',
                'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'profit_sharing', 'description' => 'Caretaker Share (50%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 35000, 'total' => 35000, 'sort_order' => 1],
                    ['category' => 'profit_sharing', 'description' => 'Member Share Distribution (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 17500, 'total' => 17500, 'sort_order' => 2],
                    ['category' => 'profit_sharing', 'description' => 'Association Fund Allocation (25%)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 17500, 'total' => 17500, 'sort_order' => 3],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-004',
                'meeting_title' => 'Monthly Association Meeting - November 2025',
                'meeting_date' => '2025-11-02',
                'title' => 'Approval of Pen Repair Budget for CYC-2025-004 Housing',
                'description' => 'Resolution approving a 3,500 PHP budget for roof repair and 2,500 PHP for flooring materials for the pig pen used by CYC-2025-004.',
                'status' => 'finalized',
                'approval_deadline' => '2025-11-20',
                'focal_person_name' => 'Pedro S. Santos',
                'line_items' => [
                    ['category' => 'supplies', 'description' => 'Cement and Sand (Flooring)', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 2500, 'total' => 2500, 'sort_order' => 1],
                    ['category' => 'supplies', 'description' => 'Roof Repair Materials', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 3500, 'total' => 3500, 'sort_order' => 2],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-005',
                'meeting_title' => 'Monthly Association Meeting - January 2026',
                'meeting_date' => '2026-01-04',
                'title' => 'Approval of CYC-2026-003 Startup Fund Release',
                'description' => 'Resolution to release association funds for the purchase of 10 piglets, initial feeds, and medicines for CYC-2026-003.',
                'status' => 'finalized',
                'approval_deadline' => '2026-01-20',
                'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'acquisition', 'description' => '10 Piglets at P7,000/head', 'quantity' => 10, 'unit' => 'head', 'unit_cost' => 7000, 'total' => 70000, 'sort_order' => 1],
                    ['category' => 'feed', 'description' => 'Pre-Starter Feed (3 bags)', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1300, 'total' => 3900, 'sort_order' => 2],
                    ['category' => 'medicine', 'description' => 'Vetracin Spray (3 bottles)', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 110, 'total' => 330, 'sort_order' => 3],
                    ['category' => 'transport', 'description' => 'Piglet Transport', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 600, 'total' => 600, 'sort_order' => 4],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-006',
                'meeting_title' => 'Monthly Association Meeting - February 2026',
                'meeting_date' => '2026-02-01',
                'title' => 'Approval of CYC-2026-004 Startup and Expenses',
                'description' => 'Resolution approving the purchase of 12 piglets, emergency hog nipples, and initial feeds and medicines for CYC-2026-004.',
                'status' => 'finalized',
                'approval_deadline' => '2026-02-20',
                'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'acquisition', 'description' => '12 Piglets at P7,000/head', 'quantity' => 12, 'unit' => 'head', 'unit_cost' => 7000, 'total' => 84000, 'sort_order' => 1],
                    ['category' => 'feed', 'description' => 'Pre-Starter Feed (3 bags)', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1320, 'total' => 3960, 'sort_order' => 2],
                    ['category' => 'emergency', 'description' => 'Hog Nipple Drinkers (2 pcs)', 'quantity' => 2, 'unit' => 'pc', 'unit_cost' => 180, 'total' => 360, 'sort_order' => 3],
                    ['category' => 'medicine', 'description' => 'Vetracin Spray (3 bottles)', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 110, 'total' => 330, 'sort_order' => 4],
                ],
            ],
            [
                'resolution_number' => 'RES-2026-007',
                'meeting_title' => 'Monthly Association Meeting - April 2026',
                'meeting_date' => '2026-04-05',
                'title' => 'Approval of CYC-2026-005 Startup Fund Release',
                'description' => 'Resolution for the release of association funds for CYC-2026-005: purchase of 9 piglets and initial care supplies.',
                'status' => 'approved',
                'approval_deadline' => '2026-04-20',
                'focal_person_name' => 'Eva G. Vivas',
                'line_items' => [
                    ['category' => 'acquisition', 'description' => '9 Piglets at P7,000/head', 'quantity' => 9, 'unit' => 'head', 'unit_cost' => 7000, 'total' => 63000, 'sort_order' => 1],
                    ['category' => 'feed', 'description' => 'Pre-Starter Feed (2 bags)', 'quantity' => 2, 'unit' => 'bag', 'unit_cost' => 1320, 'total' => 2640, 'sort_order' => 2],
                    ['category' => 'medicine', 'description' => 'Vetracin Spray (2 bottles)', 'quantity' => 2, 'unit' => 'bottle', 'unit_cost' => 110, 'total' => 220, 'sort_order' => 3],
                    ['category' => 'transport', 'description' => 'Transport (pickup)', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 500, 'total' => 500, 'sort_order' => 4],
                ],
            ],
        ];
    }

    // ─── WITHDRAWALS ──────────────────────────────────────────

    private function seedWithdrawals(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        $treasurerId = User::where('email', 'treasurer.anaceta@pigsikap.local')->value('id');

        foreach ($this->withdrawals() as $row) {
            $resolution = Resolution::where('resolution_number', $row['resolution_number'])->first();

            if (! $resolution) {
                continue;
            }

            $withdrawal = Withdrawal::updateOrCreate(
                [
                    'resolution_id' => $resolution->id,
                    'amount' => $row['amount'],
                ],
                $this->onlyExistingColumns('withdrawals', [
                    'resolution_id' => $resolution->id,
                    'requested_by' => $presidentId,
                    'authorized_withdrawer_id' => $treasurerId,
                    'amount' => $row['amount'],
                    'currency' => 'PHP',
                    'bank_reference' => $row['bank_reference'] ?? null,
                    'status' => $row['status'],
                    'requested_at' => $row['requested_at'],
                    'completed_at' => $row['status'] === 'completed' ? now() : null,
                    'notes' => $row['notes'] ?? null,
                ])
            );

            // Liquidation report for completed withdrawals
            if ($row['status'] === 'completed' && $row['has_liquidation'] ?? false) {
                LiquidationReport::updateOrCreate(
                    [
                        'withdrawal_id' => $withdrawal->id,
                    ],
                    $this->onlyExistingColumns('liquidation_reports', [
                        'withdrawal_id' => $withdrawal->id,
                        'generated_by' => $treasurerId,
                        'summary' => 'All expenses accounted for and receipts submitted.',
                        'liquidation_status' => 'submitted',
                    ])
                );
            }
        }
    }

    private function withdrawals(): array
    {
        return [
            [
                'resolution_number' => 'RES-2026-001',
                'amount' => 90000,
                'status' => 'completed',
                'requested_at' => '2026-01-20',
                'bank_reference' => 'BDO-TRF-001',
                'notes' => 'Profit sharing withdrawal for CYC-2025-001.',
                'has_liquidation' => true,
            ],
            [
                'resolution_number' => 'RES-2026-002',
                'amount' => 36000,
                'status' => 'completed',
                'requested_at' => '2026-03-01',
                'bank_reference' => 'BDO-TRF-002',
                'notes' => 'Profit sharing withdrawal for CYC-2025-002.',
                'has_liquidation' => true,
            ],
            [
                'resolution_number' => 'RES-2026-004',
                'amount' => 6000,
                'status' => 'completed',
                'requested_at' => '2025-11-15',
                'bank_reference' => 'BDO-TRF-003',
                'notes' => 'Pen repair materials fund release.',
                'has_liquidation' => true,
            ],
            [
                'resolution_number' => 'RES-2026-005',
                'amount' => 74830,
                'status' => 'completed',
                'requested_at' => '2026-01-10',
                'bank_reference' => 'BDO-TRF-004',
                'notes' => 'CYC-2026-003 startup fund.',
                'has_liquidation' => true,
            ],
            [
                'resolution_number' => 'RES-2026-006',
                'amount' => 88650,
                'status' => 'completed',
                'requested_at' => '2026-02-10',
                'bank_reference' => 'BDO-TRF-005',
                'notes' => 'CYC-2026-004 startup fund.',
                'has_liquidation' => true,
            ],
            [
                'resolution_number' => 'RES-2026-007',
                'amount' => 66360,
                'status' => 'pending',
                'requested_at' => '2026-04-10',
                'bank_reference' => null,
                'notes' => 'Pending bank processing for CYC-2026-005 startup.',
                'has_liquidation' => false,
            ],
        ];
    }

    // ─── DSWD SUBMISSIONS ─────────────────────────────────────

    private function seedDswdSubmissions(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        $submissions = [
            ['resolution_number' => 'RES-2026-001', 'status' => 'approved', 'notes' => 'DSWD approved the profit sharing distribution scheme.'],
            ['resolution_number' => 'RES-2026-004', 'status' => 'approved', 'notes' => 'Pen repair expenses approved by DSWD.'],
            ['resolution_number' => 'RES-2026-005', 'status' => 'submitted', 'notes' => 'Documents submitted. Awaiting DSWD review.'],
        ];

        foreach ($submissions as $row) {
            $resolution = Resolution::where('resolution_number', $row['resolution_number'])->first();

            if (! $resolution) {
                continue;
            }

            DswdSubmission::updateOrCreate(
                ['resolution_id' => $resolution->id],
                $this->onlyExistingColumns('dswd_submissions', [
                    'resolution_id' => $resolution->id,
                    'status' => $row['status'],
                    'notes' => $row['notes'],
                    'submitted_by' => $presidentId,
                    'submitted_at' => $row['status'] !== 'not_submitted' ? now() : null,
                    'dswd_approval_date' => $row['status'] === 'approved' ? now() : null,
                ])
            );
        }
    }

    // ─── PENALTIES ────────────────────────────────────────────

    private function seedPenalties(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        $penalties = [
            [
                'user_email' => 'member.elena@pigsikap.local',
                'meeting_title' => 'Monthly Association Meeting - December 2025',
                'meeting_date' => '2025-12-07',
                'amount' => 100,
                'status' => 'paid',
                'reason' => 'Unexcused absence at December 2025 meeting.',
            ],
            [
                'user_email' => 'member.luis@pigsikap.local',
                'meeting_title' => 'Monthly Association Meeting - December 2025',
                'meeting_date' => '2025-12-07',
                'amount' => 100,
                'status' => 'waived',
                'reason' => 'Unexcused absence at December 2025 meeting. Waived due to valid reason.',
            ],
            [
                'user_email' => 'member.rodrigo@pigsikap.local',
                'meeting_title' => 'Pig Production Review - CYC-2025-001 Closeout',
                'meeting_date' => '2026-01-15',
                'amount' => 100,
                'status' => 'pending',
                'reason' => 'Unexcused absence at January 2026 production review.',
            ],
        ];

        foreach ($penalties as $row) {
            $user = User::where('email', $row['user_email'])->first();
            $meeting = Meeting::where('title', $row['meeting_title'])
                ->where('date', $row['meeting_date'])
                ->first();

            if (! $user || ! $meeting) {
                continue;
            }

            AttendancePenalty::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'meeting_id' => $meeting->id,
                ],
                $this->onlyExistingColumns('attendance_penalties', [
                    'user_id' => $user->id,
                    'meeting_id' => $meeting->id,
                    'amount' => $row['amount'],
                    'status' => $row['status'],
                    'reason' => $row['reason'],
                    'waived_by' => $row['status'] === 'waived' ? $presidentId : null,
                    'waived_at' => $row['status'] === 'waived' ? now() : null,
                    'paid_at' => $row['status'] === 'paid' ? now() : null,
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
}
