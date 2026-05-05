<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting signatory (attendee) pivot model.
 */
class MeetingSignatory extends Model
{
    use HasFactory;

    public const ATTENDANCE_STATUSES = ['present', 'absent', 'excused'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'meeting_id',
        'user_id',
        'attendance_status',
        'penalty_applied',
    ];

    protected function casts(): array
    {
        return [
            'penalty_applied' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
