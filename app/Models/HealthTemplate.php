<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthTemplate extends Model
{
    use HasFactory;

    public const DEFAULT_TEMPLATE_CODE = 'STANDARD_PURCHASED_PIG_CYCLE';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return 'health_templates';
    }

    public function items(): HasMany
    {
        return $this->hasMany(HealthTemplateItem::class, 'health_template_id');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(PigCycle::class, 'health_template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
