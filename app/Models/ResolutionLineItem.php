<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resolution line-item model – represents a single budget entry
 * in a resolution (category, description, qty, unit, unit-cost, total).
 */
class ResolutionLineItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resolution_id',
        'category',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'total',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }
}
