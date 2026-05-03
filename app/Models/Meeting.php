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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
     * Count of present attendees.
     */
    public function getPresentCountAttribute(): int
    {
        return $this->signatories->where('attendance_status', 'present')->count();
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
