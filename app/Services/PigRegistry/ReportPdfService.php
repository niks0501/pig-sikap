<?php

namespace App\Services\PigRegistry;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportPdfService
{
    public const STORAGE_PATH = 'generated/reports/';

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{labels?: list<string>, datasets?: list<array<string, mixed>>}>  $charts
     * @return array{file_name: string, content: string, stored_path: string}
     */
    public function build(string $view, array $data, string $filePrefix, array $charts = []): array
    {
        if (! empty($charts)) {
            $data['chartImages'] = $this->renderChartImages($charts);
        }

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

    /**
     * @param  array<string, array{labels?: list<string>, datasets?: list<array<string, mixed>>}>  $charts
     * @return array<string, string>
     */
    private function renderChartImages(array $charts): array
    {
        $service = app(QuickChartService::class);
        $images = [];

        foreach ($charts as $key => $chartData) {
            if (empty($chartData['labels'] ?? null) || empty($chartData['datasets'] ?? null)) {
                continue;
            }

            $chartType = str_contains($key, 'Pie') || str_contains($key, 'ByCause') ? 'pie' : 'bar';
            $images[$key] = $service->renderChart($chartType, $chartData);
        }

        return $images;
    }
}
