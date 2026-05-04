<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentUploadAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_upload_id',
        'user_id',
        'action',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function documentUpload(): BelongsTo
    {
        return $this->belongsTo(DocumentUpload::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}