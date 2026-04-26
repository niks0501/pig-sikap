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
            $table->string('receipt_path')->nullable()->after('notes');

            $table->index(['batch_id', 'expense_date'], 'pig_cycle_expenses_batch_date_idx');
            $table->index(['category', 'expense_date'], 'pig_cycle_expenses_category_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycle_expenses', function (Blueprint $table): void {
            $table->dropIndex('pig_cycle_expenses_batch_date_idx');
            $table->dropIndex('pig_cycle_expenses_category_date_idx');
            $table->dropColumn('receipt_path');
        });
    }
};
