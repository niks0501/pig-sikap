<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('resolutions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['resolution_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_approvals');
    }
};
