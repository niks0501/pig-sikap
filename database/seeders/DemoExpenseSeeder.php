<?php

namespace Database\Seeders;

use App\Models\AssociationExpense;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoExpenseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCycleExpenses();
            $this->seedAssociationExpenses();
        });
    }

    private function seedCycleExpenses(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->cycleExpenses() as $row) {
            $cycle = PigCycle::where('batch_code', $row['cycle_code'])->first();

            if (! $cycle) {
                continue;
            }

            PigCycleExpense::updateOrCreate(
                [
                    'batch_id' => $cycle->id,
                    'item_name' => $row['item_name'],
                    'expense_date' => $row['expense_date'],
                    'amount' => $row['amount'],
                ],
                $this->onlyExistingColumns('pig_cycle_expenses', [
                    'batch_id' => $cycle->id,
                    'category' => $row['category'],
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'unit_cost' => $row['unit_cost'],
                    'amount' => $row['amount'],
                    'expense_date' => $row['expense_date'],
                    'notes' => $row['notes'] ?? null,
                    'receipt_reference' => $row['receipt_reference'] ?? null,
                    'receipt_path' => null,
                    'created_by' => $presidentId,
                    'updated_by' => $presidentId,
                ])
            );
        }
    }

    private function seedAssociationExpenses(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->associationExpenses() as $row) {
            AssociationExpense::updateOrCreate(
                [
                    'item_name' => $row['item_name'],
                    'expense_date' => $row['expense_date'],
                    'amount' => $row['amount'],
                ],
                $this->onlyExistingColumns('association_expenses', [
                    'item_name' => $row['item_name'],
                    'category' => $row['category'],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'unit_cost' => $row['unit_cost'],
                    'amount' => $row['amount'],
                    'expense_date' => $row['expense_date'],
                    'receipt_reference' => $row['receipt_reference'] ?? null,
                    'receipt_path' => null,
                    'fund_source' => $row['fund_source'] ?? 'association_fund',
                    'notes' => $row['notes'] ?? null,
                    'created_by' => $presidentId,
                    'updated_by' => $presidentId,
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
    private function cycleExpenses(): array
    {
        return [
            // CYC-2025-004 expenses (8 pigs, purchased 2025-11-10, completed)
            ['cycle_code' => 'CYC-2025-004', 'category' => 'acquisition', 'item_name' => 'Piglets (8 heads)', 'quantity' => 8, 'unit' => 'head', 'unit_cost' => 6800, 'amount' => 54400, 'expense_date' => '2025-11-10', 'notes' => null, 'receipt_reference' => null],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'transport', 'item_name' => 'Transport (pickup)', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 500, 'amount' => 500, 'expense_date' => '2025-11-10', 'notes' => null, 'receipt_reference' => 'OR-4521'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'feed', 'item_name' => 'Pre-Starter Feed', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1275, 'amount' => 3825, 'expense_date' => '2025-11-12', 'notes' => null, 'receipt_reference' => 'INV-8801'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'medicine', 'item_name' => 'Vetracin Spray', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 105, 'amount' => 315, 'expense_date' => '2025-11-12', 'notes' => null, 'receipt_reference' => 'INV-8801'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 4, 'unit' => 'bag', 'unit_cost' => 1850, 'amount' => 7400, 'expense_date' => '2025-11-30', 'notes' => null, 'receipt_reference' => 'INV-8821'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 4, 'unit' => 'bag', 'unit_cost' => 1850, 'amount' => 7400, 'expense_date' => '2025-12-21', 'notes' => null, 'receipt_reference' => 'INV-8845'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'feed', 'item_name' => 'Hog Grower', 'quantity' => 5, 'unit' => 'bag', 'unit_cost' => 1640, 'amount' => 8200, 'expense_date' => '2026-01-15', 'notes' => null, 'receipt_reference' => 'INV-8870'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'feed', 'item_name' => 'Hog Grower', 'quantity' => 5, 'unit' => 'bag', 'unit_cost' => 1640, 'amount' => 8200, 'expense_date' => '2026-02-08', 'notes' => null, 'receipt_reference' => 'INV-8895'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'feed', 'item_name' => 'Hog Finisher', 'quantity' => 4, 'unit' => 'bag', 'unit_cost' => 1630, 'amount' => 6520, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => 'INV-8920'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'transport', 'item_name' => 'Transport (delivery)', 'quantity' => 2, 'unit' => 'trip', 'unit_cost' => 500, 'amount' => 1000, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => 'OR-4601'],
            ['cycle_code' => 'CYC-2025-004', 'category' => 'medicine', 'item_name' => 'Injectable Vitamins', 'quantity' => 1, 'unit' => 'dose', 'unit_cost' => 250, 'amount' => 250, 'expense_date' => '2026-02-08', 'notes' => null, 'receipt_reference' => 'INV-8895'],
            // CYC-2025-005 expenses (7 pigs, purchased 2025-12-01, for sale)
            ['cycle_code' => 'CYC-2025-005', 'category' => 'acquisition', 'item_name' => 'Piglets (7 heads)', 'quantity' => 7, 'unit' => 'head', 'unit_cost' => 6900, 'amount' => 48300, 'expense_date' => '2025-12-01', 'notes' => null, 'receipt_reference' => null],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'feed', 'item_name' => 'Pre-Starter Feed', 'quantity' => 2, 'unit' => 'bag', 'unit_cost' => 1300, 'amount' => 2600, 'expense_date' => '2025-12-01', 'notes' => null, 'receipt_reference' => 'INV-8930'],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'medicine', 'item_name' => 'Vetracin Spray', 'quantity' => 2, 'unit' => 'bottle', 'unit_cost' => 105, 'amount' => 210, 'expense_date' => '2025-12-01', 'notes' => null, 'receipt_reference' => 'INV-8930'],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1890, 'amount' => 5670, 'expense_date' => '2025-12-22', 'notes' => null, 'receipt_reference' => 'INV-8955'],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1890, 'amount' => 5670, 'expense_date' => '2026-01-18', 'notes' => null, 'receipt_reference' => 'INV-8980'],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'feed', 'item_name' => 'Hog Grower', 'quantity' => 4, 'unit' => 'bag', 'unit_cost' => 1695, 'amount' => 6780, 'expense_date' => '2026-02-15', 'notes' => null, 'receipt_reference' => 'INV-9005'],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'feed', 'item_name' => 'Hog Grower', 'quantity' => 4, 'unit' => 'bag', 'unit_cost' => 1695, 'amount' => 6780, 'expense_date' => '2026-03-10', 'notes' => null, 'receipt_reference' => 'INV-9030'],
            ['cycle_code' => 'CYC-2025-005', 'category' => 'transport', 'item_name' => 'Transport (pickup)', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 500, 'amount' => 500, 'expense_date' => '2025-12-01', 'notes' => null, 'receipt_reference' => 'OR-4620'],
            // CYC-2026-003 expenses (10 pigs, purchased 2026-01-12, fattening)
            ['cycle_code' => 'CYC-2026-003', 'category' => 'acquisition', 'item_name' => 'Piglets (10 heads)', 'quantity' => 10, 'unit' => 'head', 'unit_cost' => 7000, 'amount' => 70000, 'expense_date' => '2026-01-12', 'notes' => null, 'receipt_reference' => null],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'feed', 'item_name' => 'Pre-Starter Feed', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1300, 'amount' => 3900, 'expense_date' => '2026-01-12', 'notes' => null, 'receipt_reference' => 'INV-9055'],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'medicine', 'item_name' => 'Vetracin Spray', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 105, 'amount' => 315, 'expense_date' => '2026-01-12', 'notes' => null, 'receipt_reference' => 'INV-9055'],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'transport', 'item_name' => 'Transport (pickup)', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 600, 'amount' => 600, 'expense_date' => '2026-01-12', 'notes' => null, 'receipt_reference' => 'OR-4650'],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 5, 'unit' => 'bag', 'unit_cost' => 1890, 'amount' => 9450, 'expense_date' => '2026-02-05', 'notes' => null, 'receipt_reference' => 'INV-9080'],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 5, 'unit' => 'bag', 'unit_cost' => 1890, 'amount' => 9450, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => 'INV-9105'],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'medicine', 'item_name' => 'Injectable Vitamins', 'quantity' => 1, 'unit' => 'dose', 'unit_cost' => 250, 'amount' => 250, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => 'INV-9105'],
            ['cycle_code' => 'CYC-2026-003', 'category' => 'feed', 'item_name' => 'Hog Grower', 'quantity' => 5, 'unit' => 'bag', 'unit_cost' => 1695, 'amount' => 8475, 'expense_date' => '2026-04-01', 'notes' => null, 'receipt_reference' => 'INV-9130'],
            // CYC-2026-004 expenses (12 pigs, purchased 2026-03-01, growing)
            ['cycle_code' => 'CYC-2026-004', 'category' => 'acquisition', 'item_name' => 'Piglets (12 heads)', 'quantity' => 12, 'unit' => 'head', 'unit_cost' => 7000, 'amount' => 84000, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => null],
            ['cycle_code' => 'CYC-2026-004', 'category' => 'feed', 'item_name' => 'Pre-Starter Feed', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1300, 'amount' => 3900, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => 'INV-9150'],
            ['cycle_code' => 'CYC-2026-004', 'category' => 'emergency', 'item_name' => 'Hog Nipple Drinker', 'quantity' => 2, 'unit' => 'pc', 'unit_cost' => 180, 'amount' => 360, 'expense_date' => '2026-03-01', 'notes' => null, 'receipt_reference' => 'INV-9150'],
            ['cycle_code' => 'CYC-2026-004', 'category' => 'medicine', 'item_name' => 'Vetracin Spray', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 105, 'amount' => 315, 'expense_date' => '2026-03-05', 'notes' => null, 'receipt_reference' => 'INV-9160'],
            ['cycle_code' => 'CYC-2026-004', 'category' => 'feed', 'item_name' => 'Hog Starter', 'quantity' => 5, 'unit' => 'bag', 'unit_cost' => 1890, 'amount' => 9450, 'expense_date' => '2026-03-28', 'notes' => null, 'receipt_reference' => 'INV-9180'],
            ['cycle_code' => 'CYC-2026-004', 'category' => 'transport', 'item_name' => 'Transport (delivery)', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 400, 'amount' => 400, 'expense_date' => '2026-03-28', 'notes' => null, 'receipt_reference' => 'OR-4700'],
            // CYC-2026-005 expenses (9 pigs, purchased 2026-04-01, piglet)
            ['cycle_code' => 'CYC-2026-005', 'category' => 'acquisition', 'item_name' => 'Piglets (9 heads)', 'quantity' => 9, 'unit' => 'head', 'unit_cost' => 7000, 'amount' => 63000, 'expense_date' => '2026-04-01', 'notes' => null, 'receipt_reference' => null],
            ['cycle_code' => 'CYC-2026-005', 'category' => 'feed', 'item_name' => 'Pre-Starter Feed', 'quantity' => 2, 'unit' => 'bag', 'unit_cost' => 1320, 'amount' => 2640, 'expense_date' => '2026-04-01', 'notes' => null, 'receipt_reference' => 'INV-9200'],
            ['cycle_code' => 'CYC-2026-005', 'category' => 'medicine', 'item_name' => 'Vetracin Spray', 'quantity' => 2, 'unit' => 'bottle', 'unit_cost' => 110, 'amount' => 220, 'expense_date' => '2026-04-01', 'notes' => null, 'receipt_reference' => 'INV-9200'],
            ['cycle_code' => 'CYC-2026-005', 'category' => 'transport', 'item_name' => 'Transport (pickup)', 'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 500, 'amount' => 500, 'expense_date' => '2026-04-01', 'notes' => null, 'receipt_reference' => 'OR-4750'],
            ['cycle_code' => 'CYC-2026-005', 'category' => 'feed', 'item_name' => 'Pre-Starter Feed', 'quantity' => 2, 'unit' => 'bag', 'unit_cost' => 1320, 'amount' => 2640, 'expense_date' => '2026-04-18', 'notes' => null, 'receipt_reference' => 'INV-9225'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function associationExpenses(): array
    {
        return [
            [
                'item_name' => 'Monthly Utility Bill (Water & Electric)',
                'category' => 'utilities',
                'quantity' => 1, 'unit' => 'month', 'unit_cost' => 1200, 'amount' => 1200,
                'expense_date' => '2025-11-05', 'receipt_reference' => 'MERALCO-2025-11',
                'fund_source' => 'association_fund', 'notes' => 'Monthly electricity and water for pig pens.',
            ],
            [
                'item_name' => 'Monthly Utility Bill (Water & Electric)',
                'category' => 'utilities',
                'quantity' => 1, 'unit' => 'month', 'unit_cost' => 1250, 'amount' => 1250,
                'expense_date' => '2025-12-05', 'receipt_reference' => 'MERALCO-2025-12',
                'fund_source' => 'association_fund', 'notes' => 'Monthly electricity and water for pig pens.',
            ],
            [
                'item_name' => 'Monthly Utility Bill (Water & Electric)',
                'category' => 'utilities',
                'quantity' => 1, 'unit' => 'month', 'unit_cost' => 1180, 'amount' => 1180,
                'expense_date' => '2026-01-05', 'receipt_reference' => 'MERALCO-2026-01',
                'fund_source' => 'association_fund', 'notes' => 'Monthly electricity and water for pig pens.',
            ],
            [
                'item_name' => 'Monthly Utility Bill (Water & Electric)',
                'category' => 'utilities',
                'quantity' => 1, 'unit' => 'month', 'unit_cost' => 1300, 'amount' => 1300,
                'expense_date' => '2026-02-05', 'receipt_reference' => 'MERALCO-2026-02',
                'fund_source' => 'association_fund', 'notes' => 'Monthly electricity and water for pig pens.',
            ],
            [
                'item_name' => 'Monthly Utility Bill (Water & Electric)',
                'category' => 'utilities',
                'quantity' => 1, 'unit' => 'month', 'unit_cost' => 1250, 'amount' => 1250,
                'expense_date' => '2026-03-05', 'receipt_reference' => 'MERALCO-2026-03',
                'fund_source' => 'association_fund', 'notes' => 'Monthly electricity and water for pig pens.',
            ],
            [
                'item_name' => 'Monthly Utility Bill (Water & Electric)',
                'category' => 'utilities',
                'quantity' => 1, 'unit' => 'month', 'unit_cost' => 1350, 'amount' => 1350,
                'expense_date' => '2026-04-05', 'receipt_reference' => 'MERALCO-2026-04',
                'fund_source' => 'association_fund', 'notes' => 'Monthly electricity and water for pig pens.',
            ],
            [
                'item_name' => 'Rice Bran (Darak) Bulk Purchase',
                'category' => 'feed',
                'quantity' => 2, 'unit' => 'sack', 'unit_cost' => 850, 'amount' => 1700,
                'expense_date' => '2025-11-20', 'receipt_reference' => 'INV-RB-001',
                'fund_source' => 'association_fund', 'notes' => 'Supplemental feed for all cycles.',
            ],
            [
                'item_name' => 'Rice Bran (Darak) Bulk Purchase',
                'category' => 'feed',
                'quantity' => 2, 'unit' => 'sack', 'unit_cost' => 850, 'amount' => 1700,
                'expense_date' => '2026-01-15', 'receipt_reference' => 'INV-RB-002',
                'fund_source' => 'association_fund', 'notes' => 'Supplemental feed for all cycles.',
            ],
            [
                'item_name' => 'Rice Bran (Darak) Bulk Purchase',
                'category' => 'feed',
                'quantity' => 2, 'unit' => 'sack', 'unit_cost' => 870, 'amount' => 1740,
                'expense_date' => '2026-03-10', 'receipt_reference' => 'INV-RB-003',
                'fund_source' => 'association_fund', 'notes' => 'Supplemental feed for all cycles.',
            ],
            [
                'item_name' => 'Pen Flooring Repair Materials (Cement & Sand)',
                'category' => 'supplies',
                'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 2500, 'amount' => 2500,
                'expense_date' => '2025-12-15', 'receipt_reference' => 'SM-HW-8820',
                'fund_source' => 'association_fund', 'notes' => 'Minor pen maintenance.',
            ],
            [
                'item_name' => 'Pen Roof Repair',
                'category' => 'supplies',
                'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 3500, 'amount' => 3500,
                'expense_date' => '2026-02-10', 'receipt_reference' => 'SM-HW-9100',
                'fund_source' => 'association_fund', 'notes' => 'Roof patch after heavy rain.',
            ],
            [
                'item_name' => 'Disinfectant Supplies (Chlorine Solution)',
                'category' => 'supplies',
                'quantity' => 5, 'unit' => 'liter', 'unit_cost' => 180, 'amount' => 900,
                'expense_date' => '2026-01-10', 'receipt_reference' => 'INV-DS-001',
                'fund_source' => 'association_fund', 'notes' => 'Monthly pen disinfection.',
            ],
            [
                'item_name' => 'Disinfectant Supplies (Chlorine Solution)',
                'category' => 'supplies',
                'quantity' => 5, 'unit' => 'liter', 'unit_cost' => 180, 'amount' => 900,
                'expense_date' => '2026-03-10', 'receipt_reference' => 'INV-DS-002',
                'fund_source' => 'association_fund', 'notes' => 'Monthly pen disinfection.',
            ],
            [
                'item_name' => 'Emergency Fund - Typhoon Preparedness',
                'category' => 'emergency',
                'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 3000, 'amount' => 3000,
                'expense_date' => '2025-11-01', 'receipt_reference' => null,
                'fund_source' => 'emergency_fund', 'notes' => 'Emergency supplies for pen protection during typhoon season.',
            ],
            [
                'item_name' => 'Association Meeting Snacks',
                'category' => 'other',
                'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 500, 'amount' => 500,
                'expense_date' => '2025-12-05', 'receipt_reference' => null,
                'fund_source' => 'association_fund', 'notes' => 'Refreshments for monthly general assembly.',
            ],
            [
                'item_name' => 'Association Meeting Snacks',
                'category' => 'other',
                'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 500, 'amount' => 500,
                'expense_date' => '2026-02-05', 'receipt_reference' => null,
                'fund_source' => 'association_fund', 'notes' => 'Refreshments for monthly general assembly.',
            ],
            [
                'item_name' => 'Association Meeting Snacks',
                'category' => 'other',
                'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 500, 'amount' => 500,
                'expense_date' => '2026-04-05', 'receipt_reference' => null,
                'fund_source' => 'association_fund', 'notes' => 'Refreshments for monthly general assembly.',
            ],
            [
                'item_name' => 'Transport - DSWD Document Submission',
                'category' => 'transport',
                'quantity' => 1, 'unit' => 'trip', 'unit_cost' => 800, 'amount' => 800,
                'expense_date' => '2026-02-20', 'receipt_reference' => 'OR-4800',
                'fund_source' => 'association_fund', 'notes' => 'Travel to DSWD Batangas for document submission.',
            ],
        ];
    }
}
