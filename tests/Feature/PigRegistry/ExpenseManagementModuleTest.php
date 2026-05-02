<?php

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\seed;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    seed(RoleSeeder::class);
    Storage::fake('public');
    $this->travelTo(Carbon::create(2026, 4, 30, 12, 0, 0));
});

afterEach(function () {
    $this->travelBack();
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

test('authorized user can store expense with quantity unit cost and computed total', function () {
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    $response = actingAs($treasurer)->post(route('expenses.store'), expensePayload($cycle, [
        'quantity' => '3',
        'unit' => 'sack',
        'unit_cost' => '625.50',
        'amount' => '1.00',
        'notes' => 'Three sacks of grower feed.',
    ]));

    $expense = PigCycleExpense::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('expenses.show', $expense));

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $expense->id,
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'quantity' => '3.00',
        'unit' => 'sack',
        'unit_cost' => '625.50',
        'amount' => '1876.50',
        'notes' => 'Three sacks of grower feed.',
    ]);
});

test('authorized user can store direct amount only for lump sum expense', function () {
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    actingAs($treasurer)->post(route('expenses.store'), expensePayload($cycle, [
        'category' => 'emergency',
        'amount' => '500.00',
        'notes' => 'Emergency fund release.',
    ]))->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('pig_cycle_expenses', [
        'batch_id' => $cycle->id,
        'category' => 'emergency',
        'quantity' => null,
        'unit' => null,
        'unit_cost' => null,
        'amount' => '500.00',
        'notes' => 'Emergency fund release.',
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
        'quantity' => '2',
        'unit' => 'trip',
        'unit_cost' => '325.25',
        'amount' => 1,
        'expense_date' => now()->toDateString(),
        'notes' => 'Updated transport details',
    ]);

    $updateResponse
        ->assertRedirect(route('expenses.show', $expense));

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $expense->id,
        'category' => 'transport',
        'quantity' => '2.00',
        'unit' => 'trip',
        'unit_cost' => '325.25',
        'amount' => '650.50',
        'notes' => 'Updated transport details',
        'updated_by' => $secretary->id,
    ]);

    actingAs($treasurer)
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();

    actingAs($president)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    assertDatabaseMissing('pig_cycle_expenses', [
        'id' => $expense->id,
        'deleted_at' => null,
    ]);

    assertDatabaseHas('audit_trails', [
        'action' => 'expense_deleted',
        'module' => 'expense_management',
        'user_id' => $president->id,
    ]);
});

test('authorized users can duplicate expenses with a valid non-future date', function () {
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    $expense = PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 900,
        'expense_date' => now()->subDay()->toDateString(),
        'notes' => 'Recurring feed purchase',
        'created_by' => $treasurer->id,
    ]);

    actingAs($treasurer)
        ->postJson(route('expenses.duplicate', $expense), [
            'expense_date' => now()->toDateString(),
        ])
        ->assertOk()
        ->assertJsonStructure(['message', 'redirect_url']);

    $duplicatedExpense = PigCycleExpense::query()
        ->where('id', '!=', $expense->id)
        ->firstOrFail();

    expect($duplicatedExpense->expense_date?->toDateString())->toBe(now()->toDateString());

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $duplicatedExpense->id,
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 900,
        'notes' => 'Recurring feed purchase',
        'created_by' => $treasurer->id,
        'updated_by' => $treasurer->id,
    ]);

    actingAs($treasurer)
        ->postJson(route('expenses.duplicate', $expense), [
            'expense_date' => now()->addDay()->toDateString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expense_date']);
});

