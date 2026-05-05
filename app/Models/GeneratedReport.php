<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReport extends Model
{
    use HasFactory;

    public const FORMATS = ['pdf', 'csv'];

    public const STATUSES = ['generated', 'failed', 'archived'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report_type',
        'format',
        'cycle_id',
        'filters_json',
        'generated_by',
        'schedule_id',
        'status',
        'file_path',
        'file_size',
        'generated_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'generated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'cycle_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'schedule_id');
    }

    public function fileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/'.$this->file_path);
    }
}
