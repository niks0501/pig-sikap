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
        Schema::table('pig_cycles', function (Blueprint $table): void {
            $table->foreignId('health_template_id')
                ->nullable()
                ->after('cycle_number')
                ->constrained('health_templates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('health_template_id');
        });
    }
};
