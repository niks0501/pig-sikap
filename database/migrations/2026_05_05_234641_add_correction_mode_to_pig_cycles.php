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
        Schema::table('pig_cycles', function (Blueprint $table) {
            $table->boolean('correction_mode')->default(false);
            $table->timestamp('correction_mode_enabled_at')->nullable();
            $table->foreignId('correction_mode_enabled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycles', function (Blueprint $table) {
            $table->dropForeign(['correction_mode_enabled_by']);
            $table->dropColumn(['correction_mode', 'correction_mode_enabled_at', 'correction_mode_enabled_by']);
        });
    }
};
