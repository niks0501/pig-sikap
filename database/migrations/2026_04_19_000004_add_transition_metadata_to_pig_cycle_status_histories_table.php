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
        Schema::table('pig_cycle_status_histories', function (Blueprint $table): void {
            $table->string('transition_type')->nullable()->after('remarks');
            $table->string('transition_origin')->nullable()->after('transition_type');
            $table->string('transition_key')->nullable()->after('transition_origin');
            $table->json('context_json')->nullable()->after('transition_key');

            $table->unique('transition_key', 'pig_cycle_status_histories_transition_key_uq');
            $table->index('transition_type', 'pig_cycle_status_histories_transition_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pig_cycle_status_histories', function (Blueprint $table): void {
            $table->dropUnique('pig_cycle_status_histories_transition_key_uq');
            $table->dropIndex('pig_cycle_status_histories_transition_type_idx');
            $table->dropColumn(['transition_type', 'transition_origin', 'transition_key', 'context_json']);
        });
    }
};
