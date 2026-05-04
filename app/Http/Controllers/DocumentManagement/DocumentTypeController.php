<?php

namespace App\Http\Controllers\DocumentManagement;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', DocumentType::class);

        $documentTypes = DocumentType::orderBy('name')->get();

        return response()->json($documentTypes);
    }

    public function store(Request $request)
    {
        $this->authorize('create', DocumentType::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'allowed_file_types' => ['required', 'array'],
            'allowed_file_types.*' => ['string', 'in:pdf,jpg,jpeg,png,gif,doc,docx'],
            'max_size_kb' => ['required', 'integer', 'min:1', 'max:51200'],
        ]);

        $documentType = DocumentType::create($validated);

        return response()->json($documentType, 201);
    }

    public function show(DocumentType $documentType)
    {
        $this->authorize('view', $documentType);

        return response()->json($documentType);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $this->authorize('update', $documentType);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'allowed_file_types' => ['required', 'array'],
            'allowed_file_types.*' => ['string', 'in:pdf,jpg,jpeg,png,gif,doc,docx'],
            'max_size_kb' => ['required', 'integer', 'min:1', 'max:51200'],
        ]);

        $documentType->update($validated);

        return response()->json($documentType);
    }

    public function destroy(DocumentType $documentType)
    {
        $this->authorize('delete', $documentType);

        $documentType->delete();

        return response()->json(['message' => 'Document type deleted successfully']);
    }
}