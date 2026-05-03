<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a nullable withdrawal_id FK on pig_cycle_expenses so that
 * actual expenses can be linked to a specific withdrawal for the
 * budget-vs-actual comparison in the liquidation report (REQ-010).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pig_cycle_expenses', function (Blueprint $table) {
            $table->foreignId('withdrawal_id')
                ->nullable()
                ->after('batch_id')
                ->constrained('withdrawals')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pig_cycle_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_id');
        });
    }
};
