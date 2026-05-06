<?php

namespace Database\Seeders;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\ProfitabilitySnapshot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoBulkCyclesSeeder extends Seeder
{
    public function run(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        $caretakerId = User::where('email', 'officer.maricon@pigsikap.local')->value('id') ?? $presidentId;

        $cycles = $this->cycleData($caretakerId);
        $cycleIds = [];

        foreach ($cycles as $row) {
            $c = PigCycle::updateOrCreate(
                ['batch_code' => $row['batch_code']],
                array_merge($row, ['created_by' => $presidentId])
            );
            $cycleIds[$row['batch_code']] = $c->id;
        }

        $this->seedExpenses($cycleIds, $presidentId);
        $this->seedSales($cycleIds, $presidentId);
        $this->seedProfitability($presidentId);
    }

    private function seedExpenses(array $cycleIds, int $presidentId): void
    {
        $rows = [];
        foreach ($this->expenseData($cycleIds) as $row) {
            $rows[] = array_merge($row, ['created_by' => $presidentId, 'updated_by' => $presidentId]);
        }
        if ($rows) DB::table('pig_cycle_expenses')->insert($rows);
    }

    private function seedSales(array $cycleIds, int $presidentId): void
    {
        $rows = [];
        foreach ($this->saleData($cycleIds) as $row) {
            $rows[] = array_merge($row, ['created_by' => $presidentId, 'updated_by' => $presidentId]);
        }
        if ($rows) DB::table('pig_cycle_sales')->insert($rows);
    }

