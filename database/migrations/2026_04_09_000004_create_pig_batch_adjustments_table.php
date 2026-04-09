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
        Schema::create('pig_batch_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('pig_batches')->cascadeOnDelete();
            $table->string('adjustment_type');
            $table->unsignedInteger('quantity_before');
            $table->integer('quantity_change');
            $table->unsignedInteger('quantity_after');
            $table->string('reason');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('adjustment_type');
            $table->index('reason');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pig_batch_adjustments');
    }
};
