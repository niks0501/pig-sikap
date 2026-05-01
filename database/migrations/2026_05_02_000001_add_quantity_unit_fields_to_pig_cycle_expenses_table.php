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
        Schema::table('pig_cycle_expenses', function (Blueprint $table): void {
            $table->decimal('quantity', 10, 2)->nullable()->after('category');
            $table->string('unit', 50)->nullable()->after('quantity');
            $table->decimal('unit_cost', 12, 2)->nullable()->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycle_expenses', function (Blueprint $table): void {
            $table->dropColumn(['quantity', 'unit', 'unit_cost']);
        });
    }
};
