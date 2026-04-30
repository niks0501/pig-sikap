<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PigCycleSale extends Model
{
    use HasFactory;

    public const DIGITAL_RECEIPT_STATUSES = [
        'not_sent',
        'sent',
        'failed',
    ];

    public const SALE_METHODS = [
        'live_weight',
        'per_head',
    ];

    public const PAYMENT_STATUSES = [
        'paid',
        'partial',
        'pending',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'buyer_id',
        'pigs_sold',
        'amount',
        'sale_date',
        'sale_method',
        'live_weight_kg',
        'price_per_kg',
        'price_per_head',
        'payment_status',
        'amount_paid',
        'receipt_reference',
        'receipt_path',
        'digital_receipt_number',
        'digital_receipt_path',
        'digital_receipt_email',
        'digital_receipt_status',
        'digital_receipt_sent_at',
        'digital_receipt_error',
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
            'pigs_sold' => 'integer',
            'live_weight_kg' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'price_per_head' => 'decimal:2',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'sale_date' => 'date',
            'digital_receipt_sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'pig_cycle_sales';
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'batch_id');
    }

    public function batch(): BelongsTo
    {
        return $this->cycle();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(PigBuyer::class, 'buyer_id');
    }

    public function receiptUrl(): ?string
    {
        if (! is_string($this->receipt_path) || $this->receipt_path === '') {
            return null;
        }

        return asset('storage/'.$this->receipt_path);
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
