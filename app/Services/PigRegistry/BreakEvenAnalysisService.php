<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;

class BreakEvenAnalysisService
{
    /**
     * Compute break-even advisory for planning purposes only.
     * Advisory does not mutate official financial records.
     *
     * @param  array<string, mixed>  $computed
     * @return array<string, mixed>
     */
    public function analyze(PigCycle $cycle, array $computed): array
    {
        $totalSales = (float) ($computed['total_sales'] ?? 0);
        $totalExpenses = (float) ($computed['total_expenses'] ?? 0);
        $netProfitOrLoss = (float) ($computed['net_profit_or_loss'] ?? 0);
        $salesSummary = $computed['sales_summary'] ?? [];

        $totalLiveWeight = (float) ($salesSummary['total_live_weight_kg'] ?? 0);
        $averagePricePerKg = $salesSummary['average_price_per_kg'] ?? null;
        $pigsSoldCount = (int) ($salesSummary['sale_count'] ?? 0);

        $revenueGap = round(max($totalExpenses - $totalSales, 0), 2);

        $breakEvenPricePerKg = $totalLiveWeight > 0
            ? round($totalExpenses / $totalLiveWeight, 2)
            : null;

        $projections = [];
        if ($breakEvenPricePerKg !== null && $totalLiveWeight > 0) {
            $pricePoints = $this->buildPricePoints($breakEvenPricePerKg);

            foreach ($pricePoints as $price) {
                $projectedRevenue = round($totalLiveWeight * $price, 2);
                $projectedProfit = round($projectedRevenue - $totalExpenses, 2);
                $marginPercent = $totalExpenses > 0 ? round(($projectedProfit / $totalExpenses) * 100, 1) : 0;

                $projections[] = [
                    'price_per_kg' => $price,
                    'projected_revenue' => $projectedRevenue,
                    'projected_profit_or_loss' => $projectedProfit,
                    'margin_percent' => $marginPercent,
                    'is_break_even' => $price === $breakEvenPricePerKg,
                ];
            }
        }

        $warnings = [];
        $recommendations = [];

        if ($totalLiveWeight <= 0) {
            $warnings[] = 'Live weight data is missing. Break-even per kg cannot be computed. Record live weight in sales to enable this advisory.';
        }

        if ($netProfitOrLoss < 0) {
            $recommendations[] = 'This cycle currently shows a loss. Review feed, medicine, or transport costs for possible savings.';
            $recommendations[] = "An additional ₱".number_format($revenueGap, 2)." in sales revenue is needed to break even.";
        } elseif ($netProfitOrLoss === 0.0 && $totalExpenses > 0) {
            $recommendations[] = 'This cycle is at break-even. Any additional sales or cost reduction will produce profit.';
        } elseif ($netProfitOrLoss > 0 && $totalExpenses > 0) {
            $marginPercent = round(($netProfitOrLoss / $totalExpenses) * 100, 1);
            $recommendations[] = "The cycle is profitable with a {$marginPercent}% margin on costs.";
        }

        if ($breakEvenPricePerKg !== null && $averagePricePerKg !== null) {
            if ($averagePricePerKg < $breakEvenPricePerKg) {
                $recommendations[] = "Current price of ₱{$averagePricePerKg}/kg is below the break-even of ₱{$breakEvenPricePerKg}/kg. Consider negotiating a better price.";
            } elseif ($averagePricePerKg >= $breakEvenPricePerKg) {
                $recommendations[] = "Current price of ₱{$averagePricePerKg}/kg covers costs at ₱{$breakEvenPricePerKg}/kg break-even.";
            }
        }

        return [
            'total_live_weight_kg' => $totalLiveWeight,
            'break_even_price_per_kg' => $breakEvenPricePerKg,
            'revenue_gap_to_break_even' => $revenueGap,
            'average_price_per_kg' => $averagePricePerKg,
            'pigs_sold_count' => $pigsSoldCount,
            'projections' => $projections,
            'warnings' => $warnings,
            'recommendations' => $recommendations,
            'has_live_weight_data' => $totalLiveWeight > 0,
            'is_planning_only' => true,
        ];
    }

    /**
     * Build price points around the break-even price for projection.
     *
     * @return list<float>
     */
    private function buildPricePoints(float $breakEvenPricePerKg): array
    {
        if ($breakEvenPricePerKg <= 0) {
            return [50.0, 80.0, 100.0, 120.0, 150.0];
        }

        $points = [
            round($breakEvenPricePerKg * 1.10, 0),
            round($breakEvenPricePerKg * 1.20, 0),
            round($breakEvenPricePerKg * 1.30, 0),
            round($breakEvenPricePerKg * 1.40, 0),
            round($breakEvenPricePerKg * 1.50, 0),
        ];

        return array_values(array_unique($points));
    }
}