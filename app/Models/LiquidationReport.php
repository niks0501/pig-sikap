<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Liquidation report model – the final report generated
 * after a withdrawal, reconciling budget vs. actual expenses.
 */
class LiquidationReport extends Model
{
    use HasFactory;

    protected $table = 'liquidation_reports';

    public const STATUSES = ['draft', 'submitted', 'reviewed', 'approved', 'returned'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'withdrawal_id',
        'generated_by',
        'report_file_path',
        'summary',
        'liquidation_status',
        'finalized_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Public URL for the report file.
     */
    public function reportFileUrl(): ?string
    {
        if (! $this->report_file_path) {
            return null;
        }

        return asset('storage/' . $this->report_file_path);
    }

    /**
     * Human-readable liquidation status label.
     */
    public function getLiquidationStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'reviewed' => 'Under Review',
            'approved' => 'Approved',
            'returned' => 'Returned for Revision',
        ];

        return $labels[$this->liquidation_status] ?? ucfirst($this->liquidation_status);
    }

    /**
     * Color key for liquidation status badge.
     */
    public function getLiquidationStatusColorAttribute(): string
    {
        $colors = [
            'draft' => 'gray',
            'submitted' => 'blue',
            'reviewed' => 'amber',
            'approved' => 'emerald',
            'returned' => 'red',
        ];

        return $colors[$this->liquidation_status] ?? 'gray';
    }
}
