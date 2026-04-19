<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleHealthIncident extends Model
{
    use HasFactory;

    public const INCIDENT_TYPE_SICK = 'sick';

    public const INCIDENT_TYPE_ISOLATED = 'isolated';

    public const INCIDENT_TYPE_DECEASED = 'deceased';

    public const INCIDENT_TYPE_RECOVERED = 'recovered';

    public const LEGACY_INCIDENT_TYPE_TREATED = 'treated';

    public const INCIDENT_TYPES = [
        self::INCIDENT_TYPE_SICK,
        self::INCIDENT_TYPE_ISOLATED,
        self::INCIDENT_TYPE_DECEASED,
        self::INCIDENT_TYPE_RECOVERED,
    ];

    public const ACCEPTED_INCIDENT_TYPES = [
        ...self::INCIDENT_TYPES,
        self::LEGACY_INCIDENT_TYPE_TREATED,
    ];

    public const PIG_SPECIFIC_INCIDENT_TYPES = [
        self::INCIDENT_TYPE_ISOLATED,
        self::INCIDENT_TYPE_DECEASED,
        self::INCIDENT_TYPE_RECOVERED,
    ];

    public const RESOLUTION_INCIDENT_TYPES = [
        self::INCIDENT_TYPE_DECEASED,
        self::INCIDENT_TYPE_RECOVERED,
    ];

    public const RESOLUTION_TARGETS = [
        self::INCIDENT_TYPE_SICK,
        self::INCIDENT_TYPE_ISOLATED,
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
        'resolution_target',
        'resolved_incident_id',
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
            'resolved_incident_id' => 'integer',
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

    public function resolvedIncident(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resolved_incident_id');
    }

    public static function normalizeIncidentType(mixed $incidentType): string
    {
        $normalized = strtolower(trim((string) $incidentType));

        return match ($normalized) {
            self::LEGACY_INCIDENT_TYPE_TREATED => self::INCIDENT_TYPE_RECOVERED,
            default => $normalized,
        };
    }

    public static function normalizeResolutionTarget(mixed $resolutionTarget): ?string
    {
        $normalized = strtolower(trim((string) $resolutionTarget));

        return in_array($normalized, self::RESOLUTION_TARGETS, true)
            ? $normalized
            : null;
    }

    public static function isPigSpecificIncidentType(string $incidentType): bool
    {
        return in_array(self::normalizeIncidentType($incidentType), self::PIG_SPECIFIC_INCIDENT_TYPES, true);
    }

    public static function isResolutionIncidentType(string $incidentType): bool
    {
        return in_array(self::normalizeIncidentType($incidentType), self::RESOLUTION_INCIDENT_TYPES, true);
    }

    public static function requiresResolutionTarget(string $incidentType): bool
    {
        return self::normalizeIncidentType($incidentType) === self::INCIDENT_TYPE_RECOVERED;
    }
}

