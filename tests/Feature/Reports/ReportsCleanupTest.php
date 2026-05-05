<?php

use App\Console\Commands\CleanupReportFiles;
use App\Models\GeneratedReport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RoleSeeder::class);
    Storage::fake('public');
});

test('cleanup archives old generated reports and removes files', function () {
    Storage::disk('public')->put('generated/reports/old-report.pdf', 'pdf-content');

    $report = GeneratedReport::factory()->create([
        'report_type' => 'expense',
        'format' => 'pdf',
        'status' => 'generated',
        'file_path' => 'generated/reports/old-report.pdf',
        'generated_at' => now()->subDays(120),
    ]);

    $this->artisan(CleanupReportFiles::class, ['--days' => 90])
        ->assertSuccessful()
        ->expectsOutputToContain('Archived 1 report');

    $report->refresh();
    expect($report->status)->toBe('archived');
    expect(Storage::disk('public')->exists('generated/reports/old-report.pdf'))->toBeFalse();
});

test('cleanup skips recent reports', function () {
    Storage::disk('public')->put('generated/reports/recent-report.pdf', 'content');

    $report = GeneratedReport::factory()->create([
        'report_type' => 'expense',
        'format' => 'pdf',
        'status' => 'generated',
        'file_path' => 'generated/reports/recent-report.pdf',
        'generated_at' => now()->subDays(10),
    ]);

    $this->artisan(CleanupReportFiles::class, ['--days' => 90])
        ->assertSuccessful()
        ->expectsOutputToContain('No old reports');

    $report->refresh();
    expect($report->status)->toBe('generated');
    expect(Storage::disk('public')->exists('generated/reports/recent-report.pdf'))->toBeTrue();
});
