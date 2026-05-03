<?php

/**
 * FileUploadTest – Validates file upload handling using Storage::fake.
 * Ensures correct MIME types, size limits, and storage paths.
 */

use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeUploadOfficer(): User
{
    $roleId = \DB::table('roles')->insertGetId([
        'name' => 'Secretary',
        'slug' => 'secretary',
        'description' => 'Secretary role',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

// ── Meeting file uploads ──

test('meeting accepts PDF file upload', function () {
    Storage::fake('public');
    $user = makeUploadOfficer();

    $file = UploadedFile::fake()->create('minutes.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->postJson(route('workflow.meetings.store'), [
        'title' => 'Meeting with PDF',
        'date' => now()->subDay()->toDateString(),
        'status' => 'draft',
        'minutes_file' => $file,
    ]);

    $response->assertStatus(201);
    $meeting = Meeting::latest()->first();
    expect($meeting->minutes_file_path)->toStartWith('meetings/');
    Storage::disk('public')->assertExists($meeting->minutes_file_path);
});

test('meeting accepts JPEG image upload', function () {
    Storage::fake('public');
    $user = makeUploadOfficer();

    $file = UploadedFile::fake()->image('minutes-photo.jpg', 800, 600);

    $response = $this->actingAs($user)->postJson(route('workflow.meetings.store'), [
        'title' => 'Meeting with photo',
        'date' => now()->subDay()->toDateString(),
        'status' => 'draft',
        'minutes_file' => $file,
    ]);

    $response->assertStatus(201);
    $meeting = Meeting::latest()->first();
    Storage::disk('public')->assertExists($meeting->minutes_file_path);
});

test('meeting rejects non-allowed file types', function () {
    Storage::fake('public');
    $user = makeUploadOfficer();

    $file = UploadedFile::fake()->create('virus.exe', 500, 'application/x-msdownload');

    $response = $this->actingAs($user)->postJson(route('workflow.meetings.store'), [
        'title' => 'Meeting with bad file',
        'date' => now()->subDay()->toDateString(),
        'status' => 'draft',
        'minutes_file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('minutes_file');
});

test('meeting rejects files over 10MB', function () {
    Storage::fake('public');
    $user = makeUploadOfficer();

    // Create a file larger than 10MB (10240 KB)
    $file = UploadedFile::fake()->create('huge-scan.pdf', 11000, 'application/pdf');

    $response = $this->actingAs($user)->postJson(route('workflow.meetings.store'), [
        'title' => 'Meeting with huge file',
        'date' => now()->subDay()->toDateString(),
        'status' => 'draft',
        'minutes_file' => $file,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('minutes_file');
});

// ── DSWD file uploads ──

test('DSWD submission accepts PDF upload', function () {
    Storage::fake('public');
    $user = makeUploadOfficer();

    $meeting = Meeting::create([
        'title' => 'DSWD test meeting',
        'date' => now()->subDay()->toDateString(),
        'status' => 'confirmed',
        'created_by' => $user->id,
    ]);

    $resolution = Resolution::create([
        'meeting_id' => $meeting->id,
        'title' => 'DSWD test resolution',
        'status' => 'approved',
        'created_by' => $user->id,
    ]);

    $file = UploadedFile::fake()->create('dswd-approval.pdf', 400, 'application/pdf');

    $response = $this->actingAs($user)->postJson(
        route('workflow.resolutions.dswd.store', $resolution),
        [
            'status' => 'submitted',
            'submission_file' => $file,
            'notes' => 'Submitted for review',
        ]
    );

    $response->assertOk();
    $submission = $resolution->dswdSubmissions()->latest()->first();
    expect($submission->submission_file_path)->toStartWith('dswd_submissions/');
    Storage::disk('public')->assertExists($submission->submission_file_path);
});
