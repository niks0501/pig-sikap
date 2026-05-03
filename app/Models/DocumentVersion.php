<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks every version of a document associated with a resolution.
 * Provides an immutable audit trail of all generated, uploaded, and approved documents.
 */
class DocumentVersion extends Model
{
    use HasFactory;

    public const TYPES = [
        'generated_pdf',
        'generated_docx',
        'signed_resolution',
        'dswd_approval',
        'signature_sheet',
        'supporting_attachment',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resolution_id',
        'version_number',
        'document_type',
        'file_path',
        'file_size',
        'file_hash',
        'generated_by',
        'generated_at',
        'description',
        'metadata_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'generated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // ─── Accessors ────────────────────────────────────────────

    /**
     * Public URL for this document version.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Human-readable document type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->document_type));
    }
}
