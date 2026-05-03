<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('resolution_file_path')->nullable();
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'dswd_submitted',
                'withdrawn',
                'finalized',
            ])->default('draft');
            $table->date('approval_deadline')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
