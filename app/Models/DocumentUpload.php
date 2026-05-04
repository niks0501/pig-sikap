<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'user_id',
        'file_path',
        'original_name',
        'size_kb',
        'status',
        'reviewer_id',
        'review_comment',
        'reviewed_at',
        'module_type',
        'module_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'size_kb' => 'integer',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function module(): MorphTo
    {
        return $this->morphTo();
    }

    public function audits()
    {
        return $this->hasMany(DocumentUploadAudit::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function needsResubmission(): bool
    {
        return $this->status === 'needs_resubmission';
    }
}