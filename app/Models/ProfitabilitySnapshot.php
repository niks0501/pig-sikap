<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitabilitySnapshot extends Model
{
    use HasFactory;

    public const RE_FINALIZE_REASON_CODES = [
        'corrected_expense' => 'Corrected Expense',
        'corrected_sale' => 'Corrected Sale',
        'late_payment_update' => 'Late Payment Update',
        'missing_receipt_added' => 'Missing Receipt Added',
        'wrong_amount_encoded' => 'Wrong Amount Encoded',
        'cycle_record_correction' => 'Cycle Record Correction',
        'other' => 'Other Reason',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pig_cycle_id',
        'snapshot_number',
        'version_number',
        'gross_income',
        'total_collected',
        'receivables',
        'total_expenses',
        'net_profit_or_loss',
        'distributable_profit',
        'caretaker_share',
        'member_share',
        'association_share',
        'expense_breakdown_json',
        'share_rule_json',
        'sales_summary_json',
        'validation_warnings_json',
        'finalized_at',
        'finalized_by_user_id',
        'notes',
        'computation_version',
        'source_hash',
        'is_current',
        'supersedes_snapshot_id',
        're_finalize_reason_code',
        're_finalize_reason_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_number' => 'integer',
            'version_number' => 'integer',
            'gross_income' => 'decimal:2',
            'total_collected' => 'decimal:2',
            'receivables' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'net_profit_or_loss' => 'decimal:2',
            'distributable_profit' => 'decimal:2',
            'caretaker_share' => 'decimal:2',
            'member_share' => 'decimal:2',
            'association_share' => 'decimal:2',
            'expense_breakdown_json' => 'array',
            'share_rule_json' => 'array',
            'sales_summary_json' => 'array',
            'validation_warnings_json' => 'array',
            'is_current' => 'boolean',
            'finalized_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'pig_cycle_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_snapshot_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_snapshot_id');
    }

    public function isLoss(): bool
    {
        return (float) $this->net_profit_or_loss < 0;
    }

    public function hasDistribution(): bool
    {
        return (float) $this->distributable_profit > 0;
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeForCycle(Builder $query, int $cycleId): Builder
    {
        return $query->where('pig_cycle_id', $cycleId);
    }

    public function reFinalizeReasonLabel(): ?string
    {
        if ($this->re_finalize_reason_code === null) {
            return null;
        }

        return self::RE_FINALIZE_REASON_CODES[$this->re_finalize_reason_code] ?? $this->re_finalize_reason_code;
    }

    /**
     * @return array<string, mixed>
     */
    public function toProfitabilitySummary(): array
    {
        $expenseBreakdown = $this->expense_breakdown_json ?? [];
        $netProfitOrLoss = (float) $this->net_profit_or_loss;

        return [
            'gross_income' => (float) $this->gross_income,
            'total_sales' => (float) $this->gross_income,
            'total_collected' => (float) $this->total_collected,
            'receivables' => (float) $this->receivables,
            'total_expenses' => (float) $this->total_expenses,
            'total_cycle_sales' => (float) $this->gross_income,
            'total_cycle_expense' => (float) $this->total_expenses,
            'net_profit_or_loss' => $netProfitOrLoss,
            'distributable_profit' => (float) $this->distributable_profit,
            'caretaker_share' => (float) $this->caretaker_share,
            'member_share' => (float) $this->member_share,
            'association_share' => (float) $this->association_share,
            'association_fund_share' => (float) $this->association_share,
            'expense_breakdown' => $expenseBreakdown,
            'breakdown' => $expenseBreakdown,
            'expense_breakdown_rows' => $this->expenseBreakdownRows($expenseBreakdown),
            'sales_breakdown_rows' => $this->salesBreakdownRows(),
            'sales_summary' => $this->sales_summary_json ?? [],
            'status' => $netProfitOrLoss > 0 ? 'profit' : ($netProfitOrLoss < 0 ? 'loss' : 'break_even'),
            'has_sales' => (float) $this->gross_income > 0,
            'has_expenses' => (float) $this->total_expenses > 0,
            'has_receivables' => (float) $this->receivables > 0,
            'share_rule' => $this->share_rule_json ?? [],
            'computation_version' => $this->computation_version,
            'is_finalized' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @return list<array<string, mixed>>
     */
    private function expenseBreakdownRows(array $breakdown): array
    {
        $labels = PigCycleExpense::categoryLabels();

        return collect(PigCycleExpense::CATEGORIES)
            ->map(fn (string $category): array => [
                'category' => $category,
                'label' => $labels[$category] ?? ucfirst($category),
                'total' => round((float) ($breakdown[$category] ?? 0), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesBreakdownRows(): array
    {
        $salesSummary = $this->sales_summary_json ?? [];

        if (! empty($salesSummary['breakdown_rows'])) {
            return $salesSummary['breakdown_rows'];
        }

        return [[
            'method' => 'finalized_snapshot',
            'label' => 'Finalized Gross Revenue',
            'pigs_sold' => null,
            'total' => (float) $this->gross_income,
        ]];
    }
}