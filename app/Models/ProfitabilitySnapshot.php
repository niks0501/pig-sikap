<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitabilitySnapshot extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pig_cycle_id',
        'gross_income',
        'total_expenses',
        'net_profit_or_loss',
        'distributable_profit',
        'caretaker_share',
        'member_share',
        'association_share',
        'expense_breakdown_json',
        'share_rule_json',
        'finalized_at',
        'finalized_by_user_id',
        'notes',
        'computation_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_income' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'net_profit_or_loss' => 'decimal:2',
            'distributable_profit' => 'decimal:2',
            'caretaker_share' => 'decimal:2',
            'member_share' => 'decimal:2',
            'association_share' => 'decimal:2',
            'expense_breakdown_json' => 'array',
            'share_rule_json' => 'array',
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
            'total_expenses' => (float) $this->total_expenses,
            'total_cycle_sales' => (float) $this->gross_income,
            'total_cycle_expense' => (float) $this->total_expenses,
            'net_profit_or_loss' => $netProfitOrLoss,
            'distributable_profit' => (float) $this->distributable_profit,
            'caretaker_share' => (float) $this->caretaker_share,
            'member_share' => (float) $this->member_share,
            'association_share' => (float) $this->association_share,
            'expense_breakdown' => $expenseBreakdown,
            'breakdown' => $expenseBreakdown,
            'expense_breakdown_rows' => $this->expenseBreakdownRows($expenseBreakdown),
            'sales_breakdown_rows' => [[
                'method' => 'finalized_snapshot',
                'label' => 'Finalized Gross Revenue',
                'pigs_sold' => null,
                'total' => (float) $this->gross_income,
            ]],
            'status' => $netProfitOrLoss > 0 ? 'profit' : ($netProfitOrLoss < 0 ? 'loss' : 'break_even'),
            'has_sales' => (float) $this->gross_income > 0,
            'has_expenses' => (float) $this->total_expenses > 0,
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
}
