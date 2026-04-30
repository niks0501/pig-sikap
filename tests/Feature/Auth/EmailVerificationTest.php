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
