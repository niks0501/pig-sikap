<?php

namespace Database\Seeders;

use App\Models\AssociationExpense;
use App\Models\AuditTrail;
use App\Models\Canvass;
use App\Models\CanvassItem;
use App\Models\GeneratedReport;
use App\Models\PigBuyer;
use App\Models\ReportSchedule;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoBulkOperationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedBuyers();
            $this->seedSuppliers();
            $this->seedCanvasses();
            $this->seedAssociationExpenses();
            $this->seedAuditTrails();
            $this->seedReports();
        });
    }

    private function seedBuyers(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        foreach ($this->buyers() as $row) {
            $buyer = PigBuyer::withTrashed()->firstOrNew(['name' => $row['name']]);
            $buyer->fill($this->onlyExistingCols('pig_buyers', [
                'name' => $row['name'], 'contact_number' => $row['contact_number'],
                'address' => $row['address'], 'notes' => $row['notes'],
                'created_by' => $presidentId, 'updated_by' => $presidentId,
            ]));
            if (method_exists($buyer, 'trashed') && $buyer->trashed()) $buyer->restore();
            $buyer->save();
        }
    }

    private function seedSuppliers(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        foreach ($this->suppliers() as $row) {
            Supplier::updateOrCreate(
                ['name' => $row['name']],
                $this->onlyExistingCols('suppliers', [
                    'name' => $row['name'], 'contact_person' => $row['contact_person'],
                    'contact_number' => $row['contact_number'], 'address' => $row['address'],
                    'notes' => $row['notes'], 'created_by' => $presidentId,
                ])
            );
        }
    }

    private function seedCanvasses(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        foreach ($this->canvasses() as $row) {
            $canvass = Canvass::updateOrCreate(
                ['title' => $row['title'], 'canvass_date' => $row['canvass_date']],
                $this->onlyExistingCols('canvasses', [
                    'title' => $row['title'], 'canvass_date' => $row['canvass_date'],
                    'notes' => $row['notes'], 'status' => $row['status'],
                    'created_by' => $presidentId, 'updated_by' => $presidentId,
                ])
            );
            foreach ($row['items'] as $item) {
                $supplier = Supplier::where('name', $item['supplier'])->first();
                CanvassItem::updateOrCreate(
                    ['canvass_id' => $canvass->id, 'description' => $item['description'], 'supplier_id' => $supplier?->id],
                    $this->onlyExistingCols('canvass_items', [
                        'canvass_id' => $canvass->id, 'supplier_id' => $supplier?->id,
                        'description' => $item['description'], 'category' => $item['category'],
                        'quantity' => $item['quantity'], 'unit' => $item['unit'],
                        'unit_cost' => $item['unit_cost'], 'total' => $item['total'],
                        'is_selected' => $item['is_selected'] ?? false, 'sort_order' => $item['sort_order'],
                    ])
                );
            }
        }
    }

    private function seedAssociationExpenses(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        foreach ($this->associationExpenses() as $row) {
            AssociationExpense::updateOrCreate(
                ['item_name' => $row['item_name'], 'expense_date' => $row['expense_date'], 'amount' => $row['amount']],
                $this->onlyExistingCols('association_expenses', [
                    'item_name' => $row['item_name'], 'category' => $row['category'],
                    'quantity' => $row['qty'] ?? 1, 'unit' => $row['unit'] ?? 'lot',
                    'unit_cost' => $row['unit_cost'] ?? $row['amount'],
                    'amount' => $row['amount'], 'expense_date' => $row['expense_date'],
                    'receipt_reference' => $row['ref'] ?? null, 'fund_source' => $row['fund_source'] ?? 'association_fund',
                    'notes' => $row['notes'] ?? null, 'created_by' => $presidentId, 'updated_by' => $presidentId,
                ])
            );
        }
    }

    private function seedAuditTrails(): void
    {
        foreach ($this->auditTrails() as $row) {
            $user = User::where('email', $row['user_email'])->first();
            AuditTrail::updateOrCreate(
                ['user_id' => $user?->id, 'action' => $row['action'], 'module' => $row['module'], 'created_at' => $row['created_at']],
                ['user_id' => $user?->id, 'action' => $row['action'], 'module' => $row['module'], 'description' => $row['description'], 'created_at' => $row['created_at'], 'updated_at' => $row['created_at']]
            );
        }
    }

    private function seedReports(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');
        foreach ($this->generatedReports() as $row) {
            GeneratedReport::updateOrCreate(
                ['report_type' => $row['report_type'], 'generated_at' => $row['generated_at']],
                $this->onlyExistingCols('generated_reports', [
                    'report_type' => $row['report_type'], 'format' => $row['format'],
                    'generated_by' => $presidentId, 'status' => 'generated',
                    'generated_at' => $row['generated_at'], 'notes' => $row['notes'] ?? null,
                ])
            );
        }
        foreach ($this->reportSchedules() as $row) {
            ReportSchedule::updateOrCreate(
                ['report_type' => $row['report_type'], 'frequency' => $row['frequency']],
                $this->onlyExistingCols('report_schedules', [
                    'report_type' => $row['report_type'], 'format' => $row['format'],
                    'frequency' => $row['frequency'], 'day_of_month' => $row['day_of_month'],
                    'run_at' => $row['run_at'], 'status' => 'active',
                    'last_run_at' => $row['last_run_at'], 'next_run_at' => $row['next_run_at'],
                    'created_by' => $presidentId,
                ])
            );
        }
    }

    private function onlyExistingCols(string $table, array $attrs): array
    {
        if (! Schema::hasTable($table)) return [];
        return collect($attrs)->filter(fn($v, string $c): bool => Schema::hasColumn($table, $c))->all();
    }

    private function buyers(): array
    {
        return [
            ['name' => 'Mang Romy\'s Eatery', 'contact_number' => '0915-555-9001', 'address' => 'Lian Town Proper, Batangas', 'notes' => 'Regular buyer. Purchases for restaurant meat supply.'],
            ['name' => 'Balayan Meat Market', 'contact_number' => '0927-555-9002', 'address' => 'Balayan Public Market, Batangas', 'notes' => 'Wholesale meat buyer. Buys multiple pigs per transaction.'],
            ['name' => 'Rosario L. Villamil', 'contact_number' => '0939-555-9003', 'address' => 'Brgy. Prenza, Lian, Batangas', 'notes' => 'Individual buyer. Purchases 1-2 pigs per visit.'],
            ['name' => 'Nasugbu Lechoneros Association', 'contact_number' => '0918-555-9004', 'address' => 'Nasugbu Town, Batangas', 'notes' => 'Group buyer for lechon. Books in advance.'],
            ['name' => 'Crisanto\'s Food Services', 'contact_number' => '0922-555-9005', 'address' => 'Batangas City', 'notes' => 'Catering company. Buys processed pork cuts.'],
        ];
    }

    private function suppliers(): array
    {
        return [
            ['name' => 'San Juan Feed Mill', 'contact_person' => 'Mang Ben', 'contact_number' => '0919-555-7001', 'address' => 'San Juan, Batangas', 'notes' => 'Direct feed manufacturer. Bulk discount available.'],
            ['name' => 'Batangas Veterinary Clinic', 'contact_person' => 'Dr. Lopez', 'contact_number' => '0917-555-7002', 'address' => 'Batangas City', 'notes' => 'Full veterinary services and medical supplies.'],
            ['name' => 'Lian Farmers Cooperative', 'contact_person' => 'Ka Polding', 'contact_number' => '0908-555-7003', 'address' => 'Lian Town, Batangas', 'notes' => 'Cooperative supply store. Competitive prices on feeds.'],
            ['name' => 'Lipa Agri Trading', 'contact_person' => 'Mrs. Ramos', 'contact_number' => '0920-555-7004', 'address' => 'Lipa City, Batangas', 'notes' => 'Wholesale agri-supplies. Delivers to Lian area.'],
            ['name' => 'Bauan Hardware & Supply', 'contact_person' => 'Jun C.', 'contact_number' => '0935-555-7005', 'address' => 'Bauan, Batangas', 'notes' => 'Hardware and construction materials.'],
            ['name' => 'Tagaytay Livestock Supply', 'contact_person' => 'Mario D.', 'contact_number' => '0916-555-7006', 'address' => 'Tagaytay City', 'notes' => 'Premium livestock supplies and equipment.'],
            ['name' => 'Humayingan Sari-Sari Store', 'contact_person' => 'Aling Bising', 'contact_number' => '0909-555-7007', 'address' => 'Brgy. Humayingan, Lian, Batangas', 'notes' => 'Local store. Source of small supplies and emergency items.'],
            ['name' => 'Nasugbu Agrivet Center', 'contact_person' => 'Rey V.', 'contact_number' => '0921-555-7008', 'address' => 'Nasugbu, Batangas', 'notes' => 'Full agrivet store with delivery service.'],
            ['name' => 'Balayan Rice Mill', 'contact_person' => 'Mang Tony', 'contact_number' => '0918-555-7009', 'address' => 'Balayan, Batangas', 'notes' => 'Source of rice bran (darak) for supplemental feed.'],
            ['name' => 'JTC General Merchandise', 'contact_person' => 'Tess C.', 'contact_number' => '0927-555-7010', 'address' => 'Lian Town, Batangas', 'notes' => 'General supplies, tools, and pen maintenance materials.'],
        ];
    }

    private function canvasses(): array
    {
        return [
            [
                'title' => 'Feeds Price Comparison - Q1 2026', 'canvass_date' => '2026-01-10',
                'notes' => 'Quarterly feeds price check for all active cycles.', 'status' => 'awarded',
                'items' => [
                    ['supplier' => 'Lian Agri-Supply', 'description' => 'Hog Starter Feed', 'category' => 'feed', 'quantity' => 10, 'unit' => 'bag', 'unit_cost' => 1920, 'total' => 19200, 'is_selected' => false, 'sort_order' => 1],
                    ['supplier' => 'Batangas Feeds Center', 'description' => 'Hog Starter Feed', 'category' => 'feed', 'quantity' => 10, 'unit' => 'bag', 'unit_cost' => 1880, 'total' => 18800, 'is_selected' => true, 'sort_order' => 2],
                    ['supplier' => 'Lian Farmers Cooperative', 'description' => 'Hog Starter Feed', 'category' => 'feed', 'quantity' => 10, 'unit' => 'bag', 'unit_cost' => 1900, 'total' => 19000, 'is_selected' => false, 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Grower Feed Comparison - CYC-2026-008', 'canvass_date' => '2026-02-15',
                'notes' => 'Grower feed canvass for mid-cycle transition.', 'status' => 'awarded',
                'items' => [
                    ['supplier' => 'San Juan Feed Mill', 'description' => 'Hog Grower Feed', 'category' => 'feed', 'quantity' => 8, 'unit' => 'bag', 'unit_cost' => 1680, 'total' => 13440, 'is_selected' => true, 'sort_order' => 1],
                    ['supplier' => 'Lipa Agri Trading', 'description' => 'Hog Grower Feed', 'category' => 'feed', 'quantity' => 8, 'unit' => 'bag', 'unit_cost' => 1720, 'total' => 13760, 'is_selected' => false, 'sort_order' => 2],
                    ['supplier' => 'Batangas Feeds Center', 'description' => 'Hog Grower Feed', 'category' => 'feed', 'quantity' => 8, 'unit' => 'bag', 'unit_cost' => 1700, 'total' => 13600, 'is_selected' => false, 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Medicine Supply Canvass - Q2 2026', 'canvass_date' => '2026-04-01',
                'notes' => 'Bulk medicine purchase for all active cycles.', 'status' => 'awarded',
                'items' => [
                    ['supplier' => 'Humayingan Veterinary Supply', 'description' => 'Vetracin Spray (10 bottles)', 'category' => 'medicine', 'quantity' => 10, 'unit' => 'bottle', 'unit_cost' => 108, 'total' => 1080, 'is_selected' => true, 'sort_order' => 1],
                    ['supplier' => 'Batangas Veterinary Clinic', 'description' => 'Vetracin Spray (10 bottles)', 'category' => 'medicine', 'quantity' => 10, 'unit' => 'bottle', 'unit_cost' => 115, 'total' => 1150, 'is_selected' => false, 'sort_order' => 2],
                    ['supplier' => 'Humayingan Veterinary Supply', 'description' => 'Injectable Vitamins (5 doses)', 'category' => 'medicine', 'quantity' => 5, 'unit' => 'dose', 'unit_cost' => 245, 'total' => 1225, 'is_selected' => true, 'sort_order' => 3],
                    ['supplier' => 'Batangas Animal Health Center', 'description' => 'Injectable Vitamins (5 doses)', 'category' => 'medicine', 'quantity' => 5, 'unit' => 'dose', 'unit_cost' => 260, 'total' => 1300, 'is_selected' => false, 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Pen Construction Materials', 'canvass_date' => '2026-04-15',
                'notes' => 'Materials for pen expansion per RES-2026-013.', 'status' => 'awarded',
                'items' => [
                    ['supplier' => 'RJG General Merchandise', 'description' => 'Cement (10 bags)', 'category' => 'supplies', 'quantity' => 10, 'unit' => 'bag', 'unit_cost' => 260, 'total' => 2600, 'is_selected' => true, 'sort_order' => 1],
                    ['supplier' => 'Bauan Hardware & Supply', 'description' => 'Cement (10 bags)', 'category' => 'supplies', 'quantity' => 10, 'unit' => 'bag', 'unit_cost' => 270, 'total' => 2700, 'is_selected' => false, 'sort_order' => 2],
                    ['supplier' => 'RJG General Merchandise', 'description' => 'Hollow Blocks (100 pcs)', 'category' => 'supplies', 'quantity' => 100, 'unit' => 'pc', 'unit_cost' => 18, 'total' => 1800, 'is_selected' => true, 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Feeds Bulk Purchase - CYC-2026-012', 'canvass_date' => '2026-05-01',
                'notes' => 'Bulk feed order for finishing stage.', 'status' => 'in_progress',
                'items' => [
                    ['supplier' => 'Batangas Feeds Center', 'description' => 'Hog Finisher Feed', 'category' => 'feed', 'quantity' => 15, 'unit' => 'bag', 'unit_cost' => 1620, 'total' => 24300, 'is_selected' => false, 'sort_order' => 1],
                    ['supplier' => 'Lipa Agri Trading', 'description' => 'Hog Finisher Feed', 'category' => 'feed', 'quantity' => 15, 'unit' => 'bag', 'unit_cost' => 1600, 'total' => 24000, 'is_selected' => true, 'sort_order' => 2],
                    ['supplier' => 'San Juan Feed Mill', 'description' => 'Hog Finisher Feed', 'category' => 'feed', 'quantity' => 15, 'unit' => 'bag', 'unit_cost' => 1635, 'total' => 24525, 'is_selected' => false, 'sort_order' => 3],
                ],
            ],
        ];
    }

    private function associationExpenses(): array
    {
        return [
            ['item_name' => 'Monthly Utility Bill (Water & Electric)', 'category' => 'utilities', 'qty' => 1, 'unit' => 'month', 'unit_cost' => 1300, 'amount' => 1300, 'expense_date' => '2025-09-05', 'ref' => 'MERALCO-2025-09', 'fund_source' => 'association_fund', 'notes' => 'Electricity and water for pig pens.'],
            ['item_name' => 'Monthly Utility Bill (Water & Electric)', 'category' => 'utilities', 'qty' => 1, 'unit' => 'month', 'unit_cost' => 1350, 'amount' => 1350, 'expense_date' => '2025-10-05', 'ref' => 'MERALCO-2025-10', 'fund_source' => 'association_fund'],
            ['item_name' => 'Monthly Utility Bill (Water & Electric)', 'category' => 'utilities', 'qty' => 1, 'unit' => 'month', 'unit_cost' => 1280, 'amount' => 1280, 'expense_date' => '2026-01-05', 'ref' => 'MERALCO-2026-01', 'fund_source' => 'association_fund'],
            ['item_name' => 'Monthly Utility Bill (Water & Electric)', 'category' => 'utilities', 'qty' => 1, 'unit' => 'month', 'unit_cost' => 1400, 'amount' => 1400, 'expense_date' => '2026-04-05', 'ref' => 'MERALCO-2026-04', 'fund_source' => 'association_fund'],
            ['item_name' => 'Pen Cleaning Supplies (Disinfectant)', 'category' => 'supplies', 'qty' => 5, 'unit' => 'liter', 'unit_cost' => 180, 'amount' => 900, 'expense_date' => '2025-10-15', 'ref' => 'INV-DS-B01', 'fund_source' => 'association_fund', 'notes' => 'Monthly pen disinfection.'],
            ['item_name' => 'Pen Cleaning Supplies (Disinfectant)', 'category' => 'supplies', 'qty' => 5, 'unit' => 'liter', 'unit_cost' => 180, 'amount' => 900, 'expense_date' => '2026-02-15', 'ref' => 'INV-DS-B02', 'fund_source' => 'association_fund'],
            ['item_name' => 'Rice Bran (Darak) Bulk Purchase', 'category' => 'feed', 'qty' => 3, 'unit' => 'sack', 'unit_cost' => 850, 'amount' => 2550, 'expense_date' => '2025-11-25', 'ref' => 'INV-RB-B01', 'fund_source' => 'association_fund', 'notes' => 'Supplemental feed for all active cycles.'],
            ['item_name' => 'Rice Bran (Darak) Bulk Purchase', 'category' => 'feed', 'qty' => 3, 'unit' => 'sack', 'unit_cost' => 860, 'amount' => 2580, 'expense_date' => '2026-02-10', 'ref' => 'INV-RB-B02', 'fund_source' => 'association_fund'],
            ['item_name' => 'Rice Bran (Darak) Bulk Purchase', 'category' => 'feed', 'qty' => 3, 'unit' => 'sack', 'unit_cost' => 870, 'amount' => 2610, 'expense_date' => '2026-04-15', 'ref' => 'INV-RB-B03', 'fund_source' => 'association_fund'],
            ['item_name' => 'Emergency Fund - Pen Repair', 'category' => 'emergency', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 2500, 'amount' => 2500, 'expense_date' => '2025-12-20', 'ref' => null, 'fund_source' => 'emergency_fund', 'notes' => 'Minor pen repairs after typhoon.'],
            ['item_name' => 'Emergency Fund - Medication Stock', 'category' => 'emergency', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 2000, 'amount' => 2000, 'expense_date' => '2026-03-10', 'ref' => null, 'fund_source' => 'emergency_fund', 'notes' => 'Emergency medicine stock for disease outbreak prevention.'],
            ['item_name' => 'Association Meeting Snacks', 'category' => 'other', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 500, 'amount' => 500, 'expense_date' => '2025-11-03', 'ref' => null, 'fund_source' => 'association_fund'],
            ['item_name' => 'Association Meeting Snacks', 'category' => 'other', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 500, 'amount' => 500, 'expense_date' => '2026-01-05', 'ref' => null, 'fund_source' => 'association_fund'],
            ['item_name' => 'Association Meeting Snacks', 'category' => 'other', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 500, 'amount' => 500, 'expense_date' => '2026-03-01', 'ref' => null, 'fund_source' => 'association_fund'],
            ['item_name' => 'Transport - DSWD Document Submission', 'category' => 'transport', 'qty' => 1, 'unit' => 'trip', 'unit_cost' => 800, 'amount' => 800, 'expense_date' => '2026-02-25', 'ref' => 'OR-B-8001', 'fund_source' => 'association_fund', 'notes' => 'DSWD Batangas document submission trip.'],
            ['item_name' => 'Transport - DSWD Document Submission', 'category' => 'transport', 'qty' => 1, 'unit' => 'trip', 'unit_cost' => 800, 'amount' => 800, 'expense_date' => '2026-04-05', 'ref' => 'OR-B-8002', 'fund_source' => 'association_fund'],
            ['item_name' => 'Office Supplies (Ledger, Pens, Receipt Book)', 'category' => 'supplies', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 750, 'amount' => 750, 'expense_date' => '2026-01-15', 'ref' => 'INV-B-OS01', 'fund_source' => 'association_fund', 'notes' => 'Record-keeping supplies.'],
            ['item_name' => 'Pen Lighting Fixture Repair', 'category' => 'supplies', 'qty' => 1, 'unit' => 'lot', 'unit_cost' => 1200, 'amount' => 1200, 'expense_date' => '2026-03-20', 'ref' => 'INV-B-E01', 'fund_source' => 'association_fund', 'notes' => 'Electrical repair for pen lighting.'],
            ['item_name' => 'Membership ID Printing', 'category' => 'other', 'qty' => 20, 'unit' => 'pc', 'unit_cost' => 25, 'amount' => 500, 'expense_date' => '2026-02-01', 'ref' => null, 'fund_source' => 'association_fund', 'notes' => 'New member ID cards.'],
            ['item_name' => 'Veterinary Consultation Fee', 'category' => 'other', 'qty' => 1, 'unit' => 'visit', 'unit_cost' => 1500, 'amount' => 1500, 'expense_date' => '2026-03-15', 'ref' => 'INV-VET-001', 'fund_source' => 'association_fund', 'notes' => 'Quarterly vet check-up for all cycles.'],
        ];
    }

    private function auditTrails(): array
    {
        return [
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-006 with 10 pigs.', 'created_at' => '2025-09-05 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-007 with 8 pigs.', 'created_at' => '2025-09-20 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2025-008 with 12 pigs.', 'created_at' => '2025-10-15 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-006 with 6 pigs.', 'created_at' => '2025-11-01 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-007 with 9 pigs.', 'created_at' => '2025-11-20 08:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.create', 'module' => 'PigCycle', 'description' => 'Created pig cycle CYC-2026-008 with 11 pigs.', 'created_at' => '2025-12-10 08:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded acquisition for CYC-2025-006: 10 piglets.', 'created_at' => '2025-09-05 09:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'PigCycleExpense', 'description' => 'Recorded feed expense for CYC-2025-006: Hog Starter.', 'created_at' => '2025-09-26 10:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'sale.create', 'module' => 'PigCycleSale', 'description' => 'Recorded sale for CYC-2025-006: live-weight sale.', 'created_at' => '2026-01-15 11:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'sale.create', 'module' => 'PigCycleSale', 'description' => 'Recorded sale for CYC-2025-008: live-weight sale.', 'created_at' => '2026-02-20 11:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.create', 'module' => 'Meeting', 'description' => 'Created Monthly Association Meeting - October 2025.', 'created_at' => '2025-10-05 10:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.confirm', 'module' => 'Meeting', 'description' => 'Confirmed Monthly Association Meeting - October 2025.', 'created_at' => '2025-10-07 12:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.create', 'module' => 'Meeting', 'description' => 'Created General Assembly - Mid-Year Review 2026.', 'created_at' => '2026-04-29 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.create', 'module' => 'Resolution', 'description' => 'Created RES-2026-008: CYC-2025-006 Profit Sharing.', 'created_at' => '2026-02-10 14:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.approve', 'module' => 'Resolution', 'description' => 'RES-2026-008 approved by members.', 'created_at' => '2026-02-20 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.create', 'module' => 'Resolution', 'description' => 'Created RES-2026-012: Q2 Cycle Expansion.', 'created_at' => '2026-03-01 15:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'withdrawal.create', 'module' => 'Withdrawal', 'description' => 'Created withdrawal for RES-2026-008: P68,000 profit sharing.', 'created_at' => '2026-02-15 11:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'withdrawal.complete', 'module' => 'Withdrawal', 'description' => 'Completed withdrawal for RES-2026-008.', 'created_at' => '2026-02-22 14:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'withdrawal.create', 'module' => 'Withdrawal', 'description' => 'Created withdrawal for RES-2026-012: P343,000 expansion fund.', 'created_at' => '2026-03-05 10:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'canvass.create', 'module' => 'Canvass', 'description' => 'Created feeds price comparison for Q1 2026.', 'created_at' => '2026-01-10 08:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'canvass.award', 'module' => 'Canvass', 'description' => 'Awarded Q1 2026 feeds to Batangas Feeds Center.', 'created_at' => '2026-01-12 11:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'supplier.create', 'module' => 'Supplier', 'description' => 'Added supplier: San Juan Feed Mill.', 'created_at' => '2025-11-01 09:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'supplier.create', 'module' => 'Supplier', 'description' => 'Added supplier: Lian Farmers Cooperative.', 'created_at' => '2025-12-15 09:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'dswd.submit', 'module' => 'DswdSubmission', 'description' => 'Submitted RES-2026-008 to DSWD.', 'created_at' => '2026-02-12 13:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'dswd.approve', 'module' => 'DswdSubmission', 'description' => 'DSWD approved RES-2026-008.', 'created_at' => '2026-02-25 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated CYC-2025-006 profitability report (PDF).', 'created_at' => '2026-02-12 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated Q1 2026 expense breakdown (CSV).', 'created_at' => '2026-04-05 15:00:00'],
            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'health.incident.create', 'module' => 'CycleHealthIncident', 'description' => 'Reported deceased pig in CYC-2025-007: Pig #3.', 'created_at' => '2025-10-05 08:00:00'],
            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'health.task.complete', 'module' => 'CycleHealthTask', 'description' => 'Completed deworming for CYC-2025-006.', 'created_at' => '2025-10-20 10:00:00'],
            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'health.task.complete', 'module' => 'CycleHealthTask', 'description' => 'Completed oral medication for CYC-2026-009.', 'created_at' => '2026-03-08 10:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'expense.create', 'module' => 'AssociationExpense', 'description' => 'Recorded monthly utilities for October 2025.', 'created_at' => '2025-10-05 09:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2025-006 (Completed/Closed).', 'created_at' => '2026-02-12 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2025-007 (Completed/Closed).', 'created_at' => '2026-02-22 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2025-008 (Completed/Closed).', 'created_at' => '2026-02-22 16:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2026-006 (Completed/Closed).', 'created_at' => '2026-04-12 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2026-007 (Completed/Closed).', 'created_at' => '2026-04-22 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2026-008 (Completed/Closed).', 'created_at' => '2026-04-22 16:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'cycle.archive', 'module' => 'PigCycle', 'description' => 'Archived CYC-2026-010 (Completed/Closed).', 'created_at' => '2026-04-08 16:30:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated quarterly sales summary for Q1 2026.', 'created_at' => '2026-04-01 09:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated association-wide health summary.', 'created_at' => '2026-04-15 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'report.generate', 'module' => 'GeneratedReport', 'description' => 'Generated mid-year profitability review.', 'created_at' => '2026-04-30 14:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'meeting.create', 'module' => 'Meeting', 'description' => 'Created Pig Production Review - CYC-2025-006 Startup.', 'created_at' => '2025-09-01 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'penalty.create', 'module' => 'AttendancePenalty', 'description' => 'Applied penalty for unexcused absence at production review.', 'created_at' => '2026-02-25 10:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'penalty.waive', 'module' => 'AttendancePenalty', 'description' => 'Waived penalty for member with medical excuse.', 'created_at' => '2026-04-10 11:00:00'],
            ['user_email' => 'treasurer.anaceta@pigsikap.local', 'action' => 'login', 'module' => 'User', 'description' => 'Treasurer logged in for monthly report.', 'created_at' => '2026-05-01 08:00:00'],
            ['user_email' => 'secretary.ronalyn@pigsikap.local', 'action' => 'login', 'module' => 'User', 'description' => 'Secretary logged in to prepare meeting minutes.', 'created_at' => '2026-04-29 08:00:00'],
            ['user_email' => 'canvasser.pedro@pigsikap.local', 'action' => 'login', 'module' => 'User', 'description' => 'Canvasser logged in to update supplier list.', 'created_at' => '2026-04-15 09:00:00'],
            ['user_email' => 'officer.maricon@pigsikap.local', 'action' => 'login', 'module' => 'User', 'description' => 'Caretaker logged in for daily health check.', 'created_at' => '2026-05-02 07:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'resolution.approve', 'module' => 'Resolution', 'description' => 'RES-2026-014 approved by members.', 'created_at' => '2026-04-08 16:00:00'],
            ['user_email' => 'president.eva@pigsikap.local', 'action' => 'dswd.submit', 'module' => 'DswdSubmission', 'description' => 'Submitted RES-2026-012 to DSWD.', 'created_at' => '2026-03-07 13:00:00'],
        ];
    }

    private function generatedReports(): array
    {
        return [
            ['report_type' => 'cycle_profitability', 'format' => 'pdf', 'generated_at' => '2026-02-12 10:00:00', 'notes' => 'CYC-2025-006 profitability report.'],
            ['report_type' => 'cycle_profitability', 'format' => 'pdf', 'generated_at' => '2026-02-22 10:00:00', 'notes' => 'CYC-2025-007 and CYC-2025-008 combined profitability.'],
            ['report_type' => 'expense_breakdown', 'format' => 'csv', 'generated_at' => '2026-04-05 15:00:00', 'notes' => 'Q1 2026 expense breakdown.'],
            ['report_type' => 'expense_breakdown', 'format' => 'pdf', 'generated_at' => '2026-04-05 16:00:00', 'notes' => 'Association-wide expense summary Q1 2026.'],
            ['report_type' => 'sales_summary', 'format' => 'pdf', 'generated_at' => '2026-04-01 09:00:00', 'notes' => 'Q1 2026 sales summary.'],
            ['report_type' => 'sales_summary', 'format' => 'csv', 'generated_at' => '2026-04-30 09:00:00', 'notes' => 'April 2026 sales report.'],
            ['report_type' => 'health_summary', 'format' => 'pdf', 'generated_at' => '2026-04-15 10:00:00', 'notes' => 'Health status across all active cycles.'],
            ['report_type' => 'cycle_profitability', 'format' => 'pdf', 'generated_at' => '2026-04-30 14:00:00', 'notes' => 'Mid-year profitability review (all completed cycles).'],
        ];
    }

    private function reportSchedules(): array
    {
        return [
            ['report_type' => 'cycle_profitability', 'format' => 'pdf', 'frequency' => 'monthly', 'day_of_month' => 7, 'run_at' => '09:00:00', 'last_run_at' => '2026-04-07 09:00:00', 'next_run_at' => '2026-05-07 09:00:00'],
            ['report_type' => 'health_summary', 'format' => 'pdf', 'frequency' => 'monthly', 'day_of_month' => 15, 'run_at' => '10:00:00', 'last_run_at' => '2026-04-15 10:00:00', 'next_run_at' => '2026-05-15 10:00:00'],
            ['report_type' => 'expense_breakdown', 'format' => 'csv', 'frequency' => 'monthly', 'day_of_month' => 3, 'run_at' => '08:00:00', 'last_run_at' => '2026-04-03 08:00:00', 'next_run_at' => '2026-05-03 08:00:00'],
        ];
    }
}
