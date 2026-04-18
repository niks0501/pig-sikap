<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleHealthIncident extends Model
{
    use HasFactory;

    public const INCIDENT_TYPES = [
        'sick',
        'isolated',
        'deceased',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'event_key',
        'pig_id',
        'source_channel',
        'incident_type',
        'date_reported',
        'affected_count',
        'suspected_cause',
        'treatment_given',
        'remarks',
        'media_path',
        'reported_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pig_id' => 'integer',
            'date_reported' => 'date',
            'affected_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'cycle_health_incidents';
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'batch_id');
    }

    public function batch(): BelongsTo
    {
        return $this->cycle();
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function pig(): BelongsTo
    {
        return $this->belongsTo(Pig::class, 'pig_id');
    }
}