test('bulk delete is president only and soft deletes selected expenses', function () {
    $president = expenseUser('president');
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($president);

    $expenses = collect([
        PigCycleExpense::query()->create([
            'batch_id' => $cycle->id,
            'category' => 'feed',
            'amount' => 400,
            'expense_date' => now()->toDateString(),
            'notes' => 'Bulk feed expense',
            'created_by' => $president->id,
        ]),
        PigCycleExpense::query()->create([
            'batch_id' => $cycle->id,
            'category' => 'transport',
            'amount' => 150,
            'expense_date' => now()->toDateString(),
            'notes' => 'Bulk transport expense',
            'created_by' => $president->id,
        ]),
    ]);

    actingAs($treasurer)
        ->postJson(route('expenses.bulk-delete'), [
            'ids' => $expenses->pluck('id')->all(),
        ])
        ->assertForbidden();

    foreach ($expenses as $expense) {
        assertDatabaseHas('pig_cycle_expenses', [
            'id' => $expense->id,
            'deleted_at' => null,
        ]);
    }

    actingAs($president)
        ->postJson(route('expenses.bulk-delete'), [
            'ids' => $expenses->pluck('id')->all(),
        ])
        ->assertOk()
        ->assertJsonPath('message', '2 expense record(s) deleted successfully.');

    foreach ($expenses as $expense) {
        assertDatabaseMissing('pig_cycle_expenses', [
            'id' => $expense->id,
            'deleted_at' => null,
        ]);
    }
});

test('summary page shows computed totals for selected scope', function () {
    $president = expenseUser('president');
    $cycle = expenseCycle($president, ['batch_code' => 'EXP-SUM-001']);

    actingAs($president)->post(route('expenses.store'), [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1000.00',
        'expense_date' => now()->toDateString(),
        'notes' => 'Feed total',
    ])->assertRedirect()->assertSessionHasNoErrors();

    actingAs($president)->post(route('expenses.store'), [
        'batch_id' => $cycle->id,
        'category' => 'transport',
        'amount' => '250.00',
        'expense_date' => now()->toDateString(),
        'notes' => 'Transport total',
    ])->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('pig_cycle_expenses', [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1000.00',
        'notes' => 'Feed total',
    ]);

    assertDatabaseHas('pig_cycle_expenses', [
        'batch_id' => $cycle->id,
        'category' => 'transport',
        'amount' => '250.00',
        'notes' => 'Transport total',
    ]);

    actingAs($president)
        ->get(route('expenses.summary', ['timeframe' => 'this_month', 'cycle_id' => $cycle->id]))
        ->assertOk()
        ->assertSee('Php 1,250.00')
        ->assertSee('Qty / Unit')
        ->assertSee('Unit Cost')
        ->assertSee('Total Amount')
        ->assertSee('Feed')
        ->assertSee('Transport');
});

test('json store remembers user expense defaults', function () {
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    $response = actingAs($treasurer)->postJson(route('expenses.store'), expensePayload($cycle, [
        'category' => 'medicine',
        'amount' => '725.00',
        'expense_date' => now()->toDateString(),
    ]));

    $expense = PigCycleExpense::query()->latest('id')->firstOrFail();

    $response
        ->assertCreated()
        ->assertJsonPath('expense.id', $expense->id)
        ->assertJsonPath('preferences.last_category', 'medicine')
        ->assertJsonPath('preferences.last_cycle_id', $cycle->id);

    assertDatabaseHas('user_expense_preferences', [
        'user_id' => $treasurer->id,
        'preference_key' => 'last_category',
        'preference_value' => 'medicine',
    ]);

    assertDatabaseHas('user_expense_preferences', [
        'user_id' => $treasurer->id,
        'preference_key' => 'last_cycle_id',
        'preference_value' => (string) $cycle->id,
    ]);
});

test('authorized users can fetch and update expense preferences', function () {
    $secretary = expenseUser('secretary');
    $cycle = expenseCycle($secretary);

    actingAs($secretary)
        ->putJson(route('expenses.preferences.update'), [
            'last_category' => 'transport',
            'last_cycle_id' => $cycle->id,
            'preset_amounts' => [250, 750, 1500],
        ])
        ->assertOk()
        ->assertJsonPath('preferences.last_category', 'transport')
        ->assertJsonPath('preferences.last_cycle_id', $cycle->id)
        ->assertJsonPath('preferences.preset_amounts.1', 750);

    actingAs($secretary)
        ->getJson(route('expenses.preferences'))
        ->assertOk()
        ->assertJsonPath('preferences.last_category', 'transport')
        ->assertJsonPath('preferences.preset_amounts.2', 1500);
});

