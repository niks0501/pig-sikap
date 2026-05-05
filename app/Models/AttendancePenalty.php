<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttendancePenalty model – tracks penalties for absent members
 * at meetings. Auto-applied on meeting confirmation.
 */
class AttendancePenalty extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'paid', 'waived', 'cancelled'];

    protected $fillable = [
        'user_id',
        'meeting_id',
        'amount',
        'status',
        'reason',
        'waived_by',
        'waived_at',
        'paid_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'waived_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function waivered(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}