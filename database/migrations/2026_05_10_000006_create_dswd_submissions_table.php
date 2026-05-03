<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dswd_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('resolutions')->cascadeOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->string('submission_file_path')->nullable();
            $table->enum('status', ['not_submitted', 'submitted', 'approved', 'returned'])->default('not_submitted');
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('resolution_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dswd_submissions');
    }
};
