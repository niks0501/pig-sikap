<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DSWD submission model – tracks the submission
 * and review status of a resolution by DSWD.
 */
class DswdSubmission extends Model
{
    use HasFactory;

    public const STATUSES = ['not_submitted', 'submitted', 'approved', 'returned'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resolution_id',
        'submitted_at',
        'submission_file_path',
        'status',
        'notes',
        'submitted_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Public URL for uploaded submission file.
     */
    public function submissionFileUrl(): ?string
    {
        if (! $this->submission_file_path) {
            return null;
        }

        return asset('storage/' . $this->submission_file_path);
    }
}
