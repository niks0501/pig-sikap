<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('guest can verify email using signed link', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('login', absolute: false));
});

test('signed verification link works even when another user is logged in', function () {
    $targetUser = User::factory()->unverified()->create();
    $otherUser = User::factory()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $targetUser->id, 'hash' => sha1($targetUser->email)]
    );

    $response = $this->actingAs($otherUser)->get($verificationUrl);

    expect($targetUser->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('login', absolute: false));
});

test('admin session is cleared when verifying an admin-created president account', function () {
    $this->seed(RoleSeeder::class);

    $systemAdminRole = Role::where('slug', 'system_admin')->firstOrFail();
    $presidentRole = Role::where('slug', 'president')->firstOrFail();

    $admin = User::factory()->create([
        'role_id' => $systemAdminRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $president = User::factory()->unverified()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => true,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $president->id, 'hash' => sha1($president->email)]
    );

    $this->actingAs($admin)
        ->get($verificationUrl)
        ->assertRedirect(route('login', absolute: false));

    expect($president->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('login'))->assertOk();

    $this->post(route('login'), [
        'email' => $president->email,
        'password' => 'password',
    ])->assertRedirect(route('password.force.edit', absolute: false));

    $this->put(route('password.force.update'), [
        'current_password' => 'password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect(route('dashboard'));
});

test('admin created users who click verification before login are verified before password change completes', function () {
    $user = User::factory()->unverified()->create([
        'must_change_password' => true,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($verificationUrl)
        ->assertRedirect(route('login', absolute: false));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('password.force.edit', absolute: false));

    $this->get(route('dashboard'))
        ->assertRedirect(route('password.force.edit'));

    $this->put(route('password.force.update'), [
        'current_password' => 'password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect(route('dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse()
        ->and($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('dashboard'))
        ->assertOk();
});

test('first login completes intended verification link before temporary password change', function () {
    $user = User::factory()->unverified()->create([
        'must_change_password' => true,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->withSession(['url.intended' => $verificationUrl])
        ->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($verificationUrl);

    $this->get($verificationUrl)
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('dashboard'))
        ->assertRedirect(route('password.force.edit'));

    $this->put(route('password.force.update'), [
        'current_password' => 'password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertOk();
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('unverified users are redirected from dashboard to verification notice', function () {
    $user = User::factory()->unverified()->create([
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('unverified users are redirected from protected modules to verification notice', function () {
    $this->seed(RoleSeeder::class);

    $presidentRole = Role::where('slug', 'president')->firstOrFail();
    $user = User::factory()->unverified()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($user)->get(route('sales.index'));

    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('verified users can reach protected modules normally', function () {
    $this->seed(RoleSeeder::class);

    $presidentRole = Role::where('slug', 'president')->firstOrFail();
    $user = User::factory()->create([
        'role_id' => $presidentRole->id,
        'is_active' => true,
        'must_change_password' => false,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('sales.index'));

    $response->assertOk();
});
