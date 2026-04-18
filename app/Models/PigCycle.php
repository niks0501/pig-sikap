<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PigCycle extends Model
{
    use HasFactory, SoftDeletes;

    public const DEFAULT_READY_FOR_SALE_MONTHS = 4;

    public const NEAR_HARVEST_DAYS = 14;

    public const STAGES = [
        'Piglet',
        'Weaning',
        'Growing',
        'Fattening',
        'For Sale',
        'Completed',
    ];

    public const STATUSES = [
        'Active',
        'Under Monitoring',
        'Ready for Sale',
        'Sold',
        'Closed',
    ];

    public const ARCHIVED_STATUSES = [
        'Sold',
        'Closed',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_code',
        'caretaker_user_id',
        'cycle_number',
        'health_template_id',
        'date_of_purchase',
        'initial_count',
        'current_count',
        'average_weight',
        'stage',
        'status',
        'has_pig_profiles',
        'notes',
        'last_reviewed_at',
        'archived_at',
        'archived_by',
        'reopened_at',
        'reopened_by',
        'created_by',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'expected_ready_for_sale_date',
        'expected_harvest_month',
        'days_since_acquisition',
        'days_until_ready_for_sale',
        'is_near_harvest_window',
        'is_overdue_for_sale_review',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_purchase' => 'date',
            'average_weight' => 'decimal:2',
            'has_pig_profiles' => 'boolean',
            'last_reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
            'reopened_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'pig_cycles';
    }

    public function getRouteKeyName(): string
    {
        return 'batch_code';
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caretaker_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function healthTemplate(): BelongsTo
    {
        return $this->belongsTo(HealthTemplate::class, 'health_template_id');
    }

    public function pigs(): HasMany
    {
        return $this->hasMany(Pig::class, 'batch_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PigCycleAdjustment::class, 'batch_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PigCycleStatusHistory::class, 'batch_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PigCycleExpense::class, 'batch_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PigCycleSale::class, 'batch_id');
    }

    public function healthTasks(): HasMany
    {
        return $this->hasMany(CycleHealthTask::class, 'batch_id');
    }

    public function healthIncidents(): HasMany
    {
        return $this->hasMany(CycleHealthIncident::class, 'batch_id');
    }

    public function scopeActiveRecords(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', self::ARCHIVED_STATUSES)
            ->where('stage', '!=', 'Completed');
    }

    public function scopeArchivedRecords(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereIn('status', self::ARCHIVED_STATUSES)
                ->orWhere('stage', 'Completed');
        });
    }

    public function isArchived(): bool
    {
        return in_array($this->status, self::ARCHIVED_STATUSES, true) || $this->stage === 'Completed';
    }

    protected function expectedReadyForSaleDate(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->expectedReadyForSaleCarbon()?->toDateString(),
        );
    }

    protected function expectedHarvestMonth(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->expectedReadyForSaleCarbon()?->format('F Y'),
        );
    }

    protected function daysSinceAcquisition(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                $purchaseDate = $this->purchaseDate();

                if ($purchaseDate === null) {
                    return null;
                }

                return max(0, $purchaseDate->diffInDays(today(), false));
            },
        );
    }

    protected function daysUntilReadyForSale(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                $expectedDate = $this->expectedReadyForSaleCarbon();

                if ($expectedDate === null) {
                    return null;
                }

                return today()->diffInDays($expectedDate, false);
            },
        );
    }

    protected function isNearHarvestWindow(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $daysRemaining = $this->days_until_ready_for_sale;

                return is_int($daysRemaining)
                    && $daysRemaining >= 0
                    && $daysRemaining <= self::NEAR_HARVEST_DAYS;
            },
        );
    }

    protected function isOverdueForSaleReview(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => is_int($this->days_until_ready_for_sale) && $this->days_until_ready_for_sale < 0,
        );
    }

    private function purchaseDate(): ?Carbon
    {
        if ($this->date_of_purchase === null) {
            return null;
        }

        if ($this->date_of_purchase instanceof Carbon) {
            return $this->date_of_purchase->copy()->startOfDay();
        }

        return Carbon::parse((string) $this->date_of_purchase)->startOfDay();
    }

    private function expectedReadyForSaleCarbon(): ?Carbon
    {
        return $this->purchaseDate()?->addMonthsNoOverflow(self::DEFAULT_READY_FOR_SALE_MONTHS);
    }
}