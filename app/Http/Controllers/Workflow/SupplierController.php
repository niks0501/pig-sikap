<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreSupplierRequest;
use App\Http\Requests\Workflow\UpdateSupplierRequest;
use App\Models\AuditTrail;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::with('creator')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('workflow.suppliers-index', compact('suppliers'));
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = Supplier::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'supplier_created',
            'module' => 'workflow',
            'description' => "Created supplier: {$supplier->name}",
            'context_json' => ['supplier_id' => $supplier->id],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('workflow.suppliers.index')
            ->with('status', "Supplier '{$supplier->name}' created successfully.");
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $oldData = $supplier->only(['name', 'contact_person', 'contact_number']);

        $supplier->update($request->validated());

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'supplier_updated',
            'module' => 'workflow',
            'description' => "Updated supplier: {$supplier->name}",
            'context_json' => [
                'supplier_id' => $supplier->id,
                'old' => $oldData,
                'new' => $supplier->only(['name', 'contact_person', 'contact_number']),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('workflow.suppliers.index')
            ->with('status', "Supplier '{$supplier->name}' updated successfully.");
    }

    public function destroy(Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'supplier_deleted',
            'module' => 'workflow',
            'description' => "Deleted supplier: {$name}",
            'context_json' => ['supplier_name' => $name],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return redirect()->route('workflow.suppliers.index')
            ->with('status', "Supplier '{$name}' deleted successfully.");
    }
}