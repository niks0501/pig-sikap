<?php

namespace Database\Seeders;

use App\Models\PigBuyer;
use App\Models\PigCycle;
use App\Models\PigCycleSale;
use App\Models\ProfitabilitySnapshot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoSaleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $buyers = $this->seedBuyers();
            $this->seedSales($buyers);
            $this->seedProfitabilitySnapshots();
        });
    }

    private function seedBuyers(): array
    {
        $buyers = [];
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        if (! Schema::hasTable('pig_buyers')) {
            return $buyers;
        }

        foreach ($this->buyers() as $row) {
            $buyer = PigBuyer::withTrashed()->firstOrNew(['name' => $row['name']]);
            $buyer->fill($this->onlyExistingColumns('pig_buyers', [
                'name' => $row['name'],
                'contact_number' => $row['contact_number'],
                'address' => $row['address'],
                'notes' => $row['notes'],
                'created_by' => $presidentId,
                'updated_by' => $presidentId,
            ]));

            if (method_exists($buyer, 'trashed') && $buyer->trashed()) {
                $buyer->restore();
            }

            $buyer->save();
            $buyers[$row['name']] = $buyer;
        }

        return $buyers;
    }

    private function seedSales(array $buyers): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->sales() as $row) {
            $cycle = PigCycle::where('batch_code', $row['cycle_code'])->first();

            if (! $cycle) {
                continue;
            }

            PigCycleSale::updateOrCreate(
                [
                    'batch_id' => $cycle->id,
                    'sale_date' => $row['sale_date'],
                    'amount' => $row['amount'],
                    'receipt_reference' => $row['receipt_reference'],
                ],
                $this->onlyExistingColumns('pig_cycle_sales', [
                    'batch_id' => $cycle->id,
                    'buyer_id' => isset($row['buyer']) ? ($buyers[$row['buyer']]->id ?? null) : null,
                    'pigs_sold' => $row['pigs_sold'],
                    'amount' => $row['amount'],
                    'sale_date' => $row['sale_date'],
                    'sale_method' => $row['sale_method'],
                    'live_weight_kg' => $row['live_weight_kg'],
                    'price_per_kg' => $row['price_per_kg'],
                    'price_per_head' => $row['price_per_head'],
                    'payment_status' => $row['payment_status'],
                    'amount_paid' => $row['amount_paid'],
                    'receipt_reference' => $row['receipt_reference'],
                    'receipt_path' => null,
                    'digital_receipt_status' => 'not_sent',
                    'notes' => $row['notes'],
                    'created_by' => $presidentId,
                    'updated_by' => $presidentId,
                ])
            );
        }
    }

    private function seedProfitabilitySnapshots(): void
    {
        if (! Schema::hasTable('profitability_snapshots')) {
            return;
        }

        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        $cycleCodes = ['CYC-2025-003', 'CYC-2025-004', 'CYC-2025-005'];

        foreach ($cycleCodes as $cycleCode) {
            $cycle = PigCycle::where('batch_code', $cycleCode)->first();

            if (! $cycle) {
                continue;
            }

            $expenseBreakdown = $cycle->expenses()
                ->selectRaw('category, SUM(amount) as total')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->map(fn ($value) => round((float) $value, 2))
                ->all();

            $grossIncome = round((float) $cycle->sales()->sum('amount'), 2);
            $totalCollected = round((float) $cycle->sales()->sum('amount_paid'), 2);
            $totalExpenses = round((float) $cycle->expenses()->sum('amount'), 2);
            $net = round($grossIncome - $totalExpenses, 2);
            $distributable = max($net, 0);
            $snapshotNumber = (int) substr($cycleCode, -3);

            DB::table('profitability_snapshots')->updateOrInsert(
                [
                    'pig_cycle_id' => $cycle->id,
                    'version_number' => 1,
                ],
                $this->onlyExistingColumns('profitability_snapshots', [
                    'pig_cycle_id' => $cycle->id,
                    'snapshot_number' => $snapshotNumber,
                    'version_number' => 1,
                    'gross_income' => $grossIncome,
                    'total_collected' => $totalCollected,
                    'receivables' => round($grossIncome - $totalCollected, 2),
                    'total_expenses' => $totalExpenses,
                    'net_profit_or_loss' => $net,
                    'distributable_profit' => $distributable,
                    'caretaker_share' => round($distributable * 0.50, 2),
                    'member_share' => round($distributable * 0.25, 2),
                    'association_share' => round($distributable * 0.25, 2),
                    'expense_breakdown_json' => json_encode($expenseBreakdown),
                    'share_rule_json' => json_encode([
                        'caretaker' => 0.50,
                        'members' => 0.25,
                        'association' => 0.25,
                        'source' => 'Association standard: 50/25/25 after deducting expenses.',
                    ]),
                    'sales_summary_json' => json_encode([
                        'total_sales' => $grossIncome,
                        'total_collected' => $totalCollected,
                        'receivables' => round($grossIncome - $totalCollected, 2),
                        'sales_count' => $cycle->sales()->count(),
                        'pigs_sold' => $cycle->sales()->sum('pigs_sold'),
                    ]),
                    'validation_warnings_json' => json_encode($net < 0 ? [[
                        'code' => 'net_loss',
                        'severity' => 'critical',
                        'message' => 'This cycle has a net loss. No profit share should be distributed.',
                    ]] : []),
                    'source_hash' => hash('sha256', json_encode([
                        'expenses' => $cycle->expenses()->orderBy('id')->get(['id', 'amount', 'category', 'expense_date'])->toArray(),
                        'sales' => $cycle->sales()->orderBy('id')->get(['id', 'amount', 'amount_paid', 'payment_status', 'sale_date'])->toArray(),
                    ])),
                    'is_current' => true,
                    'finalized_at' => now(),
                    'finalized_by_user_id' => $presidentId,
                    'notes' => 'Cycle profitability snapshot.',
                    'computation_version' => '2026-05-cycle-profitability-v1',
                    'created_at' => now(),
                    'updated_at' => now(),
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
    private function buyers(): array
    {
        return [
            [
                'name' => 'Mang Jose C. Delgado',
                'contact_number' => '0917-555-1001',
                'address' => 'Brgy. Humayingan, Lian, Batangas',
                'notes' => 'Regular per-head buyer for lechon and meat shops.',
            ],
            [
                'name' => 'Aling Nena\'s Meat Shop',
                'contact_number' => '0920-555-2002',
                'address' => 'Lian Public Market, Batangas',
                'notes' => 'Bulk live-weight buyer. Prefers whole pig purchases.',
            ],
            [
                'name' => 'Batangas Lechon House',
                'contact_number' => '0908-555-3003',
                'address' => 'Nasugbu Highway, Batangas',
                'notes' => 'Purchases pigs for lechon roasting business.',
            ],
            [
                'name' => 'Roberto G. Fernandez',
                'contact_number' => '0935-555-4004',
                'address' => 'Brgy. Bunga, Lian, Batangas',
                'notes' => 'Local meat vendor. Buys per-head with itemized pricing.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sales(): array
    {
        return [
            // CYC-2025-003 sales (5 pigs, ready for sale)
            [
                'cycle_code' => 'CYC-2025-003', 'buyer' => 'Aling Nena\'s Meat Shop',
                'pigs_sold' => 1, 'amount' => 19656, 'sale_date' => '2026-03-05',
                'sale_method' => 'live_weight', 'live_weight_kg' => 75.6, 'price_per_kg' => 260, 'price_per_head' => null,
                'payment_status' => 'paid', 'amount_paid' => 19656,
                'receipt_reference' => 'SALE-CYC003-001', 'notes' => 'Whole pig, 75.6 kg live weight.',
            ],
            [
                'cycle_code' => 'CYC-2025-003', 'buyer' => 'Mang Jose C. Delgado',
                'pigs_sold' => 1, 'amount' => 20500, 'sale_date' => '2026-03-18',
                'sale_method' => 'per_head', 'live_weight_kg' => 78.2, 'price_per_kg' => null, 'price_per_head' => 20500,
                'payment_status' => 'paid', 'amount_paid' => 20500,
                'receipt_reference' => 'SALE-CYC003-002', 'notes' => 'Per-head: Body/Pata/Ulo itemized.',
            ],
            [
                'cycle_code' => 'CYC-2025-003', 'buyer' => 'Batangas Lechon House',
                'pigs_sold' => 1, 'amount' => 21840, 'sale_date' => '2026-04-05',
                'sale_method' => 'live_weight', 'live_weight_kg' => 84.0, 'price_per_kg' => 260, 'price_per_head' => null,
                'payment_status' => 'paid', 'amount_paid' => 21840,
                'receipt_reference' => 'SALE-CYC003-003', 'notes' => 'Whole pig, 84.0 kg live weight.',
            ],
            [
                'cycle_code' => 'CYC-2025-003', 'buyer' => 'Roberto G. Fernandez',
                'pigs_sold' => 2, 'amount' => 41000, 'sale_date' => '2026-04-18',
                'sale_method' => 'per_head', 'live_weight_kg' => 155.0, 'price_per_kg' => null, 'price_per_head' => 20500,
                'payment_status' => 'paid', 'amount_paid' => 41000,
                'receipt_reference' => 'SALE-CYC003-004', 'notes' => 'Two pigs, per-head pricing.',
            ],
            // CYC-2025-004 sales (8 pigs, completed)
            [
                'cycle_code' => 'CYC-2025-004', 'buyer' => 'Aling Nena\'s Meat Shop',
                'pigs_sold' => 2, 'amount' => 42120, 'sale_date' => '2026-02-15',
                'sale_method' => 'live_weight', 'live_weight_kg' => 162.0, 'price_per_kg' => 260, 'price_per_head' => null,
                'payment_status' => 'paid', 'amount_paid' => 42120,
                'receipt_reference' => 'SALE-CYC004-001', 'notes' => 'Two pig batch, whole live weight.',
            ],
            [
                'cycle_code' => 'CYC-2025-004', 'buyer' => 'Mang Jose C. Delgado',
                'pigs_sold' => 3, 'amount' => 61500, 'sale_date' => '2026-03-01',
                'sale_method' => 'per_head', 'live_weight_kg' => 230.0, 'price_per_kg' => null, 'price_per_head' => 20500,
                'payment_status' => 'paid', 'amount_paid' => 61500,
                'receipt_reference' => 'SALE-CYC004-002', 'notes' => 'Three pigs per-head itemized.',
            ],
            [
                'cycle_code' => 'CYC-2025-004', 'buyer' => 'Batangas Lechon House',
                'pigs_sold' => 3, 'amount' => 65700, 'sale_date' => '2026-03-20',
                'sale_method' => 'live_weight', 'live_weight_kg' => 252.7, 'price_per_kg' => 260, 'price_per_head' => null,
                'payment_status' => 'paid', 'amount_paid' => 65700,
                'receipt_reference' => 'SALE-CYC004-003', 'notes' => 'Three pigs, live weight sale.',
            ],
            // CYC-2025-005 sales (7 pigs, ready for sale - partial)
            [
                'cycle_code' => 'CYC-2025-005', 'buyer' => 'Mang Jose C. Delgado',
                'pigs_sold' => 1, 'amount' => 21000, 'sale_date' => '2026-04-10',
                'sale_method' => 'per_head', 'live_weight_kg' => 80.0, 'price_per_kg' => null, 'price_per_head' => 21000,
                'payment_status' => 'paid', 'amount_paid' => 21000,
                'receipt_reference' => 'SALE-CYC005-001', 'notes' => 'Per-head sale.',
            ],
            [
                'cycle_code' => 'CYC-2025-005', 'buyer' => 'Roberto G. Fernandez',
                'pigs_sold' => 2, 'amount' => 42000, 'sale_date' => '2026-04-22',
                'sale_method' => 'per_head', 'live_weight_kg' => 158.0, 'price_per_kg' => null, 'price_per_head' => 21000,
                'payment_status' => 'partial', 'amount_paid' => 25000,
                'receipt_reference' => 'SALE-CYC005-002', 'notes' => 'Partial payment. Remaining P17,000 due May 2026.',
            ],
        ];
    }
}
