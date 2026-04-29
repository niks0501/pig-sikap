<?php

use App\Models\PigBuyer;
use App\Models\PigCycle;
use App\Models\PigCycleAdjustment;
use App\Models\PigCycleSale;
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

function salesUser(string $roleSlug, array $overrides = []): User
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

function salesCycle(User $actor, array $overrides = []): PigCycle
{
    return PigCycle::query()->create([
        'batch_code' => 'SALE-'.fake()->unique()->numerify('###'),
        'caretaker_user_id' => $actor->id,
        'cycle_number' => 1,
        'date_of_purchase' => now()->subDays(30)->toDateString(),
        'initial_count' => 10,
        'current_count' => 10,
        'average_weight' => 85.00,
        'stage' => 'For Sale',
        'status' => 'Ready for Sale',
        'has_pig_profiles' => false,
        'notes' => 'Sales module cycle',
        'last_reviewed_at' => now(),
        'created_by' => $actor->id,
        ...$overrides,
    ]);
}

/**
 * @return array<string, mixed>
 */
function salePayload(PigCycle $cycle, array $overrides = []): array
{
    return [
        'batch_id' => $cycle->id,
        'buyer_name' => 'Juan Dela Cruz',
        'buyer_contact_number' => '09171234567',
        'buyer_address' => 'Public Market',
        'buyer_notes' => 'Frequent buyer',
        'pigs_sold' => 2,
        'sale_date' => now()->toDateString(),
        'sale_method' => 'per_head',
        'price_per_head' => '3500.00',
        'payment_status' => 'paid',
        'amount_paid' => '7000.00',
        'receipt_reference' => 'Receipt-001',
        'notes' => 'Sold two pigs.',
        ...$overrides,
    ];
}

test('sales routes are accessible to president treasurer and secretary but forbidden for officer', function () {
    $president = salesUser('president');
    $treasurer = salesUser('treasurer');
    $secretary = salesUser('secretary');
    $officer = salesUser('officer');

    actingAs($president)->get(route('sales.index'))->assertOk();
    actingAs($treasurer)->get(route('sales.index'))->assertOk();
    actingAs($secretary)->get(route('sales.index'))->assertOk();

    actingAs($officer)->get(route('sales.index'))->assertForbidden();
});

test('authorized user can store sale with receipt and inventory adjusts', function () {
    $treasurer = salesUser('treasurer');
    $cycle = salesCycle($treasurer, ['current_count' => 5]);

    $response = actingAs($treasurer)->post(route('sales.store'), salePayload($cycle, [
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ]));

    $sale = PigCycleSale::query()->latest('id')->first();

    expect($sale)->not->toBeNull();

    $response->assertRedirect(route('sales.show', $sale));

    assertDatabaseHas('pig_cycle_sales', [
        'id' => $sale->id,
        'batch_id' => $cycle->id,
        'pigs_sold' => 2,
        'payment_status' => 'paid',
        'created_by' => $treasurer->id,
    ]);

    $cycle->refresh();
    expect($cycle->current_count)->toBe(3);

    $adjustment = PigCycleAdjustment::query()->where('source_id', $sale->id)->first();
    expect($adjustment)->not->toBeNull();
    expect($adjustment->reason)->toBe('sale deduction');

    expect($sale->receipt_path)->not->toBeNull();
    expect(Storage::disk('public')->exists((string) $sale->receipt_path))->toBeTrue();

    assertDatabaseHas('audit_trails', [
        'action' => 'sale_created',
        'module' => 'sales_management',
        'user_id' => $treasurer->id,
    ]);
});

test('oversell attempt fails validation and does not adjust inventory', function () {
    $president = salesUser('president');
    $cycle = salesCycle($president, ['current_count' => 3]);

    $response = actingAs($president)
        ->from(route('sales.create'))
        ->post(route('sales.store'), salePayload($cycle, [
            'pigs_sold' => 5,
        ]));

    $response
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors(['pigs_sold']);

    assertDatabaseMissing('pig_cycle_sales', [
        'batch_id' => $cycle->id,
    ]);

    $cycle->refresh();
    expect($cycle->current_count)->toBe(3);
});

test('payment rules reject inconsistent amounts', function () {
    $treasurer = salesUser('treasurer');
    $cycle = salesCycle($treasurer);

    $response = actingAs($treasurer)
        ->from(route('sales.create'))
        ->post(route('sales.store'), salePayload($cycle, [
            'payment_status' => 'paid',
            'amount_paid' => '1000.00',
        ]));

    $response
        ->assertRedirect(route('sales.create'))
        ->assertSessionHasErrors(['amount_paid']);

    assertDatabaseMissing('pig_cycle_sales', [
        'batch_id' => $cycle->id,
    ]);
});

test('selling out a cycle updates status to sold and completed', function () {
    $president = salesUser('president');
    $cycle = salesCycle($president, ['current_count' => 2]);

    $response = actingAs($president)->post(route('sales.store'), salePayload($cycle, [
        'pigs_sold' => 2,
        'price_per_head' => '4200.00',
        'amount_paid' => '8400.00',
    ]));

    $sale = PigCycleSale::query()->latest('id')->first();

    $response->assertRedirect(route('sales.show', $sale));

    $cycle->refresh();
    expect($cycle->status)->toBe('Sold');
    expect($cycle->stage)->toBe('Completed');
});

test('secretary can update receipt but cannot update payment details', function () {
    $president = salesUser('president');
    $secretary = salesUser('secretary');
    $cycle = salesCycle($president);

    $sale = PigCycleSale::query()->create([
        'batch_id' => $cycle->id,
        'pigs_sold' => 1,
        'amount' => 3500,
        'sale_date' => now()->toDateString(),
        'sale_method' => 'per_head',
        'price_per_head' => 3500,
        'payment_status' => 'pending',
        'amount_paid' => 0,
        'created_by' => $president->id,
    ]);

    actingAs($secretary)
        ->from(route('sales.show', $sale))
        ->put(route('sales.update', $sale), [
            'payment_status' => 'paid',
            'amount_paid' => 3500,
        ])
        ->assertSessionHasErrors(['payment_status']);

    $response = actingAs($secretary)->put(route('sales.update', $sale), [
        'receipt_reference' => 'Receipt update',
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertRedirect(route('sales.show', $sale));

    $sale->refresh();
    expect($sale->receipt_path)->not->toBeNull();
    expect(Storage::disk('public')->exists((string) $sale->receipt_path))->toBeTrue();
});

test('authorized users can create and update buyers', function () {
    $treasurer = salesUser('treasurer');

    $createResponse = actingAs($treasurer)
        ->postJson(route('buyers.store'), [
            'name' => 'Buyer One',
            'contact_number' => '0900111222',
            'address' => 'Market Road',
            'notes' => 'Test buyer',
        ]);

    $createResponse->assertCreated();

    $buyer = PigBuyer::query()->latest('id')->first();

    expect($buyer)->not->toBeNull();

    $updateResponse = actingAs($treasurer)
        ->putJson(route('buyers.update', $buyer), [
            'name' => 'Buyer Updated',
            'contact_number' => '0900111222',
            'address' => 'Market Road',
            'notes' => 'Updated notes',
        ]);

    $updateResponse->assertOk();

    assertDatabaseHas('pig_buyers', [
        'id' => $buyer->id,
        'name' => 'Buyer Updated',
    ]);
});
