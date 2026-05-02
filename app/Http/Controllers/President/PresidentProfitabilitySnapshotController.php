<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Controller;
use App\Models\ProfitabilitySnapshot;
use Illuminate\View\View;

class PresidentProfitabilitySnapshotController extends Controller
{
    public function show(ProfitabilitySnapshot $snapshot): View
    {
        $snapshot->loadMissing([
            'cycle:id,batch_code,status,stage,caretaker_user_id',
            'cycle.caretaker:id,name',
            'finalizedBy:id,name',
            'supersedes:id,version_number,finalized_at',
            'supersededBy:id,version_number,finalized_at',
        ]);

        $history = ProfitabilitySnapshot::query()
            ->where('pig_cycle_id', $snapshot->pig_cycle_id)
            ->with('finalizedBy:id,name')
            ->orderByDesc('version_number')
            ->get();

        return view('profitability.snapshots.show', [
            'snapshot' => $snapshot,
            'profitability' => $snapshot->toProfitabilitySummary(),
            'history' => $history,
            'cycle' => $snapshot->cycle,
        ]);
    }
}