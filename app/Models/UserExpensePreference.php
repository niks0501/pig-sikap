<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExpensePreference extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'preference_key',
        'preference_value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
