<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigBatchStatusRequest;
use App\Models\PigBatch;
use App\Services\PigRegistry\UpdatePigBatchStatusService;
use Illuminate\Http\RedirectResponse;

class PresidentPigBatchStatusController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        StorePigBatchStatusRequest $request,
        PigBatch $batch,
        UpdatePigBatchStatusService $updatePigBatchStatusService
    ): RedirectResponse {
        $history = $updatePigBatchStatusService->handle($batch, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'batch_status_updated',
            "Updated {$batch->batch_code} status to {$history->new_stage} / {$history->new_status}."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Batch status updated successfully.');
    }
}
