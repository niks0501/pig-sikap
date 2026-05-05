<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AssociationPolicySetting model – key-value store for configurable
 * association rules: meeting schedule, penalties, dividends, etc.
 */
class AssociationPolicySetting extends Model
{
    use HasFactory;

    public const GROUPS = ['financial', 'attendance', 'meeting', 'membership'];

    protected $fillable = [
        'key',
        'value',
        'description',
        'value_type',
        'group',
        'updated_by',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}