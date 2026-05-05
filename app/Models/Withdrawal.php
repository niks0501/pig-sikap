<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Withdrawal model – represents a fund withdrawal request
 * linked to an approved resolution.
 */
class Withdrawal extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'pending', 'completed', 'cancelled'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resolution_id',
        'requested_by',
        'authorized_withdrawer_id',
        'amount',
        'currency',
        'bank_account',
        'proof_file_path',
        'status',
        'requested_at',
        'completed_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * The authorized withdrawer who executed this withdrawal (if any).
     */
    public function authorizedWithdrawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_withdrawer_id');
    }

    /**
     * Liquidation report generated for this withdrawal.
     */
    public function liquidationReport(): HasOne
    {
        return $this->hasOne(LiquidationReport::class);
    }

    /**
     * Actual expenses linked to this withdrawal (for budget-vs-actual report).
     */
    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PigCycleExpense::class);
    }

    /**
     * Public URL for uploaded proof file.
     */
    public function proofFileUrl(): ?string
    {
        if (! $this->proof_file_path) {
            return null;
        }

        return asset('storage/' . $this->proof_file_path);
    }
}
