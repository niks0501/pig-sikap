<?php

use App\Models\LiquidationReport;
use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function createLiquidationPdfRole(string $slug): int
{
    return DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeLiquidationPdfUser(string $roleSlug = 'secretary'): User
{
    $role = DB::table('roles')->where('slug', $roleSlug)->first();
    $roleId = $role?->id ?? createLiquidationPdfRole($roleSlug);

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
        'must_change_password' => false,
    ]);
}

function makeLiquidationPdfWithdrawal(User $officer): Withdrawal
{
    $meeting = Meeting::create([
        'title' => 'Monthly Feed Procurement Meeting',
        'date' => now()->subDay()->toDateString(),
        'location' => 'Barangay Hall',
        'agenda' => 'Approve feed purchase and withdrawal.',
        'minutes_summary' => 'Members approved feed purchase.',
        'status' => 'confirmed',
        'created_by' => $officer->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Buy feeds for pig cycle',
        'description' => 'Purchase feed using approved livelihood funds.',
        'status' => 'withdrawn',
        'created_by' => $officer->id,
    ]);

    $resolution->lineItems()->create([
        'category' => 'Feed',
        'description' => 'Pig grower pellets',
        'quantity' => 10,
        'unit' => 'sack',
        'unit_cost' => 1200,
        'total' => 12000,
        'sort_order' => 0,
    ]);

    $resolution->approvals()->create([
        'user_id' => $officer->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);

    $resolution->dswdSubmissions()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'notes' => 'Approved by DSWD.',
        'submitted_by' => $officer->id,
    ]);

    return $resolution->withdrawals()->create([
        'requested_by' => $officer->id,
        'amount' => 12000,
        'currency' => 'PHP',
        'status' => 'pending',
        'requested_at' => now(),
        'notes' => 'Withdrawal for feed procurement.',
    ]);
}

test('authorized officer can generate and store official liquidation pdf', function () {
    Storage::fake('public');

    $secretary = makeLiquidationPdfUser('secretary');
    $withdrawal = makeLiquidationPdfWithdrawal($secretary);

    $response = $this->actingAs($secretary)->postJson(
        route('workflow.withdrawals.report', $withdrawal),
        ['summary' => 'All funds were used for feed procurement.']
    );

    $response->assertOk()
        ->assertJsonPath('message', 'Liquidation report generated successfully.')
        ->assertJsonStructure(['report', 'preview_url', 'download_url']);

    $report = LiquidationReport::firstOrFail();

    expect($report->withdrawal_id)->toBe($withdrawal->id);
    expect($report->report_file_path)->toBe('workflow/liquidation-reports/liquidation-report-'.$withdrawal->id.'.pdf');
    expect($report->finalized_at)->not->toBeNull();

    Storage::disk('public')->assertExists($report->report_file_path);
});

test('preview route returns stored pdf inline', function () {
    Storage::fake('public');

    $secretary = makeLiquidationPdfUser('secretary');
    $withdrawal = makeLiquidationPdfWithdrawal($secretary);
    $this->actingAs($secretary)->postJson(route('workflow.withdrawals.report', $withdrawal));

    $report = LiquidationReport::firstOrFail();

    $response = $this->actingAs($secretary)->get(route('workflow.withdrawals.report.preview', [$withdrawal, $report]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename="liquidation-report-'.$withdrawal->id.'.pdf"');

    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('download route returns stored pdf as attachment', function () {
    Storage::fake('public');

    $secretary = makeLiquidationPdfUser('treasurer');
    $withdrawal = makeLiquidationPdfWithdrawal($secretary);
    $this->actingAs($secretary)->postJson(route('workflow.withdrawals.report', $withdrawal));

    $report = LiquidationReport::firstOrFail();

    $response = $this->actingAs($secretary)->get(route('workflow.withdrawals.report.download', [$withdrawal, $report]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="liquidation-report-'.$withdrawal->id.'.pdf"');
});

test('unauthorized role cannot preview or download liquidation pdf', function () {
    Storage::fake('public');

    $secretary = makeLiquidationPdfUser('secretary');
    $withdrawal = makeLiquidationPdfWithdrawal($secretary);
    $this->actingAs($secretary)->postJson(route('workflow.withdrawals.report', $withdrawal));

    $report = LiquidationReport::firstOrFail();
    $member = makeLiquidationPdfUser('member');

    $this->actingAs($member)
        ->get(route('workflow.withdrawals.report.preview', [$withdrawal, $report]))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('workflow.withdrawals.report.download', [$withdrawal, $report]))
        ->assertForbidden();
});

test('report generation is blocked when dswd approval is missing', function () {
    Storage::fake('public');

    $secretary = makeLiquidationPdfUser('secretary');
    $meeting = Meeting::create([
        'title' => 'Meeting without DSWD approval',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $secretary->id,
    ]);
    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'Resolution without DSWD approval',
        'status' => 'withdrawn',
        'created_by' => $secretary->id,
    ]);
    $resolution->lineItems()->create([
        'category' => 'Feed',
        'description' => 'Feed',
        'quantity' => 1,
        'unit' => 'sack',
        'unit_cost' => 1000,
        'total' => 1000,
        'sort_order' => 0,
    ]);
    $resolution->approvals()->create([
        'user_id' => $secretary->id,
        'is_approved' => true,
        'approved_at' => now(),
    ]);
    $withdrawal = $resolution->withdrawals()->create([
        'requested_by' => $secretary->id,
        'amount' => 1000,
        'currency' => 'PHP',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($secretary)
        ->postJson(route('workflow.withdrawals.report', $withdrawal))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('withdrawal');

    expect(LiquidationReport::count())->toBe(0);
});

test('generating liquidation report twice reuses one report record and file path', function () {
    Storage::fake('public');

    $secretary = makeLiquidationPdfUser('president');
    $withdrawal = makeLiquidationPdfWithdrawal($secretary);

    $this->actingAs($secretary)->postJson(route('workflow.withdrawals.report', $withdrawal), [
        'summary' => 'First report summary.',
    ])->assertOk();

    $firstReport = LiquidationReport::firstOrFail();
    $firstPath = $firstReport->report_file_path;

    $this->actingAs($secretary)->postJson(route('workflow.withdrawals.report', $withdrawal), [
        'summary' => 'Updated report summary.',
    ])->assertOk();

    expect(LiquidationReport::count())->toBe(1);
    expect($firstReport->fresh()->report_file_path)->toBe($firstPath);
    expect($firstReport->fresh()->summary)->toBe('Updated report summary.');
    Storage::disk('public')->assertExists($firstPath);
});
