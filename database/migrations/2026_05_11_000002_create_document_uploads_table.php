<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedInteger('size_kb');
            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_resubmission'])->default('pending');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_comment')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('module_type')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_uploads');
    }
};