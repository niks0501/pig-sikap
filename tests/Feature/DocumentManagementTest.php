<?php

use App\Models\DocumentType;
use App\Models\DocumentUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(RefreshDatabase::class);

function docCreateUserWithRole(string $slug): User
{
    $roleId = \DB::table('roles')->insertGetId([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'description' => "{$slug} role",
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::factory()->create([
        'role_id' => $roleId,
        'is_active' => true,
    ]);
}

beforeEach(function () {
    $this->documentType = DocumentType::create([
        'name' => 'Test Document',
        'description' => 'A test document type',
        'allowed_file_types' => ['pdf', 'jpg', 'png'],
        'max_size_kb' => 10240,
    ]);
});

test('president can view document upload page', function () {
    $president = docCreateUserWithRole('president');

    $response = $this->actingAs($president)->get('/workflow/documents/upload');

    $response->assertStatus(200);
});

test('president can view document review page', function () {
    $president = docCreateUserWithRole('president');

    $response = $this->actingAs($president)->get('/workflow/documents/review');

    $response->assertStatus(200);
});

test('president can upload document with valid file type', function () {
    $president = docCreateUserWithRole('president');

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($president)->post('/workflow/documents/upload', [
        'document_type_id' => $this->documentType->id,
        'file' => $file,
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['message', 'upload']);
    $this->assertDatabaseHas('document_uploads', [
        'document_type_id' => $this->documentType->id,
        'user_id' => $president->id,
        'status' => 'pending',
    ]);
});

test('president cannot upload document with invalid file type', function () {
    $president = docCreateUserWithRole('president');

    $file = UploadedFile::fake()->create('document.exe', 100);

    $response = $this->actingAs($president)->post('/workflow/documents/upload', [
        'document_type_id' => $this->documentType->id,
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
});

test('president cannot upload document exceeding max size', function () {
    $president = docCreateUserWithRole('president');

    $file = UploadedFile::fake()->create('large.pdf', 11000);

    $response = $this->actingAs($president)->post('/workflow/documents/upload', [
        'document_type_id' => $this->documentType->id,
        'file' => $file,
    ]);

    $response->assertSessionHasErrors('file');
});

test('uploaded document starts with pending status', function () {
    $president = docCreateUserWithRole('president');

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $this->actingAs($president)->post('/workflow/documents/upload', [
        'document_type_id' => $this->documentType->id,
        'file' => $file,
    ]);

    $upload = DocumentUpload::first();
    expect($upload->status)->toBe('pending');
});

test('president can approve document upload', function () {
    $president = docCreateUserWithRole('president');
    $uploader = docCreateUserWithRole('member');

    $upload = DocumentUpload::create([
        'document_type_id' => $this->documentType->id,
        'user_id' => $uploader->id,
        'file_path' => 'test.pdf',
        'original_name' => 'test.pdf',
        'size_kb' => 100,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($president)->patch("/workflow/documents/{$upload->id}/status", [
        'status' => 'approved',
        'review_comment' => 'Looks good',
    ]);

    $response->assertStatus(200);
    $upload->refresh();
    expect($upload->status)->toBe('approved');
    expect($upload->reviewer_id)->toBe($president->id);
});

test('reviewer must provide comment when rejecting document', function () {
    $president = docCreateUserWithRole('president');
    $uploader = docCreateUserWithRole('member');

    $upload = DocumentUpload::create([
        'document_type_id' => $this->documentType->id,
        'user_id' => $uploader->id,
        'file_path' => 'test.pdf',
        'original_name' => 'test.pdf',
        'size_kb' => 100,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($president)->patch("/workflow/documents/{$upload->id}/status", [
        'status' => 'rejected',
    ]);

    $response->assertSessionHasErrors('review_comment');
});

test('secretary can review document uploads', function () {
    $secretary = docCreateUserWithRole('secretary');
    $uploader = docCreateUserWithRole('member');

    $upload = DocumentUpload::create([
        'document_type_id' => $this->documentType->id,
        'user_id' => $uploader->id,
        'file_path' => 'test.pdf',
        'original_name' => 'test.pdf',
        'size_kb' => 100,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($secretary)->patch("/workflow/documents/{$upload->id}/status", [
        'status' => 'approved',
    ]);

    $response->assertStatus(200);
});

test('regular member cannot review documents', function () {
    $member = docCreateUserWithRole('member');

    $upload = DocumentUpload::create([
        'document_type_id' => $this->documentType->id,
        'user_id' => $member->id,
        'file_path' => 'test.pdf',
        'original_name' => 'test.pdf',
        'size_kb' => 100,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($member)->patch("/workflow/documents/{$upload->id}/status", [
        'status' => 'approved',
    ]);

    $response->assertStatus(403);
});

test('document upload summary returns correct counts', function () {
    $president = docCreateUserWithRole('president');
    $uploader = docCreateUserWithRole('member');

    DocumentUpload::create([
        'document_type_id' => $this->documentType->id,
        'user_id' => $uploader->id,
        'file_path' => 'test1.pdf',
        'original_name' => 'test1.pdf',
        'size_kb' => 100,
        'status' => 'pending',
    ]);

    DocumentUpload::create([
        'document_type_id' => $this->documentType->id,
        'user_id' => $uploader->id,
        'file_path' => 'test2.pdf',
        'original_name' => 'test2.pdf',
        'size_kb' => 100,
        'status' => 'approved',
    ]);

    $response = $this->actingAs($president)->get('/workflow/documents/summary');

    $response->assertStatus(200);
    $response->assertJson([
        'pending' => 1,
        'approved' => 1,
        'rejected' => 0,
        'needs_resubmission' => 0,
    ]);
});