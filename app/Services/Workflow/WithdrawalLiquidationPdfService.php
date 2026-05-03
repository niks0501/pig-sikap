<?php

namespace App\Services\Workflow;

use App\Models\LiquidationReport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the official liquidation PDF used after a valid withdrawal.
 */
class WithdrawalLiquidationPdfService
{
    public const STORAGE_PATH = 'workflow/liquidation-reports/';

    /**
     * @return array{file_name: string, content: string, stored_path: string}
     */
    public function buildAndStore(LiquidationReport $report): array
    {
        $report->loadMissing([
            'generator:id,name',
            'withdrawal:id,resolution_id,requested_by,amount,currency,bank_account,proof_file_path,status,requested_at,completed_at,notes',
            'withdrawal.requester:id,name',
            'withdrawal.resolution:id,meeting_id,title,description,status,created_by,created_at',
            'withdrawal.resolution.creator:id,name',
            'withdrawal.resolution.meeting:id,title,date,location,agenda,minutes_summary,status',
            'withdrawal.resolution.lineItems',
            'withdrawal.resolution.dswdSubmission',
            'withdrawal.resolution.dswdSubmission.submitter:id,name',
            'withdrawal.expenses',
        ]);

        $withdrawal = $report->withdrawal;
        $resolution = $withdrawal->resolution;
        $lineItems = $resolution->lineItems;
        $actualExpenses = $withdrawal->expenses;
        $budgetTotal = (float) $lineItems->sum('total');
        $actualTotal = (float) $actualExpenses->sum('amount');
        $variance = $budgetTotal - $actualTotal;
        $generatedAt = now();

        $pdf = Pdf::loadView('pdf.workflow.liquidation-report', [
            'associationName' => 'Elite Visionaries of Humayingan SLP Association',
            'associationAddress' => 'Brgy. Humayingan, Lian, Batangas',
            'report' => $report,
            'withdrawal' => $withdrawal,
            'resolution' => $resolution,
            'meeting' => $resolution->meeting,
            'dswdSubmission' => $resolution->dswdSubmission,
            'lineItems' => $lineItems,
            'actualExpenses' => $actualExpenses,
            'budgetTotal' => $budgetTotal,
            'actualTotal' => $actualTotal,
            'variance' => $variance,
            'isOverBudget' => $variance < 0,
            'preparedBy' => $report->generator,
            'treasurer' => $this->officerByRole('treasurer'),
            'president' => $this->officerByRole('president'),
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf('liquidation-report-%d.pdf', $withdrawal->id);
        $content = $pdf->output();
        $storedPath = self::STORAGE_PATH.$fileName;

        Storage::disk('public')->put($storedPath, $content);

        return [
            'file_name' => $fileName,
            'content' => $content,
            'stored_path' => $storedPath,
        ];
    }

    private function officerByRole(string $roleSlug): ?User
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('slug', $roleSlug))
            ->orderBy('name')
            ->first(['id', 'name']);
    }
}
