<?php

namespace App\Http\Controllers\DocumentManagement;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentPageController extends Controller
{
    public function upload()
    {
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('documents.upload', [
            'documentTypes' => $documentTypes,
        ]);
    }

    public function review()
    {
        return view('documents.review');
    }

    public function manageTypes()
    {
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('documents.manage-types', [
            'documentTypes' => $documentTypes,
        ]);
    }
}