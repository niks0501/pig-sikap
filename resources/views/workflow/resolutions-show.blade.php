<x-app-layout>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('workflow.resolutions.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#0c6d57] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to Resolutions
        </a>
    </div>

    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
    @endif

    @if (session('error'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ session('error') }}
    </div>
    @endif

    @if ($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Resolution Detail Vue Component --}}
    <div
        data-vue-component="resolution-detail"
        data-props="{{ json_encode([
            'resolution' => [
                'id' => $resolution->id,
                'title' => $resolution->title,
                'description' => $resolution->description,
                'status' => $resolution->status,
                'workflow_status' => $resolution->workflow_status ?? 'draft',
                'workflow_status_label' => $resolution->workflow_status_label,
                'resolution_number' => $resolution->resolution_number,
                'approval_deadline' => $resolution->approval_deadline?->format('M d, Y'),
                'resolution_file_url' => $resolution->resolutionFileUrl(),
                'grand_total' => (float) $resolution->grand_total,
                'total_withdrawn' => (float) $resolution->total_withdrawn,
                'remaining_balance' => (float) $resolution->remaining_balance,
                'approval_percentage' => (float) $resolution->approval_percentage,
                'approved_count' => $resolution->approved_count,
                'has_met_threshold' => $resolution->hasMetApprovalThreshold(),
                'total_members' => $totalMembers,
                'creator_name' => $resolution->creator?->name,
                'created_at' => $resolution->created_at?->format('M d, Y h:i A'),
            ],
            'meeting' => $resolution->meeting ? [
                'id' => $resolution->meeting->id,
                'title' => $resolution->meeting->title,
                'date' => $resolution->meeting->date?->format('M d, Y'),
                'show_url' => route('workflow.meetings.show', $resolution->meeting),
            ] : null,
            'lineItems' => $resolution->lineItems->map(fn ($li) => [
                'id' => $li->id,
                'category' => $li->category,
                'description' => $li->description,
                'quantity' => (float) $li->quantity,
                'unit' => $li->unit,
                'unit_cost' => (float) $li->unit_cost,
                'total' => (float) $li->total,
            ])->values(),
            'approvals' => $resolution->approvals->map(fn ($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'user_name' => $a->user?->name,
                'user_role' => $a->user?->role?->name,
                'is_approved' => $a->is_approved,
                'approved_at' => $a->approved_at?->format('M d, Y'),
                'rejection_reason' => $a->rejection_reason,
            ])->values(),
            'dswdSubmission' => $resolution->dswdSubmission ? [
                'id' => $resolution->dswdSubmission->id,
                'status' => $resolution->dswdSubmission->status,
                'submitted_at' => $resolution->dswdSubmission->submitted_at?->format('M d, Y'),
                'submission_file_url' => $resolution->dswdSubmission->submissionFileUrl(),
                'notes' => $resolution->dswdSubmission->notes,
            ] : null,
            'withdrawals' => $resolution->withdrawals->map(fn ($w) => [
                'id' => $w->id,
                'amount' => (float) $w->amount,
                'status' => $w->status,
                'requested_at' => $w->requested_at?->format('M d, Y'),
                'completed_at' => $w->completed_at?->format('M d, Y'),
                'requester_name' => $w->requester?->name,
                'has_report' => (bool) $w->liquidationReport?->report_file_path,
                'generate_report_url' => route('workflow.withdrawals.report', $w),
                'preview_url' => $w->liquidationReport?->report_file_path ? route('workflow.withdrawals.report.preview', ['withdrawal' => $w, 'report' => $w->liquidationReport]) : null,
                'download_url' => $w->liquidationReport?->report_file_path ? route('workflow.withdrawals.report.download', ['withdrawal' => $w, 'report' => $w->liquidationReport]) : null,
                'proof_file_url' => $w->proofFileUrl(),
                'notes' => $w->notes,
            ])->values(),
            'totalMembers' => $totalMembers,
            'eligibility' => $eligibility,
            'threshold' => \App\Models\Resolution::APPROVAL_THRESHOLD,
            'routes' => [
                'approvalsStore' => route('workflow.resolutions.approvals.store', $resolution),
                'approvalsData' => route('workflow.resolutions.approvals.data', $resolution),
                'dswdStore' => route('workflow.resolutions.dswd.store', $resolution),
                'withdrawCreate' => route('workflow.withdrawals.create', $resolution),
                'withdrawStore' => route('workflow.withdrawals.store', $resolution),
                'resolutionsIndex' => route('workflow.resolutions.index'),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>

    {{-- Document Workflow Checklist --}}
    @php
        $user = auth()->user();
        $permissions = [
            'canGenerate' => $user->can('generateDocuments', $resolution),
            'canUploadSigned' => $user->can('uploadSignedDocument', $resolution),
            'canVerifyApproval' => $user->can('verifyApprovalThreshold', $resolution),
            'canUploadDswd' => $user->can('uploadDswdApproval', $resolution),
        ];
    @endphp

    <div class="mt-6"
        data-vue-component="document-checklist"
        data-props="{{ json_encode([
            'resolution' => [
                'id' => $resolution->id,
                'workflow_status' => $resolution->workflow_status ?? 'draft',
                'workflow_status_label' => $resolution->workflow_status_label,
                'resolution_number' => $resolution->resolution_number,
                'has_met_threshold' => $resolution->hasMetApprovalThreshold(),
                'approval_percentage' => (float) $resolution->approval_percentage,
                'approved_count' => $resolution->approved_count,
                'total_members' => $totalMembers,
            ],
            'documentVersions' => $resolution->documentVersions->map(fn ($dv) => [
                'id' => $dv->id,
                'version_number' => $dv->version_number,
                'document_type' => $dv->document_type,
                'file_url' => $dv->file_url,
                'file_size' => $dv->file_size,
                'formatted_file_size' => $dv->formatted_file_size,
                'file_hash' => $dv->file_hash,
                'generated_at' => $dv->generated_at?->format('M d, Y h:i A'),
                'generated_by' => $dv->generatedBy?->name,
                'description' => $dv->description,
            ])->values(),
            'permissions' => $permissions,
            'routes' => [
                'generatePdf' => route('workflow.resolutions.generate-pdf', $resolution),
                'generateDocx' => route('workflow.resolutions.generate-docx', $resolution),
                'verifyApprovals' => route('workflow.resolutions.verify-approvals', $resolution),
            ],
            'csrfToken' => csrf_token(),
        ]) }}"
    ></div>

    {{-- File Upload: Signed Document --}}
    @if ($permissions['canUploadSigned'])
    <div class="mt-6"
        data-vue-component="file-upload"
        data-props="{{ json_encode([
            'uploadUrl' => route('workflow.resolutions.upload-signed', $resolution),
            'csrfToken' => csrf_token(),
            'fieldName' => 'signed_document',
            'accept' => '.pdf',
            'maxSize' => 10 * 1024 * 1024,
            'acceptLabel' => 'PDF files',
            'maxSizeLabel' => '10MB',
            'label' => 'Upload Signed Resolution',
            'showDescription' => true,
            'showSignatureSheet' => true,
        ]) }}"
    ></div>
    @endif

    {{-- File Upload: DSWD Approval --}}
    @if ($permissions['canUploadDswd'])
    <div class="mt-6"
        data-vue-component="file-upload"
        data-props="{{ json_encode([
            'uploadUrl' => route('workflow.resolutions.upload-dswd-approval', $resolution),
            'csrfToken' => csrf_token(),
            'fieldName' => 'dswd_approval_file',
            'accept' => '.pdf,.doc,.docx',
            'maxSize' => 10 * 1024 * 1024,
            'acceptLabel' => 'PDF, DOC, DOCX files',
            'maxSizeLabel' => '10MB',
            'label' => 'Upload DSWD Approval Document',
            'showDescription' => true,
            'showDswdFields' => true,
        ]) }}"
    ></div>
    @endif
</div>
</x-app-layout>
