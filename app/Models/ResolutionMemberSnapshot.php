<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ResolutionMemberSnapshot model – immutable snapshot of active
 * members at the time a resolution enters pending_member_approval.
 * Used to compute the 75% approval threshold.
 */
class ResolutionMemberSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'resolution_id',
        'snapshot_data',
        'eligible_count',
        'required_approvals',
        'snapshot_taken_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'snapshot_taken_at' => 'datetime',
        ];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }
}