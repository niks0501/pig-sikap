<?php

namespace App\Services;

use App\Models\DocumentUpload;
use App\Models\DocumentUploadAudit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentUploadService
{
    public function createUpload(
        int $documentTypeId,
        int $userId,
        UploadedFile $file,
        ?string $moduleType = null,
        ?int $moduleId = null
    ): DocumentUpload {
        $filePath = $file->store('document_uploads', 'local');

        $upload = DocumentUpload::create([
            'document_type_id' => $documentTypeId,
            'user_id' => $userId,
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'size_kb' => (int) round($file->getSize() / 1024),
            'status' => 'pending',
            'module_type' => $moduleType,
            'module_id' => $moduleId,
        ]);

        $this->logAudit($upload, $userId, 'uploaded', [
            'original_name' => $upload->original_name,
            'size_kb' => $upload->size_kb,
        ]);

        return $upload;
    }

    public function updateStatus(
        DocumentUpload $upload,
        string $status,
        int $reviewerId,
        ?string $comment = null
    ): DocumentUpload {
        $upload->update([
            'status' => $status,
            'reviewer_id' => $reviewerId,
            'review_comment' => $comment,
            'reviewed_at' => now(),
        ]);

        $this->logAudit($upload, $reviewerId, 'status_changed', [
            'new_status' => $status,
            'comment' => $comment,
        ]);

        return $upload->fresh();
    }

    public function getSignedUrl(DocumentUpload $upload): string
    {
        return Storage::disk('local')->temporaryUrl(
            $upload->file_path,
            now()->addHours(2)
        );
    }

    protected function logAudit(DocumentUpload $upload, int $userId, string $action, array $details = []): void
    {
        DocumentUploadAudit::create([
            'document_upload_id' => $upload->id,
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
        ]);
    }
}