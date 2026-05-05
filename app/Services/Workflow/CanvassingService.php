<?php

namespace App\Services\Workflow;

use App\Models\Canvass;
use App\Models\CanvassItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CanvassingService – handles creation and management of canvass
 * records with supplier price comparison and winner selection.
 */
class CanvassingService
{
    /**
     * Create a canvass with items.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $data, array $items, User $user): Canvass
    {
        return DB::transaction(function () use ($data, $items, $user) {
            $canvass = Canvass::create([
                'resolution_id' => $data['resolution_id'] ?? null,
                'meeting_id' => $data['meeting_id'] ?? null,
                'title' => $data['title'],
                'canvass_date' => $data['canvass_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            foreach ($items as $index => $item) {
                $total = ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_cost'] ?? 0));

                CanvassItem::create([
                    'canvass_id' => $canvass->id,
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'description' => $item['description'],
                    'specifications' => $item['specifications'] ?? null,
                    'category' => $item['category'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => $item['unit'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'total' => $total,
                    'is_selected' => false,
                    'sort_order' => $index,
                ]);
            }

            return $canvass->load('items.supplier');
        });
    }

    /**
     * Update a canvass with new items (replaces existing items).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function update(Canvass $canvass, array $data, array $items, User $user): Canvass
    {
        return DB::transaction(function () use ($canvass, $data, $items, $user) {
            $canvass->update([
                'resolution_id' => $data['resolution_id'] ?? $canvass->resolution_id,
                'meeting_id' => $data['meeting_id'] ?? $canvass->meeting_id,
                'title' => $data['title'] ?? $canvass->title,
                'canvass_date' => $data['canvass_date'] ?? $canvass->canvass_date,
                'notes' => $data['notes'] ?? $canvass->notes,
                'updated_by' => $user->id,
            ]);

            // Replace all items
            $canvass->items()->delete();

            foreach ($items as $index => $item) {
                $total = ((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_cost'] ?? 0));

                CanvassItem::create([
                    'canvass_id' => $canvass->id,
                    'supplier_id' => $item['supplier_id'] ?? null,
                    'description' => $item['description'],
                    'specifications' => $item['specifications'] ?? null,
                    'category' => $item['category'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => $item['unit'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'total' => $total,
                    'is_selected' => false,
                    'sort_order' => $index,
                ]);
            }

            return $canvass->load('items.supplier');
        });
    }

    /**
     * Select (mark as winner) a specific item. Unselects other items
     * for the same description/supplier combination.
     */
    public function selectItem(Canvass $canvass, CanvassItem $item): Canvass
    {
        return DB::transaction(function () use ($canvass, $item) {
            // Ensure the item belongs to this canvass
            if ($item->canvass_id !== $canvass->id) {
                throw new \InvalidArgumentException('Item does not belong to this canvass.');
            }

            // Unselect all items for this canvass
            $canvass->items()->update(['is_selected' => false]);

            // Select the chosen item
            $item->update(['is_selected' => true]);

            return $canvass->fresh(['items.supplier']);
        });
    }

    /**
     * Mark a canvass as awarded.
     */
    public function markAwarded(Canvass $canvass, User $user): Canvass
    {
        $canvass->update([
            'status' => 'awarded',
            'updated_by' => $user->id,
        ]);

        return $canvass->fresh(['items.supplier']);
    }
}