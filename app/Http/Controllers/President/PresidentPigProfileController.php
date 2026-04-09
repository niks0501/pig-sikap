<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigRequest;
use App\Http\Requests\PigRegistry\UpdatePigRequest;
use App\Models\Pig;
use App\Models\PigBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresidentPigProfileController extends Controller
{
    use RecordsAuditTrail;

    public function index(PigBatch $batch): View
    {
        $batch->load(['breeder:id,breeder_code,name_or_tag', 'pigs' => fn ($query) => $query->orderBy('pig_no')]);

        return view('batches.pigs', [
            'batch' => $batch,
            'pigStatuses' => Pig::STATUSES,
            'sexOptions' => Pig::SEX_OPTIONS,
        ]);
    }

    public function store(StorePigRequest $request, PigBatch $batch): RedirectResponse
    {
        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot accept new pig profiles.',
            ]);
        }

        $pig = $batch->pigs()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        if (! $batch->has_pig_profiles) {
            $batch->update([
                'has_pig_profiles' => true,
                'last_reviewed_at' => now(),
            ]);
        }

        $this->recordAudit(
            $request,
            'pig_profile_created',
            "Created pig profile #{$pig->pig_no} in batch {$batch->batch_code}."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Pig profile added successfully.');
    }

    public function update(UpdatePigRequest $request, PigBatch $batch, Pig $pig): RedirectResponse
    {
        if ($pig->batch_id !== $batch->id) {
            abort(404);
        }

        if ($batch->isArchived()) {
            return back()->withErrors([
                'batch' => 'Archived batches cannot modify pig profiles.',
            ]);
        }

        $pig->update($request->validated());

        $this->recordAudit(
            $request,
            'pig_profile_updated',
            "Updated pig profile #{$pig->pig_no} in batch {$batch->batch_code}."
        );

        return redirect()
            ->route('batches.show', $batch)
            ->with('status', 'Pig profile updated successfully.');
    }
}
