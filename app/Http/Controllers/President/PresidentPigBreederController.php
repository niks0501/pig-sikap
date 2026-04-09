<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigBreederRequest;
use App\Models\PigBreeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresidentPigBreederController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $breeders = PigBreeder::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('breeder_code', 'like', "%{$search}%")
                        ->orWhere('name_or_tag', 'like', "%{$search}%")
                        ->orWhere('reproductive_status', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $breeders->items(),
                'meta' => [
                    'current_page' => $breeders->currentPage(),
                    'last_page' => $breeders->lastPage(),
                    'per_page' => $breeders->perPage(),
                    'total' => $breeders->total(),
                ],
            ]);
        }

        return view('breeders.create', [
            'breeders' => $breeders,
            'search' => $search,
            'reproductiveStatuses' => PigBreeder::REPRODUCTIVE_STATUSES,
        ]);
    }

    public function store(StorePigBreederRequest $request): RedirectResponse
    {
        $breeder = PigBreeder::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $this->recordAudit(
            $request,
            'breeder_created',
            "Created breeder {$breeder->breeder_code} ({$breeder->name_or_tag})."
        );

        return redirect()
            ->route('breeders.create')
            ->with('status', "Breeder {$breeder->breeder_code} added to registry.");
    }
}
