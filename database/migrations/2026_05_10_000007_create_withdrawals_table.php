<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained('resolutions')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('bank_account')->nullable();
            $table->string('proof_file_path')->nullable();
            $table->enum('status', ['draft', 'pending', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('resolution_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
