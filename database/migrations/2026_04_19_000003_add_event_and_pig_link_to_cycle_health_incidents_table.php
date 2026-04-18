<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cycle_health_incidents', function (Blueprint $table): void {
            $table->uuid('event_key')->nullable()->after('batch_id');
            $table->foreignId('pig_id')->nullable()->after('event_key')->constrained('pigs')->nullOnDelete();
            $table->string('source_channel')->nullable()->after('pig_id');

            $table->index('pig_id', 'cycle_health_incidents_pig_id_idx');
        });

        DB::table('cycle_health_incidents')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('cycle_health_incidents')
                        ->where('id', $row->id)
                        ->whereNull('event_key')
                        ->update([
                            'event_key' => (string) Str::uuid(),
                            'source_channel' => 'legacy',
                        ]);
                }
            });

        Schema::table('cycle_health_incidents', function (Blueprint $table): void {
            $table->unique(['batch_id', 'event_key'], 'cycle_health_incidents_batch_id_event_key_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cycle_health_incidents', function (Blueprint $table): void {
            $table->dropUnique('cycle_health_incidents_batch_id_event_key_uq');
            $table->dropIndex('cycle_health_incidents_pig_id_idx');
            $table->dropConstrainedForeignId('pig_id');
            $table->dropColumn(['event_key', 'source_channel']);
        });
    }
};
