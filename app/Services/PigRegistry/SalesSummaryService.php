<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycleSale;
use Illuminate\Database\Eloquent\Builder;

class SalesSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFromQuery(Builder $query): array
    {
        $totalAmount = round((float) (clone $query)->sum('amount'), 2);
        $totalPaid = round((float) (clone $query)->sum('amount_paid'), 2);
        $totalBalance = round(max($totalAmount - $totalPaid, 0), 2);
        $totalPigsSold = (int) (clone $query)->sum('pigs_sold');

        $byStatus = (clone $query)
            ->reorder()
            ->selectRaw('payment_status, COUNT(*) as entry_count, SUM(amount) as total_amount, SUM(amount_paid) as total_paid')
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        $statusSummary = [];

        foreach (PigCycleSale::PAYMENT_STATUSES as $status) {
            $row = $byStatus->get($status);
            $statusSummary[$status] = [
                'entry_count' => (int) ($row?->entry_count ?? 0),
                'total_amount' => round((float) ($row?->total_amount ?? 0), 2),
                'total_paid' => round((float) ($row?->total_paid ?? 0), 2),
            ];
        }

        return [
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'total_pigs_sold' => $totalPigsSold,
            'by_status' => $statusSummary,
        ];
    }
}
