<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\UpdatePolicySettingsRequest;
use App\Models\AssociationPolicySetting;
use App\Models\AuditTrail;
use App\Services\Workflow\PolicyService;

class AssociationPolicyController extends Controller
{
    public function __construct(
        private readonly PolicyService $policyService
    ) {}

    public function index()
    {
        $settings = AssociationPolicySetting::orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return view('admin.policy-settings', compact('settings'));
    }

    public function update(UpdatePolicySettingsRequest $request)
    {
        $oldSettings = AssociationPolicySetting::pluck('value', 'key')->toArray();

        foreach ($request->input('settings', []) as $setting) {
            AssociationPolicySetting::where('key', $setting['key'])
                ->update([
                    'value' => $setting['value'],
                    'updated_by' => auth()->id(),
                ]);
        }

        // Clear the service cache
        $this->policyService->clearCache();

        $newSettings = AssociationPolicySetting::pluck('value', 'key')->toArray();

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'policy_settings_updated',
            'module' => 'settings',
            'description' => 'Updated association policy settings',
            'context_json' => [
                'old' => $oldSettings,
                'new' => $newSettings,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('workflow.settings.index')
            ->with('status', 'Policy settings updated successfully.');
    }
}