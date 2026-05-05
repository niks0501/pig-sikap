<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Canvass model – a supplier quotation comparison record linked
 * to a meeting or resolution. Contains multiple CanvassItem entries.
 */
class Canvass extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'in_progress', 'awarded', 'cancelled'];

    protected $fillable = [
        'resolution_id',
        'meeting_id',
        'title',
        'canvass_date',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'canvass_date' => 'date',
        ];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CanvassItem::class)->orderBy('sort_order');
    }

    /**
     * Selected (winning) items for this canvass.
     */
    public function selectedItems(): HasMany
    {
        return $this->hasMany(CanvassItem::class)->where('is_selected', true);
    }
}