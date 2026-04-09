<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigBatchAdjustmentRequest;
use App\Models\PigBatch;
use App\Services\PigRegistry\AdjustPigBatchCountService;
use Illuminate\Http\RedirectResponse;

class PresidentPigBatchAdjustmentController extends Controller
{
    use RecordsAuditTrail;

    public function store(
        StorePigBatchAdjustmentRequest $request,
        PigBatch $batch,
        AdjustPigBatchCountService $adjustPigBatchCountService
    ): RedirectResponse {
        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot be adjusted without reopening.',
            ]);
        }

        $adjustment = $adjustPigBatchCountService->handle($batch, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'batch_count_adjusted',
            "Adjusted {$batch->batch_code} from {$adjustment->quantity_before} to {$adjustment->quantity_after} ({$adjustment->reason})."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Batch count adjusted successfully.');
    }
}
