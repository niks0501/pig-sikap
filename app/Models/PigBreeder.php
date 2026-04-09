<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PigBreeder extends Model
{
    use HasFactory, SoftDeletes;

    public const REPRODUCTIVE_STATUSES = [
        'Active',
        'Pregnant',
        'Lactating',
        'Resting',
        'Retired',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'breeder_code',
        'name_or_tag',
        'reproductive_status',
        'acquisition_date',
        'expected_farrowing_date',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'expected_farrowing_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(PigBatch::class, 'breeder_id');
    }
}
