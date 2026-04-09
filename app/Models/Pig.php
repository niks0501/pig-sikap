<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pig extends Model
{
    use HasFactory, SoftDeletes;

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
            'deleted_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PigBatch::class, 'batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
