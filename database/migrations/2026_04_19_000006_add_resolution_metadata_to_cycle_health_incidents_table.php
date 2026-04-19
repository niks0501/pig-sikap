<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cycle_health_incidents', function (Blueprint $table): void {
            $table->string('resolution_target')->nullable()->after('source_channel');
            $table->foreignId('resolved_incident_id')
                ->nullable()
                ->after('resolution_target')
                ->constrained('cycle_health_incidents')
                ->nullOnDelete();

            $table->index(['batch_id', 'resolution_target'], 'cycle_health_incidents_batch_resolution_target_idx');
            $table->index(['batch_id', 'incident_type', 'date_reported'], 'cycle_health_incidents_projection_idx');
        });

        DB::table('cycle_health_incidents')
            ->where('incident_type', 'treated')
            ->update(['incident_type' => 'recovered']);

        DB::table('cycle_health_incidents')
            ->where('incident_type', 'recovered')
            ->whereNull('resolution_target')
            ->update(['resolution_target' => 'sick']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cycle_health_incidents')
            ->where('incident_type', 'recovered')
            ->where('resolution_target', 'sick')
            ->update(['incident_type' => 'treated']);

        Schema::table('cycle_health_incidents', function (Blueprint $table): void {
            $table->dropIndex('cycle_health_incidents_batch_resolution_target_idx');
            $table->dropIndex('cycle_health_incidents_projection_idx');
            $table->dropConstrainedForeignId('resolved_incident_id');
            $table->dropColumn('resolution_target');
        });
    }
};
