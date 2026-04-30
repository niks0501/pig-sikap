<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigBuyerRequest;
use App\Http\Requests\PigRegistry\UpdatePigBuyerRequest;
use App\Models\PigBuyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresidentPigBuyerController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = PigBuyer::query()->orderBy('name');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('contact_number', 'like', '%'.$search.'%');
            });
        }

        $buyers = $query
            ->limit(50)
            ->get(['id', 'name', 'email', 'contact_number', 'address', 'notes']);

        return response()->json([
            'buyers' => $buyers->values(),
        ]);
    }

    public function store(StorePigBuyerRequest $request): JsonResponse
    {
        $buyer = PigBuyer::query()->create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'contact_number' => $request->input('contact_number'),
            'address' => $request->input('address'),
            'notes' => $request->input('notes'),
            'created_by' => $request->user()?->id,
        ]);

        $this->recordAudit(
            $request,
            'buyer_created',
            "Created buyer {$buyer->name}.",
            'sales_management',
            [
                'buyer_id' => $buyer->id,
                'name' => $buyer->name,
            ]
        );

        return response()->json([
            'message' => 'Buyer saved successfully.',
            'buyer' => $buyer,
        ], 201);
    }

    public function update(UpdatePigBuyerRequest $request, PigBuyer $buyer): JsonResponse
    {
        $buyer->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'contact_number' => $request->input('contact_number'),
            'address' => $request->input('address'),
            'notes' => $request->input('notes'),
            'updated_by' => $request->user()?->id,
        ]);

        $this->recordAudit(
            $request,
            'buyer_updated',
            "Updated buyer {$buyer->name}.",
            'sales_management',
            [
                'buyer_id' => $buyer->id,
                'name' => $buyer->name,
            ]
        );

        return response()->json([
            'message' => 'Buyer updated successfully.',
            'buyer' => $buyer,
        ]);
    }
}
