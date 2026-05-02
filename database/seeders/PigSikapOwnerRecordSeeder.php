<?php

namespace Database\Seeders;

use App\Models\PigBuyer;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\ProfitabilitySnapshot;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PigSikapOwnerRecordSeeder extends Seeder
{
    /**
     * Password for all demo users: password
     *
     * Data source:
     * - REPORT(Expenses Cycle 1-5).csv
     * - REPORT(Pig Cycle 1).csv
     * - REPORT(Pig Cycle 2).csv
     *
     * Notes:
     * - REPORT(Additional Expenses).csv is association-level data and is not linked here
     *   because the current expense table is cycle-based through pig_cycle_expenses.batch_id.
     * - Two obvious source date typos were adjusted in expense notes:
     *   Cycle 3 row 18 and Cycle 4 row 19 are moved to 2026 to fit their cycle timeline.
     */
    public function run(): void
    {
        if (Role::count() === 0) {
            $this->call(RoleSeeder::class);
        }

        DB::transaction(function (): void {
            $this->seedUsers();
            $this->clearDemoCycleData();
            $cycles = $this->seedCycles();
            $buyers = $this->seedBuyers();
            $this->seedExpenses($cycles);
            $this->seedSales($cycles, $buyers);
            $this->seedProfitabilitySnapshots($cycles);
        });
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $users = [];

        foreach ($this->demoUsers() as $row) {
            $roleId = Role::where('slug', $row['role'])->value('id');

            $user = User::updateOrCreate(
                ['email' => $row['email']],
                $this->onlyExistingColumns('users', [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $roleId,
                    'is_active' => true,
                    'must_change_password' => false,
                ])
            );

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $users[$row['email']] = $user;
        }

        return $users;
    }

    private function clearDemoCycleData(): void
    {
        $cycleIds = PigCycle::withTrashed()
            ->whereIn('batch_code', array_column($this->cycles(), 'batch_code'))
            ->pluck('id')
            ->all();

        if ($cycleIds === []) {
            return;
        }

        if (Schema::hasTable('profitability_snapshots')) {
            DB::table('profitability_snapshots')->whereIn('pig_cycle_id', $cycleIds)->delete();
        }

        if (Schema::hasTable('pig_cycle_sales')) {
            DB::table('pig_cycle_sales')->whereIn('batch_id', $cycleIds)->delete();
        }

        if (Schema::hasTable('pig_cycle_expenses')) {
            DB::table('pig_cycle_expenses')->whereIn('batch_id', $cycleIds)->delete();
        }

        PigCycle::withTrashed()
            ->whereIn('id', $cycleIds)
            ->get()
            ->each(fn (PigCycle $cycle) => $cycle->forceDelete());
    }

    /**
     * @return array<string, PigCycle>
     */
    private function seedCycles(): array
    {
        $cycles = [];
        $presidentId = User::where('email', 'eva.vivas@pigsikap.local')->value('id');

        foreach ($this->cycles() as $row) {
            $caretakerId = User::where('email', $row['caretaker_email'])->value('id');

            $cycle = PigCycle::create($this->onlyExistingColumns('pig_cycles', [
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
            ]));

            $cycles[$row['batch_code']] = $cycle;
        }

        return $cycles;
    }

    /**
     * @return array<string, PigBuyer|null>
     */
    private function seedBuyers(): array
    {
        $buyers = [];

        if (! Schema::hasTable('pig_buyers')) {
            return $buyers;
        }

        $presidentId = User::where('email', 'eva.vivas@pigsikap.local')->value('id');

        foreach (['CSV Buyer Cycle 1', 'CSV Buyer Cycle 2'] as $name) {
            $buyer = PigBuyer::withTrashed()->firstOrNew(['name' => $name]);
            $buyer->fill($this->onlyExistingColumns('pig_buyers', [
                'name' => $name,
                'contact_number' => null,
                'address' => 'Barangay Humayingan, Lian, Batangas',
                'notes' => 'Demo buyer generated from uploaded Pig Cycle CSV sales records.',
                'created_by' => $presidentId,
                'updated_by' => $presidentId,
            ]));

            if (method_exists($buyer, 'trashed') && $buyer->trashed()) {
                $buyer->restore();
            }

            $buyer->save();
            $buyers[$name] = $buyer;
        }

        return $buyers;
    }

    /**
     * @param  array<string, PigCycle>  $cycles
     */
    private function seedExpenses(array $cycles): void
    {
        $presidentId = User::where('email', 'eva.vivas@pigsikap.local')->value('id');

        foreach ($this->expenses() as $row) {
            $cycle = $cycles[$row['cycle_code']] ?? null;

            if (! $cycle) {
                continue;
            }

            PigCycleExpense::create($this->onlyExistingColumns('pig_cycle_expenses', [
                'batch_id' => $cycle->id,
                'category' => $row['category'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'unit_cost' => $row['unit_cost'],
                'amount' => $row['amount'],
                'expense_date' => $row['expense_date'],
                'notes' => trim($row['notes'].'; receipt: '.($row['receipt_reference'] ?? 'none')),
                'receipt_path' => null,
                'created_by' => $presidentId,
                'updated_by' => $presidentId,
            ]));
        }
    }

    /**
     * @param  array<string, PigCycle>  $cycles
     * @param  array<string, PigBuyer|null>  $buyers
     */
    private function seedSales(array $cycles, array $buyers): void
    {
        $presidentId = User::where('email', 'eva.vivas@pigsikap.local')->value('id');

        foreach ($this->sales() as $row) {
            $cycle = $cycles[$row['cycle_code']] ?? null;

            if (! $cycle) {
                continue;
            }

            PigCycleSale::create($this->onlyExistingColumns('pig_cycle_sales', [
                'batch_id' => $cycle->id,
                'buyer_id' => $buyers[$row['buyer']]->id ?? null,
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
            ]));
        }
    }

    /**
     * @param  array<string, PigCycle>  $cycles
     */
    private function seedProfitabilitySnapshots(array $cycles): void
    {
        if (! Schema::hasTable('profitability_snapshots')) {
            return;
        }

        $presidentId = User::where('email', 'eva.vivas@pigsikap.local')->value('id');

        foreach (['CSV-CYCLE-001', 'CSV-CYCLE-002'] as $cycleCode) {
            $cycle = $cycles[$cycleCode] ?? null;

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
            $snapshotNumber = (int) str_replace('CSV-CYCLE-', '', $cycleCode);

            $payload = $this->onlyExistingColumns('profitability_snapshots', [
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
                    'source' => 'association practice: 50/25/25 after deducting expenses',
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
                'notes' => 'Demo finalized snapshot generated from uploaded CSV source data.',
                'computation_version' => '2026-05-cycle-profitability-v1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('profitability_snapshots')->insert($payload);
        }
    }

    /**
     * Keep this seeder resilient if migrations are adjusted during development.
     *
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
     * @return list<array<string, string>>
     */
    private function demoUsers(): array
    {
        return [
            [
                'name' => 'Eva G. Vivas',
                'email' => 'eva.vivas@pigsikap.local',
                'role' => 'president',
            ],
            [
                'name' => 'Ronalyn C. Balbar',
                'email' => 'ronalyn.balbar@pigsikap.local',
                'role' => 'secretary',
            ],
            [
                'name' => 'Anaceta C. Guevarra',
                'email' => 'anaceta.guevarra@pigsikap.local',
                'role' => 'treasurer',
            ],
            [
                'name' => 'Maricon Aquino',
                'email' => 'maricon.aquino@pigsikap.local',
                'role' => 'officer',
            ],
            [
                'name' => 'Leciria Vabingan',
                'email' => 'leciria.vabingan@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Cycle 3 Caretaker',
                'email' => 'csv.caretaker3@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Cycle 4 Caretaker',
                'email' => 'csv.caretaker4@pigsikap.local',
                'role' => 'member',
            ],
            [
                'name' => 'Cycle 5 Caretaker',
                'email' => 'csv.caretaker5@pigsikap.local',
                'role' => 'member',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cycles(): array
    {
        return [
            [
                'batch_code' => 'CSV-CYCLE-001',
                'caretaker_email' => 'maricon.aquino@pigsikap.local',
                'cycle_number' => 1,
                'date_of_purchase' => '2025-08-15',
                'initial_count' => 9,
                'current_count' => 0,
                'average_weight' => null,
                'stage' => 'Completed',
                'status' => 'Closed',
                'has_pig_profiles' => false,
                'notes' => 'Seeded from REPORT(Pig Cycle 1).csv and REPORT(Expenses Cycle 1).csv. Total pigs: 9; caretaker from CSV: Maricon Aquino.',
            ],
            [
                'batch_code' => 'CSV-CYCLE-002',
                'caretaker_email' => 'leciria.vabingan@pigsikap.local',
                'cycle_number' => 2,
                'date_of_purchase' => '2025-08-30',
                'initial_count' => 5,
                'current_count' => 0,
                'average_weight' => null,
                'stage' => 'Completed',
                'status' => 'Closed',
                'has_pig_profiles' => false,
                'notes' => 'Seeded from REPORT(Pig Cycle 2).csv and REPORT(Expenses Cycle 2).csv. Source sale report says total pigs: 5; expense report lists 6 piglets.',
            ],
            [
                'batch_code' => 'CSV-CYCLE-003',
                'caretaker_email' => 'csv.caretaker3@pigsikap.local',
                'cycle_number' => 3,
                'date_of_purchase' => '2025-10-09',
                'initial_count' => 5,
                'current_count' => 5,
                'average_weight' => null,
                'stage' => 'For Sale',
                'status' => 'Ready for Sale',
                'has_pig_profiles' => false,
                'notes' => 'Seeded from REPORT(Expenses Cycle 3).csv. No matching sales CSV uploaded.',
            ],
            [
                'batch_code' => 'CSV-CYCLE-004',
                'caretaker_email' => 'csv.caretaker4@pigsikap.local',
                'cycle_number' => 4,
                'date_of_purchase' => '2026-01-26',
                'initial_count' => 12,
                'current_count' => 12,
                'average_weight' => null,
                'stage' => 'Fattening',
                'status' => 'Under Monitoring',
                'has_pig_profiles' => false,
                'notes' => 'Seeded from REPORT(Expenses Cycle 4).csv. No matching sales CSV uploaded.',
            ],
            [
                'batch_code' => 'CSV-CYCLE-005',
                'caretaker_email' => 'csv.caretaker5@pigsikap.local',
                'cycle_number' => 5,
                'date_of_purchase' => '2026-02-15',
                'initial_count' => 5,
                'current_count' => 5,
                'average_weight' => null,
                'stage' => 'Growing',
                'status' => 'Active',
                'has_pig_profiles' => false,
                'notes' => 'Seeded from REPORT(Expenses Cycle 5).csv. No matching sales CSV uploaded.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expenses(): array
    {
        return [
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'emergency',
                'item' => 'Drum',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1000,
                'amount' => 1000,
                'expense_date' => '2025-08-14',
                'receipt_reference' => '3965781',
                'notes' => 'CSV row 1: Drum',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'acquisition',
                'item' => 'Biik',
                'quantity' => 9,
                'unit' => 'unit',
                'unit_cost' => 7000,
                'amount' => 63000,
                'expense_date' => '2025-08-15',
                'receipt_reference' => null,
                'notes' => 'CSV row 2: Biik',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 25,
                'unit' => 'unit',
                'unit_cost' => 33.2,
                'amount' => 830,
                'expense_date' => '2025-08-16',
                'receipt_reference' => '75608',
                'notes' => 'CSV row 3: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 2005,
                'amount' => 2005,
                'expense_date' => '2025-08-16',
                'receipt_reference' => '16401',
                'notes' => 'CSV row 4: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Vitamin Pro',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 20,
                'amount' => 60,
                'expense_date' => '2025-08-16',
                'receipt_reference' => '16401',
                'notes' => 'CSV row 5: Vitamin Pro',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Streptopen',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 60,
                'amount' => 120,
                'expense_date' => '2025-08-16',
                'receipt_reference' => '16401',
                'notes' => 'CSV row 6: Streptopen',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Latigo 1000',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 32,
                'amount' => 32,
                'expense_date' => '2025-08-16',
                'receipt_reference' => '16401',
                'notes' => 'CSV row 7: Latigo 1000',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Vetracin',
                'quantity' => 12,
                'unit' => 'unit',
                'unit_cost' => 24,
                'amount' => 288,
                'expense_date' => '2025-08-17',
                'receipt_reference' => null,
                'notes' => 'CSV row 8: Vetracin',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Syringe',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 15,
                'amount' => 15,
                'expense_date' => '2025-08-17',
                'receipt_reference' => null,
                'notes' => 'CSV row 9: Syringe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 2005,
                'amount' => 6015,
                'expense_date' => '2025-08-24',
                'receipt_reference' => '16501',
                'notes' => 'CSV row 10: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 85,
                'amount' => 85,
                'expense_date' => '2025-08-24',
                'receipt_reference' => '160764',
                'notes' => 'CSV row 11: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 1520,
                'amount' => 7600,
                'expense_date' => '2025-09-06',
                'receipt_reference' => '869',
                'notes' => 'CSV row 12: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Vitmin Pro',
                'quantity' => 18,
                'unit' => 'unit',
                'unit_cost' => 20,
                'amount' => 360,
                'expense_date' => '2025-09-06',
                'receipt_reference' => '869',
                'notes' => 'CSV row 13: Vitmin Pro',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Vetracin',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 24,
                'amount' => 120,
                'expense_date' => '2025-09-06',
                'receipt_reference' => '869',
                'notes' => 'CSV row 14: Vetracin',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Latigo 1000',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 24,
                'amount' => 68,
                'expense_date' => '2025-09-06',
                'receipt_reference' => '869',
                'notes' => 'CSV row 15: Latigo 1000',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-09-06',
                'receipt_reference' => '90389003',
                'notes' => 'CSV row 16: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Injection',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 250,
                'amount' => 250,
                'expense_date' => '2025-09-13',
                'receipt_reference' => '3965782',
                'notes' => 'CSV row 17: Injection',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1350,
                'amount' => 8100,
                'expense_date' => '2025-09-24',
                'receipt_reference' => '898',
                'notes' => 'CSV row 18: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-09-24',
                'receipt_reference' => '9038904',
                'notes' => 'CSV row 19: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Vetracin',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 24,
                'amount' => 72,
                'expense_date' => '2025-09-24',
                'receipt_reference' => '898',
                'notes' => 'CSV row 20: Vetracin',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Injection',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 150,
                'amount' => 150,
                'expense_date' => '2025-10-06',
                'receipt_reference' => '9038905',
                'notes' => 'CSV row 21: Injection',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1350,
                'amount' => 8100,
                'expense_date' => '2025-10-14',
                'receipt_reference' => '932',
                'notes' => 'CSV row 22: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-10-14',
                'receipt_reference' => '9038907',
                'notes' => 'CSV row 23: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 1350,
                'amount' => 4050,
                'expense_date' => '2025-10-31',
                'receipt_reference' => '961',
                'notes' => 'CSV row 24: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'medicine',
                'item' => 'Latigo 1000',
                'quantity' => 4,
                'unit' => 'unit',
                'unit_cost' => 34,
                'amount' => 136,
                'expense_date' => '2025-10-31',
                'receipt_reference' => '961',
                'notes' => 'CSV row 25: Latigo 1000',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-10-31',
                'receipt_reference' => '9038908',
                'notes' => 'CSV row 26: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1350,
                'amount' => 8100,
                'expense_date' => '2025-11-07',
                'receipt_reference' => '972',
                'notes' => 'CSV row 27: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-11-07',
                'receipt_reference' => '9038904',
                'notes' => 'CSV row 28: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1350,
                'amount' => 8100,
                'expense_date' => '2025-11-29',
                'receipt_reference' => '1002',
                'notes' => 'CSV row 29: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-11-29',
                'receipt_reference' => '9038910',
                'notes' => 'CSV row 30: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gastos Katay',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 500,
                'amount' => 500,
                'expense_date' => '2025-12-12',
                'receipt_reference' => '3965790',
                'notes' => 'CSV row 31: Gastos Katay',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'emergency',
                'item' => 'Plastic',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 85,
                'amount' => 85,
                'expense_date' => '2025-12-12',
                'receipt_reference' => '3965790',
                'notes' => 'CSV row 32: Plastic',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 1390,
                'amount' => 4170,
                'expense_date' => '2025-12-19',
                'receipt_reference' => '1034',
                'notes' => 'CSV row 33: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2025-12-19',
                'receipt_reference' => '9038911',
                'notes' => 'CSV row 34: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gastos Katay',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 500,
                'amount' => 500,
                'expense_date' => '2025-12-22',
                'receipt_reference' => '3965793',
                'notes' => 'CSV row 35: Gastos Katay',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1390,
                'amount' => 1390,
                'expense_date' => '2025-12-30',
                'receipt_reference' => '1048',
                'notes' => 'CSV row 36: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gastos Katay',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 677.5,
                'amount' => 1355,
                'expense_date' => '2026-01-01',
                'receipt_reference' => '3965798',
                'notes' => 'CSV row 37: Gastos Katay',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'category' => 'transport',
                'item' => 'Gastos Katay',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 612,
                'amount' => 1835,
                'expense_date' => '2026-01-12',
                'receipt_reference' => '3965796',
                'notes' => 'CSV row 38: Gastos Katay',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Pre Starter',
                'quantity' => 12.5,
                'unit' => 'unit',
                'unit_cost' => 62.8,
                'amount' => 787,
                'expense_date' => '2025-08-30',
                'receipt_reference' => '16527',
                'notes' => 'CSV row 1: Pre Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 2005,
                'amount' => 2005,
                'expense_date' => '2025-08-30',
                'receipt_reference' => '16527',
                'notes' => 'CSV row 2: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'medicine',
                'item' => 'Vetricin',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 105,
                'amount' => 105,
                'expense_date' => '2025-08-30',
                'receipt_reference' => '16527',
                'notes' => 'CSV row 3: Vetricin',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'acquisition',
                'item' => 'Piglet',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 7000,
                'amount' => 42000,
                'expense_date' => '2025-08-30',
                'receipt_reference' => null,
                'notes' => 'CSV row 4: Piglet',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1850,
                'amount' => 3700,
                'expense_date' => '2025-09-08',
                'receipt_reference' => '550',
                'notes' => 'CSV row 5: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 1680,
                'amount' => 5040,
                'expense_date' => '2025-09-08',
                'receipt_reference' => '550',
                'notes' => 'CSV row 6: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'medicine',
                'item' => 'Vetricin',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 24,
                'amount' => 120,
                'expense_date' => '2025-09-04',
                'receipt_reference' => '16585',
                'notes' => 'CSV row 7: Vetricin',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'medicine',
                'item' => 'Latigo 1000',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 32,
                'amount' => 64,
                'expense_date' => '2025-09-04',
                'receipt_reference' => '16585',
                'notes' => 'CSV row 8: Latigo 1000',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'medicine',
                'item' => 'Vitmin Pro',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 20,
                'amount' => 100,
                'expense_date' => '2025-09-04',
                'receipt_reference' => '16585',
                'notes' => 'CSV row 9: Vitmin Pro',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1640,
                'amount' => 9840,
                'expense_date' => '2025-10-05',
                'receipt_reference' => '803',
                'notes' => 'CSV row 10: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1640,
                'amount' => 9840,
                'expense_date' => '2025-10-27',
                'receipt_reference' => '825',
                'notes' => 'CSV row 11: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 6,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 9780,
                'expense_date' => '2025-11-20',
                'receipt_reference' => '835',
                'notes' => 'CSV row 12: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 1630,
                'expense_date' => '2025-12-09',
                'receipt_reference' => null,
                'notes' => 'CSV row 13: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 3260,
                'expense_date' => '2025-12-16',
                'receipt_reference' => '849',
                'notes' => 'CSV row 14: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 3260,
                'expense_date' => '2025-12-22',
                'receipt_reference' => '551',
                'notes' => 'CSV row 15: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 3260,
                'expense_date' => '2026-01-03',
                'receipt_reference' => '555',
                'notes' => 'CSV row 16: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 1630,
                'expense_date' => '2026-01-14',
                'receipt_reference' => '561',
                'notes' => 'CSV row 17: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'category' => 'feed',
                'item' => 'Hog Finisher',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1630,
                'amount' => 3260,
                'expense_date' => '2026-01-22',
                'receipt_reference' => '570',
                'notes' => 'CSV row 18: Hog Finisher',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'acquisition',
                'item' => 'Piglet',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 7000,
                'amount' => 35000,
                'expense_date' => '2025-10-09',
                'receipt_reference' => null,
                'notes' => 'CSV row 1: Piglet',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Pre Starter',
                'quantity' => 12,
                'unit' => 'unit',
                'unit_cost' => 100,
                'amount' => 787,
                'expense_date' => '2025-10-09',
                'receipt_reference' => '16736',
                'notes' => 'CSV row 2: Pre Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Pre Starter',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1890,
                'amount' => 1890,
                'expense_date' => '2025-10-09',
                'receipt_reference' => '16736',
                'notes' => 'CSV row 3: Pre Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'medicine',
                'item' => 'Vetracin',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 24,
                'amount' => 120,
                'expense_date' => '2025-10-09',
                'receipt_reference' => '16736',
                'notes' => 'CSV row 4: Vetracin',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 100,
                'expense_date' => '2025-10-09',
                'receipt_reference' => '3965783',
                'notes' => 'CSV row 5: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'medicine',
                'item' => 'Injection',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 200,
                'expense_date' => '2025-10-26',
                'receipt_reference' => null,
                'notes' => 'CSV row 6: Injection',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1890,
                'amount' => 3780,
                'expense_date' => '2025-10-26',
                'receipt_reference' => '16808',
                'notes' => 'CSV row 7: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 100,
                'expense_date' => '2025-10-24',
                'receipt_reference' => '3965784',
                'notes' => 'CSV row 8: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1890,
                'amount' => 3780,
                'expense_date' => '2025-11-12',
                'receipt_reference' => '16862',
                'notes' => 'CSV row 9: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 100,
                'expense_date' => '2025-11-12',
                'receipt_reference' => '3965786',
                'notes' => 'CSV row 10: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 1795,
                'amount' => 5385,
                'expense_date' => '2025-11-23',
                'receipt_reference' => '16900',
                'notes' => 'CSV row 11: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 100,
                'expense_date' => '2025-11-23',
                'receipt_reference' => '3965786',
                'notes' => 'CSV row 12: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 10,
                'unit' => 'unit',
                'unit_cost' => 1795,
                'amount' => 17950,
                'expense_date' => '2025-12-05',
                'receipt_reference' => '16949',
                'notes' => 'CSV row 13: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'medicine',
                'item' => 'Latigo 1000',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 33,
                'amount' => 66,
                'expense_date' => '2025-12-05',
                'receipt_reference' => '16950',
                'notes' => 'CSV row 14: Latigo 1000',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 242,
                'amount' => 484,
                'expense_date' => '2025-12-05',
                'receipt_reference' => '3965791',
                'notes' => 'CSV row 15: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1695,
                'amount' => 3390,
                'expense_date' => '2026-01-11',
                'receipt_reference' => '1277',
                'notes' => 'CSV row 16: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => null,
                'amount' => 100,
                'expense_date' => '2026-01-11',
                'receipt_reference' => '3965799',
                'notes' => 'CSV row 17: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-003',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1520,
                'amount' => 1520,
                'expense_date' => '2026-01-25',
                'receipt_reference' => '131552',
                'notes' => 'CSV row 18: Hog Grower; source date adjusted from 2025-01-25 to 2026-01-25',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'acquisition',
                'item' => 'Piglet',
                'quantity' => 12,
                'unit' => 'unit',
                'unit_cost' => 6800,
                'amount' => 81600,
                'expense_date' => '2026-01-26',
                'receipt_reference' => null,
                'notes' => 'CSV row 1: Piglet',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 100,
                'expense_date' => '2026-01-26',
                'receipt_reference' => null,
                'notes' => 'CSV row 2: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Vitmin Pro',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 330,
                'amount' => 330,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '1084',
                'notes' => 'CSV row 3: Vitmin Pro',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Aquadox',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 180,
                'amount' => 180,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '1084',
                'notes' => 'CSV row 4: Aquadox',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Vimtin Pro',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 650,
                'amount' => 650,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '1084',
                'notes' => 'CSV row 5: Vimtin Pro',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Dextrose Powder',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => null,
                'amount' => 70,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '1084',
                'notes' => 'CSV row 6: Dextrose Powder',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Streptopen',
                'quantity' => 12,
                'unit' => 'unit',
                'unit_cost' => null,
                'amount' => 70,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '82619',
                'notes' => 'CSV row 7: Streptopen',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'Hog Nipple',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 180,
                'amount' => 180,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '82619',
                'notes' => 'CSV row 8: Hog Nipple',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'acquisition',
                'item' => 'Bayad pagkuha ng biik',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => null,
                'amount' => 500,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '82619',
                'notes' => 'CSV row 9: Bayad pagkuha ng biik',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'Hog Nipple',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 180,
                'amount' => 180,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '9038912',
                'notes' => 'CSV row 10: Hog Nipple',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'PVC. PIPE 1/2',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 55,
                'amount' => 55,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '9296497',
                'notes' => 'CSV row 11: PVC. PIPE 1/2',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'PVC. MALE',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 15,
                'amount' => 15,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '9296497',
                'notes' => 'CSV row 12: PVC. MALE',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'PVC. FEMALE',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 15,
                'amount' => 30,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '9296497',
                'notes' => 'CSV row 13: PVC. FEMALE',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'ELBOW',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 12,
                'amount' => 36,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '9296497',
                'notes' => 'CSV row 14: ELBOW',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Pre Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1275,
                'amount' => 2550,
                'expense_date' => '2026-01-26',
                'receipt_reference' => '195854',
                'notes' => 'CSV row 15: Pre Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Pre Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1275,
                'amount' => 2550,
                'expense_date' => '2026-02-05',
                'receipt_reference' => '195709',
                'notes' => 'CSV row 16: Pre Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1570,
                'amount' => 1570,
                'expense_date' => '2026-02-05',
                'receipt_reference' => '1097',
                'notes' => 'CSV row 17: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'emergency',
                'item' => 'Drum',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 219,
                'amount' => 219,
                'expense_date' => '2026-02-05',
                'receipt_reference' => '61312',
                'notes' => 'CSV row 18: Drum',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 3,
                'unit' => 'unit',
                'unit_cost' => 1570,
                'amount' => 4710,
                'expense_date' => '2026-02-15',
                'receipt_reference' => '1111',
                'notes' => 'CSV row 19: Hog Starter; source date adjusted from 2025-02-15 to 2026-02-15',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2026-02-15',
                'receipt_reference' => '9038914',
                'notes' => 'CSV row 20: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Injectable',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 250,
                'amount' => 250,
                'expense_date' => '2026-02-20',
                'receipt_reference' => '9038916',
                'notes' => 'CSV row 21: Injectable',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 1570,
                'amount' => 7850,
                'expense_date' => '2026-03-01',
                'receipt_reference' => '1134',
                'notes' => 'CSV row 22: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'transport',
                'item' => 'Gas',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2026-03-01',
                'receipt_reference' => '9038917',
                'notes' => 'CSV row 23: Gas',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1570,
                'amount' => 3140,
                'expense_date' => '2026-03-12',
                'receipt_reference' => '1152',
                'notes' => 'CSV row 24: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 1390,
                'amount' => 2780,
                'expense_date' => '2026-03-12',
                'receipt_reference' => '1152',
                'notes' => 'CSV row 25: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'transport',
                'item' => 'Delivery Charge',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 100,
                'amount' => 100,
                'expense_date' => '2026-03-12',
                'receipt_reference' => '1152',
                'notes' => 'CSV row 26: Delivery Charge',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1570,
                'amount' => 1570,
                'expense_date' => '2026-03-14',
                'receipt_reference' => '1155',
                'notes' => 'CSV row 27: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 10,
                'unit' => 'unit',
                'unit_cost' => 1460,
                'amount' => 14600,
                'expense_date' => '2026-03-20',
                'receipt_reference' => '1162',
                'notes' => 'CSV row 28: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Latigo 1000',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 34,
                'amount' => 68,
                'expense_date' => '2026-03-20',
                'receipt_reference' => '1162',
                'notes' => 'CSV row 29: Latigo 1000',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-004',
                'category' => 'medicine',
                'item' => 'Injectable',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 250,
                'amount' => 250,
                'expense_date' => '2026-03-21',
                'receipt_reference' => null,
                'notes' => 'CSV row 30: Injectable',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'acquisition',
                'item' => 'Piglet',
                'quantity' => 5,
                'unit' => 'unit',
                'unit_cost' => 6800,
                'amount' => 34000,
                'expense_date' => '2026-02-15',
                'receipt_reference' => null,
                'notes' => 'CSV row 1: Piglet',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'transport',
                'item' => 'Delivery Charge',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 400,
                'amount' => 400,
                'expense_date' => '2026-02-15',
                'receipt_reference' => '61313',
                'notes' => 'CSV row 2: Delivery Charge',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'feed',
                'item' => 'Pre Starter',
                'quantity' => 2,
                'unit' => 'unit',
                'unit_cost' => 150,
                'amount' => 200,
                'expense_date' => '2026-02-15',
                'receipt_reference' => '2301',
                'notes' => 'CSV row 3: Pre Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'feed',
                'item' => 'Tracking for feeds',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 200,
                'amount' => 200,
                'expense_date' => '2026-02-15',
                'receipt_reference' => '61314',
                'notes' => 'CSV row 4: Tracking for feeds',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 1,
                'unit' => 'unit',
                'unit_cost' => 1790,
                'amount' => 1790,
                'expense_date' => '2026-02-19',
                'receipt_reference' => '2475',
                'notes' => 'CSV row 5: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'feed',
                'item' => 'Hog Starter',
                'quantity' => 4,
                'unit' => 'unit',
                'unit_cost' => 1890,
                'amount' => 7560,
                'expense_date' => '2026-03-03',
                'receipt_reference' => '1',
                'notes' => 'CSV row 6: Hog Starter',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 200,
                'amount' => 200,
                'expense_date' => '2026-03-03',
                'receipt_reference' => '61315',
                'notes' => 'CSV row 7: Pamasahe',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'feed',
                'item' => 'Hog Grower',
                'quantity' => 4,
                'unit' => 'unit',
                'unit_cost' => 1995,
                'amount' => 7980,
                'expense_date' => '2026-03-19',
                'receipt_reference' => '17402',
                'notes' => 'CSV row 8: Hog Grower',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'medicine',
                'item' => 'Injectable',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 200,
                'amount' => 200,
                'expense_date' => '2026-03-19',
                'receipt_reference' => '2',
                'notes' => 'CSV row 9: Injectable',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-005',
                'category' => 'transport',
                'item' => 'Pamasahe',
                'quantity' => null,
                'unit' => null,
                'unit_cost' => 250,
                'amount' => 250,
                'expense_date' => '2026-03-19',
                'receipt_reference' => '2',
                'notes' => 'CSV row 10: Pamasahe',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sales(): array
    {
        return [
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'buyer' => 'CSV Buyer Cycle 1',
                'pigs_sold' => 1,
                'amount' => 22875,
                'sale_date' => '2025-12-04',
                'sale_method' => 'per_head',
                'live_weight_kg' => 74.7,
                'price_per_kg' => null,
                'price_per_head' => 22875,
                'payment_status' => 'paid',
                'amount_paid' => 22875,
                'receipt_reference' => 'CSV-C1-P1',
                'notes' => 'CSV sale P1: Body/Pata/Ulo itemized total.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'buyer' => 'CSV Buyer Cycle 1',
                'pigs_sold' => 1,
                'amount' => 19475,
                'sale_date' => '2025-12-12',
                'sale_method' => 'per_head',
                'live_weight_kg' => 63.9,
                'price_per_kg' => null,
                'price_per_head' => 19475,
                'payment_status' => 'paid',
                'amount_paid' => 19475,
                'receipt_reference' => 'CSV-C1-P2',
                'notes' => 'CSV sale P2: Body/Pata/Ulo itemized total.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'buyer' => 'CSV Buyer Cycle 1',
                'pigs_sold' => 1,
                'amount' => 16471,
                'sale_date' => '2025-12-23',
                'sale_method' => 'per_head',
                'live_weight_kg' => 63.8,
                'price_per_kg' => null,
                'price_per_head' => 16471,
                'payment_status' => 'paid',
                'amount_paid' => 16471,
                'receipt_reference' => 'CSV-C1-P3',
                'notes' => 'CSV sale P3: Body/Pata itemized total.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'buyer' => 'CSV Buyer Cycle 1',
                'pigs_sold' => 2,
                'amount' => 42259,
                'sale_date' => '2025-12-24',
                'sale_method' => 'per_head',
                'live_weight_kg' => 142.3,
                'price_per_kg' => null,
                'price_per_head' => 21129.5,
                'payment_status' => 'paid',
                'amount_paid' => 42259,
                'receipt_reference' => 'CSV-C1-P4-P5',
                'notes' => 'CSV sale P4 & P5: Body/Pata/Ulo itemized total.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'buyer' => 'CSV Buyer Cycle 1',
                'pigs_sold' => 3,
                'amount' => 56941,
                'sale_date' => '2025-12-31',
                'sale_method' => 'per_head',
                'live_weight_kg' => 199.1,
                'price_per_kg' => null,
                'price_per_head' => 18980.33,
                'payment_status' => 'paid',
                'amount_paid' => 56941,
                'receipt_reference' => 'CSV-C1-P6-P8',
                'notes' => 'CSV sale P6, P7, P8: Body/Pata/Ulo itemized total.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-001',
                'buyer' => 'CSV Buyer Cycle 1',
                'pigs_sold' => 1,
                'amount' => 17100,
                'sale_date' => '2026-01-12',
                'sale_method' => 'live_weight',
                'live_weight_kg' => 95,
                'price_per_kg' => 180,
                'price_per_head' => null,
                'payment_status' => 'paid',
                'amount_paid' => 17100,
                'receipt_reference' => 'CSV-C1-P9',
                'notes' => 'CSV sale P9: Whole, 95 kg at ₱180.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'buyer' => 'CSV Buyer Cycle 2',
                'pigs_sold' => 1,
                'amount' => 16406,
                'sale_date' => '2025-12-05',
                'sale_method' => 'live_weight',
                'live_weight_kg' => 63.1,
                'price_per_kg' => 260,
                'price_per_head' => null,
                'payment_status' => 'paid',
                'amount_paid' => 16406,
                'receipt_reference' => 'CSV-C2-P10',
                'notes' => 'CSV sale P10: Whole, 63.1 kg at ₱260.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'buyer' => 'CSV Buyer Cycle 2',
                'pigs_sold' => 1,
                'amount' => 19731.6,
                'sale_date' => '2026-02-16',
                'sale_method' => 'live_weight',
                'live_weight_kg' => 243,
                'price_per_kg' => 81.2,
                'price_per_head' => null,
                'payment_status' => 'paid',
                'amount_paid' => 19731.6,
                'receipt_reference' => 'CSV-C2-P11',
                'notes' => 'CSV sale P11: Whole, 243 kg at ₱81.20.',
            ],
            [
                'cycle_code' => 'CSV-CYCLE-002',
                'buyer' => 'CSV Buyer Cycle 2',
                'pigs_sold' => 3,
                'amount' => 46080,
                'sale_date' => '2026-02-16',
                'sale_method' => 'per_head',
                'live_weight_kg' => null,
                'price_per_kg' => null,
                'price_per_head' => 15360,
                'payment_status' => 'paid',
                'amount_paid' => 46080,
                'receipt_reference' => 'CSV-C2-P12-P15',
                'notes' => 'CSV sale P12, P13, P15: grouped sale; date/weight not specified in CSV.',
            ],
        ];
    }
}