    private function seedProfitability(int $presidentId): void
    {
        if (! Schema::hasTable('profitability_snapshots')) {
            return;
        }
        foreach (['CYC-2025-006', 'CYC-2025-007', 'CYC-2025-008', 'CYC-2026-009', 'CYC-2026-010'] as $code) {
            $cycle = PigCycle::where('batch_code', $code)->first();
            if (! $cycle) {
                continue;
            }
            $gross = (float) PigCycleSale::where('batch_id', $cycle->id)->sum('amount');
            $collected = (float) PigCycleSale::where('batch_id', $cycle->id)->sum('amount_paid');
            $expenses = (float) PigCycleExpense::where('batch_id', $cycle->id)->sum('amount');
            $net = round($gross - $expenses, 2);
            $dist = max($net, 0.0);
            $shareRule = json_encode([
                'caretaker' => 0.50,
                'members' => 0.25,
                'association' => 0.25,
            ]);
            DB::table('profitability_snapshots')->updateOrInsert(
                ['pig_cycle_id' => $cycle->id, 'version_number' => 1],
                [
                    'pig_cycle_id' => $cycle->id,
                    'snapshot_number' => (int) substr($code, -3),
                    'version_number' => 1,
                    'gross_income' => $gross,
                    'total_collected' => $collected,
                    'receivables' => round($gross - $collected, 2),
                    'total_expenses' => $expenses,
                    'net_profit_or_loss' => $net,
                    'distributable_profit' => $dist,
                    'caretaker_share' => round($dist * 0.50, 2),
                    'member_share' => round($dist * 0.25, 2),
                    'association_share' => round($dist * 0.25, 2),
                    'expense_breakdown_json' => '{}',
                    'share_rule_json' => $shareRule,
                    'is_current' => true,
                    'finalized_at' => now(),
                    'finalized_by_user_id' => $presidentId,
                    'notes' => 'Cycle profitability snapshot.',
                    'computation_version' => '2026-05-cycle-profitability-v1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function cycleData(int $caretakerId): array
    {
        return [
            ['batch_code' => 'CYC-2025-006', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 6, 'date_of_purchase' => '2025-09-05', 'initial_count' => 10, 'current_count' => 0, 'stage' => 'Completed', 'status' => 'Closed', 'notes' => 'Batch of 10. Net profit positive.'],
            ['batch_code' => 'CYC-2025-007', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 7, 'date_of_purchase' => '2025-09-20', 'initial_count' => 8, 'current_count' => 0, 'stage' => 'Completed', 'status' => 'Closed', 'notes' => 'Batch of 8. One mortality.'],
            ['batch_code' => 'CYC-2025-008', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 8, 'date_of_purchase' => '2025-10-15', 'initial_count' => 12, 'current_count' => 0, 'stage' => 'Completed', 'status' => 'Closed', 'notes' => 'Large batch. High profit.'],
            ['batch_code' => 'CYC-2026-009', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 9, 'date_of_purchase' => '2026-01-08', 'initial_count' => 7, 'current_count' => 0, 'stage' => 'Completed', 'status' => 'Closed', 'notes' => 'Seven pigs. Premium price.'],
            ['batch_code' => 'CYC-2026-010', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 10, 'date_of_purchase' => '2026-01-22', 'initial_count' => 10, 'current_count' => 0, 'stage' => 'Completed', 'status' => 'Closed', 'notes' => 'Strong batch. Three transactions.'],
            ['batch_code' => 'CYC-2026-011', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 11, 'date_of_purchase' => '2026-02-05', 'initial_count' => 8, 'current_count' => 8, 'stage' => 'For Sale', 'status' => 'Ready for Sale', 'notes' => 'Ready for harvest.'],
            ['batch_code' => 'CYC-2026-012', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 12, 'date_of_purchase' => '2026-03-05', 'initial_count' => 10, 'current_count' => 10, 'stage' => 'Fattening', 'status' => 'Under Monitoring', 'notes' => 'Good weight gain.'],
            ['batch_code' => 'CYC-2026-013', 'caretaker_user_id' => $caretakerId, 'cycle_number' => 13, 'date_of_purchase' => '2026-04-10', 'initial_count' => 9, 'current_count' => 9, 'stage' => 'Growing', 'status' => 'Active', 'notes' => 'Healthy batch.'],
        ];
    }

    private function expenseData(array $cycleIds): array
    {
        $rows = [];
        foreach ([
            ['code'=>'CYC-2025-006','date'=>'2025-09-05','count'=>10],
            ['code'=>'CYC-2025-007','date'=>'2025-09-20','count'=>8],
            ['code'=>'CYC-2025-008','date'=>'2025-10-15','count'=>12],
            ['code'=>'CYC-2026-009','date'=>'2026-01-08','count'=>7],
            ['code'=>'CYC-2026-010','date'=>'2026-01-22','count'=>10],
            ['code'=>'CYC-2026-011','date'=>'2026-02-05','count'=>8],
            ['code'=>'CYC-2026-012','date'=>'2026-03-05','count'=>10],
            ['code'=>'CYC-2026-013','date'=>'2026-04-10','count'=>9],
        ] as $c) {
            if (! isset($cycleIds[$c['code']])) continue;
            $bid = $cycleIds[$c['code']];
            $d = fn(int $days) => date('Y-m-d', strtotime($c['date'].' + '.$days.' days'));
            $bags = max(2, (int) ceil($c['count'] / 3));
            $rows[] = ['batch_id'=>$bid,'category'=>'acquisition','item_name'=>"Piglets ({$c['count']} heads)",'quantity'=>$c['count'],'unit'=>'head','unit_cost'=>7000,'amount'=>$c['count']*7000,'expense_date'=>$c['date']];
            $rows[] = ['batch_id'=>$bid,'category'=>'transport','item_name'=>'Transport','quantity'=>1,'unit'=>'trip','unit_cost'=>500,'amount'=>500,'expense_date'=>$c['date']];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Pre-Starter Feed','quantity'=>2,'unit'=>'bag','unit_cost'=>1300,'amount'=>2600,'expense_date'=>$c['date']];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Hog Starter','quantity'=>$bags,'unit'=>'bag','unit_cost'=>1890,'amount'=>$bags*1890,'expense_date'=>$d(21)];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Hog Starter','quantity'=>$bags,'unit'=>'bag','unit_cost'=>1890,'amount'=>$bags*1890,'expense_date'=>$d(42)];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Hog Grower','quantity'=>$bags,'unit'=>'bag','unit_cost'=>1695,'amount'=>$bags*1695,'expense_date'=>$d(63)];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Hog Grower','quantity'=>$bags,'unit'=>'bag','unit_cost'=>1695,'amount'=>$bags*1695,'expense_date'=>$d(84)];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Hog Finisher','quantity'=>$bags,'unit'=>'bag','unit_cost'=>1630,'amount'=>$bags*1630,'expense_date'=>$d(105)];
            $rows[] = ['batch_id'=>$bid,'category'=>'feed','item_name'=>'Hog Finisher','quantity'=>$bags,'unit'=>'bag','unit_cost'=>1630,'amount'=>$bags*1630,'expense_date'=>$d(126)];
            $rows[] = ['batch_id'=>$bid,'category'=>'medicine','item_name'=>'Injectable Vitamins','quantity'=>1,'unit'=>'dose','unit_cost'=>250,'amount'=>250,'expense_date'=>$d(45)];
        }
        return $rows;
    }

    private function saleData(array $cycleIds): array
    {
        $rows = [];
        foreach ([
            ['code'=>'CYC-2025-006','date'=>'2025-09-05','count'=>10],
            ['code'=>'CYC-2025-007','date'=>'2025-09-20','count'=>7],
            ['code'=>'CYC-2025-008','date'=>'2025-10-15','count'=>12],
            ['code'=>'CYC-2026-009','date'=>'2026-01-08','count'=>7],
            ['code'=>'CYC-2026-010','date'=>'2026-01-22','count'=>10],
        ] as $c) {
            if (! isset($cycleIds[$c['code']])) continue;
            $bid = $cycleIds[$c['code']];
            $startSale = date('Y-m-d', strtotime($c['date'].' + 119 days'));
            $half = (int) ceil($c['count'] / 2);
            $rest = $c['count'] - $half;
            $rows[] = ['batch_id'=>$bid,'pigs_sold'=>$half,'amount'=>$half*90*250,'sale_date'=>$startSale,'sale_method'=>'live_weight','live_weight_kg'=>$half*90,'price_per_kg'=>250,'price_per_head'=>null,'payment_status'=>'paid','amount_paid'=>$half*90*250,'receipt_reference'=>'SALE-'.$c['code'].'-01','notes'=>$half.' pigs at 90 kg avg.'];
            if ($rest > 0) {
                $saleDate2 = date('Y-m-d', strtotime($startSale.' + 10 days'));
                $rows[] = ['batch_id'=>$bid,'pigs_sold'=>$rest,'amount'=>$rest*95*255,'sale_date'=>$saleDate2,'sale_method'=>'live_weight','live_weight_kg'=>$rest*95,'price_per_kg'=>255,'price_per_head'=>null,'payment_status'=>'paid','amount_paid'=>$rest*95*255,'receipt_reference'=>'SALE-'.$c['code'].'-02','notes'=>$rest.' pigs at 95 kg avg.'];
            }
        }
        if (isset($cycleIds['CYC-2026-011'])) {
            $bid = $cycleIds['CYC-2026-011'];
            $rows[] = ['batch_id'=>$bid,'pigs_sold'=>3,'amount'=>63000,'sale_date'=>'2026-05-01','sale_method'=>'per_head','live_weight_kg'=>240,'price_per_kg'=>null,'price_per_head'=>21000,'payment_status'=>'paid','amount_paid'=>63000,'receipt_reference'=>'SALE-CYC-2026-011-01','notes'=>'First batch sold.'];
        }
        return $rows;
    }
}
