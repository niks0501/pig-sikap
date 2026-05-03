<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Resolution model – represents a formal resolution
 * created from a meeting with budget line-items and approval tracking.
 */
class Resolution extends Model
{
    use HasFactory;

    /** Approval threshold percentage required before proceeding. */
    public const APPROVAL_THRESHOLD = 75;

    public const STATUSES = [
        'draft',
        'pending_approval',
        'approved',
        'dswd_submitted',
        'withdrawn',
        'finalized',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'resolution_file_path',
        'status',
        'approval_deadline',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approval_deadline' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Budget line-items for this resolution.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(ResolutionLineItem::class)->orderBy('sort_order');
    }

    /**
     * Member approval records.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ResolutionApproval::class);
    }

    /**
     * DSWD submission record.
     */
    public function dswdSubmission(): HasOne
    {
        return $this->hasOne(DswdSubmission::class)->latestOfMany();
    }

    /**
     * All DSWD submissions (history).
     */
    public function dswdSubmissions(): HasMany
    {
        return $this->hasMany(DswdSubmission::class);
    }

    /**
     * Withdrawal requests for this resolution.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    // ─── Accessors ────────────────────────────────────────────

    /**
     * Grand total of all budget line-items.
     */
    public function getGrandTotalAttribute(): float
    {
        return (float) $this->lineItems->sum('total');
    }

    /**
     * Compute approval percentage (signed / total active members × 100).
     */
    public function getApprovalPercentageAttribute(): float
    {
        $totalMembers = User::where('is_active', true)->count();

        if ($totalMembers === 0) {
            return 0;
        }

        $approvedCount = $this->approvals()->where('is_approved', true)->count();

        return round(($approvedCount / $totalMembers) * 100, 1);
    }

    /**
     * Number of approved members.
     */
    public function getApprovedCountAttribute(): int
    {
        return $this->approvals()->where('is_approved', true)->count();
    }

    /**
     * Whether approval threshold has been met.
     */
    public function hasMetApprovalThreshold(): bool
    {
        return $this->approval_percentage >= self::APPROVAL_THRESHOLD;
    }

    /**
     * Total amount already withdrawn against this resolution.
     */
    public function getTotalWithdrawnAttribute(): float
    {
        return (float) $this->withdrawals()
            ->whereIn('status', ['pending', 'completed'])
            ->sum('amount');
    }

    /**
     * Remaining balance available for withdrawal.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->grand_total - $this->total_withdrawn);
    }

    /**
     * Public URL for uploaded resolution file.
     */
    public function resolutionFileUrl(): ?string
    {
        if (! $this->resolution_file_path) {
            return null;
        }

        return asset('storage/' . $this->resolution_file_path);
    }
}
