<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleHealthTask extends Model
{
    use HasFactory;

    public const TASK_TYPES = HealthTemplateItem::TASK_TYPES;

    public const STATUSES = [
        'pending',
        'in_progress',
        'completed',
        'partially_completed',
        'skipped',
        'rescheduled',
        'overdue',
        'not_applicable',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'partially_completed' => 'Partially Completed',
        'skipped' => 'Skipped',
        'rescheduled' => 'Rescheduled',
        'overdue' => 'Overdue',
        'not_applicable' => 'Not Applicable',
    ];

    public const TERMINAL_STATUSES = [
        'completed',
        'skipped',
        'not_applicable',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'health_template_item_id',
        'task_name',
        'task_type',
        'planned_start_date',
        'planned_end_date',
        'actual_date',
        'status',
        'target_count',
        'completed_count',
        'remaining_count',
        'is_optional',
        'remarks',
        'follow_up_date',
        'completed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_date' => 'date',
            'target_count' => 'integer',
            'completed_count' => 'integer',
            'remaining_count' => 'integer',
            'is_optional' => 'boolean',
            'follow_up_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'cycle_health_tasks';
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PigCycle::class, 'batch_id');
    }

    public function batch(): BelongsTo
    {
        return $this->cycle();
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(HealthTemplateItem::class, 'health_template_item_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('planned_start_date', today());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereDate('planned_start_date', '<', today())
            ->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('planned_start_date', '>', today());
    }

    public function getFormattedStatusAttribute(): string
    {
        return self::formatStatusLabel((string) $this->status);
    }

    public static function formatStatusLabel(?string $status): string
    {
        $normalizedStatus = (string) $status;

        return self::STATUS_LABELS[$normalizedStatus]
            ?? str($normalizedStatus)->replace('_', ' ')->title()->toString();
    }
}
