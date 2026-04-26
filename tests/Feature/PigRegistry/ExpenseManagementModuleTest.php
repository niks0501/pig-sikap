<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\seed;
use function Pest\Laravel\withoutVite;

uses(RefreshDatabase::class);

beforeEach(function () {
    withoutVite();
    seed(RoleSeeder::class);
    Storage::fake('public');
});

function expenseUser(string $roleSlug, array $overrides = []): User
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

function expenseCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'EXP-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(30)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 8.50,
        'stage' => 'Growing',
        'status' => 'Active',
        'has_pig_profiles' => false,
        'notes' => 'Expense module cycle',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

/**
 * @return array<string, mixed>
 */
function expensePayload(PigCycle $cycle, array $overrides = []): array
{
    return [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1450.50',
        'expense_date' => now()->toDateString(),
        'notes' => 'Two sacks of feed for weekly allocation.',
        ...$overrides,
    ];
}

test('expense routes are accessible to president treasurer and secretary but forbidden for officer', function () {
    $president = expenseUser('president');
    $treasurer = expenseUser('treasurer');
    $secretary = expenseUser('secretary');
    $officer = expenseUser('officer');

    actingAs($president)->get(route('expenses.index'))->assertOk();
    actingAs($treasurer)->get(route('expenses.index'))->assertOk();
    actingAs($secretary)->get(route('expenses.index'))->assertOk();

    actingAs($officer)->get(route('expenses.index'))->assertForbidden();
});

test('authorized user can store expense with receipt and audit entry is recorded', function () {
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    $response = actingAs($treasurer)->post(route('expenses.store'), expensePayload($cycle, [
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ]));

    $expense = PigCycleExpense::query()->latest('id')->first();

    expect($expense)->not->toBeNull();

    $response
        ->assertRedirect(route('expenses.show', $expense));

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $expense->id,
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'notes' => 'Two sacks of feed for weekly allocation.',
        'created_by' => $treasurer->id,
    ]);

    expect($expense->receipt_path)->not->toBeNull();
    expect(Storage::disk('public')->exists((string) $expense->receipt_path))->toBeTrue();

    assertDatabaseHas('audit_trails', [
        'action' => 'expense_created',
        'module' => 'expense_management',
        'user_id' => $treasurer->id,
    ]);
});

test('store request rejects archived cycle and future date', function () {
    $president = expenseUser('president');
    $archivedCycle = expenseCycle($president, [
        'stage' => 'Completed',
        'status' => 'Closed',
    ]);

    $response = actingAs($president)
        ->from(route('expenses.create'))
        ->post(route('expenses.store'), expensePayload($archivedCycle, [
            'expense_date' => now()->addDay()->toDateString(),
        ]));

    $response
        ->assertRedirect(route('expenses.create'))
        ->assertSessionHasErrors(['batch_id', 'expense_date']);

    assertDatabaseMissing('pig_cycle_expenses', [
        'batch_id' => $archivedCycle->id,
        'notes' => 'Two sacks of feed for weekly allocation.',
    ]);
});

test('authorized users can update expense and only president can delete', function () {
    $president = expenseUser('president');
    $secretary = expenseUser('secretary');
    $treasurer = expenseUser('treasurer');

    $cycle = expenseCycle($president);

    $expense = PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'medicine',
        'amount' => 550,
        'expense_date' => now()->subDay()->toDateString(),
        'notes' => 'Initial medicine purchase',
        'created_by' => $president->id,
    ]);

    $updateResponse = actingAs($secretary)->put(route('expenses.update', $expense), [
        'batch_id' => $cycle->id,
        'category' => 'transport',
        'amount' => 650,
        'expense_date' => now()->toDateString(),
        'notes' => 'Updated transport details',
    ]);

    $updateResponse
        ->assertRedirect(route('expenses.show', $expense));

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $expense->id,
        'category' => 'transport',
        'notes' => 'Updated transport details',
    ]);

    actingAs($treasurer)
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();

    actingAs($president)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    assertDatabaseMissing('pig_cycle_expenses', [
        'id' => $expense->id,
    ]);

    assertDatabaseHas('audit_trails', [
        'action' => 'expense_deleted',
        'module' => 'expense_management',
        'user_id' => $president->id,
    ]);
});

test('summary page shows computed totals for selected scope', function () {
    $president = expenseUser('president');
    $cycle = expenseCycle($president, ['batch_code' => 'EXP-SUM-001']);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 1000,
        'expense_date' => now()->toDateString(),
        'notes' => 'Feed total',
        'created_by' => $president->id,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'transport',
        'amount' => 250,
        'expense_date' => now()->toDateString(),
        'notes' => 'Transport total',
        'created_by' => $president->id,
    ]);

    actingAs($president)
        ->get(route('expenses.summary', ['timeframe' => 'this_month', 'cycle_id' => $cycle->id]))
        ->assertOk()
        ->assertSee('Php 1,250.00')
        ->assertSee('Feed')
        ->assertSee('Transport');
});
