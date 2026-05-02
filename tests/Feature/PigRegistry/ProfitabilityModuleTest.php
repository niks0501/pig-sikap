<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\seed;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    seed(RoleSeeder::class);
});

function profitabilityUser(string $roleSlug, array $overrides = []): User
{
    $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
        ...$overrides,
    ]);
}

function profitabilityCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'PM-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(120)->toDateString(),
        'initial_count' => 10,
        'current_count' => 0,
        'average_weight' => 85.00,
        'stage' => 'Completed',
        'status' => 'Sold',
        'has_pig_profiles' => false,
        'notes' => 'Profitability feature test cycle',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

function profitabilityExpense(PigCycle $cycle, float $amount, string $category = 'feed'): PigCycleExpense
{
    return PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => $category,
        'amount' => $amount,
        'expense_date' => now()->toDateString(),
        'created_by' => $cycle->created_by,
    ]);
}

function profitabilitySale(PigCycle $cycle, float $amount, array $overrides = []): PigCycleSale
{
    return PigCycleSale::query()->create([
        'batch_id' => $cycle->id,
        'pigs_sold' => 2,
        'amount' => $amount,
        'sale_date' => now()->toDateString(),
        'sale_method' => 'per_head',
        'price_per_head' => $amount / 2,
        'payment_status' => 'paid',
        'amount_paid' => $amount,
        'created_by' => $cycle->created_by,
        ...$overrides,
    ]);
}

test('profitability pages are visible to finance officers but forbidden for regular officer', function () {
    $president = profitabilityUser('president');
    $treasurer = profitabilityUser('treasurer');
    $secretary = profitabilityUser('secretary');
    $officer = profitabilityUser('officer');

    actingAs($president)->get(route('profitability.index'))->assertOk();
    actingAs($treasurer)->get(route('profitability.index'))->assertOk();
    actingAs($secretary)->get(route('profitability.index'))->assertOk();
    actingAs($officer)->get(route('profitability.index'))->assertForbidden();
});

test('cycle profitability page renders computed cycle values', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->get(route('profitability.show', $cycle))
        ->assertOk()
        ->assertViewIs('profitability.show')
        ->assertViewHas('profitability', fn (array $profitability): bool => $profitability['net_profit_or_loss'] === 3000.0)
        ->assertSee($cycle->batch_code)
        ->assertSee('₱3,000.00');
});

test('live computation changes when an expense is edited before finalization', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    $expense = profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->get(route('profitability.show', $cycle))
        ->assertViewHas('profitability', fn (array $profitability): bool => $profitability['net_profit_or_loss'] === 3000.0);

    $expense->update(['amount' => 1500]);

    actingAs($president)
        ->get(route('profitability.show', $cycle))
        ->assertViewHas('profitability', fn (array $profitability): bool => $profitability['net_profit_or_loss'] === 2500.0);
});

test('finalized snapshot remains unchanged when expenses change later', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle), [
            'notes' => 'Approved after reviewing cycle records.',
        ])
        ->assertRedirect(route('profitability.sharing', $cycle));

    assertDatabaseHas('profitability_snapshots', [
        'pig_cycle_id' => $cycle->id,
        'gross_income' => '4000.00',
        'total_expenses' => '1000.00',
        'net_profit_or_loss' => '3000.00',
        'caretaker_share' => '1500.00',
    ]);

    profitabilityExpense($cycle, 2000, 'medicine');

    actingAs($president)
        ->get(route('profitability.sharing', $cycle))
        ->assertOk()
        ->assertViewHas('profitability', fn (array $profitability): bool => $profitability['net_profit_or_loss'] === 3000.0)
        ->assertSee('Finalized Official Snapshot');
});

