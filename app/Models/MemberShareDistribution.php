<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberShareDistribution extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'profitability_snapshot_id',
        'user_id',
        'allocated_amount',
        'allocation_percentage',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'allocation_percentage' => 'decimal:2',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ProfitabilitySnapshot::class, 'profitability_snapshot_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
