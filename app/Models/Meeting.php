<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Meeting model – captures meeting minutes data
 * (date, agenda, attendees, summary, and scanned minutes file).
 */
class Meeting extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'confirmed', 'cancelled'];

    public const MEETING_TYPES = ['pig_production', 'monthly_association', 'general'];

    /**
     * Default structured agenda items keyed by meeting type.
     * These mirror the association's actual resolution template fields.
     */
    public const DEFAULT_AGENDA = [
        'pig_production' => [
            'Canvassing Assign Person',
            'Canvassing Date',
            'Number of Piglets to Buy',
            'Number of Sacks of Feeds',
            'Feed Price',
            'Medicines for Piglets',
            'Caretaker / Place of Raising',
            'Raising Duration',
            'Group Policy',
        ],
        'monthly_association' => [
            'Call to Order',
            'Roll Call / Attendance',
            'Reading of Previous Minutes',
            "Treasurer's Report",
            'Attendance Review & Penalties',
            'Old Business / Matters Arising',
            'New Business',
            'Adjournment',
        ],
        'general' => [
            'Opening / Call to Order',
            'Old Business',
            'New Business',
            'Other Matters',
            'Adjournment',
        ],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'date',
        'location',
        'agenda',
        'minutes_summary',
        'minutes_file_path',
        'created_by',
        'updated_by',
        'status',
        'meeting_type',
        'agenda_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'agenda_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Members who attended or were listed for this meeting.
     */
    public function signatories(): HasMany
    {
        return $this->hasMany(MeetingSignatory::class);
    }

    /**
     * Users pivot (through signatories).
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_signatories')
            ->withPivot('attendance_status')
            ->withTimestamps();
    }

    /**
     * Resolutions created from this meeting.
     */
    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class);
    }

    // ─── Accessors ────────────────────────────────────────────

    /**
     * Count of present attendees (active members who were present at this meeting).
     * This is the denominator for the 75% approval threshold calculation.
     */
    public function getPresentCountAttribute(): int
    {
        return $this->signatories->where('attendance_status', 'present')->count();
    }

    /**
     * Count of all meeting signatories (present + absent + excused).
     */
    public function getSignatoryCountAttribute(): int
    {
        return $this->signatories->count();
    }

    /**
     * Structured agenda items (from agenda_json, falls back to parsed agenda text).
     */
    public function getStructuredAgendaAttribute(): array
    {
        if ($this->agenda_json && is_array($this->agenda_json) && count($this->agenda_json) > 0) {
            return $this->agenda_json;
        }

        // Fallback: parse free-text agenda into bullet lines
        if ($this->agenda) {
            $lines = array_filter(array_map('trim', explode("\n", $this->agenda)));
            if (count($lines) > 0) {
                return array_values($lines);
            }
        }

        return [];
    }

    /**
     * Get the default agenda for this meeting's type.
     */
    public function getDefaultAgendaAttribute(): array
    {
        $type = $this->meeting_type ?: 'pig_production';

        return static::DEFAULT_AGENDA[$type] ?? static::DEFAULT_AGENDA['general'];
    }

    /**
     * Meeting type label for display.
     */
    public function getMeetingTypeLabelAttribute(): string
    {
        $labels = [
            'pig_production' => 'Pig Production / Purchase',
            'monthly_association' => 'Monthly Association Meeting',
            'general' => 'General Meeting',
        ];

        return $labels[$this->meeting_type] ?? 'General Meeting';
    }

    /**
     * Public URL for the uploaded minutes file.
     */
    public function minutesFileUrl(): ?string
    {
        if (! $this->minutes_file_path) {
            return null;
        }

        return asset('storage/' . $this->minutes_file_path);
    }
}
