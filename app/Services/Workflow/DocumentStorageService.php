<?php

namespace App\Services\Workflow;

use App\Models\DocumentVersion;
use App\Models\Resolution;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles file storage, hashing, and version tracking for resolution documents.
 */
class DocumentStorageService
{
    /**
     * Store a generated document (PDF or DOCX) and create a version record.
     */
    public function storeGeneratedDocument(
        string $content,
        Resolution $resolution,
        string $extension,
        int $versionNumber,
        array $metadata = []
    ): string {
        $directory = $this->getStoragePath($resolution, 'generated');
        $filename = sprintf(
            '%s_v%d_%s.%s',
            Str::slug($resolution->resolution_number),
            $versionNumber,
            now()->format('Ymd'),
            $extension
        );
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->put($path, $content);

        $hash = hash('sha256', $content);
        $size = strlen($content);

        DocumentVersion::create([
            'resolution_id' => $resolution->id,
            'version_number' => $versionNumber,
            'document_type' => 'generated_' . $extension,
            'file_path' => $path,
            'file_size' => $size,
            'file_hash' => $hash,
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'description' => "Generated {$extension} document v{$versionNumber}",
            'metadata_json' => $metadata ?: null,
        ]);

        return $path;
    }

    /**
     * Store an uploaded document (signed resolution, DSWD approval, etc.)
     * and create a version record.
     */
    public function storeSignedDocument(
        UploadedFile $file,
        Resolution $resolution,
        string $type = 'signed_resolution',
        ?string $description = null
    ): string {
        $this->validateFileIntegrity($file);

        $subdir = match ($type) {
            'signed_resolution' => 'signed',
            'dswd_approval' => 'dswd',
            'signature_sheet' => 'signed',
            default => 'attachments',
        };

        $directory = $this->getStoragePath($resolution, $subdir);
        $versionNumber = $resolution->documentVersions()
            ->where('document_type', $type)
            ->max('version_number') + 1;

        $filename = sprintf(
            '%s_%s_v%d_%s.%s',
            $type,
            Str::slug($resolution->resolution_number),
            $versionNumber,
            now()->format('Ymd_His'),
            $file->getClientOriginalExtension()
        );

        $path = $file->storeAs($directory, $filename, 'public');

        DocumentVersion::create([
            'resolution_id' => $resolution->id,
            'version_number' => $versionNumber,
            'document_type' => $type,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
            'description' => $description,
        ]);

        return $path;
    }

    /**
     * Get the next version number for a resolution's generated documents.
     */
    public function getNextVersionNumber(Resolution $resolution): int
    {
        return $resolution->documentVersions()->max('version_number') + 1;
    }

    /**
     * Get the storage path for a resolution's documents.
     */
    protected function getStoragePath(Resolution $resolution, ?string $subdir = null): string
    {
        $base = "resolutions/{$resolution->id}";

        return $subdir ? "{$base}/{$subdir}" : $base;
    }

    /**
     * Basic file integrity check before storing.
     */
    protected function validateFileIntegrity(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new \RuntimeException('The uploaded file is not valid.');
        }

        if ($file->getSize() === 0) {
            throw new \RuntimeException('The uploaded file is empty.');
        }
    }
}
