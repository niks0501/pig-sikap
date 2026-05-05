<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PigCycleExpense extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'acquisition',
        'feed',
        'medicine',
        'transport',
        'emergency',
    ];

    public const FEED_SUBCATEGORIES = [
        'pre_starter',
        'starter',
        'grower',
        'finisher',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'withdrawal_id',
        'item_name',
        'supplier_id',
        'receipt_reference',
        'feed_subcategory',
        'category',
        'quantity',
        'unit',
        'unit_cost',
        'amount',
        'expense_date',
        'notes',
        'receipt_path',
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

    public function getTable(): string
    {
        return 'pig_cycle_expenses';
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'acquisition' => 'Acquisition',
            'feed' => 'Feed',
            'medicine' => 'Medicine',
            'transport' => 'Transport',
            'emergency' => 'Emergency',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryLabels()[$this->category] ?? ucfirst($this->category);
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

    public function feedSubcategoryLabel(): string
    {
        if (! $this->feed_subcategory) {
            return '';
        }

        return self::feedSubcategoryLabels()[$this->feed_subcategory] ?? ucfirst((string) $this->feed_subcategory);
    }

    public function receiptUrl(): ?string
    {
        if (! is_string($this->receipt_path) || $this->receipt_path === '') {
            return null;
        }

        return asset('storage/'.$this->receipt_path);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'batch_id');
    }

    public function batch(): BelongsTo
    {
        return $this->cycle();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Withdrawal this expense is linked to (for liquidation report).
     */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    /**
     * Supplier who provided the goods or services for this expense.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
