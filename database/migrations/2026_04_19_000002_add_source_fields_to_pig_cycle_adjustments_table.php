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
        Schema::table('pig_cycle_adjustments', function (Blueprint $table): void {
            $table->string('source_module')->nullable()->after('remarks');
            $table->string('source_type')->nullable()->after('source_module');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('source_event_key')->nullable()->after('source_id');

            $table->unique('source_event_key', 'pig_cycle_adjustments_source_event_key_uq');
            $table->index(['source_type', 'source_id'], 'pig_cycle_adjustments_source_type_source_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycle_adjustments', function (Blueprint $table): void {
            $table->dropUnique('pig_cycle_adjustments_source_event_key_uq');
            $table->dropIndex('pig_cycle_adjustments_source_type_source_id_idx');
            $table->dropColumn(['source_module', 'source_type', 'source_id', 'source_event_key']);
        });
    }
};
