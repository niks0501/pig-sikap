<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OverallDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardOverviewController extends Controller
{
    /**
     * Return the full consolidated dashboard overview as JSON.
     *
     * Used by the Vue control-center dashboard for fetching and refreshing.
     */
    public function __invoke(Request $request, OverallDashboardService $service): JsonResponse
    {
        $filters = [
            'cycle_id' => $request->integer('cycle_id') ?: null,
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
            'pig_status' => $request->string('pig_status')->toString() ?: null,
            'pig_sex' => $request->string('pig_sex')->toString() ?: null,
        ];

        return response()->json($service->getOverview($filters));
    }
}