test('only president can finalize and active cycles cannot be finalized', function () {
    $president = profitabilityUser('president');
    $treasurer = profitabilityUser('treasurer');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($treasurer)
        ->post(route('profitability.finalize', $cycle))
        ->assertForbidden();

    assertDatabaseMissing('profitability_snapshots', [
        'pig_cycle_id' => $cycle->id,
    ]);

    $activeCycle = profitabilityCycle($president, [
        'batch_code' => 'PM-ACTIVE',
        'stage' => 'For Sale',
        'status' => 'Ready for Sale',
        'current_count' => 2,
    ]);
    profitabilityExpense($activeCycle, 1000);
    profitabilitySale($activeCycle, 4000);

    actingAs($president)
        ->from(route('profitability.sharing', $activeCycle))
        ->post(route('profitability.finalize', $activeCycle))
        ->assertRedirect(route('profitability.sharing', $activeCycle))
        ->assertSessionHasErrors(['cycle']);
});

test('snapshot show page displays finalized values', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle));

    $snapshot = $cycle->profitabilitySnapshot;

    actingAs($president)
        ->get(route('profitability.snapshots.show', $snapshot))
        ->assertOk()
        ->assertSee('Finalized Official Snapshot')
        ->assertSee('₱3,000.00');
});

test('re-finalization creates version 2 with reason', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    $sale = profitabilitySale($cycle, 4000);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle), ['notes' => 'First version']);

    $sale->update(['amount' => 5000, 'amount_paid' => 5000]);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle), [
            're_finalize' => 1,
            're_finalize_reason_code' => 'corrected_sale',
            're_finalize_reason_notes' => 'Customer paid additional amount',
            'notes' => 'Updated after additional payment',
        ])
        ->assertRedirect(route('profitability.sharing', $cycle));

    assertDatabaseHas('profitability_snapshots', [
        'pig_cycle_id' => $cycle->id,
        'version_number' => 2,
        'is_current' => true,
        're_finalize_reason_code' => 'corrected_sale',
    ]);
});

test('data changed after finalization is visible in sharing view', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    $sale = profitabilitySale($cycle, 4000);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle));

    $sale->update(['amount' => 5000, 'amount_paid' => 5000]);

    actingAs($president)
        ->get(route('profitability.sharing', $cycle))
        ->assertOk()
        ->assertSee('Data Changed After Finalization');
});

test('treasurer can view but cannot finalize', function () {
    $treasurer = profitabilityUser('treasurer');
    $cycle = profitabilityCycle($treasurer);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($treasurer)
        ->get(route('profitability.show', $cycle))
        ->assertOk();

    actingAs($treasurer)
        ->get(route('profitability.sharing', $cycle))
        ->assertOk();

    actingAs($treasurer)
        ->post(route('profitability.finalize', $cycle))
        ->assertForbidden();
});

test('secretary can view sharing and snapshot page', function () {
    $secretary = profitabilityUser('secretary');
    $cycle = profitabilityCycle($secretary);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    $president = profitabilityUser('president');
    $cycle->update(['created_by' => $president->id]);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle));

    $snapshot = $cycle->profitabilitySnapshot;

    actingAs($secretary)
        ->get(route('profitability.snapshots.show', $snapshot))
        ->assertOk();
});

test('draft report preview returns PDF response', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->get(route('profitability.report.preview', $cycle))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('snapshot report download returns PDF attachment', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->post(route('profitability.finalize', $cycle));

    $snapshot = $cycle->profitabilitySnapshot;

    actingAs($president)
        ->get(route('profitability.snapshots.report.download', $snapshot))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition');
});

test('show view renders break-even advisory', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000, [
        'sale_method' => 'live_weight',
        'live_weight_kg' => 200.00,
        'price_per_kg' => 20.00,
    ]);

    actingAs($president)
        ->get(route('profitability.show', $cycle))
        ->assertOk()
        ->assertSee('Break-even Advisory');
});

test('show view shows validation checklist for president', function () {
    $president = profitabilityUser('president');
    $cycle = profitabilityCycle($president);
    profitabilityExpense($cycle, 1000);
    profitabilitySale($cycle, 4000);

    actingAs($president)
        ->get(route('profitability.show', $cycle))
        ->assertOk()
        ->assertSee('Finalization Checklist');
});