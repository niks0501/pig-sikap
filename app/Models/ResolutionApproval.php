<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resolution approval model – tracks each member's
 * approval/rejection of a resolution.
 */
class ResolutionApproval extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resolution_id',
        'user_id',
        'is_approved',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
