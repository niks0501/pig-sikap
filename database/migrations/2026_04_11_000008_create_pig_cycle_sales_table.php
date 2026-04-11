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
        Schema::create('pig_cycle_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('pig_cycles')->cascadeOnDelete();
            $table->unsignedInteger('pigs_sold')->default(0);
            $table->decimal('amount', 12, 2);
            $table->date('sale_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('sale_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pig_cycle_sales');
    }
};
