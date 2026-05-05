<?php

/**
 * CanvassingTest – Feature tests for canvassing records,
 * supplier CRUD, and winner selection.
 */

use App\Models\Canvass;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

function canvassMakeRole(string $slug): int
{
    $existing = DB::table('roles')->where('slug', $slug)->first();
    if ($existing) {
        return $existing->id;
    }

    return DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function canvassLogin(string $roleSlug = 'president'): User
{
    $role = DB::table('roles')->where('slug', $roleSlug)->first();
    if (! $role) {
        $roleId = canvassMakeRole($roleSlug);
    } else {
        $roleId = $role->id;
    }

    $user = User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);

    test()->actingAs($user);

    return $user;
}

test('Supplier CRUD works', function () {
    $user = canvassLogin('president');

    // Create
    $this->post(route('workflow.suppliers.store'), [
        'name' => 'Test Supplier',
        'contact_person' => 'Juan',
        'contact_number' => '09171234567',
    ])->assertRedirect(route('workflow.suppliers.index'));

    $this->assertDatabaseHas('suppliers', ['name' => 'Test Supplier']);

    $supplier = Supplier::where('name', 'Test Supplier')->first();

    // Update
    $this->put(route('workflow.suppliers.update', $supplier), [
        'name' => 'Updated Supplier',
        'contact_person' => 'Maria',
        'contact_number' => '09179876543',
    ])->assertRedirect();

    $this->assertDatabaseHas('suppliers', ['name' => 'Updated Supplier']);

    // Delete
    $this->delete(route('workflow.suppliers.destroy', $supplier))->assertRedirect();

    $this->assertDatabaseMissing('suppliers', ['name' => 'Updated Supplier']);
});

test('Canvass created with items and suppliers', function () {
    $user = canvassLogin('president');
    $supplier = Supplier::factory()->create(['created_by' => $user->id]);

    $this->post(route('workflow.canvasses.store'), [
        'title' => 'Price Comparison',
        'canvass_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'description' => 'Fencing Materials',
                'quantity' => 5,
                'unit' => 'roll',
                'unit_cost' => 1200,
                'supplier_id' => $supplier->id,
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('canvasses', ['title' => 'Price Comparison']);
    $this->assertDatabaseHas('canvass_items', ['description' => 'Fencing Materials']);
});

test('Canvass winner selection works', function () {
    $user = canvassLogin('president');

    $supplier1 = Supplier::factory()->create(['created_by' => $user->id]);
    $supplier2 = Supplier::factory()->create(['created_by' => $user->id]);

    $canvass = app(\App\Services\Workflow\CanvassingService::class)->create(
        [
            'title' => 'Winner Test',
            'canvass_date' => now()->format('Y-m-d'),
        ],
        [
            [
                'description' => 'Item X',
                'quantity' => 1,
                'unit' => 'pc',
                'unit_cost' => 500,
                'supplier_id' => $supplier1->id,
            ],
            [
                'description' => 'Item X',
                'quantity' => 1,
                'unit' => 'pc',
                'unit_cost' => 450,
                'supplier_id' => $supplier2->id,
            ],
        ],
        $user
    );

    $winningItem = $canvass->items->last();

    $this->patch(
        route('workflow.canvasses.items.select', ['canvass' => $canvass, 'item' => $winningItem])
    )->assertRedirect();

    $canvass->refresh();
    $canvass->load('items');
    $selected = $canvass->items->where('is_selected', true);
    expect($selected)->toHaveCount(1);
    expect($selected->first()->supplier_id)->toBe($supplier2->id);
});