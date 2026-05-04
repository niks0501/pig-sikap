<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'allowed_file_types',
        'max_size_kb',
    ];

    protected function casts(): array
    {
        return [
            'allowed_file_types' => 'array',
            'max_size_kb' => 'integer',
        ];
    }

    public function documentUploads(): HasMany
    {
        return $this->hasMany(DocumentUpload::class);
    }
}