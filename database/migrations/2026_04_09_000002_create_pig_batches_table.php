<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pig_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_code')->unique();
            $table->foreignId('breeder_id')->nullable()->constrained('pig_breeders')->nullOnDelete();
            $table->foreignId('caretaker_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('cycle_number')->nullable();
            $table->date('birth_date');
            $table->unsignedInteger('initial_count');
            $table->unsignedInteger('current_count');
            $table->decimal('average_weight', 8, 2)->nullable();
            $table->string('stage');
            $table->string('status');
            $table->boolean('has_pig_profiles')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('birth_date');
            $table->index('stage');
            $table->index('status');
            $table->index(['stage', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pig_batches');
    }
};