test('recent templates return last five unique expenses for the user', function () {
    $treasurer = expenseUser('treasurer');
    $otherUser = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 500,
        'expense_date' => now()->subDays(2)->toDateString(),
        'notes' => 'Starter feed',
        'created_by' => $treasurer->id,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 500,
        'expense_date' => now()->toDateString(),
        'notes' => 'Starter feed',
        'created_by' => $treasurer->id,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'transport',
        'amount' => 300,
        'expense_date' => now()->subDay()->toDateString(),
        'notes' => 'Motor fare',
        'created_by' => $treasurer->id,
    ]);

    PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'medicine',
        'amount' => 900,
        'expense_date' => now()->toDateString(),
        'notes' => 'Other user medicine',
        'created_by' => $otherUser->id,
    ]);

    actingAs($treasurer)
        ->getJson(route('expenses.recent-templates'))
        ->assertOk()
        ->assertJsonCount(2, 'templates')
        ->assertJsonPath('templates.0.notes', 'Starter feed')
        ->assertJsonPath('templates.1.notes', 'Motor fare');
});

test('legacy expenses with null quantity unit and unit_cost still display correctly in index show and summary', function () {
    $president = expenseUser('president');
    $cycle = expenseCycle($president);

    $legacy = PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'emergency',
        'amount' => '375.00',
        'expense_date' => now()->toDateString(),
        'notes' => 'Old legacy expense without structured fields',
        'created_by' => $president->id,
        'quantity' => null,
        'unit' => null,
        'unit_cost' => null,
    ]);

    actingAs($president)
        ->get(route('expenses.index'))
        ->assertOk();

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $legacy->id,
        'amount' => '375.00',
        'quantity' => null,
        'unit' => null,
        'unit_cost' => null,
    ]);

    actingAs($president)
        ->get(route('expenses.show', $legacy))
        ->assertOk()
        ->assertSee('Old legacy expense without structured fields');

    actingAs($president)
        ->get(route('expenses.summary', ['cycle_id' => $cycle->id]))
        ->assertOk()
        ->assertViewHas('summary', function (array $summary) {
            return $summary['total_amount'] === 375.0
                && $summary['by_category']['emergency'] === 375.0;
        });
});

test('structured input overrides manual amount on update regardless of user provided total', function () {
    $treasurer = expenseUser('treasurer');
    $cycle = expenseCycle($treasurer);

    $expense = PigCycleExpense::query()->create([
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => 500,
        'expense_date' => now()->subDay()->toDateString(),
        'notes' => 'Original feed',
        'created_by' => $treasurer->id,
    ]);

    actingAs($treasurer)->put(route('expenses.update', $expense), [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'quantity' => '5',
        'unit' => 'sack',
        'unit_cost' => '200.00',
        'amount' => '99.99',
        'expense_date' => now()->toDateString(),
        'notes' => 'Updated with structured override',
    ])->assertRedirect();

    assertDatabaseHas('pig_cycle_expenses', [
        'id' => $expense->id,
        'quantity' => '5.00',
        'unit' => 'sack',
        'unit_cost' => '200.00',
        'amount' => '1000.00',
        'notes' => 'Updated with structured override',
    ]);
});

test('summary view includes month over month comparison data', function () {
    $president = expenseUser('president');
    $cycle = expenseCycle($president);

    actingAs($president)->post(route('expenses.store'), [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1000.00',
        'expense_date' => now()->subMonthNoOverflow()->toDateString(),
        'notes' => 'Last month feed',
    ])->assertRedirect()->assertSessionHasNoErrors();

    actingAs($president)->post(route('expenses.store'), [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1250.00',
        'expense_date' => now()->toDateString(),
        'notes' => 'This month feed',
    ])->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('pig_cycle_expenses', [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1000.00',
        'notes' => 'Last month feed',
    ]);

    assertDatabaseHas('pig_cycle_expenses', [
        'batch_id' => $cycle->id,
        'category' => 'feed',
        'amount' => '1250.00',
        'notes' => 'This month feed',
    ]);

    actingAs($president)
        ->get(route('expenses.summary', ['cycle_id' => $cycle->id]))
        ->assertOk()
        ->assertViewHas('summary', function (array $summary): bool {
            return ($summary['month_over_month']['this_month_total'] ?? null) === 1250.0
                && ($summary['month_over_month']['last_month_total'] ?? null) === 1000.0
                && ($summary['month_over_month']['trend'] ?? null) === 'up'
                && count($summary['top_categories'] ?? []) === 1;
        });
});
