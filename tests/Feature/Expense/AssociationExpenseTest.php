<?php

declare(strict_types=1);

use App\Models\AssociationExpense;
use App\Models\Canvass;
use App\Models\Resolution;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Withdrawal;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
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

function associationExpenseUser(string $roleSlug, array $overrides = []): User
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

it('renders the association expense index page', function () {
    $user = associationExpenseUser('treasurer');
    actingAs($user);

    AssociationExpense::factory()->create([
        'item_name' => 'Test Feed',
        'category' => 'feed',
        'amount' => 1500.00,
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('expenses.association.index'));

    $response->assertStatus(200);
    $response->assertSee('Test Feed');
});

it('renders the create association expense form', function () {
    actingAs(associationExpenseUser('treasurer'));

    $response = $this->get(route('expenses.association.create'));

    $response->assertStatus(200);
});

it('stores an association expense via json', function () {
    actingAs($user = associationExpenseUser('treasurer'));

    $response = $this->postJson(route('expenses.association.store'), [
        'item_name' => 'Hog Grower Pellets 50kg',
        'category' => 'feed',
        'feed_subcategory' => 'grower',
        'quantity' => 3,
        'unit' => 'sack',
        'unit_cost' => 1500,
        'amount' => 4500,
        'expense_date' => now()->toDateString(),
        'notes' => 'Feed for current month',
        'fund_source' => 'association_fund',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('expense.item_name', 'Hog Grower Pellets 50kg');
    $response->assertJsonPath('expense.feed_subcategory', 'grower');
    $response->assertJsonPath('expense.fund_source', 'association_fund');

    assertDatabaseHas('association_expenses', [
        'item_name' => 'Hog Grower Pellets 50kg',
        'category' => 'feed',
        'feed_subcategory' => 'grower',
        'amount' => 4500.00,
    ]);
});

it('accepts feed_subcategory when category is feed', function () {
    actingAs(associationExpenseUser('treasurer'));

    $response = $this->postJson(route('expenses.association.store'), [
        'item_name' => 'Feed with subcategory',
        'category' => 'feed',
        'feed_subcategory' => 'starter',
        'amount' => 1000,
        'expense_date' => now()->toDateString(),
        'notes' => 'Feed with valid subcategory',
    ]);

    $response->assertStatus(201);
    assertDatabaseHas('association_expenses', [
        'category' => 'feed',
        'feed_subcategory' => 'starter',
    ]);
});

it('auto-computes amount from quantity and unit_cost', function () {
    actingAs(associationExpenseUser('treasurer'));

    $response = $this->postJson(route('expenses.association.store'), [
        'item_name' => 'Vitamins',
        'category' => 'medicine',
        'quantity' => 5,
        'unit' => 'bottle',
        'unit_cost' => 250,
        'expense_date' => now()->toDateString(),
        'notes' => 'Vitamins for pigs',
    ]);

    $response->assertStatus(201);
    assertDatabaseHas('association_expenses', [
        'amount' => 1250.00,
    ]);
});

it('links to supplier and resolution', function () {
    actingAs($user = associationExpenseUser('treasurer'));
    $supplier = Supplier::factory()->create();
    $resolution = Resolution::factory()->create();

    $response = $this->postJson(route('expenses.association.store'), [
        'item_name' => 'Feed from Agri',
        'category' => 'feed',
        'feed_subcategory' => 'starter',
        'amount' => 3000,
        'expense_date' => now()->toDateString(),
        'notes' => 'Linked to supplier and resolution',
        'supplier_id' => $supplier->id,
        'approved_resolution_id' => $resolution->id,
    ]);

    $response->assertStatus(201);
    $expense = AssociationExpense::latest()->first();
    expect($expense->supplier_id)->toBe($supplier->id);
    expect($expense->approved_resolution_id)->toBe($resolution->id);
});

it('uploads a receipt with the expense', function () {
    actingAs(associationExpenseUser('treasurer'));
    $file = UploadedFile::fake()->image('receipt.jpg', 200, 200);

    $response = $this->post(route('expenses.association.store'), [
        'item_name' => 'Expense with receipt',
        'category' => 'supplies',
        'amount' => 500,
        'expense_date' => now()->toDateString(),
        'notes' => 'Has receipt',
        'receipt' => $file,
    ]);

    $response->assertSessionHasNoErrors();
    $expense = AssociationExpense::latest()->first();
    expect($expense->receipt_path)->not->toBeNull();
    Storage::disk('public')->assertExists($expense->receipt_path);
});

it('shows an association expense', function () {
    $user = associationExpenseUser('treasurer');
    actingAs($user);

    $expense = AssociationExpense::factory()->create([
        'item_name' => 'Bank Fee',
        'category' => 'utilities',
        'amount' => 150.00,
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('expenses.association.show', $expense));

    $response->assertStatus(200);
    $response->assertSee('Bank Fee');
});

it('updates an association expense', function () {
    $user = associationExpenseUser('treasurer');
    actingAs($user);

    $expense = AssociationExpense::factory()->create([
        'item_name' => 'Old Item',
        'category' => 'other',
        'amount' => 100,
        'created_by' => $user->id,
    ]);

    $response = $this->put(route('expenses.association.update', $expense), [
        'item_name' => 'Updated Item',
        'category' => 'other',
        'amount' => 200,
        'expense_date' => now()->toDateString(),
        'notes' => 'Updated notes',
    ]);

    $response->assertRedirect(route('expenses.association.show', $expense));
    assertDatabaseHas('association_expenses', [
        'id' => $expense->id,
        'item_name' => 'Updated Item',
        'amount' => 200,
    ]);
});

it('deletes an association expense', function () {
    $user = associationExpenseUser('president');
    actingAs($user);

    $expense = AssociationExpense::factory()->create([
        'created_by' => $user->id,
    ]);

    $response = $this->delete(route('expenses.association.destroy', $expense));

    $response->assertRedirect(route('expenses.association.index'));
    $this->assertSoftDeleted('association_expenses', ['id' => $expense->id]);
});

it('renders the all expenses combined view', function () {
    actingAs(associationExpenseUser('treasurer'));

    $response = $this->get(route('expenses.all'));

    $response->assertStatus(200);
});

it('requires authentication for association expenses', function () {
    $response = $this->getJson(route('expenses.association.index'));
    $response->assertStatus(401);
});

it('validates required fields on store', function () {
    actingAs(associationExpenseUser('treasurer'));

    $response = $this->postJson(route('expenses.association.store'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['item_name', 'category', 'expense_date', 'notes']);
});

it('rejects future expense dates', function () {
    actingAs(associationExpenseUser('treasurer'));

    $response = $this->postJson(route('expenses.association.store'), [
        'item_name' => 'Future expense',
        'category' => 'other',
        'amount' => 100,
        'expense_date' => now()->addDay()->toDateString(),
        'notes' => 'Invalid date',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['expense_date']);
});

it('filters by fund_source', function () {
    $user = associationExpenseUser('treasurer');
    actingAs($user);

    AssociationExpense::factory()->create([
        'fund_source' => 'emergency_fund',
        'item_name' => 'Emergency Meds',
        'amount' => 500,
        'created_by' => $user->id,
    ]);
    AssociationExpense::factory()->create([
        'fund_source' => 'association_fund',
        'item_name' => 'Office Supplies',
        'amount' => 200,
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(route('expenses.association.index', ['fund_source' => 'emergency_fund']));

    $response->assertStatus(200);
    $expenses = $response->json('expenses');
    expect(count($expenses))->toBe(1);
    expect($expenses[0]['item_name'])->toBe('Emergency Meds');
});
