<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Pig extends Model
{
    use HasFactory;

    public const STATUSES = [
        'Active',
        'Sick',
        'Isolated',
        'Sold',
        'Deceased',
    ];

    public const SEX_OPTIONS = [
        'Male',
        'Female',
    ];

    public const OUT_OF_COUNT_STATUSES = [
        'Sold',
        'Deceased',
        'Deleted',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'pig_no',
        'ear_mark_type',
        'ear_mark_value',
        'sex',
        'status',
        'remarks',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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

    public function healthIncidents(): HasMany
    {
        return $this->hasMany(CycleHealthIncident::class, 'pig_id');
    }

    public static function statusCountsTowardBatch(?string $status): bool
    {
        if ($status === null || $status === '') {
            return true;
        }

        return ! in_array($status, self::OUT_OF_COUNT_STATUSES, true);
    }

    public static function autoDecreaseReasonForStatus(string $status): string
    {
        return match ($status) {
            'Deceased' => 'mortality',
            'Sold' => 'sale deduction',
            default => 'data correction',
        };
    }

    public static function autoIncreaseReasonForStatus(?string $previousStatus): string
    {
        return match ($previousStatus) {
            'Sold', 'Isolated' => 'transfer',
            'Deceased' => 'data correction',
            default => 'recount',
        };
    }
}
