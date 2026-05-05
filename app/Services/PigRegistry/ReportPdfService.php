<?php

namespace App\Services\PigRegistry;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportPdfService
{
    public const STORAGE_PATH = 'generated/reports/';

    /**
     * @param  array<string, mixed>  $data
     * @return array{file_name: string, content: string, stored_path: string}
     */
    public function build(string $view, array $data, string $filePrefix): array
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');
        $fileName = sprintf('%s-%s.pdf', $filePrefix, now()->format('YmdHis'));
        $content = $pdf->output();
        $storedPath = self::STORAGE_PATH.$fileName;

        Storage::disk('public')->put($storedPath, $content);

        return [
            'file_name' => $fileName,
            'content' => $content,
            'stored_path' => $storedPath,
        ];
    }

    public function ensureStoragePath(): void
    {
        Storage::disk('public')->makeDirectory(self::STORAGE_PATH);
    }
}
