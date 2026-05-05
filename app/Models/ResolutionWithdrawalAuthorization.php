<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ResolutionWithdrawalAuthorization model – tracks which members
 * are authorized by the president to execute withdrawals for a
 * resolution. Supports soft revocation.
 */
class ResolutionWithdrawalAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'resolution_id',
        'user_id',
        'designated_by',
        'designated_at',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'designated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function designatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designated_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Whether this authorization has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}