<?php

namespace Database\Seeders;

use App\Models\Canvass;
use App\Models\CanvassItem;
use App\Models\Resolution;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoCanvassingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSuppliers();
            $this->seedCanvasses();
        });
    }

    private function seedSuppliers(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->suppliers() as $row) {
            Supplier::updateOrCreate(
                ['name' => $row['name']],
                $this->onlyExistingColumns('suppliers', [
                    'name' => $row['name'],
                    'contact_person' => $row['contact_person'],
                    'contact_number' => $row['contact_number'],
                    'address' => $row['address'],
                    'notes' => $row['notes'],
                    'created_by' => $presidentId,
                ])
            );
        }
    }

    private function seedCanvasses(): void
    {
        $presidentId = User::where('email', 'president.eva@pigsikap.local')->value('id');

        foreach ($this->canvasses() as $row) {
            $resolution = Resolution::where('resolution_number', $row['resolution_number'] ?? null)->first();

            $canvass = Canvass::updateOrCreate(
                [
                    'title' => $row['title'],
                    'canvass_date' => $row['canvass_date'],
                ],
                $this->onlyExistingColumns('canvasses', [
                    'title' => $row['title'],
                    'canvass_date' => $row['canvass_date'],
                    'resolution_id' => $resolution ? $resolution->id : null,
                    'notes' => $row['notes'],
                    'status' => $row['status'],
                    'created_by' => $presidentId,
                    'updated_by' => $presidentId,
                ])
            );

            foreach ($row['items'] as $item) {
                $supplier = Supplier::where('name', $item['supplier'])->first();

                CanvassItem::updateOrCreate(
                    [
                        'canvass_id' => $canvass->id,
                        'description' => $item['description'],
                        'supplier_id' => $supplier ? $supplier->id : null,
                    ],
                    $this->onlyExistingColumns('canvass_items', [
                        'canvass_id' => $canvass->id,
                        'supplier_id' => $supplier ? $supplier->id : null,
                        'description' => $item['description'],
                        'specifications' => $item['specifications'] ?? null,
                        'category' => $item['category'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_cost' => $item['unit_cost'],
                        'total' => $item['total'],
                        'is_selected' => $item['is_selected'] ?? false,
                        'sort_order' => $item['sort_order'],
                    ])
                );
            }
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
    private function suppliers(): array
    {
        return [
            [
                'name' => 'Lian Agri-Supply',
                'contact_person' => 'Aling Nena',
                'contact_number' => '0917-555-8001',
                'address' => 'Lian Public Market, Batangas',
                'notes' => 'Primary supplier for commercial hog feeds and veterinary supplies.',
            ],
            [
                'name' => 'Batangas Feeds Center',
                'contact_person' => 'Mang Tony',
                'contact_number' => '0920-555-8002',
                'address' => 'Nasugbu Highway, Batangas',
                'notes' => 'Wholesale feeds distributor. Competitive pricing on bulk orders.',
            ],
            [
                'name' => 'Humayingan Veterinary Supply',
                'contact_person' => 'Dra. Santos',
                'contact_number' => '0908-555-8003',
                'address' => 'Brgy. Humayingan, Lian, Batangas',
                'notes' => 'Local vet supply. Stocks medicines, vitamins, and injectables.',
            ],
            [
                'name' => 'RJG General Merchandise',
                'contact_person' => 'Rey G.',
                'contact_number' => '0935-555-8005',
                'address' => 'Lian Town Proper, Batangas',
                'notes' => 'Hardware and general supplies. PVC pipes, cement, tools.',
            ],
            [
                'name' => 'Mang Pedro\'s Feeds Supply',
                'contact_person' => 'Pedro H.',
                'contact_number' => '0919-555-8006',
                'address' => 'Brgy. Bunga, Lian, Batangas',
                'notes' => 'Alternative feeds supplier. Rice bran (darak) and organic feed mixes.',
            ],
            [
                'name' => 'Batangas Animal Health Center',
                'contact_person' => 'Dr. Reyes',
                'contact_number' => '0918-555-8007',
                'address' => 'Batangas City',
                'notes' => 'Full-service animal health. Injectables, dewormers, and consultation.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function canvasses(): array
    {
        return [
            [
                'title' => 'Feeds Price Comparison - CYC-2026-003',
                'canvass_date' => '2026-01-05',
                'resolution_number' => 'RES-2026-005',
                'notes' => 'Canvass for pre-starter and starter feeds for CYC-2026-003.',
                'status' => 'awarded',
                'items' => [
                    ['supplier' => 'Lian Agri-Supply', 'description' => 'Pre-Starter Feed', 'category' => 'feed', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1350, 'total' => 4050, 'is_selected' => false, 'sort_order' => 1],
                    ['supplier' => 'Batangas Feeds Center', 'description' => 'Pre-Starter Feed', 'category' => 'feed', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1280, 'total' => 3840, 'is_selected' => true, 'sort_order' => 2],
                    ['supplier' => 'Mang Pedro\'s Feeds Supply', 'description' => 'Pre-Starter Feed', 'category' => 'feed', 'quantity' => 3, 'unit' => 'bag', 'unit_cost' => 1320, 'total' => 3960, 'is_selected' => false, 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Veterinary Supplies - CYC-2026-004',
                'canvass_date' => '2026-02-05',
                'resolution_number' => 'RES-2026-006',
                'notes' => 'Canvass for Vetracin and hog nipple drinkers.',
                'status' => 'awarded',
                'items' => [
                    ['supplier' => 'Humayingan Veterinary Supply', 'description' => 'Vetracin Spray (per bottle)', 'category' => 'medicine', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 110, 'total' => 330, 'is_selected' => true, 'sort_order' => 1],
                    ['supplier' => 'Batangas Animal Health Center', 'description' => 'Vetracin Spray (per bottle)', 'category' => 'medicine', 'quantity' => 3, 'unit' => 'bottle', 'unit_cost' => 120, 'total' => 360, 'is_selected' => false, 'sort_order' => 2],
                    ['supplier' => 'Lian Agri-Supply', 'description' => 'Hog Nipple Drinker', 'category' => 'supplies', 'quantity' => 2, 'unit' => 'pc', 'unit_cost' => 185, 'total' => 370, 'is_selected' => false, 'sort_order' => 3],
                    ['supplier' => 'RJG General Merchandise', 'description' => 'Hog Nipple Drinker', 'category' => 'supplies', 'quantity' => 2, 'unit' => 'pc', 'unit_cost' => 175, 'total' => 350, 'is_selected' => true, 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Pen Repair Materials Canvass',
                'canvass_date' => '2025-11-10',
                'resolution_number' => 'RES-2026-004',
                'notes' => 'Canvass for cement, sand, and roofing materials for pen repairs.',
                'status' => 'awarded',
                'items' => [
                    ['supplier' => 'RJG General Merchandise', 'description' => 'Cement (1 bag) + Sand', 'category' => 'supplies', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 2500, 'total' => 2500, 'is_selected' => true, 'sort_order' => 1],
                    ['supplier' => 'RJG General Merchandise', 'description' => 'Roof Patch Materials', 'category' => 'supplies', 'quantity' => 1, 'unit' => 'lot', 'unit_cost' => 3500, 'total' => 3500, 'is_selected' => true, 'sort_order' => 2],
                ],
            ],
            [
                'title' => 'Feed Supply for CYC-2026-005',
                'canvass_date' => '2026-03-28',
                'resolution_number' => 'RES-2026-007',
                'notes' => 'Pre-startup canvass for feeds and medicines for CYC-2026-005.',
                'status' => 'awarded',
                'items' => [
                    ['supplier' => 'Batangas Feeds Center', 'description' => 'Pre-Starter Feed', 'category' => 'feed', 'quantity' => 2, 'unit' => 'bag', 'unit_cost' => 1300, 'total' => 2600, 'is_selected' => true, 'sort_order' => 1],
                    ['supplier' => 'Lian Agri-Supply', 'description' => 'Pre-Starter Feed', 'category' => 'feed', 'quantity' => 2, 'unit' => 'bag', 'unit_cost' => 1350, 'total' => 2700, 'is_selected' => false, 'sort_order' => 2],
                    ['supplier' => 'Humayingan Veterinary Supply', 'description' => 'Vetracin Spray', 'category' => 'medicine', 'quantity' => 2, 'unit' => 'bottle', 'unit_cost' => 110, 'total' => 220, 'is_selected' => true, 'sort_order' => 3],
                ],
            ],
        ];
    }
}
