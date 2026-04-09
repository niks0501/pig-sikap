<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PigBatch extends Model
{
    use HasFactory, SoftDeletes;

    public const STAGES = [
        'Piglet',
        'Weaning',
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
        'breeder_id',
        'caretaker_user_id',
        'cycle_number',
        'birth_date',
        'initial_count',
        'current_count',
        'average_weight',
        'stage',
        'status',
        'has_pig_profiles',
        'notes',
        'last_reviewed_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'average_weight' => 'decimal:2',
            'has_pig_profiles' => 'boolean',
            'last_reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'batch_code';
    }

    public function breeder(): BelongsTo
    {
        return $this->belongsTo(PigBreeder::class, 'breeder_id');
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caretaker_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pigs(): HasMany
    {
        return $this->hasMany(Pig::class, 'batch_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PigBatchAdjustment::class, 'batch_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PigBatchStatusHistory::class, 'batch_id');
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
}
