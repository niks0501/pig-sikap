<?php

namespace App\Services\PigRegistry;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvService
{
    public const STORAGE_PATH = 'generated/reports/';

    /**
     * @param  list<string>  $headers
     * @param  list<array<int|string, mixed>>  $rows
     */
    public function stream(string $filePrefix, array $headers, array $rows): StreamedResponse
    {
        $fileName = sprintf('%s-%s.csv', $filePrefix, now()->format('YmdHis'));

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int|string, mixed>>  $rows
     * @return array{file_name: string, stored_path: string, content: string}
     */
    public function buildAndStore(string $filePrefix, array $headers, array $rows): array
    {
        $fileName = sprintf('%s-%s.csv', $filePrefix, now()->format('YmdHis'));
        $storedPath = self::STORAGE_PATH.$fileName;

        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $content = stream_get_contents($stream) ?: '';
        fclose($stream);

        Storage::disk('public')->put($storedPath, $content);

        return [
            'file_name' => $fileName,
            'stored_path' => $storedPath,
            'content' => $content,
        ];
    }
}
