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
        Schema::create('pigs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('pig_batches')->cascadeOnDelete();
            $table->unsignedInteger('pig_no');
            $table->string('ear_mark_type')->nullable();
            $table->string('ear_mark_value')->nullable();
            $table->string('sex')->nullable();
            $table->string('status')->default('Active');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['batch_id', 'pig_no']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pigs');
    }
};
