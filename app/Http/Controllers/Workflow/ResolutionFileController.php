<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Resolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles secure download and preview of resolution documents.
 */
class ResolutionFileController extends Controller
{
    /**
     * Download a specific document version.
     */
    public function download(Request $request, Resolution $resolution, string $documentType, ?int $version = null): StreamedResponse
    {
        $this->authorize('view', $resolution);

        $document = $version
            ? $resolution->documentVersions()
                ->where('document_type', $documentType)
                ->where('version_number', $version)
                ->first()
            : $resolution->documentVersions()
                ->where('document_type', $documentType)
                ->latest('version_number')
                ->first();

        if (! $document) {
            abort(404, 'Document not found.');
        }

        if (! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found on storage.');
        }

        return Storage::disk('public')->download($document->file_path);
    }

    /**
     * Preview a document inline (for PDFs).
     */
    public function preview(Request $request, Resolution $resolution, string $documentType, ?int $version = null): BinaryFileResponse
    {
        $this->authorize('view', $resolution);

        $document = $version
            ? $resolution->documentVersions()
                ->where('document_type', $documentType)
                ->where('version_number', $version)
                ->first()
            : $resolution->documentVersions()
                ->where('document_type', $documentType)
                ->latest('version_number')
                ->first();

        if (! $document) {
            abort(404, 'Document not found.');
        }

        $fullPath = Storage::disk('public')->path($document->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'Document file not found on storage.');
        }

        return response()->file($fullPath);
    }
}
