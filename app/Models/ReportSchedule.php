<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use HasFactory;

    public const FORMATS = ['pdf', 'csv'];

    public const FREQUENCIES = ['monthly', 'quarterly'];

    public const STATUSES = ['active', 'paused'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report_type',
        'format',
        'frequency',
        'day_of_month',
        'run_at',
        'cycle_id',
        'filters_json',
        'status',
        'last_run_at',
        'next_run_at',
        'last_error',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_month' => 'integer',
            'filters_json' => 'array',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'cycle_id');
    }
}
