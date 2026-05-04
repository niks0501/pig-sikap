<?php

namespace App\Http\Controllers\DocumentManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentUploadRequest;
use App\Http\Requests\UpdateDocumentUploadStatusRequest;
use App\Models\DocumentUpload;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentUploadController extends Controller
{
    public function __construct(
        protected DocumentUploadService $uploadService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DocumentUpload::class);

        $query = DocumentUpload::with(['documentType', 'user', 'reviewer']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('document_type_id')) {
            $query->where('document_type_id', $request->document_type_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $uploads = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($uploads);
    }

    public function store(StoreDocumentUploadRequest $request)
    {
        $this->authorize('upload', DocumentUpload::class);

        $upload = $this->uploadService->createUpload(
            documentTypeId: $request->validated('document_type_id'),
            userId: Auth::id(),
            file: $request->file('file'),
            moduleType: $request->input('module_type'),
            moduleId: $request->input('module_id')
        );

        return response()->json([
            'message' => 'Document uploaded successfully and is pending review.',
            'upload' => $upload->load(['documentType', 'user']),
        ], 201);
    }

    public function show(DocumentUpload $documentUpload)
    {
        $this->authorize('view', $documentUpload);

        $documentUpload->load(['documentType', 'user', 'reviewer', 'audits']);

        $signedUrl = $this->uploadService->getSignedUrl($documentUpload);

        return response()->json([
            'upload' => $documentUpload,
            'signed_url' => $signedUrl,
        ]);
    }

    public function updateStatus(UpdateDocumentUploadStatusRequest $request, DocumentUpload $documentUpload)
    {
        $this->authorize('updateStatus', $documentUpload);

        $this->uploadService->updateStatus(
            upload: $documentUpload,
            status: $request->validated('status'),
            reviewerId: Auth::id(),
            comment: $request->validated('review_comment')
        );

        return response()->json([
            'message' => 'Document status updated successfully.',
            'upload' => $documentUpload->fresh(['documentType', 'user', 'reviewer']),
        ]);
    }

    public function download(DocumentUpload $documentUpload)
    {
        $this->authorize('view', $documentUpload);

        $signedUrl = $this->uploadService->getSignedUrl($documentUpload);

        return response()->json(['url' => $signedUrl]);
    }

    public function summary()
    {
        $this->authorize('viewAny', DocumentUpload::class);

        $summary = DocumentUpload::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = ['pending', 'approved', 'rejected', 'needs_resubmission'];
        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = $summary[$status] ?? 0;
        }

        return response()->json($result);
    }
}