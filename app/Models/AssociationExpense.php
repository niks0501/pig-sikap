<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Association-level expense not tied to a specific pig cycle.
 *
 * Covers meeting costs, bank withdrawal fees, association supplies,
 * general emergency funds, policy penalties, and other operational
 * expenses tracked outside of the cycle-based logbook.
 */
class AssociationExpense extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'feed',
        'medicine',
        'transport',
        'supplies',
        'utilities',
        'labor',
        'emergency',
        'other',
    ];

    /** Feed subcategories for granular feed-type tracking. */
    public const FEED_SUBCATEGORIES = [
        'pre_starter',
        'starter',
        'grower',
        'finisher',
    ];

    /** Allowable fund sources for an association expense. */
    public const FUND_SOURCES = [
        'association_fund',
        'withdrawn_fund',
        'emergency_fund',
        'member_contribution',
        'sales_revenue',
        'caretaker_advance',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_name',
        'category',
        'feed_subcategory',
        'quantity',
        'unit',
        'unit_cost',
        'amount',
        'expense_date',
        'receipt_reference',
        'receipt_path',
        'supplier_id',
        'canvass_id',
        'fund_source',
        'approved_resolution_id',
        'withdrawal_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'feed' => 'Feed',
            'medicine' => 'Medicine',
            'transport' => 'Transport',
            'supplies' => 'Supplies',
            'utilities' => 'Utilities',
            'labor' => 'Labor',
            'emergency' => 'Emergency',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function feedSubcategoryLabels(): array
    {
        return [
            'pre_starter' => 'Pre-Starter',
            'starter' => 'Starter',
            'grower' => 'Grower',
            'finisher' => 'Finisher',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fundSourceLabels(): array
    {
        return [
            'association_fund' => 'Association Fund',
            'withdrawn_fund' => 'Withdrawn Fund',
            'emergency_fund' => 'Emergency Fund',
            'member_contribution' => 'Member Contribution',
            'sales_revenue' => 'Sales Revenue',
            'caretaker_advance' => 'Caretaker Advance',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryLabels()[$this->category] ?? ucfirst($this->category);
    }

    public function feedSubcategoryLabel(): string
    {
        if (! $this->feed_subcategory) {
            return '';
        }

        return self::feedSubcategoryLabels()[$this->feed_subcategory] ?? ucfirst((string) $this->feed_subcategory);
    }

    public function fundSourceLabel(): string
    {
        if (! $this->fund_source) {
            return '';
        }

        return self::fundSourceLabels()[$this->fund_source] ?? ucfirst((string) $this->fund_source);
    }

    public function receiptUrl(): ?string
    {
        if (! is_string($this->receipt_path) || $this->receipt_path === '') {
            return null;
        }

        return asset('storage/' . $this->receipt_path);
    }

    // ─── Relationships ────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function canvass(): BelongsTo
    {
        return $this->belongsTo(Canvass::class);
    }

    /**
     * The resolution that approved this expense.
     */
    public function approvedResolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class, 'approved_resolution_id');
    }

    /**
     * Withdrawal linked to this expense for budget-vs-actual tracking.
     */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
