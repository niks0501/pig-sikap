<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\SelectCanvassItemRequest;
use App\Http\Requests\Workflow\StoreCanvassRequest;
use App\Http\Requests\Workflow\UpdateCanvassRequest;
use App\Models\AuditTrail;
use App\Models\Canvass;
use App\Models\CanvassItem;
use App\Services\Workflow\CanvassingService;

class CanvassController extends Controller
{
    public function __construct(
        private readonly CanvassingService $canvassingService
    ) {}

    public function index()
    {
        $canvasses = Canvass::with(['items.supplier', 'creator', 'resolution', 'meeting'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('workflow.canvasses-index', compact('canvasses'));
    }

    public function create()
    {
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $resolutions = \App\Models\Resolution::orderByDesc('created_at')->get();
        $meetings = \App\Models\Meeting::orderByDesc('date')->get();

        return view('workflow.canvasses-create', compact('suppliers', 'resolutions', 'meetings'));
    }

    public function store(StoreCanvassRequest $request)
    {
        $canvass = $this->canvassingService->create(
            $request->validated(),
            $request->input('items', []),
            auth()->user()
        );

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'canvass_created',
            'module' => 'workflow',
            'description' => "Created canvass: {$canvass->title}",
            'context_json' => ['canvass_id' => $canvass->id],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('workflow.canvasses.show', $canvass)
            ->with('status', 'Canvass created successfully.');
    }

    public function show(Canvass $canvass)
    {
        $canvass->load(['items.supplier', 'creator', 'updater', 'resolution', 'meeting']);

        return view('workflow.canvasses-show', compact('canvass'));
    }

    public function edit(Canvass $canvass)
    {
        $canvass->load('items.supplier');
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $resolutions = \App\Models\Resolution::orderByDesc('created_at')->get();
        $meetings = \App\Models\Meeting::orderByDesc('date')->get();

        return view('workflow.canvasses-create', compact('canvass', 'suppliers', 'resolutions', 'meetings'));
    }

    public function update(UpdateCanvassRequest $request, Canvass $canvass)
    {
        $canvass = $this->canvassingService->update(
            $canvass,
            $request->validated(),
            $request->input('items', []),
            auth()->user()
        );

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'canvass_updated',
            'module' => 'workflow',
            'description' => "Updated canvass: {$canvass->title}",
            'context_json' => ['canvass_id' => $canvass->id],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('workflow.canvasses.show', $canvass)
            ->with('status', 'Canvass updated successfully.');
    }

    public function destroy(Canvass $canvass)
    {
        $title = $canvass->title;
        $canvass->delete();

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'canvass_deleted',
            'module' => 'workflow',
            'description' => "Deleted canvass: {$title}",
            'context_json' => ['canvass_title' => $title],
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return redirect()->route('workflow.canvasses.index')
            ->with('status', 'Canvass deleted successfully.');
    }

    public function selectItem(Canvass $canvass, CanvassItem $item, SelectCanvassItemRequest $request)
    {
        $this->canvassingService->selectItem($canvass, $item);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'canvass_item_selected',
            'module' => 'workflow',
            'description' => "Selected winning item for canvass: {$canvass->title}",
            'context_json' => [
                'canvass_id' => $canvass->id,
                'item_id' => $item->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return redirect()->route('workflow.canvasses.show', $canvass)
            ->with('status', 'Winner selected successfully.');
    }
}