<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CanvassItem model – a line item within a canvass record,
 * storing per-supplier pricing information.
 */
class CanvassItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'canvass_id',
        'supplier_id',
        'description',
        'specifications',
        'category',
        'quantity',
        'unit',
        'unit_cost',
        'total',
        'is_selected',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'is_selected' => 'boolean',
        ];
    }

    public function canvass(): BelongsTo
    {
        return $this->belongsTo(Canvass::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}