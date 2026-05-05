<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

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

    /** Workflow statuses for the document lifecycle. */
    public const WORKFLOW_STATUSES = [
        'draft',
        'generated',
        'printed',
        'signature_sheet_uploaded',
        'pending_member_approval',
        'member_approved',
        'dswd_pending',
        'dswd_approved',
        'withdrawal_ready',
        'withdrawn',
        'archived',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'resolution_file_path',
        'resolution_number',
        'generated_pdf_path',
        'generated_docx_path',
        'signed_file_path',
        'physical_signatures_pdf_path',
        'dswd_approval_file_path',
        'version',
        'signature_verified_at',
        'workflow_status',
        'resolution_number_assigned_at',
        'status',
        'approval_deadline',
        'is_approval_locked',
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
            'signature_verified_at' => 'datetime',
            'resolution_number_assigned_at' => 'datetime',
            'is_approval_locked' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Immutable member snapshot at approval time.
     */
    public function memberSnapshot(): HasOne
    {
        return $this->hasOne(ResolutionMemberSnapshot::class);
    }

    /**
     * Authorized withdrawers designated by the president.
     */
    public function withdrawalAuthorizations(): HasMany
    {
        return $this->hasMany(ResolutionWithdrawalAuthorization::class);
    }

    /**
     * Active (non-revoked) authorized withdrawers.
     */
    public function activeWithdrawalAuthorizations(): HasMany
    {
        return $this->hasMany(ResolutionWithdrawalAuthorization::class)
            ->whereNull('revoked_at');
    }

    /**
     * Canvass records linked to this resolution.
     */
    public function canvasses(): HasMany
    {
        return $this->hasMany(Canvass::class);
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

    /**
     * All document versions for this resolution.
     */
    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /**
     * Signed document versions only.
     */
    public function signedDocumentVersions(): HasMany
    {
        return $this->documentVersions()->where('document_type', 'signed_resolution');
    }

    /**
     * DSWD approval document versions only.
     */
    public function dswdApprovalVersions(): HasMany
    {
        return $this->documentVersions()->where('document_type', 'dswd_approval');
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
     * Compute approval percentage (approved / eligible members × 100).
     * Uses the member snapshot if available, otherwise falls back
     * to live User::count() for backward compatibility.
     */
    public function getApprovalPercentageAttribute(): float
    {
        $snapshot = $this->relationLoaded('memberSnapshot')
            ? $this->memberSnapshot
            : $this->memberSnapshot()->first();

        $totalMembers = $snapshot ? $snapshot->eligible_count : User::where('is_active', true)->count();

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
     * Checks from snapshot if available, otherwise uses live threshold.
     */
    public function hasMetApprovalThreshold(): bool
    {
        $snapshot = $this->relationLoaded('memberSnapshot')
            ? $this->memberSnapshot
            : $this->memberSnapshot()->first();

        if ($snapshot) {
            return $this->approved_count >= $snapshot->required_approvals;
        }

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

    /**
     * Generate the next resolution number in sequence.
     */
    public static function generateResolutionNumber(): string
    {
        $year = date('Y');
        $lastNumber = static::where('resolution_number', 'like', "RES-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(resolution_number, "-", -1) AS UNSIGNED) DESC')
            ->value('resolution_number');

        $nextSequence = 1;

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $nextSequence = ((int) end($parts)) + 1;
        }

        return sprintf('RES-%s-%03d', $year, $nextSequence);
    }

    /**
     * Human-readable workflow status label.
     */
    public function getWorkflowStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Draft',
            'generated' => 'Document Generated',
            'printed' => 'Printed',
            'signature_sheet_uploaded' => 'Signatures Uploaded',
            'pending_member_approval' => 'Pending Member Approval',
            'member_approved' => 'Member Approved',
            'dswd_pending' => 'DSWD Pending',
            'dswd_approved' => 'DSWD Approved',
            'withdrawal_ready' => 'Ready for Withdrawal',
            'withdrawn' => 'Withdrawn',
            'archived' => 'Archived',
        ];

        return $labels[$this->workflow_status] ?? ucfirst(str_replace('_', ' ', $this->workflow_status));
    }

    /**
     * Color key for workflow status badge.
     */
    public function getWorkflowStatusColorAttribute(): string
    {
        $colors = [
            'draft' => 'gray',
            'generated' => 'blue',
            'printed' => 'blue',
            'signature_sheet_uploaded' => 'amber',
            'pending_member_approval' => 'amber',
            'member_approved' => 'emerald',
            'dswd_pending' => 'indigo',
            'dswd_approved' => 'emerald',
            'withdrawal_ready' => 'emerald',
            'withdrawn' => 'emerald',
            'archived' => 'gray',
        ];

        return $colors[$this->workflow_status] ?? 'gray';
    }
}
