<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PigCycleAdjustment extends Model
{
    use HasFactory;

    public const ADJUSTMENT_TYPES = [
        'increase',
        'decrease',
        'correction',
    ];

    public const REASONS = [
        'mortality',
        'sale deduction',
        'recount',
        'isolated pig',
        'transfer',
        'data correction',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'adjustment_type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reason',
        'remarks',
        'source_module',
        'source_type',
        'source_id',
        'source_event_key',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'pig_cycle_adjustments';
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'batch_id');
    }

    public function batch(): BelongsTo
    {
        return $this->cycle();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
