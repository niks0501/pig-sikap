<?php

namespace App\Services\PigRegistry;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuickChartService
{
    private const QUICKCHART_URL = 'https://quickchart.io/chart';

    private const TIMEOUT_SECONDS = 8;

    private const CHART_WIDTH = 600;

    private const CHART_HEIGHT = 300;

    /**
     * Generate a chart image via QuickChart.io and return a base64 data URI.
     *
     * @param  'bar'|'pie'  $chartType
     * @param  array{labels?: list<string>, datasets?: list<array<string, mixed>>}  $chartData
     * @param  array<string, mixed>  $chartOptions
     * @return string  data:image/png;base64,... or empty string on failure
     */
    public function renderChart(string $chartType, array $chartData, array $chartOptions = []): string
    {
        $cacheKey = $this->cacheKey($chartType, $chartData);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($chartType, $chartData, $chartOptions) {
            $payload = [
                'width' => self::CHART_WIDTH,
                'height' => self::CHART_HEIGHT,
                'format' => 'png',
                'backgroundColor' => '#ffffff',
                'chart' => [
                    'type' => $chartType,
                    'data' => $chartData,
                    'options' => array_merge([
                        'responsive' => false,
                        'plugins' => [
                            'legend' => [
                                'position' => 'bottom',
                                'labels' => [
                                    'font' => ['size' => 11],
                                    'padding' => 16,
                                ],
                            ],
                        ],
                    ], $chartOptions),
                ],
            ];

            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->asJson()
                    ->post(self::QUICKCHART_URL, $payload);

                if ($response->successful()) {
                    $pngContent = $response->body();

                    return 'data:image/png;base64,'.base64_encode($pngContent);
                }

                Log::warning('QuickChart.io returned non-successful response.', [
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 200),
                ]);
            } catch (Throwable $exception) {
                Log::error('QuickChart.io request failed.', [
                    'message' => $exception->getMessage(),
                ]);
            }

            return '';
        });
    }

    /**
     * Build a cache key unique to the chart configuration.
     *
     * @param  array<string, mixed>  $data
     */
    private function cacheKey(string $type, array $data): string
    {
        return sprintf('quickchart.%s.%s', $type, md5(json_encode($data)));
    }
}
