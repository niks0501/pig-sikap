<?php

use App\Models\AuditTrail;
use App\Models\Meeting;
use App\Models\PigCycle;
use App\Models\Resolution;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditTrailService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

// ─── Helpers ───────────────────────────────────────────────────────────

function auditPresident(): User
{
    $role = Role::query()->where('slug', 'president')->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

function auditTreasurer(): User
{
    $role = Role::query()->where('slug', 'treasurer')->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

function auditSecretary(): User
{
    $role = Role::query()->where('slug', 'secretary')->firstOrFail();

    return User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);
}

// ─── Page Access ───────────────────────────────────────────────────────

test('president can view audit trail page', function () {
    $president = auditPresident();

    $response = actingAs($president)->get(route('audit-trails.index'));

    $response->assertOk();
    $response->assertSee('data-vue-component="audit-trail-list"', false);
});

test('treasurer cannot view audit trail page', function () {
    $treasurer = auditTreasurer();

    $response = actingAs($treasurer)->get(route('audit-trails.index'));

    $response->assertForbidden();
});

test('secretary cannot view audit trail page', function () {
    $user = auditSecretary();

    $response = actingAs($user)->get(route('audit-trails.index'));

    $response->assertForbidden();
});

test('guest is redirected to login', function () {
    $response = get(route('audit-trails.index'));

    $response->assertRedirect(route('login'));
});

// ─── JSON Endpoint ─────────────────────────────────────────────────────

test('json endpoint returns paginated audit trail data', function () {
    $president = auditPresident();

    // Seed some audit entries
    AuditTrail::factory()->count(5)->create([
        'user_id' => $president->id,
        'module' => 'pig_registry',
        'action' => 'created_cycle',
    ]);

    $response = actingAs($president)->getJson(route('audit-trails.json'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'user', 'email', 'action', 'module', 'description', 'reference', 'context_json', 'ip_address', 'created_at'],
        ],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

test('json endpoint filters by search query', function () {
    $president = auditPresident();

    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'updated_cycle',
        'module' => 'pig_registry',
        'description' => 'Changed feed supplier to Green Farms Co.',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'added_pig',
        'module' => 'pig_registry',
        'description' => 'Registered a new piglet.',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $response = actingAs($president)->getJson(route('audit-trails.json', ['search' => 'Green Farms']));

    $response->assertOk();
    $results = $response->json('data');
    expect(count($results))->toBe(1);
    expect($results[0]['description'])->toBe('Changed feed supplier to Green Farms Co.');
});

test('json endpoint paginates correctly', function () {
    $president = auditPresident();

    AuditTrail::factory()->count(20)->create([
        'user_id' => $president->id,
    ]);

    $response = actingAs($president)->getJson(route('audit-trails.json', ['per_page' => 5]));

    $response->assertOk();
    $meta = $response->json('meta');
    expect($meta['per_page'])->toBe(5);
    expect($meta['last_page'])->toBeGreaterThanOrEqual(4);
    expect(count($response->json('data')))->toBeLessThanOrEqual(5);
});

// ─── CSV Export ────────────────────────────────────────────────────────

test('csv export streams data with correct headers', function () {
    $president = auditPresident();

    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'test_action',
        'module' => 'pig_registry',
        'description' => 'Test export entry.',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $response = actingAs($president)->get(route('audit-trails.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->streamedContent();

    expect($content)->toContain('Date/Time');
    expect($content)->toContain('User');
    expect($content)->toContain('test_action');
    expect($content)->toContain('Test export entry.');
});

// ─── Module-Scoped Endpoints ───────────────────────────────────────────

test('cycle audit endpoint returns entries for that cycle', function () {
    $president = auditPresident();

    $cycle = PigCycle::factory()->create([
        'created_by' => $president->id,
    ]);

    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'created_cycle',
        'module' => 'pig_registry',
        'description' => 'Created a new cycle.',
        'context_json' => ['cycle_id' => $cycle->id],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    // Entry for a different cycle should not appear
    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'updated_cycle',
        'module' => 'pig_registry',
        'description' => 'Updated another cycle.',
        'context_json' => ['cycle_id' => 999],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $response = actingAs($president)->getJson(route('cycles.audit', $cycle));

    $response->assertOk();
    $results = $response->json('data');
    expect(count($results))->toBe(1);
    expect($results[0]['action'])->toBe('created_cycle');
});

test('resolution audit endpoint returns entries for that resolution', function () {
    $president = auditPresident();

    $resolution = Resolution::factory()->create([
        'created_by' => $president->id,
    ]);

    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'approved_resolution',
        'module' => 'workflow',
        'description' => 'Approved the resolution.',
        'context_json' => ['resolution_id' => $resolution->id],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $response = actingAs($president)->getJson(route('workflow.resolutions.audit', $resolution));

    $response->assertOk();
    $results = $response->json('data');
    expect(count($results))->toBe(1);
    expect($results[0]['action'])->toBe('approved_resolution');
});

test('meeting audit endpoint returns entries for that meeting', function () {
    $president = auditPresident();

    $meeting = Meeting::factory()->create([
        'created_by' => $president->id,
    ]);

    AuditTrail::create([
        'user_id' => $president->id,
        'action' => 'created_meeting',
        'module' => 'workflow',
        'description' => 'Created a new meeting.',
        'context_json' => ['meeting_id' => $meeting->id],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $response = actingAs($president)->getJson(route('workflow.meetings.audit', $meeting));

    $response->assertOk();
    $results = $response->json('data');
    expect(count($results))->toBe(1);
    expect($results[0]['action'])->toBe('created_meeting');
});

// ─── Service ───────────────────────────────────────────────────────────

test('AuditTrailService records entry with all fields', function () {
    $president = auditPresident();

    $request = Request::create('/test', 'POST');
    $request->setUserResolver(fn () => $president);
    $request->server->set('REMOTE_ADDR', '192.168.1.1');
    $request->headers->set('User-Agent', 'TestAgent/1.0');

    $service = new AuditTrailService;
    $entry = $service->record(
        $request,
        'test_action',
        'Test description for service.',
        'test_module',
        ['key' => 'value']
    );

    expect($entry)->toBeInstanceOf(AuditTrail::class);
    expect($entry->user_id)->toBe($president->id);
    expect($entry->action)->toBe('test_action');
    expect($entry->description)->toBe('Test description for service.');
    expect($entry->module)->toBe('test_module');
    expect($entry->context_json)->toBe(['key' => 'value']);
    expect($entry->ip_address)->toBe('192.168.1.1');
    expect($entry->user_agent)->toBe('TestAgent/1.0');

    assertDatabaseHas('audit_trails', [
        'user_id' => $president->id,
        'action' => 'test_action',
        'module' => 'test_module',
    ]);
});
