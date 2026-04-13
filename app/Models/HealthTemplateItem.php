<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthTemplateItem extends Model
{
    use HasFactory;

    public const TASK_TYPES = [
        'oral_medication_period',
        'injectable',
        'deworming',
        'maintenance_optional',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'health_template_id',
        'task_name',
        'task_type',
        'day_offset_start',
        'day_offset_end',
        'is_optional',
        'sort_order',
        'default_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_offset_start' => 'integer',
            'day_offset_end' => 'integer',
            'is_optional' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'health_template_items';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(HealthTemplate::class, 'health_template_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CycleHealthTask::class, 'health_template_item_id');
    }
}
